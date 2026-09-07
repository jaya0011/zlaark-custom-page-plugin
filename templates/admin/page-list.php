<?php
/**
 * Admin page list template
 *
 * @package Custom_Page_Builder
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Custom Pages', 'custom-page-builder'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=custom-page-builder-new'); ?>" class="page-title-action">
        <?php _e('Add New', 'custom-page-builder'); ?>
    </a>
    
    <div class="cpb-page-list">
        <?php if (empty($pages)): ?>
            <div class="cpb-notice">
                <p><?php _e('No custom pages found. Create your first page to get started!', 'custom-page-builder'); ?></p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=custom-page-builder-new'); ?>" class="cpb-btn cpb-btn-primary">
                        <?php _e('Create First Page', 'custom-page-builder'); ?>
                    </a>
                </p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php _e('Title', 'custom-page-builder'); ?></th>
                        <th scope="col"><?php _e('Slug', 'custom-page-builder'); ?></th>
                        <th scope="col"><?php _e('Status', 'custom-page-builder'); ?></th>
                        <th scope="col"><?php _e('Date', 'custom-page-builder'); ?></th>
                        <th scope="col"><?php _e('Actions', 'custom-page-builder'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $page): ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="<?php echo admin_url('admin.php?page=custom-page-builder-edit&page_id=' . $page->get_id()); ?>">
                                        <?php echo esc_html($page->get_title()); ?>
                                    </a>
                                </strong>
                            </td>
                            <td>
                                <code><?php echo esc_html($page->get_slug()); ?></code>
                            </td>
                            <td>
                                <span class="status-<?php echo esc_attr($page->get_status()); ?>">
                                    <?php echo esc_html(ucfirst($page->get_status())); ?>
                                </span>
                                <?php if ($page->get_status() === 'scheduled' && $page->get_scheduled_at()): ?>
                                    <br><small class="scheduled-info">
                                        <?php printf(
                                            __('Scheduled: %s', 'custom-page-builder'),
                                            $page->get_scheduled_at()->format('Y-m-d H:i')
                                        ); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($page->get_status() === 'published' && $page->get_published_at()): ?>
                                    <strong><?php _e('Published:', 'custom-page-builder'); ?></strong><br>
                                    <?php echo esc_html($page->get_published_at()->format('Y-m-d H:i')); ?>
                                <?php elseif ($page->get_status() === 'scheduled' && $page->get_scheduled_at()): ?>
                                    <strong><?php _e('Scheduled:', 'custom-page-builder'); ?></strong><br>
                                    <?php echo esc_html($page->get_scheduled_at()->format('Y-m-d H:i')); ?>
                                <?php else: ?>
                                    <strong><?php _e('Created:', 'custom-page-builder'); ?></strong><br>
                                    <?php echo esc_html($page->get_created_at()->format('Y-m-d H:i')); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=custom-page-builder-edit&page_id=' . $page->get_id()); ?>" 
                                   class="cpb-btn cpb-btn-secondary">
                                    <?php _e('Edit', 'custom-page-builder'); ?>
                                </a>
                                
                                <a href="<?php echo admin_url('admin.php?page=custom-page-builder-revisions&page_id=' . $page->get_id()); ?>" 
                                   class="cpb-btn cpb-btn-secondary">
                                    <?php _e('Revisions', 'custom-page-builder'); ?>
                                </a>
                                
                                <?php if ($page->get_status() === 'scheduled'): ?>
                                    <button class="cpb-btn cpb-btn-warning cpb-unschedule-page" 
                                            data-page-id="<?php echo esc_attr($page->get_id()); ?>">
                                        <?php _e('Unschedule', 'custom-page-builder'); ?>
                                    </button>
                                <?php elseif ($page->get_status() !== 'published'): ?>
                                    <button class="cpb-btn cpb-btn-primary cpb-schedule-page" 
                                            data-page-id="<?php echo esc_attr($page->get_id()); ?>">
                                        <?php _e('Schedule', 'custom-page-builder'); ?>
                                    </button>
                                <?php endif; ?>
                                
                                <button class="cpb-btn cpb-btn-secondary cpb-duplicate-page" 
                                        data-page-id="<?php echo esc_attr($page->get_id()); ?>">
                                    <?php _e('Duplicate', 'custom-page-builder'); ?>
                                </button>
                                
                                <button class="cpb-btn cpb-btn-danger cpb-delete-page" 
                                        data-page-id="<?php echo esc_attr($page->get_id()); ?>">
                                    <?php _e('Delete', 'custom-page-builder'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Schedule Page Modal -->
<div id="cpb-schedule-modal" class="cpb-modal" style="display: none;">
    <div class="cpb-modal-content">
        <div class="cpb-modal-header">
            <h3><?php _e('Schedule Page Publication', 'custom-page-builder'); ?></h3>
            <button class="cpb-modal-close">&times;</button>
        </div>
        <div class="cpb-modal-body">
            <form id="cpb-schedule-form">
                <input type="hidden" id="schedule-page-id" name="page_id" value="">
                
                <div class="cpb-form-group">
                    <label for="scheduled-date"><?php _e('Publication Date & Time', 'custom-page-builder'); ?></label>
                    <input type="datetime-local" 
                           id="scheduled-date" 
                           name="scheduled_date" 
                           required
                           min="<?php echo date('Y-m-d\TH:i'); ?>">
                    <small class="cpb-help-text">
                        <?php _e('Select when this page should be automatically published.', 'custom-page-builder'); ?>
                    </small>
                </div>
                
                <div class="cpb-form-actions">
                    <button type="submit" class="cpb-btn cpb-btn-primary">
                        <?php _e('Schedule Publication', 'custom-page-builder'); ?>
                    </button>
                    <button type="button" class="cpb-btn cpb-btn-secondary cpb-modal-close">
                        <?php _e('Cancel', 'custom-page-builder'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>