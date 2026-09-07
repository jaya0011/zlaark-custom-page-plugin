<?php
/**
 * Test script to verify data storage is working correctly
 * Run this by accessing: /wp-content/plugins/custom-page-builder/test-data-storage.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress if not already loaded
    $wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once $wp_load_path;
    } else {
        die('WordPress not found. Please run this from within WordPress.');
    }
}

// Check if user has admin privileges
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

echo '<h1>Custom Page Builder - Data Storage Test</h1>';

global $wpdb;
$table_name = $wpdb->prefix . 'custom_pages';

// Check if table exists
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    echo '<div style="color: red;">❌ Database table not found: ' . $table_name . '</div>';
    exit;
}

echo '<div style="color: green;">✅ Database table exists: ' . $table_name . '</div>';

// Check table structure
$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
echo '<h2>Table Structure:</h2>';
echo '<table border="1" style="border-collapse: collapse;">';
echo '<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>';
foreach ($columns as $column) {
    echo '<tr>';
    echo '<td>' . $column->Field . '</td>';
    echo '<td>' . $column->Type . '</td>';
    echo '<td>' . $column->Null . '</td>';
    echo '<td>' . $column->Key . '</td>';
    echo '<td>' . $column->Default . '</td>';
    echo '</tr>';
}
echo '</table>';

// Check if sections column exists and is properly configured
$sections_column = $wpdb->get_row("SHOW COLUMNS FROM $table_name LIKE 'sections'");
if ($sections_column) {
    echo '<div style="color: green;">✅ Sections column exists with type: ' . $sections_column->Type . '</div>';
} else {
    echo '<div style="color: red;">❌ Sections column missing!</div>';
}

// Test data insertion
echo '<h2>Testing Data Storage:</h2>';

// Create test data with nested structure
$test_sections = array(
    array(
        'type' => 'hero-slider',
        'title' => 'Test Hero Slider',
        'order' => 0,
        'slides' => array(
            array(
                'title' => 'Slide 1 Title',
                'content' => 'Slide 1 content here',
                'image' => 'https://example.com/image1.jpg',
                'button_text' => 'Learn More',
                'button_link' => 'https://example.com'
            ),
            array(
                'title' => 'Slide 2 Title',
                'content' => 'Slide 2 content here',
                'image' => 'https://example.com/image2.jpg',
                'button_text' => 'Get Started',
                'button_link' => 'https://example.com/start'
            )
        )
    ),
    array(
        'type' => 'custom',
        'title' => 'Test Custom Section',
        'order' => 1,
        'elements' => array(
            array(
                'type' => 'heading',
                'content' => 'This is a test heading'
            ),
            array(
                'type' => 'paragraph',
                'content' => 'This is a test paragraph with some content.'
            )
        )
    ),
    array(
        'type' => 'testimonials',
        'title' => 'Customer Reviews',
        'content' => 'What our customers say about us',
        'author' => 'John Doe',
        'author_title' => 'CEO, Example Corp',
        'order' => 2
    )
);

$test_data = array(
    'title' => 'Test Page - ' . date('Y-m-d H:i:s'),
    'slug' => 'test-page-' . time(),
    'status' => 'draft',
    'sections' => json_encode($test_sections),
    'created_at' => current_time('mysql'),
    'updated_at' => current_time('mysql')
);

$result = $wpdb->insert($table_name, $test_data);

if ($result) {
    $page_id = $wpdb->insert_id;
    echo '<div style="color: green;">✅ Test page created successfully with ID: ' . $page_id . '</div>';
    
    // Retrieve and verify the data
    $retrieved_page = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $page_id));
    
    if ($retrieved_page && $retrieved_page->sections) {
        echo '<div style="color: green;">✅ Page data retrieved successfully</div>';
        
        $decoded_sections = json_decode($retrieved_page->sections, true);
        if (is_array($decoded_sections)) {
            echo '<div style="color: green;">✅ Sections JSON decoded successfully</div>';
            echo '<h3>Decoded Sections Data:</h3>';
            echo '<pre style="background: #f0f0f0; padding: 10px; overflow: auto;">';
            echo htmlspecialchars(print_r($decoded_sections, true));
            echo '</pre>';
            
            // Verify nested structures
            $hero_slider = $decoded_sections[0];
            if ($hero_slider['type'] === 'hero-slider' && isset($hero_slider['slides']) && is_array($hero_slider['slides'])) {
                echo '<div style="color: green;">✅ Hero slider slides array preserved correctly</div>';
                echo '<div>Number of slides: ' . count($hero_slider['slides']) . '</div>';
            } else {
                echo '<div style="color: red;">❌ Hero slider slides array not preserved correctly</div>';
            }
            
            $custom_section = $decoded_sections[1];
            if ($custom_section['type'] === 'custom' && isset($custom_section['elements']) && is_array($custom_section['elements'])) {
                echo '<div style="color: green;">✅ Custom section elements array preserved correctly</div>';
                echo '<div>Number of elements: ' . count($custom_section['elements']) . '</div>';
            } else {
                echo '<div style="color: red;">❌ Custom section elements array not preserved correctly</div>';
            }
            
        } else {
            echo '<div style="color: red;">❌ Failed to decode sections JSON</div>';
            echo '<div>Raw sections data: ' . htmlspecialchars($retrieved_page->sections) . '</div>';
        }
    } else {
        echo '<div style="color: red;">❌ Failed to retrieve page data or sections is empty</div>';
    }
    
    // Clean up test data
    $wpdb->delete($table_name, array('id' => $page_id));
    echo '<div style="color: blue;">ℹ️ Test page cleaned up</div>';
    
} else {
    echo '<div style="color: red;">❌ Failed to create test page</div>';
    echo '<div>Error: ' . $wpdb->last_error . '</div>';
}

echo '<h2>Summary:</h2>';
echo '<p>If all tests show ✅, then the data storage is working correctly and can handle nested structures like hero-slider slides and custom section elements.</p>';
echo '<p><a href="' . admin_url('admin.php?page=custom-page-builder-new') . '">← Back to Page Builder</a></p>';
?>