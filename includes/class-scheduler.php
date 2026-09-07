<?php
/**
 * Page scheduler class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

use Custom_Page_Builder\Models\CustomPage;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Scheduler class for handling scheduled page publication
 */
class Scheduler {
    
    /**
     * Initialize scheduler hooks
     */
    public function init() {
        // Register custom cron interval
        WordPress_Helper::safe_add_filter('cron_schedules', [$this, 'add_custom_cron_intervals']);
        
        // Hook into cron events
        WordPress_Helper::safe_add_action('cpb_check_scheduled_pages', [$this, 'check_scheduled_pages']);
        WordPress_Helper::safe_add_action('cpb_cleanup_temp_files', [$this, 'cleanup_temp_files']);
        
        // Hook into page status changes for scheduling
        WordPress_Helper::safe_add_action('cpb_page_scheduled', [$this, 'on_page_scheduled'], 10, 2);
        WordPress_Helper::safe_add_action('cpb_page_published', [$this, 'on_page_published'], 10, 1);
    }
    
    /**
     * Add custom cron intervals
     *
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public function add_custom_cron_intervals($schedules) {
        $schedules['custom_page_builder_5min'] = [
            'interval' => 300, // 5 minutes
            'display' => __('Every 5 Minutes (Custom Page Builder)', 'custom-page-builder')
        ];
        
        return $schedules;
    }
    
    /**
     * Check for scheduled pages ready for publication
     */
    public function check_scheduled_pages() {
        $pages_to_publish = CustomPage::get_pages_ready_for_publication();
        
        foreach ($pages_to_publish as $page) {
            $this->publish_scheduled_page($page);
        }
        
        // Log the check
        error_log(sprintf(
            'Custom Page Builder: Checked scheduled pages, published %d pages',
            count($pages_to_publish)
        ));
    }
    
    /**
     * Publish a scheduled page
     *
     * @param CustomPage $page Page to publish
     * @return bool
     */
    private function publish_scheduled_page(CustomPage $page) {
        try {
            $success = $page->publish_scheduled();
            
            if ($success) {
                // Fire action for other plugins to hook into
                do_action('cpb_page_auto_published', $page);
                
                // Log successful publication
                error_log(sprintf(
                    'Custom Page Builder: Auto-published page "%s" (ID: %d)',
                    $page->get_title(),
                    $page->get_id()
                ));
                
                // Send notification to author if enabled
                $this->maybe_send_publication_notification($page);
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log(sprintf(
                'Custom Page Builder: Failed to auto-publish page "%s" (ID: %d): %s',
                $page->get_title(),
                $page->get_id(),
                $e->getMessage()
            ));
            
            return false;
        }
    }
    
    /**
     * Send publication notification to author
     *
     * @param CustomPage $page Published page
     */
    private function maybe_send_publication_notification(CustomPage $page) {
        $settings = get_option('custom_page_builder_settings', []);
        
        if (!isset($settings['send_publication_notifications']) || !$settings['send_publication_notifications']) {
            return;
        }
        
        $author = get_user_by('id', $page->get_author_id());
        if (!$author) {
            return;
        }
        
        $subject = sprintf(
            __('Page "%s" has been published', 'custom-page-builder'),
            $page->get_title()
        );
        
        $message = sprintf(
            __("Hello %s,\n\nYour scheduled page \"%s\" has been automatically published.\n\nYou can view it here: %s\n\nBest regards,\nCustom Page Builder", 'custom-page-builder'),
            $author->display_name,
            $page->get_title(),
            home_url('/custom-page/' . $page->get_slug())
        );
        
        wp_mail($author->user_email, $subject, $message);
    }
    
    /**
     * Cleanup temporary files
     */
    public function cleanup_temp_files() {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/custom-page-builder/temp';
        
        if (!is_dir($temp_dir)) {
            return;
        }
        
        $files = glob($temp_dir . '/*');
        $cleaned = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                // Delete files older than 24 hours
                if (filemtime($file) < (time() - 86400)) {
                    if (unlink($file)) {
                        $cleaned++;
                    }
                }
            }
        }
        
        if ($cleaned > 0) {
            error_log(sprintf(
                'Custom Page Builder: Cleaned up %d temporary files',
                $cleaned
            ));
        }
    }
    
    /**
     * Handle page scheduled event
     *
     * @param CustomPage $page Scheduled page
     * @param DateTime $scheduled_date Scheduled publication date
     */
    public function on_page_scheduled(CustomPage $page, DateTime $scheduled_date) {
        // Log scheduling
        error_log(sprintf(
            'Custom Page Builder: Page "%s" (ID: %d) scheduled for publication at %s',
            $page->get_title(),
            $page->get_id(),
            $scheduled_date->format('Y-m-d H:i:s')
        ));
        
        // Fire action for other plugins
        do_action('cpb_after_page_scheduled', $page, $scheduled_date);
    }
    
    /**
     * Handle page published event
     *
     * @param CustomPage $page Published page
     */
    public function on_page_published(CustomPage $page) {
        // Clear any page-specific caches
        $this->clear_page_cache($page);
        
        // Fire action for other plugins
        do_action('cpb_after_page_published', $page);
    }
    
    /**
     * Clear page-specific cache
     *
     * @param CustomPage $page Page to clear cache for
     */
    private function clear_page_cache(CustomPage $page) {
        // Clear WordPress object cache
        wp_cache_delete('custom_page_' . $page->get_id(), 'custom_page_builder');
        wp_cache_delete('custom_page_slug_' . $page->get_slug(), 'custom_page_builder');
        
        // Clear transients
        delete_transient('cpb_page_' . $page->get_id());
        delete_transient('cpb_pages_list');
        
        // Clear any third-party caches if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear popular caching plugins
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        
        if (class_exists('WpFastestCache')) {
            $wpfc = new \WpFastestCache();
            $wpfc->deleteCache();
        }
    }
    
    /**
     * Get next scheduled check time
     *
     * @return int|false
     */
    public function get_next_scheduled_check() {
        return wp_next_scheduled('cpb_check_scheduled_pages');
    }
    
    /**
     * Force check scheduled pages (for manual trigger)
     */
    public function force_check_scheduled_pages() {
        $this->check_scheduled_pages();
    }
    
    /**
     * Get scheduled pages count
     *
     * @return int
     */
    public function get_scheduled_pages_count() {
        global $wpdb;
        
        $table_name = \Custom_Page_Builder\Includes\Installer::get_table_names()['pages'];
        
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} WHERE status = 'scheduled'"
        );
    }
    
    /**
     * Get upcoming scheduled pages
     *
     * @param int $limit Number of pages to return
     * @return array
     */
    public function get_upcoming_scheduled_pages($limit = 10) {
        global $wpdb;
        
        $table_name = \Custom_Page_Builder\Includes\Installer::get_table_names()['pages'];
        
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} 
                WHERE status = 'scheduled' 
                AND scheduled_at > %s 
                ORDER BY scheduled_at ASC 
                LIMIT %d",
                current_time('mysql'),
                $limit
            ),
            ARRAY_A
        );
        
        $pages = [];
        foreach ($rows as $row) {
            $pages[] = CustomPage::from_database($row);
        }
        
        return $pages;
    }
}