<?php
/**
 * Category AJAX handlers
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles AJAX requests for category management
 */
class Category_Ajax {
    
    /**
     * Initialize AJAX handlers
     */
    public static function init() {
        add_action('wp_ajax_cpb_add_category', [__CLASS__, 'add_category']);
        add_action('wp_ajax_cpb_delete_category', [__CLASS__, 'delete_category']);
        add_action('wp_ajax_cpb_get_categories', [__CLASS__, 'get_categories']);
    }
    
    /**
     * Add new category via AJAX
     */
    public static function add_category() {
        check_ajax_referer('cpb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        
        if (empty($name)) {
            wp_send_json_error(['message' => 'Category name is required']);
            return;
        }
        
        $category = Category_Manager::add_category($name);
        
        if ($category) {
            wp_send_json_success([
                'category' => $category,
                'message' => 'Category added successfully'
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to add category']);
        }
    }
    
    /**
     * Delete category via AJAX
     */
    public static function delete_category() {
        check_ajax_referer('cpb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        
        $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
        
        if (empty($id)) {
            wp_send_json_error(['message' => 'Category ID is required']);
            return;
        }
        
        $result = Category_Manager::delete_category($id);
        
        if ($result) {
            wp_send_json_success(['message' => 'Category deleted successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete category']);
        }
    }
    
    /**
     * Get all categories via AJAX
     */
    public static function get_categories() {
        check_ajax_referer('cpb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        
        $categories = Category_Manager::get_categories();
        wp_send_json_success(['categories' => $categories]);
    }
}

// Initialize AJAX handlers
Category_Ajax::init();
