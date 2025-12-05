<!DOCTYPE html>
<html>
<head>
    <title>Laravel Logs Viewer</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .error { color: #f48771; background: #3f1c1c; padding: 10px; margin: 10px 0; border-left: 4px solid #f48771; }
        .warning { color: #dcdcaa; background: #3f3f1c; padding: 10px; margin: 10px 0; border-left: 4px solid #dcdcaa; }
        .info { color: #4ec9b0; background: #1c3f3f; padding: 10px; margin: 10px 0; border-left: 4px solid #4ec9b0; }
        .timestamp { color: #858585; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
        h1 { color: #4ec9b0; }
        .filter { margin: 20px 0; }
        .filter input { padding: 10px; width: 300px; background: #2d2d2d; color: #d4d4d4; border: 1px solid #3f3f3f; }
        .clear-btn { background: #f48771; color: white; padding: 10px 20px; border: none; cursor: pointer; margin-left: 10px; }
        .refresh-btn { background: #4ec9b0; color: white; padding: 10px 20px; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🔍 Laravel Application Logs</h1>
    
    <div class="filter">
        <input type="text" id="searchBox" placeholder="Search logs..." onkeyup="filterLogs()">
        <button class="refresh-btn" onclick="location.reload()">🔄 Refresh</button>
        <button class="clear-btn" onclick="if(confirm('Clear all logs?')) clearLogs()">🗑️ Clear Logs</button>
    </div>

    <div id="logContent">
        <?php
        $logFile = storage_path('logs/laravel.log');
        
        if (file_exists($logFile)) {
            $logs = file_get_contents($logFile);
            
            // Get last 200 lines (most recent)
            $lines = explode("\n", $logs);
            $recentLines = array_slice($lines, -200);
            $recentLogs = implode("\n", $recentLines);
            
            // Parse and highlight logs
            $logEntries = preg_split('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $recentLogs, -1, PREG_SPLIT_DELIM_CAPTURE);
            
            for ($i = 1; $i < count($logEntries); $i += 2) {
                $timestamp = $logEntries[$i];
                $content = $logEntries[$i + 1] ?? '';
                
                // Determine log level
                $class = 'info';
                if (stripos($content, 'ERROR') !== false || stripos($content, 'exception') !== false) {
                    $class = 'error';
                } elseif (stripos($content, 'WARNING') !== false || stripos($content, 'WARN') !== false) {
                    $class = 'warning';
                }
                
                echo "<div class='log-entry {$class}'>";
                echo "<span class='timestamp'>[{$timestamp}]</span>";
                echo "<pre>" . htmlspecialchars($content) . "</pre>";
                echo "</div>";
            }
            
            echo "<hr><p style='color: #858585;'>Showing last 200 log entries from: {$logFile}</p>";
            echo "<p style='color: #858585;'>File size: " . round(filesize($logFile) / 1024 / 1024, 2) . " MB</p>";
        } else {
            echo "<div class='info'>No log file found at: {$logFile}</div>";
        }
        ?>
    </div>

    <script>
        function filterLogs() {
            const searchText = document.getElementById('searchBox').value.toLowerCase();
            const entries = document.querySelectorAll('.log-entry');
            
            entries.forEach(entry => {
                const text = entry.textContent.toLowerCase();
                entry.style.display = text.includes(searchText) ? 'block' : 'none';
            });
        }

        function clearLogs() {
            fetch('<?php echo url('/'); ?>/clear-logs.php', {
                method: 'POST'
            }).then(() => {
                alert('Logs cleared!');
                location.reload();
            });
        }
    </script>
</body>
</html>
