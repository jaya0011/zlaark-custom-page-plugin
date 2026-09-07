<?php
/**
 * APPLY IMAGE FIX - One-Click Solution
 * 
 * This script applies all necessary fixes for image display issues
 * Upload to WordPress ROOT and run once
 * 
 * URL: http://yoursite.com/APPLY_IMAGE_FIX.php
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Access denied. You must be an administrator.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Apply Image Fix</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px;
        }
        .step {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .step h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .success {
            color: #28a745;
            font-weight: 600;
        }
        .error {
            color: #dc3545;
            font-weight: 600;
        }
        .warning {
            color: #ffc107;
            font-weight: 600;
        }
        .info {
            color: #17a2b8;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .button:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .code {
            background: #2d3748;
            color: #68d391;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-error {
            background: #f8d7da;
            color: #721c24;
        }
        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .summary h2 {
            color: white;
            margin-bottom: 20px;
        }
        ul {
            margin: 15px 0;
            padding-left: 20px;
        }
        li {
            margin: 8px 0;
            line-height: 1.6;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Apply Image Fix</h1>
            <p>One-click solution for image display issues</p>
        </div>
        
        <div class="content">
            <?php
            $fixes_applied = 0;
            $issues_found = 0;
            $images_processed = 0;
            
            // STEP 1: Verify Plugin Files
            echo '<div class="step">';
            echo '<h2>Step 1: Verify Plugin Files</h2>';
            
            $handler_file = WP_PLUGIN_DIR . '/custom-page-builder/integrations/class-secure-image-handler.php';
            if (file_exists($handler_file)) {
                echo '<p class="success">✅ Secure Image Handler file exists</p>';
                
                // Check if fix is applied
                $file_content = file_get_contents($handler_file);
                if (strpos($file_content, 'image_url') !== false && strpos($file_content, 'image_data') !== false) {
                    echo '<p class="success">✅ Image fix code is present <span class="badge badge-success">FIXED</span></p>';
                    $fixes_applied++;
                } else {
                    echo '<p class="error">❌ Image fix code is missing <span class="badge badge-error">NEEDS FIX</span></p>';
                    echo '<p class="warning">⚠️ Please copy the updated class-secure-image-handler.php file</p>';
                    $issues_found++;
                }
            } else {
                echo '<p class="error">❌ Secure Image Handler file not found</p>';
                $issues_found++;
            }
            
            echo '</div>';
            
            // STEP 2: Flush Rewrite Rules
            echo '<div class="step">';
            echo '<h2>Step 2: Flush Rewrite Rules</h2>';
            flush_rewrite_rules();
            echo '<p class="success">✅ Rewrite rules flushed successfully</p>';
            $fixes_applied++;
            echo '</div>';
            
            // STEP 3: Check Plugins
            echo '<div class="step">';
            echo '<h2>Step 3: Check Required Plugins</h2>';
            
            if (class_exists('Custom_Page_Builder\\Plugin')) {
                echo '<p class="success">✅ Custom Page Builder is active</p>';
            } else {
                echo '<p class="error">❌ Custom Page Builder is NOT active</p>';
                $issues_found++;
            }
            
            if (class_exists('WooCommerce\\SecureImages\\Core\\Plugin')) {
                echo '<p class="success">✅ Zlaark Secure Images is active</p>';
            } else {
                echo '<p class="warning">⚠️ Zlaark Secure Images is NOT active</p>';
                echo '<p class="info">Images will use standard WordPress URLs (not protected)</p>';
            }
            
            echo '</div>';
            
            // STEP 4: Process Images
            echo '<div class="step">';
            echo '<h2>Step 4: Process Existing Images</h2>';
            
            global $wpdb;
            $sections_table = $wpdb->prefix . 'custom_page_sections';
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$sections_table'") === $sections_table) {
                $sections = $wpdb->get_results("SELECT * FROM $sections_table");
                
                foreach ($sections as $section) {
                    $config = json_decode($section->config, true);
                    
                    if (is_array($config)) {
                        foreach ($config as $key => $value) {
                            if (strpos($key, 'image') !== false && is_numeric($value)) {
                                $attachment_id = intval($value);
                                
                                if ($attachment_id > 0 && get_post($attachment_id)) {
                                    // Mark as protected if secure images plugin is active
                                    if (class_exists('WooCommerce\\SecureImages\\Core\\Plugin')) {
                                        update_post_meta($attachment_id, '_wc_secure_images_protected', '1');
                                    }
                                    update_post_meta($attachment_id, '_custom_page_builder_image', '1');
                                    $images_processed++;
                                }
                            }
                        }
                    }
                }
                
                if ($images_processed > 0) {
                    echo '<p class="success">✅ Processed ' . $images_processed . ' images</p>';
                    $fixes_applied++;
                } else {
                    echo '<p class="info">ℹ️ No images found to process</p>';
                }
            } else {
                echo '<p class="error">❌ Custom page sections table not found</p>';
                $issues_found++;
            }
            
            echo '</div>';
            
            // STEP 5: Test API
            echo '<div class="step">';
            echo '<h2>Step 5: Test API Response</h2>';
            
            $pages_table = $wpdb->prefix . 'custom_pages';
            $test_page = $wpdb->get_row("SELECT * FROM $pages_table WHERE status = 'published' LIMIT 1");
            
            if ($test_page) {
                echo '<p class="success">✅ Found test page: ' . esc_html($test_page->title) . '</p>';
                
                // Test API
                $request = new WP_REST_Request('GET', '/custom-pages/v1/pages/' . $test_page->id);
                $response = rest_do_request($request);
                
                if (!$response->is_error()) {
                    $data = $response->get_data();
                    
                    // Check for image data
                    $has_image_data = false;
                    if (isset($data['sections']) && is_array($data['sections'])) {
                        foreach ($data['sections'] as $section) {
                            if (isset($section['config'])) {
                                foreach ($section['config'] as $key => $value) {
                                    if (strpos($key, 'image_url') !== false || strpos($key, 'image_data') !== false) {
                                        $has_image_data = true;
                                        break 2;
                                    }
                                }
                            }
                        }
                    }
                    
                    if ($has_image_data) {
                        echo '<p class="success">✅ API returns image URLs correctly <span class="badge badge-success">WORKING</span></p>';
                    } else {
                        echo '<p class="warning">⚠️ No image data in API response (page may not have images)</p>';
                    }
                    
                    $api_url = rest_url('custom-pages/v1/pages/' . $test_page->id);
                    echo '<p><a href="' . esc_url($api_url) . '" target="_blank" class="button">View API Response</a></p>';
                } else {
                    echo '<p class="error">❌ API error: ' . esc_html($response->get_error_message()) . '</p>';
                    $issues_found++;
                }
                
                // Show page URL
                $page_url = home_url('/custom-page/' . $test_page->slug);
                echo '<p style="margin-top: 15px;"><a href="' . esc_url($page_url) . '" target="_blank" class="button">View Published Page</a></p>';
            } else {
                echo '<p class="info">ℹ️ No published pages found to test</p>';
            }
            
            echo '</div>';
            
            // SUMMARY
            echo '<div class="summary">';
            echo '<h2>📊 Summary</h2>';
            
            if ($issues_found === 0) {
                echo '<p style="font-size: 24px; margin-bottom: 20px;">🎉 All fixes applied successfully!</p>';
            } else {
                echo '<p style="font-size: 24px; margin-bottom: 20px;">⚠️ Some issues need attention</p>';
            }
            
            echo '<div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px; margin: 20px 0;">';
            echo '<p><strong>Fixes Applied:</strong> ' . $fixes_applied . '</p>';
            echo '<p><strong>Issues Found:</strong> ' . $issues_found . '</p>';
            echo '<p><strong>Images Processed:</strong> ' . $images_processed . '</p>';
            echo '</div>';
            
            echo '<h3 style="margin-top: 30px;">What Was Done:</h3>';
            echo '<ul>';
            echo '<li>✅ Verified plugin files and fix code</li>';
            echo '<li>✅ Flushed rewrite rules for secure URLs</li>';
            echo '<li>✅ Checked plugin activation status</li>';
            echo '<li>✅ Processed and marked images</li>';
            echo '<li>✅ Tested API response</li>';
            echo '</ul>';
            
            echo '<h3 style="margin-top: 30px;">Next Steps:</h3>';
            echo '<ol>';
            
            if ($issues_found > 0) {
                echo '<li><strong>Fix the issues listed above</strong></li>';
            }
            
            echo '<li>Update your React frontend to use the new image fields:</li>';
            echo '</ol>';
            
            echo '<div class="code">';
            echo '// Access image URL directly<br>';
            echo 'const imageUrl = section.config.image_url;<br><br>';
            echo '// Or use image data<br>';
            echo 'const imageData = section.config.image_data;<br>';
            echo 'console.log(imageData.url); // Full URL<br>';
            echo 'console.log(imageData.alt); // Alt text';
            echo '</div>';
            
            echo '<ol start="3">';
            echo '<li>Test your published page</li>';
            echo '<li>Clear browser cache if needed</li>';
            echo '<li><strong style="color: #ffc107;">DELETE THIS FILE after use!</strong></li>';
            echo '</ol>';
            
            echo '</div>';
            
            // RESOURCES
            echo '<div class="step">';
            echo '<h2>📚 Additional Resources</h2>';
            echo '<ul>';
            echo '<li><strong>FRONTEND_IMAGE_USAGE.md</strong> - Complete frontend integration guide</li>';
            echo '<li><strong>test-image-fix.php</strong> - Detailed test of the fix</li>';
            echo '<li><strong>IMAGE_DISPLAY_TROUBLESHOOTING.md</strong> - Troubleshooting guide</li>';
            echo '<li><strong>API_DOCUMENTATION.md</strong> - API reference</li>';
            echo '</ul>';
            echo '</div>';
            ?>
        </div>
        
        <div class="footer">
            <p><strong>Custom Page Builder v1.0.0</strong></p>
            <p style="margin-top: 10px; color: #dc3545; font-weight: 600;">
                ⚠️ DELETE THIS FILE AFTER USE FOR SECURITY!
            </p>
        </div>
    </div>
</body>
</html>
