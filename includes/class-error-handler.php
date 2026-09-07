<?php
/**
 * Error handler class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

use Custom_Page_Builder\Exceptions\PageBuilderException;
use Custom_Page_Builder\Exceptions\ValidationException;
use Custom_Page_Builder\Exceptions\ApiException;
use WP_Error;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Centralized error handling class
 */
class Error_Handler {
    
    /**
     * Initialize error handler
     */
    public static function init() {
        // Initialize error logger
        Error_Logger::init();
        
        // Initialize comprehensive error reporter if available
        if (class_exists('Custom_Page_Builder\\Error_Reporter')) {
            Error_Reporter::init();
        }
        
        // Set up WordPress error handling hooks
        add_action('wp_ajax_cpb_admin_action', [self::class, 'handle_ajax_errors'], 1);
        add_action('wp_ajax_nopriv_cpb_admin_action', [self::class, 'handle_ajax_errors'], 1);
        
        // Handle REST API errors
        add_filter('rest_request_before_callbacks', [self::class, 'setup_rest_error_handling'], 10, 3);
    }
    
    /**
     * Handle exceptions and convert to appropriate response format
     *
     * @param \Throwable $exception Exception to handle
     * @param string $context Context where error occurred (ajax, rest, admin)
     * @return array|WP_Error Error response
     */
    public static function handle_exception(\Throwable $exception, string $context = 'general') {
        // Log the exception using Error_Logger
        if ($exception instanceof PageBuilderException) {
            Error_Logger::log_exception($exception);
        } else {
            Error_Logger::error(
                $exception->getMessage(),
                [
                    'context' => $context,
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine()
                ],
                $exception
            );
        }
        
        // Also report using comprehensive error reporter if available
        if (class_exists('Custom_Page_Builder\\Error_Reporter')) {
            $reporter_context = Error_Reporter::CONTEXT_ADMIN_INTERFACE;
            
            // Map context to appropriate reporter context
            switch ($context) {
                case 'rest':
                    $reporter_context = Error_Reporter::CONTEXT_REST_API;
                    break;
                case 'ajax':
                    $reporter_context = Error_Reporter::CONTEXT_AJAX;
                    break;
                case 'admin':
                    $reporter_context = Error_Reporter::CONTEXT_ADMIN_INTERFACE;
                    break;
            }
            
            // Determine category based on exception type
            $category = Error_Reporter::CATEGORY_WARNING;
            if ($exception instanceof PageBuilderException) {
                // Use exception severity if available
                $category = Error_Reporter::CATEGORY_WARNING;
            } else {
                // Fatal errors for non-PageBuilder exceptions
                $category = Error_Reporter::CATEGORY_FATAL;
            }
            
            Error_Reporter::report_error(
                $category,
                $exception->getMessage(),
                $reporter_context,
                [
                    'exception_class' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'original_context' => $context
                ],
                $exception
            );
        }
        
        // Convert to appropriate response format
        switch ($context) {
            case 'rest':
                return self::format_rest_error($exception);
            
            case 'ajax':
                return self::format_ajax_error($exception);
            
            case 'admin':
                return self::format_admin_error($exception);
            
            default:
                return self::format_general_error($exception);
        }
    }
    
    /**
     * Format error for REST API responses
     *
     * @param \Throwable $exception Exception
     * @return WP_Error
     */
    private static function format_rest_error(\Throwable $exception): WP_Error {
        if ($exception instanceof PageBuilderException) {
            return $exception->toWpError();
        }
        
        // Generic error for non-PageBuilder exceptions
        return new WP_Error(
            'internal_error',
            __('An internal error occurred. Please try again later.', 'custom-page-builder'),
            ['status' => 500]
        );
    }
    
    /**
     * Format error for AJAX responses
     *
     * @param \Throwable $exception Exception
     * @return array
     */
    private static function format_ajax_error(\Throwable $exception): array {
        if ($exception instanceof PageBuilderException) {
            return $exception->toArray();
        }
        
        return [
            'success' => false,
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => __('An internal error occurred. Please try again later.', 'custom-page-builder')
            ]
        ];
    }
    
    /**
     * Format error for admin interface
     *
     * @param \Throwable $exception Exception
     * @return array
     */
    private static function format_admin_error(\Throwable $exception): array {
        $user_message = __('An error occurred. Please try again.', 'custom-page-builder');
        
        if ($exception instanceof ValidationException) {
            $user_message = $exception->getMessage();
        } elseif ($exception instanceof PageBuilderException) {
            $user_message = $exception->getMessage();
        }
        
        return [
            'success' => false,
            'message' => $user_message,
            'type' => 'error'
        ];
    }
    
    /**
     * Format general error
     *
     * @param \Throwable $exception Exception
     * @return array
     */
    private static function format_general_error(\Throwable $exception): array {
        if ($exception instanceof PageBuilderException) {
            return $exception->toArray();
        }
        
        return [
            'success' => false,
            'error' => [
                'code' => 'GENERAL_ERROR',
                'message' => $exception->getMessage()
            ]
        ];
    }
    
    /**
     * Handle AJAX errors by wrapping the callback
     */
    public static function handle_ajax_errors() {
        try {
            // Let the original AJAX handler run
            return;
        } catch (\Throwable $e) {
            $error_response = self::handle_exception($e, 'ajax');
            wp_send_json($error_response);
        }
    }
    
    /**
     * Set up REST API error handling
     *
     * @param \WP_REST_Response|\WP_HTTP_Response_Interface|WP_Error|mixed $response
     * @param array $handler Route handler used for the request
     * @param \WP_REST_Request $request Request used to generate the response
     * @return mixed
     */
    public static function setup_rest_error_handling($response, $handler, $request) {
        // Only handle our plugin's endpoints
        if (strpos($request->get_route(), '/custom-pages/') === false) {
            return $response;
        }
        
        // Wrap the callback to catch exceptions
        if (isset($handler['callback']) && is_callable($handler['callback'])) {
            $original_callback = $handler['callback'];
            
            $handler['callback'] = function($request) use ($original_callback) {
                try {
                    return call_user_func($original_callback, $request);
                } catch (\Throwable $e) {
                    return self::handle_exception($e, 'rest');
                }
            };
        }
        
        return $response;
    }
    
    /**
     * Validate required fields
     *
     * @param array $data Data to validate
     * @param array $required_fields Required field names
     * @param string $context Validation context
     * @throws ValidationException
     */
    public static function validate_required_fields(array $data, array $required_fields, string $context = '') {
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[$field] = sprintf(__('Field %s is required.', 'custom-page-builder'), $field);
            }
        }
        
        if (!empty($errors)) {
            throw new ValidationException(
                __('Required fields are missing.', 'custom-page-builder'),
                $errors,
                ['context' => $context]
            );
        }
    }
    
    /**
     * Validate field types
     *
     * @param array $data Data to validate
     * @param array $field_types Field type definitions
     * @param string $context Validation context
     * @throws ValidationException
     */
    public static function validate_field_types(array $data, array $field_types, string $context = '') {
        $errors = [];
        
        foreach ($field_types as $field => $expected_type) {
            if (!isset($data[$field])) {
                continue;
            }
            
            $value = $data[$field];
            $is_valid = false;
            
            switch ($expected_type) {
                case 'string':
                    $is_valid = is_string($value);
                    break;
                
                case 'integer':
                case 'int':
                    $is_valid = is_int($value) || (is_string($value) && ctype_digit($value));
                    break;
                
                case 'boolean':
                case 'bool':
                    $is_valid = is_bool($value) || in_array($value, ['true', 'false', '1', '0', 1, 0], true);
                    break;
                
                case 'array':
                    $is_valid = is_array($value);
                    break;
                
                case 'email':
                    $is_valid = is_email($value);
                    break;
                
                case 'url':
                    $is_valid = filter_var($value, FILTER_VALIDATE_URL) !== false;
                    break;
                
                default:
                    $is_valid = true; // Unknown type, skip validation
            }
            
            if (!$is_valid) {
                $errors[$field] = sprintf(
                    __('Field %s must be of type %s.', 'custom-page-builder'),
                    $field,
                    $expected_type
                );
            }
        }
        
        if (!empty($errors)) {
            throw new ValidationException(
                __('Field type validation failed.', 'custom-page-builder'),
                $errors,
                ['context' => $context]
            );
        }
    }
    
    /**
     * Sanitize input data
     *
     * @param array $data Data to sanitize
     * @param array $sanitization_rules Sanitization rules
     * @return array Sanitized data
     */
    public static function sanitize_input(array $data, array $sanitization_rules = []): array {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $rule = $sanitization_rules[$key] ?? 'text';
            
            switch ($rule) {
                case 'text':
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
                
                case 'textarea':
                    $sanitized[$key] = sanitize_textarea_field($value);
                    break;
                
                case 'email':
                    $sanitized[$key] = sanitize_email($value);
                    break;
                
                case 'url':
                    $sanitized[$key] = esc_url_raw($value);
                    break;
                
                case 'slug':
                    $sanitized[$key] = sanitize_title($value);
                    break;
                
                case 'html':
                    $sanitized[$key] = wp_kses_post($value);
                    break;
                
                case 'integer':
                case 'int':
                    $sanitized[$key] = intval($value);
                    break;
                
                case 'float':
                    $sanitized[$key] = floatval($value);
                    break;
                
                case 'boolean':
                case 'bool':
                    $sanitized[$key] = (bool) $value;
                    break;
                
                case 'array':
                    $sanitized[$key] = is_array($value) ? $value : [];
                    break;
                
                case 'json':
                    if (is_string($value)) {
                        $decoded = json_decode($value, true);
                        $sanitized[$key] = $decoded !== null ? $decoded : [];
                    } else {
                        $sanitized[$key] = $value;
                    }
                    break;
                
                default:
                    $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Add admin notice for errors
     *
     * @param string $message Error message
     * @param string $type Notice type (error, warning, info, success)
     */
    public static function add_admin_notice(string $message, string $type = 'error') {
        add_action('admin_notices', function() use ($message, $type) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($type),
                esc_html($message)
            );
        });
    }
    
    /**
     * Check user capabilities and throw exception if insufficient
     *
     * @param string $capability Required capability
     * @param string $context Context for error message
     * @throws ApiException
     */
    public static function check_capability(string $capability, string $context = '') {
        if (!current_user_can($capability)) {
            throw ApiException::authorizationFailed($context, $capability);
        }
    }
    
    /**
     * Verify nonce and throw exception if invalid
     *
     * @param string $nonce Nonce value
     * @param string $action Nonce action
     * @throws ApiException
     */
    public static function verify_nonce(string $nonce, string $action) {
        if (!wp_verify_nonce($nonce, $action)) {
            throw new ApiException(
                __('Security check failed. Please refresh the page and try again.', 'custom-page-builder'),
                'NONCE_VERIFICATION_FAILED',
                403
            );
        }
    }
}