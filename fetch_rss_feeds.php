<?php

set_time_limit(300); // 5 minutes
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__) . '/config.php';

$logFile = dirname(__DIR__) . '/logs/rss_fetcher.log';

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

logMessage("========== RSS FETCHER START ==========");

$conn = getConnection();

// Get active RSS feeds
$feedsQuery = "SELECT * FROM rss_feeds WHERE is_active = TRUE ORDER BY id";
$feeds = $conn->query($feedsQuery)->fetch_all(MYSQLI_ASSOC);

logMessage("Found " . count($feeds) . " active feeds");

$totalFetched = 0;
$totalNew = 0;
$totalErrors = 0;

foreach ($feeds as $feed) {
    logMessage("Processing: {$feed['name']} - {$feed['url']}");
    
    try {
        // Create HTTP context
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Digital Signage SMFR Bot'
            ]
        ]);
        
        // Fetch RSS content
        $rssContent = @file_get_contents($feed['url'], false, $context);
        
        if ($rssContent === false) {
            logMessage("  ❌ Failed to fetch: " . error_get_last()['message']);
            
            // Update error
            $stmt = $conn->prepare("UPDATE rss_feeds SET last_error = ? WHERE id = ?");
            $error = 'Failed to fetch: ' . (error_get_last()['message'] ?? 'Unknown error');
            $stmt->bind_param("si", $error, $feed['id']);
            $stmt->execute();
            $stmt->close();
            
            $totalErrors++;
            continue;
        }
        
        // Parse XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($rssContent);
        libxml_clear_errors();
        
        if ($xml === false) {
            logMessage("  ❌ Invalid XML");
            $totalErrors++;
            continue;
        }
        
        $itemsFound = 0;
        $itemsNew = 0;
        
        // Parse RSS 2.0 format
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $itemsFound++;
                
                $title = trim((string)$item->title);
                $description = trim((string)$item->description);
                $link = trim((string)$item->link);
                $pubDate = (string)$item->pubDate;
                $guid = trim((string)$item->guid) ?: $link;
                
                // Clean HTML tags from title and description
                $title = strip_tags($title);
                $description = strip_tags($description);
                
                // Clean entities
                $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
                $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
                
                // Truncate if too long
                if (strlen($title) > 500) {
                    $title = substr($title, 0, 497) . '...';
                }
                if (strlen($description) > 1000) {
                    $description = substr($description, 0, 997) . '...';
                }
                
                // Parse date
                $pubDateTimestamp = strtotime($pubDate);
                $pubDateSQL = $pubDateTimestamp ? date('Y-m-d H:i:s', $pubDateTimestamp) : null;
                
                // Skip if empty title
                if (empty($title)) {
                    continue;
                }
                
                // Check if already exists
                $checkStmt = $conn->prepare("SELECT id FROM rss_items WHERE guid = ?");
                $checkStmt->bind_param("s", $guid);
                $checkStmt->execute();
                $exists = $checkStmt->get_result()->num_rows > 0;
                $checkStmt->close();
                
                if (!$exists) {
                    // Insert new item
                    $insertStmt = $conn->prepare(
                        "INSERT INTO rss_items (feed_id, title, description, link, pub_date, guid) 
                         VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    $insertStmt->bind_param("isssss", $feed['id'], $title, $description, $link, $pubDateSQL, $guid);
                    
                    if ($insertStmt->execute()) {
                        $itemsNew++;
                        $totalNew++;
                    }
                    $insertStmt->close();
                }
            }
        }
        // Parse Atom format
        elseif (isset($xml->entry)) {
            foreach ($xml->entry as $entry) {
                $itemsFound++;
                
                $title = trim((string)$entry->title);
                $description = trim((string)$entry->summary);
                $link = trim((string)$entry->link['href']);
                $pubDate = (string)$entry->updated;
                $guid = trim((string)$entry->id) ?: $link;
                
                $title = strip_tags($title);
                $description = strip_tags($description);
                $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
                $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
                
                if (strlen($title) > 500) {
                    $title = substr($title, 0, 497) . '...';
                }
                
                $pubDateTimestamp = strtotime($pubDate);
                $pubDateSQL = $pubDateTimestamp ? date('Y-m-d H:i:s', $pubDateTimestamp) : null;
                
                if (empty($title)) {
                    continue;
                }
                
                $checkStmt = $conn->prepare("SELECT id FROM rss_items WHERE guid = ?");
                $checkStmt->bind_param("s", $guid);
                $checkStmt->execute();
                $exists = $checkStmt->get_result()->num_rows > 0;
                $checkStmt->close();
                
                if (!$exists) {
                    $insertStmt = $conn->prepare(
                        "INSERT INTO rss_items (feed_id, title, description, link, pub_date, guid) 
                         VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    $insertStmt->bind_param("isssss", $feed['id'], $title, $description, $link, $pubDateSQL, $guid);
                    
                    if ($insertStmt->execute()) {
                        $itemsNew++;
                        $totalNew++;
                    }
                    $insertStmt->close();
                }
            }
        }
        
        logMessage("  ✅ Found: $itemsFound items, New: $itemsNew items");
        
        // Update last_fetch time
        $stmt = $conn->prepare("UPDATE rss_feeds SET last_fetch = NOW(), last_error = NULL WHERE id = ?");
        $stmt->bind_param("i", $feed['id']);
        $stmt->execute();
        $stmt->close();
        
        $totalFetched += $itemsFound;
        
    } catch (Exception $e) {
        logMessage("  ❌ Exception: " . $e->getMessage());
        
        $stmt = $conn->prepare("UPDATE rss_feeds SET last_error = ? WHERE id = ?");
        $error = 'Exception: ' . $e->getMessage();
        $stmt->bind_param("si", $error, $feed['id']);
        $stmt->execute();
        $stmt->close();
        
        $totalErrors++;
    }
    
    // Sleep to avoid hammering servers
    sleep(1);
}

// Cleanup old items (keep last 100 per feed)
logMessage("Cleaning up old items...");

$cleanupQuery = "DELETE ri FROM rss_items ri
                 LEFT JOIN (
                     SELECT id FROM rss_items 
                     WHERE feed_id = ri.feed_id 
                     ORDER BY pub_date DESC 
                     LIMIT 100
                 ) keep ON ri.id = keep.id
                 WHERE keep.id IS NULL";

$deletedCount = 0;
foreach ($feeds as $feed) {
    $stmt = $conn->prepare(
        "DELETE FROM rss_items 
         WHERE feed_id = ? 
           AND id NOT IN (
               SELECT id FROM (
                   SELECT id FROM rss_items 
                   WHERE feed_id = ? 
                   ORDER BY pub_date DESC 
                   LIMIT 100
               ) tmp
           )"
    );
    $stmt->bind_param("ii", $feed['id'], $feed['id']);
    $stmt->execute();
    $deletedCount += $stmt->affected_rows;
    $stmt->close();
}

if ($deletedCount > 0) {
    logMessage("Deleted $deletedCount old items");
}

$conn->close();

logMessage("========== RSS FETCHER COMPLETE ==========");
logMessage("Summary:");
logMessage("  Total Items Fetched: $totalFetched");
logMessage("  New Items Added: $totalNew");
logMessage("  Feeds with Errors: $totalErrors");
logMessage("  Old Items Cleaned: $deletedCount");

// Return JSON if called via web
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'feeds_processed' => count($feeds),
        'items_fetched' => $totalFetched,
        'items_new' => $totalNew,
        'errors' => $totalErrors,
        'items_cleaned' => $deletedCount
    ]);
}
?>