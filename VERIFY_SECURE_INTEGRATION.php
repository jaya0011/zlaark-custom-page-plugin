<?php
/**
 * Verify Secure Image Integration
 * 
 * This script checks if the Custom Page Builder is properly integrated
 * with the Zlaark Secure Images plugin.
 * 
 * HOW TO USE:
 * 1. Upload this file to your WordPress root directory
 * 2. Access it in your browser: https://yoursite.com/VERIFY_SECURE_INTEGRATION.php
 * 3. Review the results
 * 4. Delete this file after verification
 */

// Load WordPress
require_once('wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied. You must be an administrator to run this script.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Secure Image Integration Verification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d2327;
            border-bottom: 3px solid #2271b1;
            padding-bottom: 10px;
        }
        h2 {
            color: #2271b1;
            margin-top: 30px;
        }
        .status {
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .status.success {
            background: #d7f0d7;
            border-color: #00a32a;
            color: #00a32a;
        }
        .status.warning {
            background: #fcf3cf;
            border-color: #dba617;
            color: #8a6d3b;
        }
        .status.error {
            background: #f8d7da;
            border-color: #d63638;
            color: #d63638;
        }
        .status.info {
            background: #e5f5fa;
            border-color: #2271b1;
            color: #2271b1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f6f7f7;
            font-weight: 600;
        }
        .check-icon {
            font-size: 20px;
            margin-right: 10px;
        }
        code {
            background: #f6f7f7;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: Consolas, Monaco, monospace;
        }
        pre {
            background: #f6f7f7;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .section {
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔒 Secure Image Integration Verification</h1>
        <p>This script verifies that the Custom Page Builder is properly integrated with the Zlaark Secure Images plugin.</p>

        <?php
        // Check 1: Plugins Active
        echo '<div class="section">';
        echo '<h2>1. Plugin Status</h2>';
        
        $cpb_active = class_exists('Custom_Page_Builder\\Secure_Image_Handler');
        $secure_img_active = class_exists('WooCommerce\\SecureImages\\Core\\Plugin');
        
        if ($cpb_active && $secure_img_active) {
            echo '<div class="status success">';
            echo '<span class="check-icon">✅</span>';
            echo '<strong>Both plugins are active!</strong>';
            echo '</div>';
        } else {
            echo '<div class="status error">';
            echo '<span class="check-icon">❌</span>';
            echo '<strong>One or more plugins are not active:</strong>';
            echo '<ul>';
            echo '<li>Custom Page Builder: ' . ($cpb_active ? '✅ Active' : '❌ Not Active') . '</li>';
            echo '<li>Zlaark Secure Images: ' . ($secure_img_active ? '✅ Active' : '❌ Not Active') . '</li>';
            echo '</ul>';
            echo '</div>';
        }
        echo '</div>';

        // Check 2: Secure Image Handler
        echo '<div class="section">';
        echo '<h2>2. Secure Image Handler</h2>';
        
        if ($cpb_active) {
            $handler = new \Custom_Page_Builder\Secure_Image_Handler();
            $is_plugin_active = $handler->is_secure_image_plugin_active();
            
            if ($is_plugin_active) {
                echo '<div class="status success">';
                echo '<span class="check-icon">✅</span>';
                echo '<strong>Secure Image Handler is working!</strong>';
                echo '<p>The handler can detect and communicate with the Zlaark Secure Images plugin.</p>';
                echo '</div>';
            } else {
                echo '<div class="status warning">';
                echo '<span class="check-icon">⚠️</span>';
                echo '<strong>Secure Image Handler is active but cannot detect the Zlaark Secure Images plugin.</strong>';
                echo '<p>Images will use standard WordPress URLs without protection.</p>';
                echo '</div>';
            }
        } else {
            echo '<div class="status error">';
            echo '<span class="check-icon">❌</span>';
            echo '<strong>Secure Image Handler class not found.</strong>';
            echo '<p>The Custom Page Builder plugin may not be properly installed.</p>';
            echo '</div>';
        }
        echo '</div>';

        // Check 3: Protected Images
        echo '<div class="section">';
        echo '<h2>3. Protected Images</h2>';
        
        global $wpdb;
        
        // Find images marked as protected by Custom Page Builder
        $protected_images = $wpdb->get_results("
            SELECT p.ID, p.post_title, pm1.meta_value as protected, pm2.meta_value as cpb_image
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_wc_secure_images_protected'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_custom_page_builder_image'
            WHERE p.post_type = 'attachment'
            AND pm2.meta_value = '1'
            LIMIT 10
        ");
        
        if (!empty($protected_images)) {
            echo '<div class="status success">';
            echo '<span class="check-icon">✅</span>';
            echo '<strong>Found ' . count($protected_images) . ' protected image(s) from Custom Page Builder!</strong>';
            echo '</div>';
            
            echo '<table>';
            echo '<thead><tr><th>ID</th><th>Title</th><th>Protected</th><th>CPB Image</th><th>Secure URL</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($protected_images as $image) {
                $is_protected = $image->protected === '1';
                $is_cpb = $image->cpb_image === '1';
                
                // Generate secure URL if handler is available
                $secure_url = '';
                if ($cpb_active && $handler) {
                    $secure_url = $handler->get_secure_image_url($image->ID, 'thumbnail');
                }
                
                echo '<tr>';
                echo '<td>' . $image->ID . '</td>';
                echo '<td>' . esc_html($image->post_title) . '</td>';
                echo '<td>' . ($is_protected ? '✅ Yes' : '❌ No') . '</td>';
                echo '<td>' . ($is_cpb ? '✅ Yes' : '❌ No') . '</td>';
                echo '<td><code>' . esc_html(substr($secure_url, 0, 50)) . '...</code></td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<div class="status info">';
            echo '<span class="check-icon">ℹ️</span>';
            echo '<strong>No protected images found yet.</strong>';
            echo '<p>Upload an image through the Custom Page Builder to test the integration.</p>';
            echo '</div>';
        }
        echo '</div>';

        // Check 4: API Integration
        echo '<div class="section">';
        echo '<h2>4. API Integration</h2>';
        
        $rest_controller_exists = class_exists('Custom_Page_Builder\\Rest_Controller');
        
        if ($rest_controller_exists) {
            echo '<div class="status success">';
            echo '<span class="check-icon">✅</span>';
            echo '<strong>REST API Controller is available!</strong>';
            echo '<p>The API will process images and add secure URLs to responses.</p>';
            echo '</div>';
            
            // Check if there are any published pages
            $table_name = $wpdb->prefix . 'custom_pages';
            $page_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'published'");
            
            if ($page_count > 0) {
                echo '<div class="status info">';
                echo '<span class="check-icon">ℹ️</span>';
                echo '<strong>Found ' . $page_count . ' published page(s).</strong>';
                echo '<p>You can test the API by accessing:</p>';
                echo '<code>' . rest_url('custom-pages/v1/pages') . '</code>';
                echo '</div>';
            } else {
                echo '<div class="status info">';
                echo '<span class="check-icon">ℹ️</span>';
                echo '<strong>No published pages found.</strong>';
                echo '<p>Create and publish a page to test the API integration.</p>';
                echo '</div>';
            }
        } else {
            echo '<div class="status warning">';
            echo '<span class="check-icon">⚠️</span>';
            echo '<strong>REST API Controller not found.</strong>';
            echo '<p>The API may not be properly configured.</p>';
            echo '</div>';
        }
        echo '</div>';

        // Check 5: Test Image Processing
        echo '<div class="section">';
        echo '<h2>5. Test Image Processing</h2>';
        
        if ($cpb_active && $handler && !empty($protected_images)) {
            $test_image = $protected_images[0];
            
            echo '<div class="status info">';
            echo '<span class="check-icon">🧪</span>';
            echo '<strong>Testing image processing with ID: ' . $test_image->ID . '</strong>';
            echo '</div>';
            
            // Test process_uploaded_image
            $result = $handler->process_uploaded_image($test_image->ID);
            
            echo '<pre>';
            echo 'Result: ' . print_r($result, true);
            echo '</pre>';
            
            if ($result['success']) {
                echo '<div class="status success">';
                echo '<span class="check-icon">✅</span>';
                echo '<strong>Image processing successful!</strong>';
                echo '<ul>';
                echo '<li>Protected: ' . ($result['is_protected'] ? 'Yes' : 'No') . '</li>';
                echo '<li>Secure URLs: ' . count($result['secure_urls']) . ' sizes</li>';
                echo '<li>Fallback URLs: ' . count($result['fallback_urls']) . ' sizes</li>';
                echo '</ul>';
                echo '</div>';
            } else {
                echo '<div class="status error">';
                echo '<span class="check-icon">❌</span>';
                echo '<strong>Image processing failed!</strong>';
                echo '<p>Error: ' . ($result['error'] ?? 'Unknown error') . '</p>';
                echo '</div>';
            }
        } else {
            echo '<div class="status info">';
            echo '<span class="check-icon">ℹ️</span>';
            echo '<strong>Cannot test image processing.</strong>';
            echo '<p>Upload an image through the Custom Page Builder first.</p>';
            echo '</div>';
        }
        echo '</div>';

        // Summary
        echo '<div class="section">';
        echo '<h2>📊 Summary</h2>';
        
        $all_good = $cpb_active && $secure_img_active && $is_plugin_active;
        
        if ($all_good) {
            echo '<div class="status success">';
            echo '<span class="check-icon">🎉</span>';
            echo '<strong>Everything looks good!</strong>';
            echo '<p>The Custom Page Builder is properly integrated with the Zlaark Secure Images plugin.</p>';
            echo '<p><strong>Next steps:</strong></p>';
            echo '<ol>';
            echo '<li>Upload images through the Custom Page Builder</li>';
            echo '<li>Check that images are marked as protected in Media Library</li>';
            echo '<li>Test the API response to verify secure URLs are included</li>';
            echo '<li>View pages on the frontend to confirm images display correctly</li>';
            echo '</ol>';
            echo '</div>';
        } else {
            echo '<div class="status warning">';
            echo '<span class="check-icon">⚠️</span>';
            echo '<strong>Some issues detected.</strong>';
            echo '<p>Review the checks above and fix any issues.</p>';
            echo '<p><strong>Common solutions:</strong></p>';
            echo '<ul>';
            if (!$cpb_active) {
                echo '<li>Activate the Custom Page Builder plugin</li>';
            }
            if (!$secure_img_active) {
                echo '<li>Activate the Zlaark Secure Images plugin</li>';
            }
            if ($cpb_active && $secure_img_active && !$is_plugin_active) {
                echo '<li>Check that both plugins are properly installed</li>';
                echo '<li>Verify the Zlaark Secure Images plugin class exists</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        echo '</div>';

        // Instructions
        echo '<div class="section">';
        echo '<h2>📝 How to Test</h2>';
        echo '<div class="status info">';
        echo '<ol>';
        echo '<li><strong>Upload an Image:</strong> Go to Page Builder → Add New → Click "+ Content Block" → Click "📁 Upload Image"</li>';
        echo '<li><strong>Check Protection:</strong> Go to Media → Library → Find the image → Edit → Check Custom Fields for <code>_wc_secure_images_protected</code></li>';
        echo '<li><strong>Test API:</strong> Access <code>' . rest_url('custom-pages/v1/pages') . '</code> and check for secure URLs in the response</li>';
        echo '<li><strong>View Frontend:</strong> View your page and inspect image sources - they should contain tokens</li>';
        echo '</ol>';
        echo '</div>';
        echo '</div>';

        // Cleanup reminder
        echo '<div class="section">';
        echo '<div class="status warning">';
        echo '<span class="check-icon">⚠️</span>';
        echo '<strong>Security Reminder:</strong> Delete this file after verification!';
        echo '</div>';
        echo '</div>';
        ?>

    </div>
</body>
</html>
