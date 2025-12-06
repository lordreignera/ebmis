<?php
/**
 * Run this script to check and attempt to fix upload limits
 * Access via: https://your-domain.com/fix-upload-limits.php
 */

// Only allow access from authorized users
session_start();
if (!isset($_SESSION['fix_authorized'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['password'] === 'ebims2025') {
        $_SESSION['fix_authorized'] = true;
    } else {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Authorization Required</title>
            <style>
                body { font-family: Arial; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f0f0; }
                .box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                input { padding: 10px; width: 300px; margin: 10px 0; }
                button { padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
            </style>
        </head>
        <body>
            <div class="box">
                <h2>🔒 Authorization Required</h2>
                <form method="POST">
                    <input type="password" name="password" placeholder="Enter password" required>
                    <br>
                    <button type="submit">Access</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Upload Limits</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .card { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #333; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f0f0f0; padding: 15px; border-radius: 5px; overflow-x: auto; }
        button { padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .success { background: #4CAF50; }
    </style>
</head>
<body>
    <h1>🔧 Fix PHP Upload Limits</h1>

    <div class="card">
        <h2>Current Settings</h2>
        <?php
        $upload_max = ini_get('upload_max_filesize');
        $post_max = ini_get('post_max_size');
        $memory = ini_get('memory_limit');
        $exec_time = ini_get('max_execution_time');
        
        echo "<p><strong>upload_max_filesize:</strong> <span class='" . ($upload_max >= 50 ? 'ok' : 'error') . "'>$upload_max</span></p>";
        echo "<p><strong>post_max_size:</strong> <span class='" . ($post_max >= 50 ? 'ok' : 'error') . "'>$post_max</span></p>";
        echo "<p><strong>memory_limit:</strong> $memory</p>";
        echo "<p><strong>max_execution_time:</strong> $exec_time seconds</p>";
        ?>
    </div>

    <div class="card">
        <h2>Solution 1: Create .user.ini file</h2>
        <?php
        $userIniPath = __DIR__ . '/.user.ini';
        $userIniContent = "; PHP Configuration Override
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
";
        
        if (file_put_contents($userIniPath, $userIniContent)) {
            echo "<p class='ok'>✓ Created .user.ini file successfully!</p>";
            echo "<p class='warning'>⚠ Note: .user.ini changes may take 5 minutes to take effect (depends on user_ini.cache_ttl)</p>";
            echo "<p>File location: <code>$userIniPath</code></p>";
        } else {
            echo "<p class='error'>✗ Failed to create .user.ini (permission denied)</p>";
        }
        ?>
    </div>

    <div class="card">
        <h2>Solution 2: SSH Commands (For server access)</h2>
        <p>If you have SSH access to your server, run these commands:</p>
        <pre>cd <?php echo dirname(dirname(__DIR__)); ?>

bash fix_upload_limits.sh</pre>
        <p>Or manually edit php.ini:</p>
        <pre>sudo nano <?php echo php_ini_loaded_file(); ?>

# Find and change these lines:
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
memory_limit = 256M

# Save and restart PHP-FPM:
sudo systemctl restart php<?php echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION; ?>-fpm
sudo systemctl restart nginx  # or apache2</pre>
    </div>

    <div class="card">
        <h2>Solution 3: Contact Support</h2>
        <p>If the above solutions don't work, contact your hosting provider (Digital Ocean) and ask them to:</p>
        <ul>
            <li>Increase <code>upload_max_filesize</code> to 100M</li>
            <li>Increase <code>post_max_size</code> to 100M</li>
            <li>Increase <code>max_execution_time</code> to 300</li>
        </ul>
    </div>

    <div class="card">
        <h2>Verify Changes</h2>
        <p>After making changes, refresh this page to see if the limits have increased.</p>
        <button onclick="location.reload()" class="success">🔄 Refresh Page</button>
        <button onclick="window.open('<?php echo dirname($_SERVER['PHP_SELF']); ?>/check-upload-config.php')">🧪 Test Upload</button>
    </div>

    <div class="card">
        <h2>PHP Info</h2>
        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
        <p><strong>PHP SAPI:</strong> <?php echo php_sapi_name(); ?></p>
        <p><strong>Loaded php.ini:</strong> <?php echo php_ini_loaded_file(); ?></p>
    </div>
</body>
</html>
