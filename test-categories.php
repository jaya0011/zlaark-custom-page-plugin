<?php
/**
 * Test Categories Script
 * Run this to test if WordPress categories are accessible
 * 
 * Access: yoursite.com/wp-content/plugins/Zlaark_custom-page/test-categories.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('You must be an administrator to run this test.');
}

echo '<html><head><title>Category Test</title>';
echo '<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}';
echo '.box{background:#fff;padding:20px;margin:10px 0;border-radius:5px;border:1px solid #ddd;}';
echo '.success{background:#d4edda;border-color:#c3e6cb;color:#155724;}';
echo '.error{background:#f8d7da;border-color:#f5c6cb;color:#721c24;}';
echo '.info{background:#d1ecf1;border-color:#bee5eb;color:#0c5460;}';
echo 'h2{margin-top:0;}ul{margin:10px 0;}li{margin:5px 0;}</style></head><body>';

echo '<h1>🧪 WordPress Categories Test</h1>';

// Test 1: Check if Category Manager class exists
echo '<div class="box">';
echo '<h2>Test 1: Category Manager Class</h2>';
if (class_exists('\Custom_Page_Builder\Category_Manager')) {
    echo '<div class="success">✅ Category_Manager class exists!</div>';
} else {
    echo '<div class="error">❌ Category_Manager class NOT found!<br>';
    echo 'File should be at: ' . __DIR__ . '/includes/class-category-manager.php</div>';
}
echo '</div>';

// Test 2: Get WordPress categories
echo '<div class="box">';
echo '<h2>Test 2: WordPress Categories</h2>';
$wp_cats = get_categories(['hide_empty' => false]);
if (!empty($wp_cats)) {
    echo '<div class="success">✅ Found ' . count($wp_cats) . ' WordPress categories:</div>';
    echo '<ul>';
    foreach ($wp_cats as $cat) {
        echo '<li><strong>' . esc_html($cat->name) . '</strong> (ID: ' . $cat->term_id . ', Posts: ' . $cat->count . ')</li>';
    }
    echo '</ul>';
} else {
    echo '<div class="error">❌ No WordPress categories found!<br>';
    echo '<a href="' . admin_url('edit-tags.php?taxonomy=category') . '">Click here to add categories</a></div>';
}
echo '</div>';

// Test 3: Test Category Manager methods
if (class_exists('\Custom_Page_Builder\Category_Manager')) {
    echo '<div class="box">';
    echo '<h2>Test 3: Category Manager Methods</h2>';
    
    try {
        $categories = \Custom_Page_Builder\Category_Manager::get_categories();
        
        if (!empty($categories)) {
            echo '<div class="success">✅ Category Manager returned ' . count($categories) . ' categories:</div>';
            echo '<ul>';
            foreach ($categories as $cat) {
                echo '<li><strong>' . esc_html($cat['name']) . '</strong> (ID: ' . $cat['id'] . ', Slug: ' . $cat['slug'] . ')</li>';
            }
            echo '</ul>';
        } else {
            echo '<div class="info">ℹ️ Category Manager returned empty array.<br>';
            echo 'This means no WordPress categories exist yet.</div>';
        }
    } catch (Exception $e) {
        echo '<div class="error">❌ Error calling Category Manager: ' . $e->getMessage() . '</div>';
    }
    echo '</div>';
}

// Test 4: Create a test category
echo '<div class="box">';
echo '<h2>Test 4: Create Test Category</h2>';
if (class_exists('\Custom_Page_Builder\Category_Manager')) {
    $test_cat = \Custom_Page_Builder\Category_Manager::add_category('Test Category ' . time());
    if ($test_cat) {
        echo '<div class="success">✅ Successfully created test category: <strong>' . $test_cat['name'] . '</strong></div>';
        echo '<p>Category ID: ' . $test_cat['id'] . '</p>';
    } else {
        echo '<div class="error">❌ Failed to create test category</div>';
    }
} else {
    echo '<div class="error">❌ Cannot test - Category Manager not loaded</div>';
}
echo '</div>';

// Test 5: Check if files exist
echo '<div class="box">';
echo '<h2>Test 5: File Existence Check</h2>';
$files_to_check = [
    'includes/class-category-manager.php',
    'admin/class-category-ajax.php',
    'admin/js/category-manager.js',
    'admin/css/category-manager.css',
    'templates/admin/partials/category-selector.php'
];

echo '<ul>';
foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        echo '<li>✅ <strong>' . $file . '</strong> exists</li>';
    } else {
        echo '<li>❌ <strong>' . $file . '</strong> NOT FOUND</li>';
    }
}
echo '</ul>';
echo '</div>';

// Summary
echo '<div class="box info">';
echo '<h2>📋 Summary</h2>';
echo '<p><strong>Next Steps:</strong></p>';
echo '<ol>';
echo '<li>If no WordPress categories exist, <a href="' . admin_url('edit-tags.php?taxonomy=category') . '">add some categories</a></li>';
echo '<li>Deactivate and reactivate the plugin to ensure all files are loaded</li>';
echo '<li>Clear your browser cache (Ctrl+Shift+Delete)</li>';
echo '<li>Try editing a custom page and adding a product</li>';
echo '</ol>';
echo '</div>';

echo '</body></html>';
