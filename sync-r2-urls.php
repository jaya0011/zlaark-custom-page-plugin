<?php
/**
 * Sync R2 CDN URLs from WooCommerce Products to WordPress Attachments
 * 
 * This script links R2 signed URLs from your WooCommerce products
 * to their corresponding WordPress attachment IDs.
 * 
 * Run once: https://yoursite.com/wp-content/plugins/custom-page-builder/sync-r2-urls.php
 * 
 * @package Custom_Page_Builder
 * @version 1.0.0
 */

// Load WordPress
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Administrator privileges required.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>R2 URL Sync - Custom Page Builder</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f0f0f1; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #2271b1; border-bottom: 3px solid #2271b1; padding-bottom: 10px; }
        h2 { color: #135e96; margin-top: 30px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 4px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 4px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 12px; border-radius: 4px; margin: 10px 0; }
        .product-item { background: #f8f9fa; border-left: 4px solid #2271b1; padding: 15px; margin: 10px 0; }
        .product-item h4 { margin: 0 0 10px 0; color: #135e96; }
        .url-preview { color: #666; font-size: 12px; word-break: break-all; margin: 5px 0; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0; }
        .stat-box { background: #2271b1; color: white; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-box h3 { margin: 0; font-size: 36px; }
        .stat-box p { margin: 10px 0 0 0; font-size: 14px; }
        .btn { display: inline-block; background: #2271b1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
        .btn:hover { background: #135e96; }
        pre { background: #f6f7f7; border: 1px solid #dcdcde; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 R2 CDN URL Sync Tool</h1>
        <p>This tool syncs R2 CDN signed URLs from WooCommerce products to WordPress attachments.</p>

<?php

// Check if WooCommerce is active
if (!class_exists('WooCommerce')) {
    echo '<div class="error"><strong>Error:</strong> WooCommerce is not active. This tool requires WooCommerce.</div>';
    echo '</div></body></html>';
    exit;
}

// Initialize counters
$total_products = 0;
$synced_count = 0;
$skipped_count = 0;
$error_count = 0;
$sync_details = array();

// Get all published products
$args = array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'ID',
    'order' => 'ASC'
);

echo '<div class="info"><strong>Step 1:</strong> Fetching WooCommerce products...</div>';

$products = get_posts($args);
$total_products = count($products);

echo '<div class="success"><strong>Found ' . $total_products . ' products</strong></div>';

if ($total_products === 0) {
    echo '<div class="error">No products found. Please ensure you have published products with images.</div>';
    echo '</div></body></html>';
    exit;
}

echo '<div class="info"><strong>Step 2:</strong> Processing products and syncing R2 URLs...</div>';

// Process each product
foreach ($products as $product) {
    $product_id = $product->ID;
    $product_title = $product->post_title;
    
    // Get R2 URL from product meta (Zlaark API structure)
    $r2_url = get_post_meta($product_id, '_product_image_url', true);
    
    // Get featured image attachment ID
    $attachment_id = get_post_thumbnail_id($product_id);
    
    // Check if both exist
    if (empty($r2_url) && empty($attachment_id)) {
        $skipped_count++;
        $sync_details[] = array(
            'status' => 'skipped',
            'product_id' => $product_id,
            'product_title' => $product_title,
            'reason' => 'No R2 URL and no featured image'
        );
        continue;
    }
    
    if (empty($r2_url)) {
        $skipped_count++;
        $sync_details[] = array(
            'status' => 'skipped',
            'product_id' => $product_id,
            'product_title' => $product_title,
            'reason' => 'No R2 URL in product meta (_product_image_url)'
        );
        continue;
    }
    
    if (empty($attachment_id)) {
        $skipped_count++;
        $sync_details[] = array(
            'status' => 'skipped',
            'product_id' => $product_id,
            'product_title' => $product_title,
            'reason' => 'No featured image attachment'
        );
        continue;
    }
    
    // Verify attachment exists
    if (!get_post($attachment_id)) {
        $error_count++;
        $sync_details[] = array(
            'status' => 'error',
            'product_id' => $product_id,
            'product_title' => $product_title,
            'reason' => 'Attachment ID ' . $attachment_id . ' does not exist'
        );
        continue;
    }
    
    // Sync R2 URL to attachment
    $updated_url = update_post_meta($attachment_id, '_r2_cdn_url', $r2_url);
    $updated_link = update_post_meta($attachment_id, '_wp_attachment_product_id', $product_id);
    
    if ($updated_url !== false || get_post_meta($attachment_id, '_r2_cdn_url', true) === $r2_url) {
        $synced_count++;
        $sync_details[] = array(
            'status' => 'success',
            'product_id' => $product_id,
            'product_title' => $product_title,
            'attachment_id' => $attachment_id,
            'r2_url' => $r2_url
        );
    } else {
        $error_count++;
        $sync_details[] = array(
            'status' => 'error',
            'product_id' => $product_id,
            'product_title' => $product_title,
            'reason' => 'Failed to update attachment meta'
        );
    }
}

// Display statistics
echo '<h2>📊 Sync Statistics</h2>';
echo '<div class="stats">';
echo '<div class="stat-box"><h3>' . $total_products . '</h3><p>Total Products</p></div>';
echo '<div class="stat-box" style="background: #46b450;"><h3>' . $synced_count . '</h3><p>Successfully Synced</p></div>';
echo '<div class="stat-box" style="background: #dc3232;"><h3>' . ($skipped_count + $error_count) . '</h3><p>Skipped/Errors</p></div>';
echo '</div>';

if ($synced_count > 0) {
    echo '<div class="success"><strong>✅ Success!</strong> Synced ' . $synced_count . ' R2 URLs to WordPress attachments.</div>';
}

// Display detailed results
echo '<h2>📋 Detailed Results</h2>';

// Show successful syncs
$success_items = array_filter($sync_details, function($item) { return $item['status'] === 'success'; });
if (count($success_items) > 0) {
    echo '<h3 style="color: #46b450;">✅ Successfully Synced (' . count($success_items) . ')</h3>';
    foreach ($success_items as $item) {
        echo '<div class="product-item">';
        echo '<h4>Product #' . $item['product_id'] . ': ' . esc_html($item['product_title']) . '</h4>';
        echo '<p><strong>Attachment ID:</strong> ' . $item['attachment_id'] . '</p>';
        echo '<div class="url-preview"><strong>R2 URL:</strong> ' . esc_html(substr($item['r2_url'], 0, 150)) . '...</div>';
        echo '</div>';
    }
}

// Show skipped items
$skipped_items = array_filter($sync_details, function($item) { return $item['status'] === 'skipped'; });
if (count($skipped_items) > 0) {
    echo '<h3 style="color: #f0ad4e;">⚠️ Skipped (' . count($skipped_items) . ')</h3>';
    foreach ($skipped_items as $item) {
        echo '<div class="product-item" style="border-left-color: #f0ad4e;">';
        echo '<h4>Product #' . $item['product_id'] . ': ' . esc_html($item['product_title']) . '</h4>';
        echo '<p><em>Reason: ' . esc_html($item['reason']) . '</em></p>';
        echo '</div>';
    }
}

// Show errors
$error_items = array_filter($sync_details, function($item) { return $item['status'] === 'error'; });
if (count($error_items) > 0) {
    echo '<h3 style="color: #dc3232;">❌ Errors (' . count($error_items) . ')</h3>';
    foreach ($error_items as $item) {
        echo '<div class="product-item" style="border-left-color: #dc3232;">';
        echo '<h4>Product #' . $item['product_id'] . ': ' . esc_html($item['product_title']) . '</h4>';
        echo '<p style="color: #dc3232;"><strong>Error: ' . esc_html($item['reason']) . '</strong></p>';
        echo '</div>';
    }
}

// Next steps
echo '<h2>🚀 Next Steps</h2>';
echo '<div class="info">';
echo '<p><strong>Test your API response:</strong></p>';
echo '<ol>';
echo '<li>Visit: <a href="/wp-json/custom-page-builder/v1/pages/home" target="_blank">/wp-json/custom-page-builder/v1/pages/home</a></li>';
echo '<li>Look for <code>image_url</code> fields with R2 signed URLs</li>';
echo '<li>Verify images load in your frontend</li>';
echo '</ol>';
echo '</div>';

// Test API button
echo '<div style="margin: 20px 0;">';
echo '<a href="/wp-json/custom-page-builder/v1/pages/home" target="_blank" class="btn">📡 Test API Response</a>';
echo '<a href="/wp-admin/edit.php?post_type=product" class="btn">📦 View Products</a>';
echo '<a href="' . $_SERVER['PHP_SELF'] . '" class="btn" style="background: #666;">🔄 Run Again</a>';
echo '</div>';

// Sample code for verification
echo '<h2>🧪 Verification Code</h2>';
echo '<p>Run this PHP snippet to verify R2 URLs are attached:</p>';
echo '<pre>&lt;?php
// Test attachment with R2 URL
$attachment_id = ' . ($synced_count > 0 ? $success_items[0]['attachment_id'] : '123') . ';
$r2_url = get_post_meta($attachment_id, \'_r2_cdn_url\', true);

if ($r2_url) {
    echo "✅ Attachment has R2 URL: " . $r2_url;
} else {
    echo "❌ No R2 URL found";
}
?&gt;</pre>';

?>

    </div>
</body>
</html>
