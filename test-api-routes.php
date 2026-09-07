<?php
/**
 * API Route Testing Script
 * 
 * This script helps test and debug the REST API routes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo "<h2>Custom Page Builder API Route Test</h2>";

// Test if WordPress is loaded
if (!function_exists('get_rest_url')) {
    echo "<p style='color: red;'>Error: WordPress not loaded properly</p>";
    exit;
}

// Get the base URL
$base_url = get_rest_url(null, 'custom-page-builder/v1/pages/');
echo "<p><strong>Base API URL:</strong> <a href='{$base_url}' target='_blank'>{$base_url}</a></p>";

// Test specific page URL
$home_url = get_rest_url(null, 'custom-page-builder/v1/pages/home');
echo "<p><strong>Home Page URL:</strong> <a href='{$home_url}' target='_blank'>{$home_url}</a></p>";

// Check if pages exist in database
global $wpdb;
$table_name = $wpdb->prefix . 'custom_pages';

// Check if table exists
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    echo "<p style='color: red;'>Error: Database table '{$table_name}' does not exist</p>";
    exit;
}

// Get all pages
$pages = $wpdb->get_results("SELECT id, title, slug, status FROM $table_name ORDER BY created_at DESC");

echo "<h3>Pages in Database:</h3>";
if (empty($pages)) {
    echo "<p>No pages found in database</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Title</th><th>Slug</th><th>Status</th><th>API URL</th></tr>";
    foreach ($pages as $page) {
        $page_url = get_rest_url(null, "custom-page-builder/v1/pages/{$page->slug}");
        $status_color = $page->status === 'published' ? 'green' : 'orange';
        echo "<tr>";
        echo "<td>{$page->id}</td>";
        echo "<td>{$page->title}</td>";
        echo "<td>{$page->slug}</td>";
        echo "<td style='color: {$status_color};'>{$page->status}</td>";
        echo "<td><a href='{$page_url}' target='_blank'>Test API</a></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check REST API registration
echo "<h3>REST API Routes Check:</h3>";
$rest_server = rest_get_server();
$routes = $rest_server->get_routes();

$found_routes = [];
foreach ($routes as $route => $handlers) {
    if (strpos($route, 'custom-page-builder/v1') !== false) {
        $found_routes[] = $route;
    }
}

if (empty($found_routes)) {
    echo "<p style='color: red;'>No custom-page-builder routes found!</p>";
} else {
    echo "<p style='color: green;'>Found routes:</p>";
    echo "<ul>";
    foreach ($found_routes as $route) {
        echo "<li><code>{$route}</code></li>";
    }
    echo "</ul>";
}

// Test API call internally
echo "<h3>Internal API Test:</h3>";
try {
    $request = new WP_REST_Request('GET', '/custom-page-builder/v1/pages/');
    $response = rest_do_request($request);
    
    if (is_wp_error($response)) {
        echo "<p style='color: red;'>API Error: " . $response->get_error_message() . "</p>";
    } else {
        $data = $response->get_data();
        echo "<p style='color: green;'>API Response: " . json_encode($data, JSON_PRETTY_PRINT) . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
}