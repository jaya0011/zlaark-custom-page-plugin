<?php
/**
 * Test API Pages Functionality
 * 
 * This script tests if pages created in the page builder are accessible via API
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
    <title>Test API Pages</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; max-height: 300px; }
        .test-url { background: #e7f3ff; padding: 10px; margin: 10px 0; border-left: 4px solid #2196F3; }
    </style>
</head>
<body>
    <h1>Custom Page Builder API Test</h1>
    
    <?php
    // Check database connection and table
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_pages';
    
    echo "<h2>1. Database Check</h2>";
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        echo "<p class='error'>✗ Database table '{$table_name}' does not exist</p>";
        exit;
    } else {
        echo "<p class='success'>✓ Database table exists</p>";
    }
    
    // Get all pages from database
    $all_pages = $wpdb->get_results("SELECT id, title, slug, status, created_at FROM $table_name ORDER BY created_at DESC");
    $published_pages = $wpdb->get_results("SELECT id, title, slug, status, created_at FROM $table_name WHERE status = 'published' ORDER BY created_at DESC");
    
    echo "<h2>2. Pages in Database</h2>";
    echo "<p><strong>Total pages:</strong> " . count($all_pages) . "</p>";
    echo "<p><strong>Published pages:</strong> " . count($published_pages) . "</p>";
    
    if (!empty($all_pages)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>Slug</th><th>Status</th><th>Created</th><th>API URL</th></tr>";
        foreach ($all_pages as $page) {
            $api_url = get_rest_url(null, "custom-page-builder/v1/pages/{$page->slug}");
            $status_class = $page->status === 'published' ? 'success' : 'warning';
            echo "<tr>";
            echo "<td>{$page->id}</td>";
            echo "<td>{$page->title}</td>";
            echo "<td><strong>{$page->slug}</strong></td>";
            echo "<td class='{$status_class}'>{$page->status}</td>";
            echo "<td>{$page->created_at}</td>";
            echo "<td><a href='{$api_url}' target='_blank'>Test API</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>No pages found in database. Create some pages in the page builder first.</p>";
    }
    
    // Test API endpoints
    echo "<h2>3. API Endpoint Tests</h2>";
    
    // Test base API
    echo "<h3>Test: Get All Pages</h3>";
    $base_api_url = get_rest_url(null, 'custom-page-builder/v1/pages/');
    echo "<div class='test-url'><strong>URL:</strong> <a href='{$base_api_url}' target='_blank'>{$base_api_url}</a></div>";
    
    try {
        $request = new WP_REST_Request('GET', '/custom-page-builder/v1/pages/');
        $response = rest_do_request($request);
        
        if (is_wp_error($response)) {
            echo "<p class='error'>API Error: " . $response->get_error_message() . "</p>";
        } else {
            $data = $response->get_data();
            echo "<p class='success'>✓ API Response: Status " . $response->get_status() . "</p>";
            echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Exception: " . $e->getMessage() . "</p>";
    }
    
    // Test individual pages
    if (!empty($published_pages)) {
        echo "<h3>Test: Individual Pages</h3>";
        
        foreach ($published_pages as $page) {
            echo "<h4>Testing page: {$page->title} (slug: {$page->slug})</h4>";
            $page_api_url = get_rest_url(null, "custom-page-builder/v1/pages/{$page->slug}");
            echo "<div class='test-url'><strong>URL:</strong> <a href='{$page_api_url}' target='_blank'>{$page_api_url}</a></div>";
            
            try {
                $request = new WP_REST_Request('GET', "/custom-page-builder/v1/pages/{$page->slug}");
                $response = rest_do_request($request);
                
                if (is_wp_error($response)) {
                    echo "<p class='error'>API Error: " . $response->get_error_message() . "</p>";
                } else {
                    $data = $response->get_data();
                    $status = $response->get_status();
                    
                    if ($status === 200) {
                        echo "<p class='success'>✓ API Response: Status {$status}</p>";
                        
                        // Show page data summary
                        if (isset($data['page'])) {
                            $pageData = $data['page'];
                            echo "<p><strong>Title:</strong> " . ($pageData->title ?? 'N/A') . "</p>";
                            echo "<p><strong>Slug:</strong> " . ($pageData->slug ?? 'N/A') . "</p>";
                            echo "<p><strong>Status:</strong> " . ($pageData->status ?? 'N/A') . "</p>";
                            
                            // Check sections
                            if (!empty($pageData->sections)) {
                                $sections = json_decode($pageData->sections, true);
                                if (is_array($sections)) {
                                    echo "<p><strong>Sections:</strong> " . count($sections) . " sections found</p>";
                                    foreach ($sections as $i => $section) {
                                        echo "<p>  - Section " . ($i + 1) . ": " . ($section['type'] ?? 'unknown') . 
                                             " (" . ($section['title'] ?? 'no title') . ")</p>";
                                    }
                                } else {
                                    echo "<p class='warning'>Sections data exists but couldn't be decoded</p>";
                                }
                            } else {
                                echo "<p class='warning'>No sections data found</p>";
                            }
                        }
                        
                        echo "<details><summary>Full API Response (click to expand)</summary>";
                        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
                        echo "</details>";
                    } else {
                        echo "<p class='error'>✗ API Response: Status {$status}</p>";
                        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
                    }
                }
            } catch (Exception $e) {
                echo "<p class='error'>Exception: " . $e->getMessage() . "</p>";
            }
            
            echo "<hr>";
        }
    }
    
    // Check for "home" page specifically
    echo "<h2>4. Home Page Check</h2>";
    $home_page = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE slug = %s",
        'home'
    ));
    
    if ($home_page) {
        echo "<p class='success'>✓ Home page found in database</p>";
        echo "<p><strong>Status:</strong> {$home_page->status}</p>";
        echo "<p><strong>Title:</strong> {$home_page->title}</p>";
        
        if ($home_page->status === 'published') {
            echo "<p class='success'>✓ Home page is published and should be accessible via API</p>";
            $home_api_url = get_rest_url(null, 'custom-page-builder/v1/pages/home');
            echo "<div class='test-url'><strong>Home API URL:</strong> <a href='{$home_api_url}' target='_blank'>{$home_api_url}</a></div>";
        } else {
            echo "<p class='warning'>⚠ Home page exists but is not published (status: {$home_page->status})</p>";
            echo "<p>Change the status to 'published' in the page builder to make it accessible via API</p>";
        }
    } else {
        echo "<p class='error'>✗ No page with slug 'home' found in database</p>";
        echo "<p>Create a page with slug 'home' in the page builder first</p>";
    }
    
    // Instructions
    echo "<h2>5. How to Create API-Accessible Pages</h2>";
    echo "<ol>";
    echo "<li>Go to WordPress Admin → Page Builder → Add New</li>";
    echo "<li>Enter a <strong>Page Title</strong> (e.g., 'Home Page')</li>";
    echo "<li>Enter a <strong>Slug</strong> (e.g., 'home') - this will be used in the API URL</li>";
    echo "<li>Set <strong>Status</strong> to 'Published'</li>";
    echo "<li>Add your sections and content</li>";
    echo "<li>Click 'Create Page' or 'Update Page'</li>";
    echo "<li>Your page will be accessible at: <code>https://api.dhawada.com/wp-json/custom-page-builder/v1/pages/[your-slug]</code></li>";
    echo "</ol>";
    
    echo "<h2>6. Troubleshooting</h2>";
    echo "<ul>";
    echo "<li><strong>404 Not Found:</strong> Check if the page exists and is published</li>";
    echo "<li><strong>Empty sections:</strong> Make sure you added and saved sections in the page builder</li>";
    echo "<li><strong>API not working:</strong> Check if WordPress REST API is enabled</li>";
    echo "<li><strong>Slug issues:</strong> Use only letters, numbers, hyphens, and underscores in slugs</li>";
    echo "</ul>";
    ?>
    
</body>
</html>