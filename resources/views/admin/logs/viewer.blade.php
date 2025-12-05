@extends('admin.body')

@section('admin')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="mdi mdi-file-document-outline"></i> Application Logs</h4>
        </div>
        <div class="card-body" style="background: #1e1e1e; color: #d4d4d4;">
            <div class="mb-3">
                <input type="text" id="searchBox" class="form-control mb-2" placeholder="Search logs..." onkeyup="filterLogs()">
                <button class="btn btn-success" onclick="location.reload()"><i class="mdi mdi-refresh"></i> Refresh</button>
                <button class="btn btn-warning" onclick="downloadLogs()"><i class="mdi mdi-download"></i> Download</button>
            </div>

            <div id="logContent" style="max-height: 70vh; overflow-y: auto; font-family: monospace; font-size: 12px;">
                @php
                $logFile = storage_path('logs/laravel.log');
                
                if (file_exists($logFile)) {
                    $logs = file_get_contents($logFile);
                    
                    // Get last 500 lines
                    $lines = explode("\n", $logs);
                    $recentLines = array_reverse(array_slice($lines, -500));
                    
                    foreach ($recentLines as $line) {
                        if (empty(trim($line))) continue;
                        
                        $class = 'text-info';
                        $icon = 'mdi-information';
                        
                        if (stripos($line, 'ERROR') !== false || stripos($line, 'exception') !== false) {
                            $class = 'text-danger';
                            $icon = 'mdi-alert-circle';
                        } elseif (stripos($line, 'WARNING') !== false) {
                            $class = 'text-warning';
                            $icon = 'mdi-alert';
                        }
                        
                        echo "<div class='log-entry {$class} mb-2 p-2' style='border-left: 3px solid;'>";
                        echo "<i class='mdi {$icon}'></i> ";
                        echo htmlspecialchars($line);
                        echo "</div>";
                    }
                    
                    echo "<hr><p class='text-muted'>Showing last 500 log entries | File: {$logFile} | Size: " . round(filesize($logFile) / 1024 / 1024, 2) . " MB</p>";
                } else {
                    echo "<div class='alert alert-warning'>No log file found</div>";
                }
                @endphp
            </div>
        </div>
    </div>
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

function downloadLogs() {
    window.location.href = '{{ route("admin.logs.download") }}';
}
</script>
@endsection
