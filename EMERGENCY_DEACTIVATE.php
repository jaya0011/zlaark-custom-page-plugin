<?php
/**
 * EMERGENCY PLUGIN DEACTIVATION
 * 
 * Upload this file to your WordPress root directory and access it via browser
 * Example: http://yoursite.com/EMERGENCY_DEACTIVATE.php
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Deactivate the plugin
$plugin_path = 'custom-page-builder/custom-page-builder.php';
deactivate_plugins($plugin_path);

echo '<h1>Plugin Deactivated!</h1>';
echo '<p>The Custom Page Builder plugin has been deactivated.</p>';
echo '<p><a href="' . admin_url() . '">Go to WordPress Admin</a></p>';
echo '<p><strong>Delete this file after use!</strong></p>';
