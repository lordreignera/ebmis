<!DOCTYPE html>
<html>
<head>
    <title>Upload Configuration Check</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .card { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #333; }
        .ok { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        td:first-child { font-weight: bold; width: 300px; }
    </style>
</head>
<body>
    <h1>🔧 PHP Upload Configuration</h1>
    
    <div class="card">
        <h2>File Upload Settings</h2>
        <table>
            <tr>
                <td>upload_max_filesize</td>
                <td class="<?php echo ini_get('upload_max_filesize') >= 50 ? 'ok' : 'warning'; ?>">
                    <?php echo ini_get('upload_max_filesize'); ?>
                </td>
            </tr>
            <tr>
                <td>post_max_size</td>
                <td class="<?php echo ini_get('post_max_size') >= 50 ? 'ok' : 'warning'; ?>">
                    <?php echo ini_get('post_max_size'); ?>
                </td>
            </tr>
            <tr>
                <td>max_file_uploads</td>
                <td><?php echo ini_get('max_file_uploads'); ?></td>
            </tr>
            <tr>
                <td>max_execution_time</td>
                <td><?php echo ini_get('max_execution_time'); ?> seconds</td>
            </tr>
            <tr>
                <td>max_input_time</td>
                <td><?php echo ini_get('max_input_time'); ?> seconds</td>
            </tr>
            <tr>
                <td>memory_limit</td>
                <td><?php echo ini_get('memory_limit'); ?></td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2>Temporary Directory</h2>
        <table>
            <tr>
                <td>upload_tmp_dir</td>
                <td><?php echo ini_get('upload_tmp_dir') ?: sys_get_temp_dir(); ?></td>
            </tr>
            <tr>
                <td>Temp directory writable?</td>
                <td class="<?php echo is_writable(sys_get_temp_dir()) ? 'ok' : 'error'; ?>">
                    <?php echo is_writable(sys_get_temp_dir()) ? '✓ Yes' : '✗ No - THIS IS A PROBLEM!'; ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2>Recommendations</h2>
        <?php
        $upload_max = ini_get('upload_max_filesize');
        $post_max = ini_get('post_max_size');
        
        if ($upload_max < 50 || $post_max < 50) {
            echo '<p class="warning">⚠️ Your server limits are low. To accept larger files, update your php.ini:</p>';
            echo '<pre style="background: #f0f0f0; padding: 15px; border-radius: 5px;">';
            echo 'upload_max_filesize = 50M' . "\n";
            echo 'post_max_size = 50M' . "\n";
            echo 'max_execution_time = 300' . "\n";
            echo 'max_input_time = 300' . "\n";
            echo 'memory_limit = 256M';
            echo '</pre>';
        } else {
            echo '<p class="ok">✓ Your server configuration looks good!</p>';
        }
        ?>
    </div>

    <div class="card">
        <h2>Test Upload</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="test_file" required>
            <button type="submit" style="padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
                Test Upload
            </button>
        </form>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
            echo '<hr><h3>Upload Test Result:</h3>';
            $file = $_FILES['test_file'];
            
            echo '<table>';
            echo '<tr><td>File Name</td><td>' . htmlspecialchars($file['name']) . '</td></tr>';
            echo '<tr><td>File Size</td><td>' . number_format($file['size'] / 1024 / 1024, 2) . ' MB</td></tr>';
            echo '<tr><td>File Type</td><td>' . htmlspecialchars($file['type']) . '</td></tr>';
            echo '<tr><td>Tmp Name</td><td>' . htmlspecialchars($file['tmp_name']) . '</td></tr>';
            
            if ($file['error'] === UPLOAD_ERR_OK) {
                echo '<tr><td>Status</td><td class="ok">✓ Upload Successful!</td></tr>';
            } else {
                $errors = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'Extension stopped upload',
                ];
                echo '<tr><td>Status</td><td class="error">✗ Error: ' . $errors[$file['error']] . '</td></tr>';
            }
            echo '</table>';
        }
        ?>
    </div>
</body>
</html>
