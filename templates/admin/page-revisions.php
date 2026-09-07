<?php
/**
 * Page revisions template
 *
 * @package Custom_Page_Builder
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$page_id = isset($_GET['page_id']) ? intval($_GET['page_id']) : 0;
$page = null;

if ($page_id > 0) {
    $page_builder = new \Custom_Page_Builder\Page_Builder();
    $page = $page_builder->get_page($page_id);
}

if (!$page) {
    wp_die(__('Page not found', 'custom-page-builder'));
}
?>

<div class="wrap cpb-revisions-page">
    <h1 class="wp-heading-inline">
        <?php printf(__('Revisions for "%s"', 'custom-page-builder'), esc_html($page->get_title())); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=custom-page-builder-edit&page_id=' . $page_id); ?>" class="page-title-action">
        <?php _e('Back to Editor', 'custom-page-builder'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <div class="cpb-revisions-container">
        <div class="cpb-revisions-toolbar">
            <button type="button" class="button cpb-create-revision" data-page-id="<?php echo esc_attr($page_id); ?>">
                <?php _e('Create Manual Revision', 'custom-page-builder'); ?>
            </button>
            
            <label class="cpb-toggle-autosaves">
                <input type="checkbox" id="show-autosaves" value="1">
                <?php _e('Show Autosaves', 'custom-page-builder'); ?>
            </label>
            
            <div class="cpb-revision-actions" style="display: none;">
                <button type="button" class="button cpb-compare-selected" disabled>
                    <?php _e('Compare Selected', 'custom-page-builder'); ?>
                </button>
                <button type="button" class="button button-secondary cpb-delete-selected" disabled>
                    <?php _e('Delete Selected', 'custom-page-builder'); ?>
                </button>
            </div>
        </div>
        
        <div class="cpb-revisions-list">
            <div class="cpb-loading-revisions">
                <span class="spinner is-active"></span>
                <?php _e('Loading revisions...', 'custom-page-builder'); ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Revision Modal -->
<div id="cpb-create-revision-modal" class="cpb-modal" style="display: none;">
    <div class="cpb-modal-content">
        <div class="cpb-modal-header">
            <h2><?php _e('Create Revision', 'custom-page-builder'); ?></h2>
            <button class="cpb-modal-close">&times;</button>
        </div>
        <div class="cpb-modal-body">
            <form id="cpb-create-revision-form">
                <input type="hidden" id="revision-page-id" name="page_id" value="<?php echo esc_attr($page_id); ?>">
                
                <div class="cpb-form-field">
                    <label for="revision-note"><?php _e('Revision Note', 'custom-page-builder'); ?></label>
                    <textarea id="revision-note" name="revision_note" rows="3" 
                              placeholder="<?php _e('Describe what changed in this revision...', 'custom-page-builder'); ?>"></textarea>
                </div>
            </form>
        </div>
        <div class="cpb-modal-footer">
            <button class="button cpb-modal-cancel"><?php _e('Cancel', 'custom-page-builder'); ?></button>
            <button class="button button-primary cpb-modal-save"><?php _e('Create Revision', 'custom-page-builder'); ?></button>
        </div>
    </div>
</div>

<!-- Revision Comparison Modal -->
<div id="cpb-revision-comparison-modal" class="cpb-modal cpb-modal-large" style="display: none;">
    <div class="cpb-modal-content">
        <div class="cpb-modal-header">
            <h2><?php _e('Compare Revisions', 'custom-page-builder'); ?></h2>
            <button class="cpb-modal-close">&times;</button>
        </div>
        <div class="cpb-modal-body">
            <div class="cpb-comparison-container">
                <div class="cpb-comparison-loading">
                    <span class="spinner is-active"></span>
                    <?php _e('Loading comparison...', 'custom-page-builder'); ?>
                </div>
            </div>
        </div>
        <div class="cpb-modal-footer">
            <button class="button cpb-modal-close"><?php _e('Close', 'custom-page-builder'); ?></button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize revisions page
    if (typeof CPB_Revisions !== 'undefined') {
        CPB_Revisions.init();
    }
});
</script>