<?php
/**
 * RSS REAL-TIME API
 * Path: api/get_rss_feeds.php
 * Mengembalikan berita terbaru dari RSS feeds
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');

// RSS Feeds
$rssFeeds = [
    [
        'name' => 'Detik.com',
        'url' => 'https://rss.detik.com/index.php/detikcom',
        'icon' => '📰'
    ],
    [
        'name' => 'Kominfo',
        'url' => 'https://www.kominfo.go.id/rss',
        'icon' => '🏛️'
    ],
    [
        'name' => 'DetikNews',
        'url' => 'https://rss.detik.com/index.php/news',
        'icon' => '📡'
    ]
];

$allNews = [];
$errors = [];

// Function to parse RSS feed
function parseRSS($url) {
    try {
        // Increase timeout
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);
        
        $rss = @file_get_contents($url, false, $context);
        
        if ($rss === false) {
            return [];
        }
        
        // Suppress XML errors
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($rss);
        libxml_clear_errors();
        
        if ($xml === false) {
            return [];
        }
        
        $items = [];
        
        // Parse RSS 2.0
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $title = (string)$item->title;
                $pubDate = (string)$item->pubDate;
                
                // Clean title
                $title = strip_tags($title);
                $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
                $title = trim($title);
                
                if (!empty($title)) {
                    $items[] = [
                        'title' => $title,
                        'pubDate' => $pubDate,
                        'timestamp' => strtotime($pubDate)
                    ];
                }
            }
        }
        // Parse Atom
        elseif (isset($xml->entry)) {
            foreach ($xml->entry as $entry) {
                $title = (string)$entry->title;
                $pubDate = (string)$entry->updated;
                
                $title = strip_tags($title);
                $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
                $title = trim($title);
                
                if (!empty($title)) {
                    $items[] = [
                        'title' => $title,
                        'pubDate' => $pubDate,
                        'timestamp' => strtotime($pubDate)
                    ];
                }
            }
        }
        
        return $items;
        
    } catch (Exception $e) {
        return [];
    }
}

// Load RSS from all sources
foreach ($rssFeeds as $feed) {
    $items = parseRSS($feed['url']);
    
    if (!empty($items)) {
        foreach ($items as $item) {
            $allNews[] = [
                'title' => $item['title'],
                'source' => $feed['name'],
                'icon' => $feed['icon'],
                'pubDate' => $item['pubDate'],
                'timestamp' => $item['timestamp']
            ];
        }
    } else {
        $errors[] = "Gagal load: " . $feed['name'];
    }
}

// Sort by timestamp (newest first)
usort($allNews, function($a, $b) {
    return $b['timestamp'] - $a['timestamp'];
});

// Limit to 30 latest news
$allNews = array_slice($allNews, 0, 30);

// Add fallback news if no RSS loaded
if (empty($allNews)) {
    $allNews = [
        [
            'title' => 'Selamat datang di Balai Monitor SMFR Kelas II Manado',
            'source' => 'SMFR',
            'icon' => '📡',
            'pubDate' => date('Y-m-d H:i:s'),
            'timestamp' => time()
        ],
        [
            'title' => 'Monitoring spektrum frekuensi radio 24/7',
            'source' => 'SMFR',
            'icon' => '📡',
            'pubDate' => date('Y-m-d H:i:s'),
            'timestamp' => time()
        ],
        [
            'title' => 'Melayani wilayah Sulawesi Utara, Tengah, Gorontalo, dan Maluku Utara',
            'source' => 'SMFR',
            'icon' => '📡',
            'pubDate' => date('Y-m-d H:i:s'),
            'timestamp' => time()
        ]
    ];
}

// Return JSON
echo json_encode([
    'success' => true,
    'count' => count($allNews),
    'news' => $allNews,
    'errors' => $errors,
    'updated_at' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE);