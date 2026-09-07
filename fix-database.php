<?php
/**
 * Database Fix Script - SAFE VERSION (preserves data)
 * Upload this to your WordPress root and run it once: http://yoursite.com/fix-database.php
 * DELETE THIS FILE AFTER RUNNING!
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

if (!current_user_can('manage_options')) {
    die('Access denied. You must be an administrator.');
}

global $wpdb;
$table_name = $wpdb->prefix . 'custom_pages';

echo '<h1>Custom Page Builder - Database Fix (Safe - Preserves Data)</h1>';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

if ($table_exists) {
    echo '<p style="color:blue;">ℹ️ Table exists - repairing structure while preserving data...</p>';
    
    // Get existing columns
    $columns = $wpdb->get_col("SHOW COLUMNS FROM $table_name");
    
    // Add missing columns without dropping table
    if (!in_array('sections', $columns)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN sections longtext NULL AFTER status");
        echo '<p style="color:green;">✅ Added missing column: sections</p>';
    }
    if (!in_array('language', $columns)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN language varchar(10) DEFAULT 'en' AFTER sections");
        echo '<p style="color:green;">✅ Added missing column: language</p>';
    }
    if (!in_array('translation_group', $columns)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN translation_group bigint(20) unsigned DEFAULT NULL AFTER language");
        echo '<p style="color:green;">✅ Added missing column: translation_group</p>';
    }
    
    // Count existing pages
    $page_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo '<p style="color:green;">✅ Your ' . $page_count . ' pages are preserved!</p>';
    
} else {
    echo '<p style="color:orange;">⚠️ Table does not exist - creating new table...</p>';
    
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'draft',
        sections longtext,
        language varchar(10) DEFAULT 'en',
        translation_group bigint(20) unsigned DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug_lang (slug, language),
        KEY status (status),
        KEY language (language),
        KEY translation_group (translation_group)
    ) $charset_collate;";

    $result = $wpdb->query($sql);

    if ($result === false) {
        echo '<p style="color:red;">❌ Error creating table: ' . $wpdb->last_error . '</p>';
    } else {
        echo '<p style="color:green;">✅ Table created successfully!</p>';
    }
}

// Verify table structure
$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
echo '<h2>Table Structure:</h2>';
echo '<table border="1" cellpadding="5">';
echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>';
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

echo '<p><strong>✅ Database is now fixed!</strong></p>';
echo '<p><a href="' . admin_url('admin.php?page=custom-page-builder') . '">Go to Custom Page Builder</a></p>';
echo '<p style="color:red;"><strong>IMPORTANT: Delete this file (fix-database.php) now!</strong></p>';
