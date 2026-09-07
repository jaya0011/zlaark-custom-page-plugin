<?php
/**
 * Admin interface class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

use Custom_Page_Builder\Error_Handler;
use Custom_Page_Builder\Error_Logger;
use Custom_Page_Builder\Input_Validator;
use Custom_Page_Builder\Exceptions\ValidationException;
use Custom_Page_Builder\Exceptions\ApiException;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin interface class for managing admin menu and pages
 */
class Admin_Interface {
    
    /**
     * Page builder instance
     *
     * @var Page_Builder
     */
    private $page_builder;
    
    /**
     * Section manager instance
     *
     * @var Section_Manager
     */
    private $section_manager;
    
    /**
     * Secure image handler instance
     *
     * @var Secure_Image_Handler
     */
    private $secure_image_handler;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Dependencies will be initialized when needed to avoid early instantiation issues
    }
    
    /**
     * Get page builder instance (lazy loading)
     *
     * @return Page_Builder
     */
    private function get_page_builder() {
        if (!$this->page_builder) {
            $this->page_builder = new Page_Builder();
        }
        return $this->page_builder;
    }
    
    /**
     * Get section manager instance (lazy loading)
     *
     * @return Section_Manager
     */
    private function get_section_manager() {
        if (!$this->section_manager) {
            $this->section_manager = new Section_Manager();
        }
        return $this->section_manager;
    }
    
    /**
     * Get secure image handler instance (lazy loading)
     *
     * @return Secure_Image_Handler
     */
    private function get_secure_image_handler() {
        if (!$this->secure_image_handler) {
            $this->secure_image_handler = new Secure_Image_Handler();
        }
        return $this->secure_image_handler;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add main menu page
        add_menu_page(
            __('Custom Pages', 'custom-page-builder'),
            __('Custom Pages', 'custom-page-builder'),
            'manage_options',
            'custom-page-builder',
            [$this, 'render_page_list'],
            'dashicons-layout',
            30
        );
        
        // Add submenu pages
        add_submenu_page(
            'custom-page-builder',
            __('All Pages', 'custom-page-builder'),
            __('All Pages', 'custom-page-builder'),
            'manage_options',
            'custom-page-builder',
            [$this, 'render_page_list']
        );
        
        add_submenu_page(
            'custom-page-builder',
            __('Add New Page', 'custom-page-builder'),
            __('Add New Page', 'custom-page-builder'),
            'manage_options',
            'custom-page-builder-new',
            [$this, 'render_page_builder']
        );
        
        // Add edit page (hidden from menu)
        add_submenu_page(
            null,
            __('Edit Page', 'custom-page-builder'),
            __('Edit Page', 'custom-page-builder'),
            'manage_options',
            'custom-page-builder-edit',
            [$this, 'render_page_builder']
        );
        
        // Add revisions page (hidden from menu)
        add_submenu_page(
            null,
            __('Page Revisions', 'custom-page-builder'),
            __('Page Revisions', 'custom-page-builder'),
            'manage_options',
            'custom-page-builder-revisions',
            [$this, 'render_page_revisions']
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'custom-page-builder') === false) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'custom-page-builder-admin',
            CUSTOM_PAGE_BUILDER_PLUGIN_URL . 'admin/assets/css/admin.css',
            [],
            CUSTOM_PAGE_BUILDER_VERSION
        );
        
        // Enqueue Rich Text Editor CSS
        wp_enqueue_style(
            'custom-page-builder-rich-text',
            CUSTOM_PAGE_BUILDER_PLUGIN_URL . 'admin/css/rich-text-editor.css',
            [],
            CUSTOM_PAGE_BUILDER_VERSION
        );
        
        // Enqueue Image Management CSS
        wp_enqueue_style(
            'custom-page-builder-image-management',
            CUSTOM_PAGE_BUILDER_PLUGIN_URL . 'admin/css/image-management.css',
            [],
            CUSTOM_PAGE_BUILDER_VERSION
        );
        
        // Enqueue Category Manager CSS
        wp_enqueue_style(
            'custom-page-builder-category-manager',
            CUSTOM_PAGE_BUILDER_PLUGIN_URL . 'admin/css/category-manager.css',
            [],
            CUSTOM_PAGE_BUILDER_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'custom-page-builder-admin',
            CUSTOM_PAGE_BUILDER_PLUGIN_URL . 'admin/assets/js/admin.js',
            ['jquery', 'jquery-ui-sortable', 'wp-media'],
            CUSTOM_PAGE_BUILDER_VERSION,
            true
        );
        
        // Enqueue Rich Text Editor JS
        wp_enqueue_script(
            'custom-page-builder-rich-text',
            CUSTOM_PAGE_BUILDER_PLUGIN_URL . 'admin/js/rich-text-editor.js',
            ['jquery'],
            CUSTOM_PAGE_BUILDER_VERSION,
            true
        );
        
        // Enqueue Category Manager JS
        wp_enqueue_script(
            'custom-page-builder-category-manager',
            CUSTOM_PAGE_BUILDER_PLUGIN_URL . 'admin/js/category-manager.js',
            ['jquery'],
            CUSTOM_PAGE_BUILDER_VERSION,
            true
        );
        
        // Enqueue Debug Panel JS (for development/debugging)
        wp_enqueue_script(
            'custom-page-builder-debug-panel',
            CUSTOM_PAGE_BUILDER_PLUGIN_URL . 'admin/js/debug-panel.js',
            ['jquery'],
            CUSTOM_PAGE_BUILDER_VERSION,
            true
        );
        
        // Localize script with AJAX data
        wp_localize_script('custom-page-builder-admin', 'cpb_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cpb_admin_nonce'),
            'strings' => [
                'confirm_delete_page' => __('Are you sure you want to delete this page?', 'custom-page-builder'),
                'confirm_delete_section' => __('Are you sure you want to delete this section?', 'custom-page-builder'),
                'save_success' => __('Page saved successfully!', 'custom-page-builder'),
                'save_error' => __('Error saving page. Please try again.', 'custom-page-builder'),
                'loading' => __('Loading...', 'custom-page-builder'),
            ]
        ]);
        
        // Enqueue WordPress media uploader
        wp_enqueue_media();
    }
    
    /**
     * Render page list
     */
    public function render_page_list() {
        // Get all pages
        $pages = $this->page_builder->get_all_pages();
        
        // Include template
        include CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/page-list.php';
    }
    
    /**
     * Render page builder interface
     */
    public function render_page_builder() {
        $page_id = isset($_GET['page_id']) ? intval($_GET['page_id']) : 0;
        $page = null;
        $sections = [];
        
        if ($page_id > 0) {
            $page = $this->page_builder->get_page($page_id);
            if ($page) {
                $sections = $this->section_manager->get_sections_by_page($page_id);
            }
        }
        
        // Include template
        include CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/page-builder.php';
    }
    
    /**
     * Handle AJAX requests
     */
    public function handle_ajax_request() {
        try {
            // Verify nonce
            Error_Handler::verify_nonce($_POST['nonce'] ?? '', 'cpb_admin_nonce');
            
            // Check user capabilities
            Error_Handler::check_capability('manage_options', 'admin_ajax');
        } catch (\Exception $e) {
            $error_response = Error_Handler::handle_exception($e, 'ajax');
            wp_send_json($error_response);
            return;
        }
        
        $action = $_POST['cpb_action'] ?? '';
        
        switch ($action) {
            case 'save_page':
                $this->handle_save_page();
                break;
            case 'delete_page':
                $this->handle_delete_page();
                break;
            case 'duplicate_page':
                $this->handle_duplicate_page();
                break;
            case 'save_section':
                $this->handle_save_section();
                break;
            case 'delete_section':
                $this->handle_delete_section();
                break;
            case 'reorder_sections':
                $this->handle_reorder_sections();
                break;
            case 'load_section_config':
                $this->handle_load_section_config();
                break;
            case 'process_image_upload':
                $this->handle_process_image_upload();
                break;
            case 'schedule_page':
                $this->handle_schedule_page();
                break;
            case 'unschedule_page':
                $this->handle_unschedule_page();
                break;
            case 'get_scheduled_pages':
                $this->handle_get_scheduled_pages();
                break;
            case 'create_revision':
                $this->handle_create_revision();
                break;
            case 'get_page_revisions':
                $this->handle_get_page_revisions();
                break;
            case 'restore_from_revision':
                $this->handle_restore_from_revision();
                break;
            case 'delete_revision':
                $this->handle_delete_revision();
                break;
            case 'compare_revisions':
                $this->handle_compare_revisions();
                break;
            case 'create_autosave':
                $this->handle_create_autosave();
                break;
            case 'get_latest_autosave':
                $this->handle_get_latest_autosave();
                break;
            case 'get_product_template':
                $this->handle_get_product_template();
                break;
            default:
                wp_send_json_error(['message' => __('Invalid action', 'custom-page-builder')]);
        }
    }
    
    /**
     * Handle save page AJAX request
     */
    public function handle_save_page() {
        try {
            $page_data = $_POST['page_data'] ?? [];
            
            // Sanitize input data
            $sanitized_data = Error_Handler::sanitize_input($page_data, [
                'page_title' => 'text',
                'page_slug' => 'slug',
                'page_status' => 'text',
                'scheduled_at' => 'text'
            ]);
            
            // Validate using Input_Validator
            $validated_data = Input_Validator::validate($sanitized_data, 'page');
            
            $page_id = intval($page_data['page_id'] ?? 0);
            
            if ($page_id > 0) {
                // Update existing page
                $this->page_builder->update_page($page_id, $validated_data);
                $result_page_id = $page_id;
            } else {
                // Create new page
                $result_page_id = $this->page_builder->create_page($validated_data);
            }
            
            Error_Logger::info('Page saved successfully', [
                'page_id' => $result_page_id,
                'action' => $page_id > 0 ? 'update' : 'create',
                'user_id' => get_current_user_id()
            ]);
            
            wp_send_json_success([
                'message' => __('Page saved successfully', 'custom-page-builder'),
                'page_id' => $result_page_id
            ]);
            
        } catch (\Exception $e) {
            $error_response = Error_Handler::handle_exception($e, 'ajax');
            wp_send_json($error_response);
        }
    }
    
    /**
     * Handle delete page AJAX request
     */
    public function handle_delete_page() {
        try {
            $page_id = intval($_POST['page_id'] ?? 0);
            
            if ($page_id <= 0) {
                wp_send_json_error(['message' => __('Invalid page ID', 'custom-page-builder')]);
                return;
            }
            
            $this->page_builder->delete_page($page_id);
            
            wp_send_json_success([
                'message' => __('Page deleted successfully', 'custom-page-builder')
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle duplicate page AJAX request
     */
    public function handle_duplicate_page() {
        try {
            $page_id = intval($_POST['page_id'] ?? 0);
            
            if ($page_id <= 0) {
                wp_send_json_error(['message' => __('Invalid page ID', 'custom-page-builder')]);
                return;
            }
            
            $new_page_id = $this->page_builder->duplicate_page($page_id);
            
            wp_send_json_success([
                'message' => __('Page duplicated successfully', 'custom-page-builder'),
                'page_id' => $new_page_id
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle save section AJAX request
     */
    public function handle_save_section() {
        try {
            $section_data = $_POST['section_data'] ?? [];
            $section_id = intval($_POST['section_id'] ?? 0);
            $page_id = intval($_POST['page_id'] ?? 0);
            
            // Log received data for debugging
            Error_Logger::info('Section save request received', [
                'section_data' => $section_data,
                'section_id' => $section_id,
                'page_id' => $page_id,
                'user_id' => get_current_user_id()
            ]);
            
            // Validate page ID first
            if ($page_id <= 0) {
                throw new ValidationException(
                    'Page ID is required',
                    ['page_id' => 'Page ID must be a positive integer']
                );
            }
            
            // Extract section type and config
            $section_type = $section_data['section_type'] ?? '';
            $config = $section_data['config'] ?? $section_data; // Fallback to full data if no config key
            
            if (empty($section_type)) {
                throw new ValidationException(
                    'Section type is required',
                    ['section_type' => 'Section type must be specified']
                );
            }
            
            // Validate and sanitize section configuration
            $validated_config = Input_Validator::validate_section_config($section_type, $config);
            
            Error_Logger::info('Section data validated', [
                'section_type' => $section_type,
                'validated_config' => $validated_config,
                'user_id' => get_current_user_id()
            ]);
            
            if ($section_id > 0) {
                // Update existing section
                $this->get_section_manager()->update_section($section_id, $validated_config);
                $result_section_id = $section_id;
            } else {
                // Create new section
                $result_section_id = $this->get_section_manager()->create_section($page_id, $section_type, $validated_config);
            }
            
            // Get updated section for response
            $section = $this->get_section_manager()->get_section($result_section_id);
            $section_html = $this->render_section_item($section);
            
            Error_Logger::info('Section saved successfully', [
                'section_id' => $result_section_id,
                'section_type' => $section_type,
                'page_id' => $page_id,
                'action' => $section_id > 0 ? 'update' : 'create',
                'user_id' => get_current_user_id()
            ]);
            
            wp_send_json_success([
                'message' => __('Section saved successfully', 'custom-page-builder'),
                'section_id' => $result_section_id,
                'section_html' => $section_html
            ]);
            
        } catch (\Exception $e) {
            // Log the error for debugging
            Error_Logger::error('Section save failed', [
                'error' => $e->getMessage(),
                'section_data' => $_POST['section_data'] ?? [],
                'user_id' => get_current_user_id()
            ]);
            
            $error_response = Error_Handler::handle_exception($e, 'ajax');
            wp_send_json($error_response);
        }
    }
    
    /**
     * Handle delete section AJAX request
     */
    public function handle_delete_section() {
        try {
            $section_id = intval($_POST['section_id'] ?? 0);
            
            if ($section_id <= 0) {
                wp_send_json_error(['message' => __('Invalid section ID', 'custom-page-builder')]);
                return;
            }
            
            $this->section_manager->delete_section($section_id);
            
            wp_send_json_success([
                'message' => __('Section deleted successfully', 'custom-page-builder')
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle reorder sections AJAX request
     */
    public function handle_reorder_sections() {
        try {
            $page_id = intval($_POST['page_id'] ?? 0);
            $section_order = $_POST['section_order'] ?? [];
            
            if ($page_id <= 0) {
                wp_send_json_error(['message' => __('Invalid page ID', 'custom-page-builder')]);
                return;
            }
            
            if (empty($section_order) || !is_array($section_order)) {
                wp_send_json_error(['message' => __('Invalid section order', 'custom-page-builder')]);
                return;
            }
            
            // Convert to integers
            $section_order = array_map('intval', $section_order);
            
            $this->section_manager->reorder_sections($page_id, $section_order);
            
            wp_send_json_success([
                'message' => __('Sections reordered successfully', 'custom-page-builder')
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Render section item HTML
     *
     * @param Section $section Section object
     * @return string Section HTML
     */
    private function render_section_item(Section $section): string {
        $config = $section->get_config();
        $section_title = $config['title'] ?? ucfirst(str_replace('_', ' ', $section->get_section_type()));
        $section_payload = [
            'id' => $section->get_id(),
            'config' => $config,
        ];
        $config_json = wp_json_encode($section_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        ob_start();
       ?>
       <div class="cpb-section-item" 
           data-section-id="<?php echo esc_attr($section->get_id()); ?>"
           data-section-type="<?php echo esc_attr($section->get_section_type()); ?>"
           data-section-config="<?php echo esc_attr($config_json ?: '{}'); ?>">
            
            <div class="cpb-section-header">
                <div>
                    <h3 class="cpb-section-title">
                        <?php echo esc_html($section_title); ?>
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
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle load section config AJAX request
     */
    public function handle_load_section_config() {
        try {
            $section_type = sanitize_text_field($_POST['section_type'] ?? '');
            $section_data = $_POST['section_data'] ?? [];
            $section_id = isset($_POST['section_id']) ? intval($_POST['section_id']) : 0;
            
            if (empty($section_type)) {
                wp_send_json_error(['message' => __('Section type is required', 'custom-page-builder')]);
                return;
            }
            
            // Validate section type
            $section_factory = new Section_Factory();
            if (!$section_factory->is_valid_section_type($section_type)) {
                wp_send_json_error(['message' => __('Invalid section type', 'custom-page-builder')]);
                return;
            }
            
            // If no section data provided but we have an ID, load config from database
            if ($section_id > 0 && (empty($section_data) || empty($section_data['config']))) {
                $section = $this->get_section_manager()->get_section($section_id);
                if ($section) {
                    $section_data = [
                        'id' => $section->get_id(),
                        'config' => $section->get_config()
                    ];
                }
            }

            // Generate section configuration HTML
            $config_html = $this->render_section_config($section_type, $section_data);
            
            wp_send_json_success([
                'config_html' => $config_html
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Render section configuration HTML
     *
     * @param string $section_type Section type
     * @param array $section_data Section data
     * @return string Configuration HTML
     */
    private function render_section_config(string $section_type, array $section_data = []): string {
        // Create a temporary section instance for rendering
        $section_factory = new Section_Factory();
        $section = $section_factory->create_section($section_type, $section_data);
        
        // Get the admin template path
        $template_path = CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/section-templates/' . $section_type . '.php';
        
        if (!file_exists($template_path)) {
            return $this->render_generic_section_config($section_type, $section_data);
        }
        
        // Render the template
        ob_start();
        include $template_path;
        return ob_get_clean();
    }
    
    /**
     * Render generic section configuration for sections without specific templates
     *
     * @param string $section_type Section type
     * @param array $section_data Section data
     * @return string Configuration HTML
     */
    private function render_generic_section_config(string $section_type, array $section_data = []): string {
        ob_start();
        ?>
        <div class="cpb-generic-section-config">
            <div class="cpb-form-field">
                <label for="section_title"><?php _e('Section Title', 'custom-page-builder'); ?></label>
                <input type="text" id="section_title" name="title" 
                       value="<?php echo esc_attr($section_data['title'] ?? ''); ?>" 
                       placeholder="<?php _e('Enter section title', 'custom-page-builder'); ?>">
            </div>
            
            <div class="cpb-form-field">
                <label for="section_description"><?php _e('Description', 'custom-page-builder'); ?></label>
                <textarea id="section_description" name="description" rows="3"
                          placeholder="<?php _e('Enter section description', 'custom-page-builder'); ?>"><?php echo esc_textarea($section_data['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="cpb-form-field">
                <label>
                    <input type="checkbox" name="visible" value="1" 
                           <?php checked($section_data['visible'] ?? true); ?>>
                    <?php _e('Section Visible', 'custom-page-builder'); ?>
                </label>
            </div>
            
            <div class="cpb-notice warning">
                <p><?php printf(__('This section type (%s) does not have a specific configuration template. Please create a template file at: %s', 'custom-page-builder'), $section_type, 'templates/admin/section-templates/' . $section_type . '.php'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle process image upload AJAX request
     */
    public function handle_process_image_upload() {
        try {
            $attachment_id = intval($_POST['attachment_id'] ?? 0);
            
            // Validate attachment ID
            if ($attachment_id <= 0) {
                throw new ValidationException(
                    'Invalid attachment ID',
                    ['attachment_id' => 'Attachment ID must be a positive integer']
                );
            }
            
            // Verify attachment exists
            $attachment = get_post($attachment_id);
            if (!$attachment || $attachment->post_type !== 'attachment') {
                throw new ValidationException(
                    'Attachment not found',
                    ['attachment_id' => 'The specified attachment does not exist']
                );
            }
            
            // Validate file type for security
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = get_post_mime_type($attachment_id);
            
            if (!in_array($file_type, $allowed_types)) {
                throw new ValidationException(
                    'Invalid file type',
                    ['file_type' => 'Only image files are allowed']
                );
            }
            
            // Process image through secure image handler
            $image_data = $this->secure_image_handler->process_uploaded_image($attachment_id);
            
            if (!$image_data['success']) {
                throw new \Exception($image_data['error'] ?? __('Failed to process image', 'custom-page-builder'));
            }
            
            // Get image metadata
            $image_meta = wp_get_attachment_metadata($attachment_id);
            $image_url = wp_get_attachment_url($attachment_id);
            
            Error_Logger::info('Image processed successfully', [
                'attachment_id' => $attachment_id,
                'file_type' => $file_type,
                'is_protected' => $image_data['is_protected'],
                'user_id' => get_current_user_id()
            ]);
            
            wp_send_json_success([
                'message' => __('Image processed successfully', 'custom-page-builder'),
                'attachment_id' => $attachment_id,
                'image_url' => $image_url,
                'secure_urls' => $image_data['secure_urls'],
                'fallback_urls' => $image_data['fallback_urls'],
                'is_protected' => $image_data['is_protected'],
                'image_meta' => $image_meta,
                'secure_image_plugin_active' => $this->secure_image_handler->is_secure_image_plugin_active()
            ]);
            
        } catch (\Exception $e) {
            $error_response = Error_Handler::handle_exception($e, 'ajax');
            wp_send_json($error_response);
        }
    }
    
    /**
     * Handle schedule page AJAX request
     */
    public function handle_schedule_page() {
        try {
            $page_id = intval($_POST['page_id'] ?? 0);
            $scheduled_date = $_POST['scheduled_date'] ?? '';
            
            if ($page_id <= 0) {
                wp_send_json_error(['message' => __('Invalid page ID', 'custom-page-builder')]);
                return;
            }
            
            if (empty($scheduled_date)) {
                wp_send_json_error(['message' => __('Scheduled date is required', 'custom-page-builder')]);
                return;
            }
            
            $this->page_builder->schedule_page($page_id, $scheduled_date);
            
            wp_send_json_success([
                'message' => __('Page scheduled successfully', 'custom-page-builder')
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle unschedule page AJAX request
     */
    public function handle_unschedule_page() {
        try {
            $page_id = intval($_POST['page_id'] ?? 0);
            
            if ($page_id <= 0) {
                wp_send_json_error(['message' => __('Invalid page ID', 'custom-page-builder')]);
                return;
            }
            
            $this->page_builder->unschedule_page($page_id);
            
            wp_send_json_success([
                'message' => __('Page unscheduled successfully', 'custom-page-builder')
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle get scheduled pages AJAX request
     */
    public function handle_get_scheduled_pages() {
        try {
            $args = [
                'limit' => intval($_POST['limit'] ?? 10),
                'offset' => intval($_POST['offset'] ?? 0)
            ];
            
            $scheduled_pages = $this->page_builder->get_scheduled_pages($args);
            $pages_data = [];
            
            foreach ($scheduled_pages as $page) {
                $pages_data[] = [
                    'id' => $page->get_id(),
                    'title' => $page->get_title(),
                    'slug' => $page->get_slug(),
                    'status' => $page->get_status(),
                    'scheduled_at' => $page->get_scheduled_at() ? $page->get_scheduled_at()->format('Y-m-d H:i:s') : null,
                    'author_name' => get_user_by('id', $page->get_author_id())->display_name ?? 'Unknown'
                ];
            }
            
            wp_send_json_success([
                'pages' => $pages_data,
                'total' => $this->page_builder->get_page_count(['status' => 'scheduled'])
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle create revision AJAX request
     */
    public function handle_create_revision() {
        try {
            $page_id = intval($_POST['page_id'] ?? 0);
            $revision_note = sanitize_text_field($_POST['revision_note'] ?? '');
            
            if ($page_id <= 0) {
                wp_send_json_error(['message' => __('Invalid page ID', 'custom-page-builder')]);
                return;
            }
            
            $revision_id = $this->page_builder->create_revision($page_id, $revision_note);
            
            wp_send_json_success([
                'message' => __('Revision created successfully', 'custom-page-builder'),
                'revision_id' => $revision_id
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle get page revisions AJAX request
     */
    public function handle_get_page_revisions() {
        try {
            $page_id = intval($_POST['page_id'] ?? 0);
            $include_autosaves = (bool) ($_POST['include_autosaves'] ?? false);
            $limit = intval($_POST['limit'] ?? 20);
            
            if ($page_id <= 0) {
                wp_send_json_error(['message' => __('Invalid page ID', 'custom-page-builder')]);
                return;
            }
            
            $revisions = $this->page_builder->get_page_revisions($page_id, $include_autosaves, $limit);
            $revisions_data = [];
            
            foreach ($revisions as $revision) {
                $author = get_user_by('id', $revision->get_created_by());
                $revisions_data[] = [
                    'id' => $revision->get_id(),
                    'page_id' => $revision->get_page_id(),
                    'revision_note' => $revision->get_revision_note(),
                    'created_at' => $revision->get_created_at()->format('Y-m-d H:i:s'),
                    'created_by' => [
                        'id' => $revision->get_created_by(),
                        'name' => $author ? $author->display_name : 'Unknown'
                    ],
                    'is_autosave' => $revision->is_autosave(),
                    'formatted_date' => $revision->get_created_at()->format('M j, Y @ g:i A')
                ];
            }
            
            wp_send_json_success([
                'revisions' => $revisions_data
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle restore from revision AJAX request
     */
    public function handle_restore_from_revision() {
        try {
            $page_id = intval($_POST['page_id'] ?? 0);
            $revision_id = intval($_POST['revision_id'] ?? 0);
            
            if ($page_id <= 0) {
                wp_send_json_error(['message' => __('Invalid page ID', 'custom-page-builder')]);
                return;
            }
            
            if ($revision_id <= 0) {
                wp_send_json_error(['message' => __('Invalid revision ID', 'custom-page-builder')]);
                return;
            }
            
            $this->page_builder->restore_from_revision($page_id, $revision_id);
            
            wp_send_json_success([
                'message' => __('Page restored from revision successfully', 'custom-page-builder')
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle delete revision AJAX request
     */
    public function handle_delete_revision() {
        try {
            $revision_id = intval($_POST['revision_id'] ?? 0);
            
            if ($revision_id <= 0) {
                wp_send_json_error(['message' => __('Invalid revision ID', 'custom-page-builder')]);
                return;
            }
            
            $this->page_builder->delete_revision($revision_id);
            
            wp_send_json_success([
                'message' => __('Revision deleted successfully', 'custom-page-builder')
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle compare revisions AJAX request
     */
    public function handle_compare_revisions() {
        try {
            $revision1_id = intval($_POST['revision1_id'] ?? 0);
            $revision2_id = intval($_POST['revision2_id'] ?? 0);
            
            if ($revision1_id <= 0 || $revision2_id <= 0) {
                wp_send_json_error(['message' => __('Invalid revision IDs', 'custom-page-builder')]);
                return;
            }
            
            $comparison = $this->page_builder->compare_revisions($revision1_id, $revision2_id);
            
            wp_send_json_success([
                'comparison' => $comparison
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle create autosave AJAX request
     */
    public function handle_create_autosave() {
        try {
            $page_id = intval($_POST['page_id'] ?? 0);
            
            if ($page_id <= 0) {
                wp_send_json_error(['message' => __('Invalid page ID', 'custom-page-builder')]);
                return;
            }
            
            $autosave_id = $this->page_builder->create_autosave($page_id);
            
            if ($autosave_id) {
                wp_send_json_success([
                    'message' => __('Autosave created successfully', 'custom-page-builder'),
                    'autosave_id' => $autosave_id
                ]);
            } else {
                wp_send_json_error(['message' => __('Failed to create autosave', 'custom-page-builder')]);
            }
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle get latest autosave AJAX request
     */
    public function handle_get_latest_autosave() {
        try {
            $page_id = intval($_POST['page_id'] ?? 0);
            $user_id = intval($_POST['user_id'] ?? get_current_user_id());
            
            if ($page_id <= 0) {
                wp_send_json_error(['message' => __('Invalid page ID', 'custom-page-builder')]);
                return;
            }
            
            $autosave = $this->page_builder->get_latest_autosave($page_id, $user_id);
            
            if ($autosave) {
                $author = get_user_by('id', $autosave->get_created_by());
                $autosave_data = [
                    'id' => $autosave->get_id(),
                    'page_id' => $autosave->get_page_id(),
                    'created_at' => $autosave->get_created_at()->format('Y-m-d H:i:s'),
                    'created_by' => [
                        'id' => $autosave->get_created_by(),
                        'name' => $author ? $author->display_name : 'Unknown'
                    ],
                    'formatted_date' => $autosave->get_created_at()->format('M j, Y @ g:i A')
                ];
                
                wp_send_json_success([
                    'autosave' => $autosave_data
                ]);
            } else {
                wp_send_json_success([
                    'autosave' => null
                ]);
            }
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle get product template AJAX request
     */
    public function handle_get_product_template() {
        try {
            // Log that this method was called
            error_log('🔵 handle_get_product_template() called');
            error_log('🔵 POST data: ' . print_r($_POST, true));
            
            $index = intval($_POST['index'] ?? 0);
            error_log('🔵 Product index: ' . $index);
            
            // Verify required constants
            if (!defined('CUSTOM_PAGE_BUILDER_PLUGIN_DIR')) {
                error_log('❌ CUSTOM_PAGE_BUILDER_PLUGIN_DIR not defined');
                throw new \Exception('Plugin directory constant not defined');
            }
            
            error_log('🔵 Plugin dir: ' . CUSTOM_PAGE_BUILDER_PLUGIN_DIR);
            
            // Check if template files exist
            $category_template = CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/partials/woocommerce-category-selector.php';
            $tag_template = CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/partials/woocommerce-tag-selector.php';
            
            error_log('🔵 Category template exists: ' . (file_exists($category_template) ? 'YES' : 'NO'));
            error_log('🔵 Tag template exists: ' . (file_exists($tag_template) ? 'YES' : 'NO'));
            
            if (!file_exists($category_template)) {
                throw new \Exception('Category selector template not found at: ' . $category_template);
            }
            
            if (!file_exists($tag_template)) {
                throw new \Exception('Tag selector template not found at: ' . $tag_template);
            }
            
            // Start output buffering
            ob_start();
            error_log('🔵 Starting output buffer');
            
            // Set up variables for the template
            $product = [
                'image' => '',
                'title' => '',
                'price' => '',
                'sale_price' => '',
                'badge' => '',
                'sku' => '',
                'description' => '',
                'link' => '',
                'button_text' => 'View Product',
                'featured' => false,
                'wc_categories' => [],
                'wc_tags' => []
            ];
            
            ?>
            <div class="product-item" data-index="<?php echo $index; ?>">
                <div class="product-header">
                    <span class="product-number"><?php echo $index + 1; ?></span>
                    <button type="button" class="remove-product button"><?php _e('🗑️ Remove', 'custom-page-builder'); ?></button>
                </div>
                
                <div class="config-row">
                    <div class="config-group">
                        <label><?php _e('Product Image', 'custom-page-builder'); ?></label>
                        <div class="image-upload-field">
                            <input type="hidden" name="config[products][<?php echo $index; ?>][image]" 
                                   value="" 
                                   class="image-url-input" />
                            <div class="image-button-group">
                                <button type="button" class="upload-image-btn button"><?php _e('📷 Upload Image', 'custom-page-builder'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="config-group">
                    <label><?php _e('Product Title', 'custom-page-builder'); ?></label>
                    <input type="text" name="config[products][<?php echo $index; ?>][title]" 
                           value="" 
                           placeholder="<?php _e('Product Name', 'custom-page-builder'); ?>" />
                </div>
                
                <!-- WooCommerce Product Categories -->
                <div class="config-group" style="background: #e3f2fd; padding: 20px; border: 3px solid #2196f3; margin: 15px 0; border-radius: 5px;">
                    <?php
                    $field_name = 'config[products][' . $index . '][wc_categories]';
                    $selected_categories = [];
                    $label = __('WooCommerce Product Categories', 'custom-page-builder');
                    $show_all_option = false;
                    include CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/partials/woocommerce-category-selector.php';
                    ?>
                </div>
                
                <!-- WooCommerce Product Tags -->
                <div class="config-group" style="background: #d1fae5; padding: 20px; border: 3px solid #10b981; margin: 15px 0; border-radius: 5px;">
                    <?php
                    $field_name = 'config[products][' . $index . '][wc_tags]';
                    $selected_tags = [];
                    $label = __('WooCommerce Product Tags', 'custom-page-builder');
                    $show_all_option = false;
                    include CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/partials/woocommerce-tag-selector.php';
                    ?>
                </div>
                
                <div class="config-row">
                    <div class="config-group">
                        <label><?php _e('Price', 'custom-page-builder'); ?></label>
                        <input type="text" name="config[products][<?php echo $index; ?>][price]" 
                               value="" 
                               placeholder="<?php _e('$99.99', 'custom-page-builder'); ?>" />
                    </div>
                    
                    <div class="config-group">
                        <label><?php _e('Sale Price (Optional)', 'custom-page-builder'); ?></label>
                        <input type="text" name="config[products][<?php echo $index; ?>][sale_price]" 
                               value="" 
                               placeholder="<?php _e('$79.99', 'custom-page-builder'); ?>" />
                    </div>
                </div>
                
                <div class="config-row">
                    <div class="config-group">
                        <label><?php _e('Badge (Optional)', 'custom-page-builder'); ?></label>
                        <input type="text" name="config[products][<?php echo $index; ?>][badge]" 
                               value="" 
                               placeholder="<?php _e('Sale, New, Featured', 'custom-page-builder'); ?>" />
                    </div>
                    
                    <div class="config-group">
                        <label><?php _e('SKU (Optional)', 'custom-page-builder'); ?></label>
                        <input type="text" name="config[products][<?php echo $index; ?>][sku]" 
                               value="" 
                               placeholder="<?php _e('Product SKU/Code', 'custom-page-builder'); ?>" />
                    </div>
                </div>
                
                <div class="config-group">
                    <label><?php _e('Description', 'custom-page-builder'); ?></label>
                    <textarea name="config[products][<?php echo $index; ?>][description]" rows="3" 
                              class="cpb-rich-textarea"
                              placeholder="<?php _e('Product description...', 'custom-page-builder'); ?>"></textarea>
                </div>
                
                <div class="config-row">
                    <div class="config-group">
                        <label><?php _e('Product Link', 'custom-page-builder'); ?></label>
                        <input type="url" name="config[products][<?php echo $index; ?>][link]" 
                               value="" 
                               placeholder="<?php _e('https://example.com/product', 'custom-page-builder'); ?>" />
                    </div>
                    
                    <div class="config-group">
                        <label><?php _e('Button Text', 'custom-page-builder'); ?></label>
                        <input type="text" name="config[products][<?php echo $index; ?>][button_text]" 
                               value="View Product" />
                    </div>
                </div>
                
                <div class="config-group">
                    <label>
                        <input type="checkbox" name="config[products][<?php echo $index; ?>][featured]" value="1" />
                        <?php _e('Featured Product', 'custom-page-builder'); ?>
                    </label>
                </div>
            </div>
            <?php
            
            $html = ob_get_clean();
            
            error_log('✅ HTML generated, length: ' . strlen($html));
            error_log('✅ HTML preview (first 200 chars): ' . substr($html, 0, 200));
            
            // Verify HTML contains expected elements
            $has_category_selector = strpos($html, 'wc-category-selector') !== false || strpos($html, 'WooCommerce Product Categories') !== false;
            $has_tag_selector = strpos($html, 'wc-tag-selector') !== false || strpos($html, 'WooCommerce Product Tags') !== false;
            
            error_log('✅ Has category selector: ' . ($has_category_selector ? 'YES' : 'NO'));
            error_log('✅ Has tag selector: ' . ($has_tag_selector ? 'YES' : 'NO'));
            
            wp_send_json_success([
                'html' => $html,
                'debug' => [
                    'index' => $index,
                    'html_length' => strlen($html),
                    'has_category_selector' => $has_category_selector,
                    'has_tag_selector' => $has_tag_selector,
                    'timestamp' => current_time('mysql')
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log('❌ Exception in handle_get_product_template: ' . $e->getMessage());
            error_log('❌ Stack trace: ' . $e->getTraceAsString());
            
            wp_send_json_error([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Render page revisions interface
     */
    public function render_page_revisions() {
        // Include template
        include CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/page-revisions.php';
    }
    
    /**
     * Display admin notices for secure image integration
     */
    public function display_admin_notices() {
        // Display notice if secure image plugin is not active
        if (!$this->secure_image_handler->is_secure_image_plugin_active()) {
            $this->secure_image_handler->display_inactive_plugin_notice();
        }
    }
}