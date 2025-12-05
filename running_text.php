<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Running Text - Digital Signage</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #000;
        }
        
        .running-text-container {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(90deg, rgba(0,0,0,0.9), rgba(0,0,0,0.95), rgba(0,0,0,0.9));
            border-top: 3px solid #667eea;
            overflow: hidden;
            z-index: 9999;
            height: 60px;
            display: flex;
            align-items: center;
        }
        
        .running-text {
            display: inline-block;
            white-space: nowrap;
            animation: scroll-left 60s linear infinite;
            padding: 0 50px;
        }
        
        .news-item {
            display: inline-block;
            padding: 0 50px;
            font-size: 24px;
            font-weight: 600;
            color: #fff;
        }
        
        .news-item .icon {
            margin-right: 10px;
            font-size: 28px;
        }
        
        .news-item .separator {
            margin: 0 30px;
            color: #667eea;
            font-size: 20px;
        }
        
        .news-item.priority {
            color: #ff4444;
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        .news-item .source {
            font-size: 18px;
            color: #999;
            margin-left: 15px;
        }
        
        .news-item .category {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 14px;
            margin-left: 10px;
        }
        
        @keyframes scroll-left {
            0% {
                transform: translateX(100%);
            }
            100% {
                transform: translateX(-100%);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }
        
        /* Loading state */
        .loading {
            color: #667eea;
            text-align: center;
            padding: 15px;
            font-size: 20px;
        }
        
        /* Error state */
        .error {
            color: #ff4444;
            text-align: center;
            padding: 15px;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="running-text-container">
        <div class="running-text" id="runningText">
            <div class="loading">⏳ Loading berita...</div>
        </div>
    </div>
    
    <script>
        // CONFIGURATION
        const DISPLAY_TYPE = 'external'; // Ganti: 'external' atau 'internal'
        const NOMOR_LAYAR = 1; // Ganti sesuai nomor layar
        const REFRESH_INTERVAL = 60000; // Refresh every 60 seconds
        const BASE_URL = window.location.origin + '/digital-signage';
        
        let newsData = [];
        let scrollSpeed = 50; // Default scroll speed
        
        /**
         * Fetch news data from API
         */
        async function fetchNews() {
            try {
                const response = await fetch(
                    `${BASE_URL}/api/get_running_text.php?display_type=${DISPLAY_TYPE}&nomor_layar=${NOMOR_LAYAR}`
                );
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    newsData = data.news;
                    scrollSpeed = data.settings.scroll_speed || 50;
                    renderNews();
                    console.log(`✅ Loaded ${newsData.length} news items`);
                } else {
                    throw new Error(data.error || 'Unknown error');
                }
                
            } catch (error) {
                console.error('❌ Error fetching news:', error);
                showError('Gagal memuat berita. Koneksi bermasalah.');
            }
        }
        
        /**
         * Render news to running text
         */
        function renderNews() {
            const container = document.getElementById('runningText');
            
            if (newsData.length === 0) {
                container.innerHTML = `
                    <div class="news-item">
                        <span class="icon">📡</span>
                        <span>Selamat datang di Balai Monitor SMFR Kelas II Manado</span>
                    </div>
                `;
                updateAnimation();
                return;
            }
            
            let html = '';
            
            newsData.forEach((news, index) => {
                const priorityClass = news.priority ? 'priority' : '';
                
                html += `
                    <div class="news-item ${priorityClass}">
                        <span class="icon">${news.icon}</span>
                        <span>${escapeHtml(news.text)}</span>
                        <span class="source">[${escapeHtml(news.sumber)}]</span>
                    </div>
                `;
                
                // Add separator (except last item)
                if (index < newsData.length - 1) {
                    html += '<span class="separator">●</span>';
                }
            });
            
            container.innerHTML = html;
            updateAnimation();
        }
        
        /**
         * Update animation duration based on scroll speed
         */
        function updateAnimation() {
            const container = document.getElementById('runningText');
            const duration = 100 - scrollSpeed; // Convert speed to duration (inverted)
            container.style.animationDuration = `${duration}s`;
        }
        
        /**
         * Show error message
         */
        function showError(message) {
            const container = document.getElementById('runningText');
            container.innerHTML = `<div class="error">⚠️ ${message}</div>`;
        }
        
        /**
         * Escape HTML to prevent XSS
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        /**
         * Initialize
         */
        async function init() {
            console.log('🚀 Running Text initialized');
            console.log(`Display: ${DISPLAY_TYPE} #${NOMOR_LAYAR}`);
            
            // Initial fetch
            await fetchNews();
            
            // Auto refresh
            setInterval(fetchNews, REFRESH_INTERVAL);
        }
        
        // Start when page loads
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
        
        // Pause on hover (optional)
        document.querySelector('.running-text-container').addEventListener('mouseenter', function() {
            document.getElementById('runningText').style.animationPlayState = 'paused';
        });
        
        document.querySelector('.running-text-container').addEventListener('mouseleave', function() {
            document.getElementById('runningText').style.animationPlayState = 'running';
        });
    </script>
</body>
</html>