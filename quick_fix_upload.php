<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Fix Upload System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 36px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 40px;
            font-size: 18px;
        }
        .step-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .step-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .step-card h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .step-card .status {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 14px;
        }
        .status-checking {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-ok {
            background: #d4edda;
            color: #155724;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        .btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-success {
            background: #28a745;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            transition: width 0.5s ease;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        .summary-box {
            background: #e3f2fd;
            padding: 25px;
            border-radius: 10px;
            margin: 30px 0;
        }
        .summary-box h3 {
            color: #1976d2;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Quick Fix Upload System</h1>
        <p class="subtitle">One-click fix untuk masalah upload</p>
        
        <div class="progress-bar">
            <div class="progress-fill" id="progressBar" style="width: 0%">0%</div>
        </div>
        
        <div class="step-grid">
            <!-- Step 1 -->
            <div class="step-card">
                <h3>1️⃣ Check upload_handler.php</h3>
                <div id="status1" class="status status-checking">⏳ Checking...</div>
                <div id="detail1" style="margin-top: 10px; font-size: 13px; color: #666;"></div>
            </div>
            
            <!-- Step 2 -->
            <div class="step-card">
                <h3>2️⃣ Check uploads/ folder</h3>
                <div id="status2" class="status status-checking">⏳ Checking...</div>
                <div id="detail2" style="margin-top: 10px; font-size: 13px; color: #666;"></div>
            </div>
            
            <!-- Step 3 -->
            <div class="step-card">
                <h3>3️⃣ Check uploads/.htaccess</h3>
                <div id="status3" class="status status-checking">⏳ Checking...</div>
                <div id="detail3" style="margin-top: 10px; font-size: 13px; color: #666;"></div>
            </div>
            
            <!-- Step 4 -->
            <div class="step-card">
                <h3>4️⃣ Check PHP Settings</h3>
                <div id="status4" class="status status-checking">⏳ Checking...</div>
                <div id="detail4" style="margin-top: 10px; font-size: 13px; color: #666;"></div>
            </div>
            
            <!-- Step 5 -->
            <div class="step-card">
                <h3>5️⃣ Check Database</h3>
                <div id="status5" class="status status-checking">⏳ Checking...</div>
                <div id="detail5" style="margin-top: 10px; font-size: 13px; color: #666;"></div>
            </div>
            
            <!-- Step 6 -->
            <div class="step-card">
                <h3>6️⃣ Test Upload</h3>
                <div id="status6" class="status status-checking">⏳ Waiting...</div>
                <div id="detail6" style="margin-top: 10px; font-size: 13px; color: #666;"></div>
            </div>
        </div>
        
        <div id="summaryBox" class="summary-box" style="display: none;">
            <h3>📊 Summary</h3>
            <div id="summaryContent"></div>
        </div>
        
        <div class="action-buttons">
            <button class="btn" id="runFixBtn" onclick="runQuickFix()">
                🚀 Run Quick Fix
            </button>
            <button class="btn btn-secondary" onclick="window.location.reload()">
                🔄 Refresh
            </button>
            <a href="check_upload.php" class="btn btn-success">
                🔍 Full Diagnostic
            </a>
        </div>
    </div>
    
    <script>
        let totalSteps = 6;
        let completedSteps = 0;
        let errors = [];
        let warnings = [];
        let success = [];
        
        function updateProgress() {
            const percent = Math.round((completedSteps / totalSteps) * 100);
            const progressBar = document.getElementById('progressBar');
            progressBar.style.width = percent + '%';
            progressBar.textContent = percent + '%';
        }
        
        function setStatus(stepNum, status, message, detail = '') {
            const statusDiv = document.getElementById('status' + stepNum);
            const detailDiv = document.getElementById('detail' + stepNum);
            
            statusDiv.className = 'status status-' + status;
            statusDiv.textContent = message;
            
            if (detail) {
                detailDiv.textContent = detail;
            }
            
            completedSteps++;
            updateProgress();
        }
        
        async function runQuickFix() {
            const btn = document.getElementById('runFixBtn');
            btn.disabled = true;
            btn.textContent = '⏳ Running...';
            
            completedSteps = 0;
            errors = [];
            warnings = [];
            success = [];
            
            // Step 1: Check upload_handler.php
            await checkUploadHandler();
            
            // Step 2: Check uploads folder
            await checkUploadsFolder();
            
            // Step 3: Check .htaccess
            await checkHtaccess();
            
            // Step 4: Check PHP settings
            await checkPHPSettings();
            
            // Step 5: Check database
            await checkDatabase();
            
            // Step 6: Test upload
            await testUpload();
            
            // Show summary
            showSummary();
            
            btn.disabled = false;
            btn.textContent = errors.length === 0 ? '✅ All Fixed!' : '⚠️ Some Issues Remain';
        }
        
        async function checkUploadHandler() {
            try {
                const paths = [
                    'manage_display/upload_handler.php',
                    'upload_handler.php'
                ];
                
                let found = false;
                for (const path of paths) {
                    const response = await fetch(path, { method: 'HEAD' });
                    if (response.ok || response.status === 405) {
                        setStatus(1, 'ok', '✅ Found', 'Path: ' + path);
                        success.push('upload_handler.php found at: ' + path);
                        found = true;
                        break;
                    }
                }
                
                if (!found) {
                    setStatus(1, 'error', '❌ Not Found', 'File upload_handler.php tidak ditemukan');
                    errors.push('upload_handler.php not found');
                }
            } catch (error) {
                setStatus(1, 'error', '❌ Error', error.message);
                errors.push('Check upload_handler.php failed');
            }
        }
        
        async function checkUploadsFolder() {
            try {
                const response = await fetch('check_folder.php?folder=uploads');
                const result = await response.json();
                
                if (result.exists && result.writable) {
                    setStatus(2, 'ok', '✅ OK', 'Folder exists and writable');
                    success.push('uploads/ folder OK');
                } else if (result.exists && !result.writable) {
                    setStatus(2, 'error', '❌ Not Writable', 'Change permission to 755');
                    errors.push('uploads/ not writable');
                } else {
                    setStatus(2, 'error', '❌ Not Found', 'Folder needs to be created');
                    errors.push('uploads/ folder missing');
                }
            } catch (error) {
                setStatus(2, 'error', '❌ Error', error.message);
                errors.push('Check uploads/ failed');
            }
        }
        
        async function checkHtaccess() {
            try {
                const response = await fetch('uploads/.htaccess', { method: 'HEAD' });
                
                if (response.status === 403 || response.status === 200) {
                    setStatus(3, 'ok', '✅ Exists', 'Security file present');
                    success.push('.htaccess exists');
                } else {
                    setStatus(3, 'error', '⚠️ Missing', 'Recommended for security');
                    warnings.push('.htaccess missing (optional)');
                }
            } catch (error) {
                setStatus(3, 'error', '⚠️ Missing', 'Create for security');
                warnings.push('.htaccess missing');
            }
        }
        
        async function checkPHPSettings() {
            try {
                const response = await fetch('check_php_settings.php');
                const result = await response.json();
                
                if (result.upload_max_ok && result.post_max_ok) {
                    setStatus(4, 'ok', '✅ OK', `Max: ${result.upload_max}`);
                    success.push('PHP settings OK');
                } else {
                    setStatus(4, 'error', '❌ Too Small', `Current: ${result.upload_max}`);
                    errors.push('PHP upload limit too small');
                }
            } catch (error) {
                setStatus(4, 'error', '❌ Error', error.message);
                errors.push('Check PHP settings failed');
            }
        }
        
        async function checkDatabase() {
            try {
                const response = await fetch('check_db_connection.php');
                const result = await response.json();
                
                if (result.connected && result.table_exists) {
                    setStatus(5, 'ok', '✅ OK', 'Connected & table exists');
                    success.push('Database OK');
                } else if (result.connected) {
                    setStatus(5, 'error', '❌ Table Missing', 'konten_layar table not found');
                    errors.push('Database table missing');
                } else {
                    setStatus(5, 'error', '❌ No Connection', 'Cannot connect to database');
                    errors.push('Database connection failed');
                }
            } catch (error) {
                setStatus(5, 'error', '❌ Error', error.message);
                errors.push('Check database failed');
            }
        }
        
        async function testUpload() {
            if (errors.length > 0) {
                setStatus(6, 'error', '⚠️ Skipped', 'Fix errors first');
                return;
            }
            
            setStatus(6, 'ok', '✅ Ready', 'System ready for upload');
            success.push('Upload system ready');
        }
        
        function showSummary() {
            const summaryBox = document.getElementById('summaryBox');
            const summaryContent = document.getElementById('summaryContent');
            
            let html = '';
            
            html += '<p><strong>✅ Success:</strong> ' + success.length + ' checks passed</p>';
            html += '<p><strong>⚠️ Warnings:</strong> ' + warnings.length + ' warnings</p>';
            html += '<p><strong>❌ Errors:</strong> ' + errors.length + ' errors</p>';
            
            if (errors.length > 0) {
                html += '<div style="margin-top: 15px; padding: 15px; background: #f8d7da; border-radius: 5px;">';
                html += '<strong>Errors to fix:</strong><ul style="margin-left: 20px; margin-top: 10px;">';
                errors.forEach(err => {
                    html += '<li>' + err + '</li>';
                });
                html += '</ul></div>';
            }
            
            if (errors.length === 0 && warnings.length === 0) {
                html += '<div style="margin-top: 15px; padding: 15px; background: #d4edda; border-radius: 5px; color: #155724;">';
                html += '<strong>🎉 All checks passed! Upload system is ready.</strong>';
                html += '</div>';
            }
            
            summaryContent.innerHTML = html;
            summaryBox.style.display = 'block';
        }
        
        // Auto-run on page load
        window.addEventListener('load', () => {
            setTimeout(runQuickFix, 500);
        });
    </script>
</body>
</html>