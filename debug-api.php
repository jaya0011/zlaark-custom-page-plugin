<?php
/**
 * Debug API Routes
 * 
 * Access this file directly in your browser to test API functionality
 * URL: https://api.dhawada.com/wp-content/plugins/custom-page-builder/debug-api.php
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
    <title>Custom Page Builder API Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Custom Page Builder API Debug</h1>
    
    <?php
    // Check if WordPress is loaded
    if (!function_exists('get_rest_url')) {
        echo "<p class='error'>Error: WordPress not loaded properly</p>";
        exit;
    }
    
    echo "<p class='success'>✓ WordPress loaded successfully</p>";
    
    // Check database table
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_pages';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        echo "<p class='error'>✗ Database table '{$table_name}' does not exist</p>";
    } else {
        echo "<p class='success'>✓ Database table exists</p>";
        
        // Get pages count
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo "<p>Pages in database: <strong>{$count}</strong></p>";
        
        // Show published pages
        $published_pages = $wpdb->get_results("SELECT id, title, slug, status FROM $table_name WHERE status = 'published'");
        if (!empty($published_pages)) {
            echo "<h3>Published Pages:</h3>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Title</th><th>Slug</th><th>API Test</th></tr>";
            foreach ($published_pages as $page) {
                $api_url = get_rest_url(null, "custom-page-builder/v1/pages/{$page->slug}");
                echo "<tr>";
                echo "<td>{$page->id}</td>";
                echo "<td>{$page->title}</td>";
                echo "<td>{$page->slug}</td>";
                echo "<td><a href='{$api_url}' target='_blank'>Test API</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // Check REST API routes
    echo "<h3>REST API Routes:</h3>";
    $rest_server = rest_get_server();
    $routes = $rest_server->get_routes();
    
    $custom_routes = [];
    foreach ($routes as $route => $handlers) {
        if (strpos($route, 'custom-page-builder') !== false) {
            $custom_routes[] = $route;
        }
    }
    
    if (empty($custom_routes)) {
        echo "<p class='error'>✗ No custom-page-builder routes found!</p>";
    } else {
        echo "<p class='success'>✓ Found " . count($custom_routes) . " routes:</p>";
        echo "<ul>";
        foreach ($custom_routes as $route) {
            echo "<li><code>{$route}</code></li>";
        }
        echo "</ul>";
    }
    
    // Test API calls
    echo "<h3>API Tests:</h3>";
    
    // Test 1: Get all pages
    echo "<h4>Test 1: GET /pages/</h4>";
    try {
        $request = new WP_REST_Request('GET', '/custom-page-builder/v1/pages/');
        $response = rest_do_request($request);
        
        if (is_wp_error($response)) {
            echo "<p class='error'>Error: " . $response->get_error_message() . "</p>";
        } else {
            $data = $response->get_data();
            echo "<p class='success'>✓ Success - Status: " . $response->get_status() . "</p>";
            echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Exception: " . $e->getMessage() . "</p>";
    }
    
    // Test 2: Get home page
    echo "<h4>Test 2: GET /pages/home</h4>";
    try {
        $request = new WP_REST_Request('GET', '/custom-page-builder/v1/pages/home');
        $response = rest_do_request($request);
        
        if (is_wp_error($response)) {
            echo "<p class='error'>Error: " . $response->get_error_message() . "</p>";
        } else {
            $data = $response->get_data();
            echo "<p class='success'>✓ Success - Status: " . $response->get_status() . "</p>";
            echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Exception: " . $e->getMessage() . "</p>";
    }
    
    // Show direct URLs for testing
    echo "<h3>Direct API URLs for Testing:</h3>";
    $base_url = get_rest_url();
    echo "<ul>";
    echo "<li><a href='{$base_url}custom-page-builder/v1/pages/' target='_blank'>{$base_url}custom-page-builder/v1/pages/</a></li>";
    echo "<li><a href='{$base_url}custom-page-builder/v1/pages/home' target='_blank'>{$base_url}custom-page-builder/v1/pages/home</a></li>";
    echo "</ul>";
    
    ?>
    
    <h3>Next Steps:</h3>
    <ol>
        <li>Test the direct API URLs above in your browser</li>
        <li>If you see errors, check the WordPress error logs</li>
        <li>Make sure you have at least one published page with slug "home"</li>
        <li>If routes are missing, try deactivating and reactivating the plugin</li>
    </ol>
    
</body>
</html>