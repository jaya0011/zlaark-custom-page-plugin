<?php
/**
 * Debug API 500 Error
 * 
 * This script helps debug the 500 internal error in the API
 */

// Load WordPress
$wp_load_paths = [
    '../../../wp-load.php',
    '../../../../wp-load.php',
    '../../../../../wp-load.php'
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists(__DIR__ . '/' . $path)) {
        require_once __DIR__ . '/' . $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('Could not load WordPress. Please check the path.');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug API 500 Error</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        .test-section { border: 1px solid #ddd; padding: 15px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Debug API 500 Error</h1>
    
    <?php
    // Test 1: Check database table
    echo "<div class='test-section'>";
    echo "<h2>Test 1: Database Table Check</h2>";
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_pages';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        echo "<p class='error'>✗ Table '$table_name' does not exist</p>";
    } else {
        echo "<p class='success'>✓ Table exists</p>";
        
        // Check table structure
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        echo "<p><strong>Table columns:</strong></p>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column->Field} ({$column->Type})</li>";
        }
        echo "</ul>";
        
        // Check for data
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo "<p><strong>Total pages:</strong> $count</p>";
        
        $published_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'published'");
        echo "<p><strong>Published pages:</strong> $published_count</p>";
    }
    echo "</div>";
    
    // Test 2: Check WordPress REST API
    echo "<div class='test-section'>";
    echo "<h2>Test 2: WordPress REST API Check</h2>";
    
    if (!function_exists('rest_get_server')) {
        echo "<p class='error'>✗ WordPress REST API not available</p>";
    } else {
        echo "<p class='success'>✓ WordPress REST API available</p>";
        
        // Check if our routes are registered
        $rest_server = rest_get_server();
        $routes = $rest_server->get_routes();
        
        $our_routes = [];
        foreach ($routes as $route => $handlers) {
            if (strpos($route, 'custom-page-builder') !== false) {
                $our_routes[] = $route;
            }
        }
        
        if (empty($our_routes)) {
            echo "<p class='error'>✗ No custom-page-builder routes found</p>";
        } else {
            echo "<p class='success'>✓ Found routes:</p>";
            echo "<ul>";
            foreach ($our_routes as $route) {
                echo "<li><code>$route</code></li>";
            }
            echo "</ul>";
        }
    }
    echo "</div>";
    
    // Test 3: Test API internally
    echo "<div class='test-section'>";
    echo "<h2>Test 3: Internal API Test</h2>";
    
    try {
        // Test the base endpoint
        echo "<h3>Testing /pages/ endpoint</h3>";
        $request = new WP_REST_Request('GET', '/custom-page-builder/v1/pages/');
        $response = rest_do_request($request);
        
        if (is_wp_error($response)) {
            echo "<p class='error'>WP Error: " . $response->get_error_message() . "</p>";
        } else {
            $status = $response->get_status();
            $data = $response->get_data();
            
            echo "<p class='success'>Status: $status</p>";
            echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        }
        
        // Test a specific page if it exists
        $test_page = $wpdb->get_row("SELECT slug FROM $table_name WHERE status = 'published' LIMIT 1");
        if ($test_page) {
            echo "<h3>Testing /pages/{$test_page->slug} endpoint</h3>";
            $request = new WP_REST_Request('GET', "/custom-page-builder/v1/pages/{$test_page->slug}");
            $response = rest_do_request($request);
            
            if (is_wp_error($response)) {
                echo "<p class='error'>WP Error: " . $response->get_error_message() . "</p>";
            } else {
                $status = $response->get_status();
                $data = $response->get_data();
                
                echo "<p class='success'>Status: $status</p>";
                echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
            }
        } else {
            echo "<p class='warning'>No published pages found to test individual endpoint</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>Exception: " . $e->getMessage() . "</p>";
    } catch (Error $e) {
        echo "<p class='error'>Fatal Error: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // Test 4: Check error logs
    echo "<div class='test-section'>";
    echo "<h2>Test 4: Recent Error Log Entries</h2>";
    
    // Try to read WordPress error log
    $error_log_paths = [
        ABSPATH . 'wp-content/debug.log',
        ini_get('error_log'),
        '/var/log/apache2/error.log',
        '/var/log/nginx/error.log'
    ];
    
    $found_logs = false;
    foreach ($error_log_paths as $log_path) {
        if ($log_path && file_exists($log_path) && is_readable($log_path)) {
            echo "<p><strong>Reading from:</strong> $log_path</p>";
            
            // Get last 50 lines
            $lines = file($log_path);
            if ($lines) {
                $recent_lines = array_slice($lines, -50);
                $cpb_lines = array_filter($recent_lines, function($line) {
                    return strpos($line, 'Custom Page Builder') !== false;
                });
                
                if (!empty($cpb_lines)) {
                    echo "<p class='success'>Found Custom Page Builder log entries:</p>";
                    echo "<pre>" . implode('', $cpb_lines) . "</pre>";
                } else {
                    echo "<p class='warning'>No Custom Page Builder entries in recent logs</p>";
                }
                $found_logs = true;
                break;
            }
        }
    }
    
    if (!$found_logs) {
        echo "<p class='warning'>Could not access error logs. Check WordPress debug settings.</p>";
        echo "<p>To enable logging, add this to wp-config.php:</p>";
        echo "<pre>define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);</pre>";
    }
    echo "</div>";
    
    // Test 5: Manual database query
    echo "<div class='test-section'>";
    echo "<h2>Test 5: Manual Database Query</h2>";
    
    try {
        $pages = $wpdb->get_results("SELECT id, title, slug, status FROM $table_name WHERE status = 'published' LIMIT 5");
        
        if ($wpdb->last_error) {
            echo "<p class='error'>Database Error: " . $wpdb->last_error . "</p>";
        } else {
            echo "<p class='success'>Query successful. Found " . count($pages) . " pages:</p>";
            if (!empty($pages)) {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Title</th><th>Slug</th><th>Status</th></tr>";
                foreach ($pages as $page) {
                    echo "<tr><td>{$page->id}</td><td>{$page->title}</td><td>{$page->slug}</td><td>{$page->status}</td></tr>";
                }
                echo "</table>";
            }
        }
    } catch (Exception $e) {
        echo "<p class='error'>Exception in database query: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    ?>
    
    <div class='test-section'>
        <h2>Next Steps</h2>
        <ol>
            <li>Check the error log entries above for specific error messages</li>
            <li>If no errors are shown, try accessing the API URL directly in a new browser tab</li>
            <li>Check WordPress debug settings to ensure errors are being logged</li>
            <li>If the internal test works but external URL doesn't, it might be a server configuration issue</li>
        </ol>
    </div>
    
</body>
</html>