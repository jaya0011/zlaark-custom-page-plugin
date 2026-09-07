<?php
/**
 * Admin page builder template
 *
 * @package Custom_Page_Builder
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$is_edit_mode = $page && $page->get_id() > 0;
$page_title = $is_edit_mode ? $page->get_title() : '';
$page_slug = $is_edit_mode ? $page->get_slug() : '';
$page_status = $is_edit_mode ? $page->get_status() : 'draft';
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo $is_edit_mode ? __('Edit Page', 'custom-page-builder') : __('Add New Page', 'custom-page-builder'); ?>
    </h1>
    
    <div class="cpb-page-builder">
        <!-- Page Header -->
        <div class="cpb-page-header">
            <form id="cpb-page-form">
                <input type="hidden" id="page_id" name="page_id" value="<?php echo $is_edit_mode ? esc_attr($page->get_id()) : ''; ?>">
                
                <div class="cpb-page-meta">
                    <div class="form-field">
                        <label for="page_title"><?php _e('Page Title', 'custom-page-builder'); ?></label>
                        <input type="text" id="page_title" name="page_title" 
                               value="<?php echo esc_attr($page_title); ?>" 
                               placeholder="<?php _e('Enter page title', 'custom-page-builder'); ?>" required>
                    </div>
                    
                    <div class="form-field">
                        <label for="page_slug"><?php _e('Page Slug', 'custom-page-builder'); ?></label>
                        <input type="text" id="page_slug" name="page_slug" 
                               value="<?php echo esc_attr($page_slug); ?>" 
                               placeholder="<?php _e('page-slug', 'custom-page-builder'); ?>">
                        <p class="description"><?php _e('Leave empty to auto-generate from title', 'custom-page-builder'); ?></p>
                    </div>
                    
                    <div class="form-field">
                        <label for="page_status"><?php _e('Status', 'custom-page-builder'); ?></label>
                        <select id="page_status" name="page_status">
                            <option value="draft" <?php selected($page_status, 'draft'); ?>><?php _e('Draft', 'custom-page-builder'); ?></option>
                            <option value="published" <?php selected($page_status, 'published'); ?>><?php _e('Published', 'custom-page-builder'); ?></option>
                            <option value="archived" <?php selected($page_status, 'archived'); ?>><?php _e('Archived', 'custom-page-builder'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="cpb-page-actions">
                    <button type="button" class="cpb-btn cpb-btn-primary cpb-save-page">
                        <?php _e('Save Page', 'custom-page-builder'); ?>
                    </button>
                    
                    <?php if ($is_edit_mode): ?>
                        <a href="<?php echo admin_url('admin.php?page=custom-page-builder-revisions&page_id=' . $page->get_id()); ?>" 
                           class="cpb-show-revisions">
                            <?php _e('View Revisions', 'custom-page-builder'); ?>
                        </a>
                        
                        <button type="button" class="cpb-btn cpb-btn-secondary cpb-duplicate-page" 
                                data-page-id="<?php echo esc_attr($page->get_id()); ?>">
                            <?php _e('Duplicate', 'custom-page-builder'); ?>
                        </button>
                        
                        <button type="button" class="cpb-btn cpb-btn-danger cpb-delete-page">
                            <?php _e('Delete Page', 'custom-page-builder'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <a href="<?php echo admin_url('admin.php?page=custom-page-builder'); ?>" 
                       class="cpb-btn cpb-btn-secondary">
                        <?php _e('Back to List', 'custom-page-builder'); ?>
                    </a>
                </div>
            </form>
            
            <!-- Auto-save indicator -->
            <div class="cpb-autosave-indicator" style="display: none;">
                <?php _e('Auto-saved', 'custom-page-builder'); ?>
            </div>
        </div>
        
        <!-- Sections Container -->
        <div class="cpb-sections-container">
            <div class="cpb-sections-header">
                <h2><?php _e('Page Sections', 'custom-page-builder'); ?></h2>
                <button type="button" class="cpb-add-section-btn">
                    <?php _e('Add Section', 'custom-page-builder'); ?>
                </button>
            </div>
            
            <div class="cpb-sections-list">
                <?php if (!empty($sections)): ?>
                    <?php foreach ($sections as $section): ?>
                        <?php
                        $config = $section->get_config();
                        $section_payload = [
                            'id' => $section->get_id(),
                            'config' => $config,
                        ];
                        $config_json = wp_json_encode($section_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        ?>
                        <div class="cpb-section-item" 
                             data-section-id="<?php echo esc_attr($section->get_id()); ?>"
                             data-section-type="<?php echo esc_attr($section->get_section_type()); ?>"
                             data-section-config="<?php echo esc_attr($config_json ?: '{}'); ?>">
                            
                            <div class="cpb-section-header">
                                <div>
                                    <h3 class="cpb-section-title">
                                        <?php echo esc_html($section->get_config()['title'] ?? ucfirst(str_replace('_', ' ', $section->get_section_type()))); ?>
                                    </h3>
                                    <span class="cpb-section-type">
                                        <?php echo esc_html(str_replace('_', ' ', $section->get_section_type())); ?>
                                    </span>
                                </div>
                                
                                <div class="cpb-section-actions">
                                    <button type="button" class="cpb-edit-section">
                                        <?php _e('Edit', 'custom-page-builder'); ?>
                                    </button>
                                    <button type="button" class="cpb-delete-section">
                                        <?php _e('Delete', 'custom-page-builder'); ?>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="cpb-section-content">
                                <?php
                                // Display section preview/configuration
                                if (!empty($config)) {
                                    echo '<pre>' . esc_html(json_encode($config, JSON_PRETTY_PRINT)) . '</pre>';
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="cpb-empty-sections">
                        <p><?php _e('No sections added yet. Click "Add Section" to get started!', 'custom-page-builder'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-generate slug from title
jQuery(document).ready(function($) {
    $('#page_title').on('input', function() {
        var title = $(this).val();
        var slug = title.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim('-');
        
        if (!$('#page_slug').val() || $('#page_slug').data('auto-generated')) {
            $('#page_slug').val(slug).data('auto-generated', true);
        }
    });
    
    $('#page_slug').on('input', function() {
        $(this).data('auto-generated', false);
    });
});
</script>