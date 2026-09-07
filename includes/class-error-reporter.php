<?php
/**
 * Comprehensive Error Reporting System
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

use Custom_Page_Builder\Exceptions\PageBuilderException;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Comprehensive error reporting and categorization system
 */
class Error_Reporter {
    
    /**
     * Error categories
     */
    const CATEGORY_FATAL = 'fatal';
    const CATEGORY_WARNING = 'warning';
    const CATEGORY_NOTICE = 'notice';
    const CATEGORY_DEBUG = 'debug';
    
    /**
     * Error contexts
     */
    const CONTEXT_PLUGIN_ACTIVATION = 'plugin_activation';
    const CONTEXT_PLUGIN_DEACTIVATION = 'plugin_deactivation';
    const CONTEXT_ADMIN_INTERFACE = 'admin_interface';
    const CONTEXT_REST_API = 'rest_api';
    const CONTEXT_AJAX = 'ajax';
    const CONTEXT_FRONTEND = 'frontend';
    const CONTEXT_DATABASE = 'database';
    const CONTEXT_FILE_SYSTEM = 'file_system';
    const CONTEXT_DEPENDENCY = 'dependency';
    const CONTEXT_VALIDATION = 'validation';
    
    /**
     * Error report storage
     *
     * @var array
     */
    private static $error_reports = [];
    
    /**
     * Maximum number of stored reports
     *
     * @var int
     */
    private static $max_reports = 1000;
    
    /**
     * Initialize error reporter
     */
    public static function init() {
        // Initialize error logger if not already done
        Error_Logger::init();
        
        // Set up WordPress error hooks
        add_action('wp_loaded', [self::class, 'setup_error_hooks']);
        
        // Register shutdown function to catch fatal errors
        register_shutdown_function([self::class, 'handle_fatal_errors']);
        
        // Set custom error handler
        set_error_handler([self::class, 'handle_php_errors']);
        
        // Set custom exception handler
        set_exception_handler([self::class, 'handle_uncaught_exceptions']);
    }
    
    /**
     * Set up WordPress-specific error hooks
     */
    public static function setup_error_hooks() {
        // WordPress error hooks
        add_action('wp_die_handler', [self::class, 'handle_wp_die'], 10, 1);
        add_action('doing_it_wrong_run', [self::class, 'handle_doing_it_wrong'], 10, 3);
        add_action('deprecated_function_run', [self::class, 'handle_deprecated_function'], 10, 3);
        add_action('deprecated_file_run', [self::class, 'handle_deprecated_file'], 10, 4);
        add_action('deprecated_argument_run', [self::class, 'handle_deprecated_argument'], 10, 3);
    }
    
    /**
     * Report an error with comprehensive information
     *
     * @param string $category Error category (fatal, warning, notice, debug)
     * @param string $message Error message
     * @param string $context Error context
     * @param array $additional_data Additional error data
     * @param \Throwable|null $exception Exception object if available
     * @return string Error report ID
     */
    public static function report_error(
        string $category,
        string $message,
        string $context = self::CONTEXT_ADMIN_INTERFACE,
        array $additional_data = [],
        ?\Throwable $exception = null
    ): string {
        // Generate unique error ID
        $error_id = self::generate_error_id();
        
        // Collect comprehensive debug information
        $debug_info = self::collect_debug_information($context, $exception);
        
        // Create error report
        $error_report = [
            'id' => $error_id,
            'timestamp' => current_time('timestamp'),
            'category' => $category,
            'message' => $message,
            'context' => $context,
            'additional_data' => $additional_data,
            'debug_info' => $debug_info,
            'exception' => $exception ? self::format_exception($exception) : null,
            'severity' => self::determine_severity($category, $context),
            'user_id' => get_current_user_id(),
            'session_id' => self::get_session_id(),
            'request_id' => self::get_request_id()
        ];
        
        // Store error report
        self::store_error_report($error_report);
        
        // Log error using appropriate level
        self::log_error_report($error_report);
        
        // Handle critical errors
        if ($category === self::CATEGORY_FATAL) {
            self::handle_critical_error($error_report);
        }
        
        return $error_id;
    }
    
    /**
     * Report fatal error
     *
     * @param string $message Error message
     * @param string $context Error context
     * @param array $additional_data Additional data
     * @param \Throwable|null $exception Exception
     * @return string Error ID
     */
    public static function report_fatal(
        string $message,
        string $context = self::CONTEXT_ADMIN_INTERFACE,
        array $additional_data = [],
        ?\Throwable $exception = null
    ): string {
        return self::report_error(self::CATEGORY_FATAL, $message, $context, $additional_data, $exception);
    }
    
    /**
     * Report warning
     *
     * @param string $message Warning message
     * @param string $context Error context
     * @param array $additional_data Additional data
     * @return string Error ID
     */
    public static function report_warning(
        string $message,
        string $context = self::CONTEXT_ADMIN_INTERFACE,
        array $additional_data = []
    ): string {
        return self::report_error(self::CATEGORY_WARNING, $message, $context, $additional_data);
    }
    
    /**
     * Report notice
     *
     * @param string $message Notice message
     * @param string $context Error context
     * @param array $additional_data Additional data
     * @return string Error ID
     */
    public static function report_notice(
        string $message,
        string $context = self::CONTEXT_ADMIN_INTERFACE,
        array $additional_data = []
    ): string {
        return self::report_error(self::CATEGORY_NOTICE, $message, $context, $additional_data);
    }
    
    /**
     * Report debug information
     *
     * @param string $message Debug message
     * @param string $context Error context
     * @param array $additional_data Additional data
     * @return string Error ID
     */
    public static function report_debug(
        string $message,
        string $context = self::CONTEXT_ADMIN_INTERFACE,
        array $additional_data = []
    ): string {
        // Only report debug messages if debug mode is enabled
        if (!self::is_debug_mode_enabled()) {
            return '';
        }
        
        return self::report_error(self::CATEGORY_DEBUG, $message, $context, $additional_data);
    }
    
    /**
     * Collect comprehensive debug information
     *
     * @param string $context Error context
     * @param \Throwable|null $exception Exception object
     * @return array Debug information
     */
    private static function collect_debug_information(string $context, ?\Throwable $exception = null): array {
        $debug_info = [
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => CUSTOM_PAGE_BUILDER_VERSION ?? 'unknown',
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit')
            ],
            'server_info' => [
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
                'php_sapi' => php_sapi_name(),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size')
            ],
            'request_info' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referer' => $_SERVER['HTTP_REFERER'] ?? '',
                'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? ''
            ],
            'wordpress_info' => [
                'is_admin' => is_admin(),
                'is_ajax' => wp_doing_ajax(),
                'is_rest' => defined('REST_REQUEST') && REST_REQUEST,
                'is_cron' => wp_doing_cron(),
                'current_screen' => self::get_current_screen_info(),
                'active_plugins' => get_option('active_plugins', []),
                'current_theme' => get_template(),
                'multisite' => is_multisite()
            ],
            'plugin_info' => [
                'plugin_dir' => CUSTOM_PAGE_BUILDER_PLUGIN_DIR ?? '',
                'plugin_url' => CUSTOM_PAGE_BUILDER_PLUGIN_URL ?? '',
                'plugin_file' => CUSTOM_PAGE_BUILDER_PLUGIN_FILE ?? '',
                'loaded_classes' => self::get_loaded_plugin_classes(),
                'hooks_registered' => self::get_registered_hooks()
            ]
        ];
        
        // Add context-specific information
        switch ($context) {
            case self::CONTEXT_DATABASE:
                $debug_info['database_info'] = self::get_database_debug_info();
                break;
                
            case self::CONTEXT_FILE_SYSTEM:
                $debug_info['filesystem_info'] = self::get_filesystem_debug_info();
                break;
                
            case self::CONTEXT_REST_API:
                $debug_info['rest_info'] = self::get_rest_debug_info();
                break;
                
            case self::CONTEXT_AJAX:
                $debug_info['ajax_info'] = self::get_ajax_debug_info();
                break;
        }
        
        // Add exception-specific information
        if ($exception) {
            $debug_info['exception_info'] = [
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => self::format_stack_trace($exception->getTrace()),
                'previous' => $exception->getPrevious() ? get_class($exception->getPrevious()) : null
            ];
        }
        
        return $debug_info;
    }
    
    /**
     * Get current screen information
     *
     * @return array Screen information
     */
    private static function get_current_screen_info(): array {
        if (!function_exists('get_current_screen')) {
            return [];
        }
        
        $screen = get_current_screen();
        if (!$screen) {
            return [];
        }
        
        return [
            'id' => $screen->id,
            'base' => $screen->base,
            'post_type' => $screen->post_type,
            'taxonomy' => $screen->taxonomy
        ];
    }
    
    /**
     * Get loaded plugin classes
     *
     * @return array Loaded classes
     */
    private static function get_loaded_plugin_classes(): array {
        $classes = get_declared_classes();
        $plugin_classes = [];
        
        foreach ($classes as $class) {
            if (strpos($class, 'Custom_Page_Builder') === 0) {
                $plugin_classes[] = $class;
            }
        }
        
        return $plugin_classes;
    }
    
    /**
     * Get registered WordPress hooks for this plugin
     *
     * @return array Registered hooks
     */
    private static function get_registered_hooks(): array {
        global $wp_filter;
        
        $plugin_hooks = [];
        
        if (!$wp_filter) {
            return $plugin_hooks;
        }
        
        foreach ($wp_filter as $hook_name => $hook_callbacks) {
            foreach ($hook_callbacks->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    if (is_array($callback['function']) && 
                        is_object($callback['function'][0]) && 
                        strpos(get_class($callback['function'][0]), 'Custom_Page_Builder') === 0) {
                        
                        $plugin_hooks[] = [
                            'hook' => $hook_name,
                            'priority' => $priority,
                            'class' => get_class($callback['function'][0]),
                            'method' => $callback['function'][1]
                        ];
                    }
                }
            }
        }
        
        return $plugin_hooks;
    }
    
    /**
     * Get database debug information
     *
     * @return array Database debug info
     */
    private static function get_database_debug_info(): array {
        global $wpdb;
        
        $info = [
            'queries_count' => get_num_queries(),
            'last_error' => $wpdb->last_error,
            'last_query' => $wpdb->last_query,
            'db_version' => $wpdb->db_version(),
            'charset' => $wpdb->charset,
            'collate' => $wpdb->collate
        ];
        
        // Add slow query information if available
        if (defined('SAVEQUERIES') && SAVEQUERIES) {
            $slow_queries = [];
            foreach ($wpdb->queries as $query) {
                if ($query[1] > 0.1) { // Queries taking more than 0.1 seconds
                    $slow_queries[] = [
                        'sql' => $query[0],
                        'time' => $query[1],
                        'stack' => $query[2]
                    ];
                }
            }
            $info['slow_queries'] = $slow_queries;
        }
        
        return $info;
    }
    
    /**
     * Get filesystem debug information
     *
     * @return array Filesystem debug info
     */
    private static function get_filesystem_debug_info(): array {
        $upload_dir = wp_upload_dir();
        
        return [
            'upload_dir' => $upload_dir,
            'wp_filesystem_method' => get_filesystem_method(),
            'disk_free_space' => disk_free_space(ABSPATH),
            'disk_total_space' => disk_total_space(ABSPATH),
            'temp_dir' => sys_get_temp_dir(),
            'plugin_dir_writable' => is_writable(CUSTOM_PAGE_BUILDER_PLUGIN_DIR ?? ''),
            'upload_dir_writable' => is_writable($upload_dir['basedir'] ?? '')
        ];
    }
    
    /**
     * Get REST API debug information
     *
     * @return array REST debug info
     */
    private static function get_rest_debug_info(): array {
        global $wp_rest_server;
        
        $info = [
            'rest_enabled' => !empty($wp_rest_server),
            'rest_url' => rest_url(),
            'rest_namespace' => 'custom-pages/v1'
        ];
        
        if ($wp_rest_server) {
            $info['registered_routes'] = array_keys($wp_rest_server->get_routes());
        }
        
        return $info;
    }
    
    /**
     * Get AJAX debug information
     *
     * @return array AJAX debug info
     */
    private static function get_ajax_debug_info(): array {
        return [
            'action' => $_POST['action'] ?? $_GET['action'] ?? '',
            'nonce' => $_POST['_wpnonce'] ?? $_GET['_wpnonce'] ?? '',
            'post_data' => $_POST,
            'get_data' => $_GET,
            'doing_ajax' => wp_doing_ajax(),
            'ajax_url' => admin_url('admin-ajax.php')
        ];
    }
    
    /**
     * Format exception for storage
     *
     * @param \Throwable $exception Exception
     * @return array Formatted exception
     */
    private static function format_exception(\Throwable $exception): array {
        return [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => self::format_stack_trace($exception->getTrace()),
            'previous' => $exception->getPrevious() ? self::format_exception($exception->getPrevious()) : null
        ];
    }
    
    /**
     * Format stack trace
     *
     * @param array $trace Stack trace
     * @return array Formatted trace
     */
    private static function format_stack_trace(array $trace): array {
        $formatted_trace = [];
        
        foreach (array_slice($trace, 0, 15) as $frame) { // Limit to 15 frames
            $formatted_frame = [];
            
            if (isset($frame['file'])) {
                $formatted_frame['file'] = $frame['file'];
            }
            
            if (isset($frame['line'])) {
                $formatted_frame['line'] = $frame['line'];
            }
            
            if (isset($frame['function'])) {
                $formatted_frame['function'] = $frame['function'];
            }
            
            if (isset($frame['class'])) {
                $formatted_frame['class'] = $frame['class'];
            }
            
            if (isset($frame['type'])) {
                $formatted_frame['type'] = $frame['type'];
            }
            
            $formatted_trace[] = $formatted_frame;
        }
        
        return $formatted_trace;
    }
    
    /**
     * Determine error severity based on category and context
     *
     * @param string $category Error category
     * @param string $context Error context
     * @return int Severity level (1-10, 10 being most severe)
     */
    private static function determine_severity(string $category, string $context): int {
        $base_severity = [
            self::CATEGORY_FATAL => 10,
            self::CATEGORY_WARNING => 6,
            self::CATEGORY_NOTICE => 3,
            self::CATEGORY_DEBUG => 1
        ];
        
        $context_modifier = [
            self::CONTEXT_PLUGIN_ACTIVATION => 2,
            self::CONTEXT_DATABASE => 1,
            self::CONTEXT_REST_API => 1,
            self::CONTEXT_ADMIN_INTERFACE => 0,
            self::CONTEXT_FRONTEND => 1,
            self::CONTEXT_DEPENDENCY => 2
        ];
        
        $severity = $base_severity[$category] ?? 5;
        $severity += $context_modifier[$context] ?? 0;
        
        return min(10, max(1, $severity));
    }
    
    /**
     * Generate unique error ID
     *
     * @return string Error ID
     */
    private static function generate_error_id(): string {
        return 'cpb_' . uniqid() . '_' . wp_generate_password(8, false);
    }
    
    /**
     * Get session ID
     *
     * @return string Session ID
     */
    private static function get_session_id(): string {
        if (session_id()) {
            return session_id();
        }
        
        // Generate pseudo session ID based on user and time
        $user_id = get_current_user_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return md5($user_id . $ip . $user_agent . date('Y-m-d-H'));
    }
    
    /**
     * Get request ID
     *
     * @return string Request ID
     */
    private static function get_request_id(): string {
        static $request_id = null;
        
        if ($request_id === null) {
            $request_id = uniqid('req_', true);
        }
        
        return $request_id;
    }
    
    /**
     * Store error report
     *
     * @param array $error_report Error report
     */
    private static function store_error_report(array $error_report) {
        // Add to memory storage
        self::$error_reports[] = $error_report;
        
        // Limit memory storage
        if (count(self::$error_reports) > self::$max_reports) {
            array_shift(self::$error_reports);
        }
        
        // Store in database for persistence
        self::store_error_in_database($error_report);
    }
    
    /**
     * Store error report in database
     *
     * @param array $error_report Error report
     */
    private static function store_error_in_database(array $error_report) {
        global $wpdb;
        
        if (!$wpdb) {
            return;
        }
        
        $table_name = $wpdb->prefix . 'cpb_error_reports';
        
        // Create table if it doesn't exist
        self::create_error_reports_table();
        
        // Insert error report
        $wpdb->insert(
            $table_name,
            [
                'error_id' => $error_report['id'],
                'timestamp' => date('Y-m-d H:i:s', $error_report['timestamp']),
                'category' => $error_report['category'],
                'context' => $error_report['context'],
                'message' => $error_report['message'],
                'severity' => $error_report['severity'],
                'user_id' => $error_report['user_id'],
                'session_id' => $error_report['session_id'],
                'request_id' => $error_report['request_id'],
                'data' => json_encode([
                    'additional_data' => $error_report['additional_data'],
                    'debug_info' => $error_report['debug_info'],
                    'exception' => $error_report['exception']
                ])
            ],
            [
                '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s'
            ]
        );
        
        // Clean up old reports (keep only last 30 days)
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE timestamp < %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
    }
    
    /**
     * Create error reports table
     */
    private static function create_error_reports_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cpb_error_reports';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            error_id varchar(100) NOT NULL,
            timestamp datetime NOT NULL,
            category varchar(20) NOT NULL,
            context varchar(50) NOT NULL,
            message text NOT NULL,
            severity tinyint(2) NOT NULL DEFAULT 5,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            session_id varchar(100) NOT NULL DEFAULT '',
            request_id varchar(100) NOT NULL DEFAULT '',
            data longtext,
            PRIMARY KEY (id),
            KEY error_id (error_id),
            KEY timestamp (timestamp),
            KEY category (category),
            KEY context (context),
            KEY severity (severity),
            KEY user_id (user_id)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Log error report using Error_Logger
     *
     * @param array $error_report Error report
     */
    private static function log_error_report(array $error_report) {
        $log_level = self::get_log_level_for_category($error_report['category']);
        
        $context = [
            'error_id' => $error_report['id'],
            'category' => $error_report['category'],
            'context' => $error_report['context'],
            'severity' => $error_report['severity'],
            'user_id' => $error_report['user_id'],
            'additional_data' => $error_report['additional_data']
        ];
        
        Error_Logger::log(
            $log_level,
            $error_report['message'],
            $context,
            $error_report['exception'] ? new \Exception($error_report['exception']['message']) : null
        );
    }
    
    /**
     * Get log level for error category
     *
     * @param string $category Error category
     * @return string Log level
     */
    private static function get_log_level_for_category(string $category): string {
        switch ($category) {
            case self::CATEGORY_FATAL:
                return Error_Logger::LEVEL_CRITICAL;
            case self::CATEGORY_WARNING:
                return Error_Logger::LEVEL_WARNING;
            case self::CATEGORY_NOTICE:
                return Error_Logger::LEVEL_INFO;
            case self::CATEGORY_DEBUG:
                return Error_Logger::LEVEL_DEBUG;
            default:
                return Error_Logger::LEVEL_ERROR;
        }
    }
    
    /**
     * Handle critical errors
     *
     * @param array $error_report Error report
     */
    private static function handle_critical_error(array $error_report) {
        // Send admin notification for critical errors
        self::send_critical_error_notification($error_report);
        
        // Attempt automatic recovery if possible
        self::attempt_error_recovery($error_report);
    }
    
    /**
     * Send critical error notification
     *
     * @param array $error_report Error report
     */
    private static function send_critical_error_notification(array $error_report) {
        $settings = get_option('custom_page_builder_settings', []);
        
        if (!($settings['enable_critical_error_notifications'] ?? true)) {
            return;
        }
        
        $admin_email = $settings['critical_error_notification_email'] ?? get_option('admin_email');
        
        if (!$admin_email) {
            return;
        }
        
        $subject = sprintf(
            '[%s] Critical Error in Custom Page Builder - %s',
            get_bloginfo('name'),
            $error_report['id']
        );
        
        $body = "A critical error occurred in the Custom Page Builder plugin:\n\n";
        $body .= "Error ID: {$error_report['id']}\n";
        $body .= "Message: {$error_report['message']}\n";
        $body .= "Context: {$error_report['context']}\n";
        $body .= "Severity: {$error_report['severity']}/10\n";
        $body .= "Time: " . date('Y-m-d H:i:s', $error_report['timestamp']) . "\n";
        $body .= "User ID: {$error_report['user_id']}\n\n";
        
        if ($error_report['exception']) {
            $body .= "Exception Details:\n";
            $body .= "Class: {$error_report['exception']['class']}\n";
            $body .= "File: {$error_report['exception']['file']}:{$error_report['exception']['line']}\n\n";
        }
        
        $body .= "Site: " . home_url() . "\n";
        $body .= "Admin URL: " . admin_url() . "\n\n";
        $body .= "This is an automated message from the Custom Page Builder error reporting system.";
        
        wp_mail($admin_email, $subject, $body);
    }
    
    /**
     * Attempt automatic error recovery
     *
     * @param array $error_report Error report
     */
    private static function attempt_error_recovery(array $error_report) {
        // Implement context-specific recovery strategies
        switch ($error_report['context']) {
            case self::CONTEXT_PLUGIN_ACTIVATION:
                self::attempt_activation_recovery($error_report);
                break;
                
            case self::CONTEXT_DATABASE:
                self::attempt_database_recovery($error_report);
                break;
                
            case self::CONTEXT_FILE_SYSTEM:
                self::attempt_filesystem_recovery($error_report);
                break;
        }
    }
    
    /**
     * Attempt plugin activation recovery
     *
     * @param array $error_report Error report
     */
    private static function attempt_activation_recovery(array $error_report) {
        // Log recovery attempt
        Error_Logger::info('Attempting plugin activation recovery', [
            'error_id' => $error_report['id'],
            'original_error' => $error_report['message']
        ]);
        
        // Clear any cached data that might be causing issues
        wp_cache_flush();
        
        // Clear opcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // Report recovery attempt
        self::report_notice(
            'Automatic recovery attempted for plugin activation error',
            self::CONTEXT_PLUGIN_ACTIVATION,
            ['original_error_id' => $error_report['id']]
        );
    }
    
    /**
     * Attempt database recovery
     *
     * @param array $error_report Error report
     */
    private static function attempt_database_recovery(array $error_report) {
        global $wpdb;
        
        // Log recovery attempt
        Error_Logger::info('Attempting database recovery', [
            'error_id' => $error_report['id'],
            'last_error' => $wpdb->last_error
        ]);
        
        // Clear database query cache
        wp_cache_flush();
        
        // Check database connection
        if (!$wpdb->check_connection()) {
            // Attempt to reconnect
            $wpdb->db_connect();
        }
    }
    
    /**
     * Attempt filesystem recovery
     *
     * @param array $error_report Error report
     */
    private static function attempt_filesystem_recovery(array $error_report) {
        // Log recovery attempt
        Error_Logger::info('Attempting filesystem recovery', [
            'error_id' => $error_report['id']
        ]);
        
        // Clear file status cache
        clearstatcache();
        
        // Attempt to create missing directories
        $upload_dir = wp_upload_dir();
        if (!file_exists($upload_dir['basedir'])) {
            wp_mkdir_p($upload_dir['basedir']);
        }
    }
    
    /**
     * Handle fatal PHP errors
     */
    public static function handle_fatal_errors() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::report_fatal(
                $error['message'],
                self::CONTEXT_ADMIN_INTERFACE,
                [
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'type' => $error['type']
                ]
            );
        }
    }
    
    /**
     * Handle PHP errors
     *
     * @param int $errno Error number
     * @param string $errstr Error message
     * @param string $errfile Error file
     * @param int $errline Error line
     * @return bool
     */
    public static function handle_php_errors(int $errno, string $errstr, string $errfile, int $errline): bool {
        // Don't handle errors that are suppressed with @
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $category = self::CATEGORY_WARNING;
        
        switch ($errno) {
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
                $category = self::CATEGORY_FATAL;
                break;
                
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                $category = self::CATEGORY_WARNING;
                break;
                
            case E_NOTICE:
            case E_USER_NOTICE:
                $category = self::CATEGORY_NOTICE;
                break;
        }
        
        self::report_error(
            $category,
            $errstr,
            self::CONTEXT_ADMIN_INTERFACE,
            [
                'errno' => $errno,
                'file' => $errfile,
                'line' => $errline
            ]
        );
        
        // Don't execute PHP internal error handler
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     *
     * @param \Throwable $exception Uncaught exception
     */
    public static function handle_uncaught_exceptions(\Throwable $exception) {
        self::report_fatal(
            'Uncaught exception: ' . $exception->getMessage(),
            self::CONTEXT_ADMIN_INTERFACE,
            [],
            $exception
        );
    }
    
    /**
     * Handle WordPress wp_die calls
     *
     * @param callable $handler Die handler
     * @return callable
     */
    public static function handle_wp_die($handler) {
        return function($message, $title = '', $args = []) use ($handler) {
            // Report wp_die as warning
            self::report_warning(
                'WordPress wp_die called: ' . $message,
                self::CONTEXT_ADMIN_INTERFACE,
                [
                    'title' => $title,
                    'args' => $args
                ]
            );
            
            // Call original handler
            return call_user_func($handler, $message, $title, $args);
        };
    }
    
    /**
     * Handle "doing it wrong" notices
     *
     * @param string $function Function name
     * @param string $message Error message
     * @param string $version WordPress version
     */
    public static function handle_doing_it_wrong(string $function, string $message, string $version) {
        self::report_notice(
            "Function {$function} was called incorrectly: {$message}",
            self::CONTEXT_ADMIN_INTERFACE,
            [
                'function' => $function,
                'version' => $version
            ]
        );
    }
    
    /**
     * Handle deprecated function notices
     *
     * @param string $function Function name
     * @param string $replacement Replacement function
     * @param string $version WordPress version
     */
    public static function handle_deprecated_function(string $function, string $replacement, string $version) {
        self::report_notice(
            "Deprecated function {$function} used (since {$version}). Use {$replacement} instead.",
            self::CONTEXT_ADMIN_INTERFACE,
            [
                'function' => $function,
                'replacement' => $replacement,
                'version' => $version
            ]
        );
    }
    
    /**
     * Handle deprecated file notices
     *
     * @param string $file File name
     * @param string $replacement Replacement file
     * @param string $version WordPress version
     * @param string $message Additional message
     */
    public static function handle_deprecated_file(string $file, string $replacement, string $version, string $message) {
        self::report_notice(
            "Deprecated file {$file} used (since {$version}). {$message}",
            self::CONTEXT_ADMIN_INTERFACE,
            [
                'file' => $file,
                'replacement' => $replacement,
                'version' => $version
            ]
        );
    }
    
    /**
     * Handle deprecated argument notices
     *
     * @param string $function Function name
     * @param string $message Error message
     * @param string $version WordPress version
     */
    public static function handle_deprecated_argument(string $function, string $message, string $version) {
        self::report_notice(
            "Deprecated argument in {$function} (since {$version}): {$message}",
            self::CONTEXT_ADMIN_INTERFACE,
            [
                'function' => $function,
                'version' => $version
            ]
        );
    }
    
    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    private static function is_debug_mode_enabled(): bool {
        return defined('WP_DEBUG') && WP_DEBUG;
    }
    
    /**
     * Get error reports by criteria
     *
     * @param array $criteria Search criteria
     * @param int $limit Number of reports to return
     * @param int $offset Offset for pagination
     * @return array Error reports
     */
    public static function get_error_reports(array $criteria = [], int $limit = 50, int $offset = 0): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cpb_error_reports';
        
        $where_clauses = [];
        $where_values = [];
        
        if (!empty($criteria['category'])) {
            $where_clauses[] = 'category = %s';
            $where_values[] = $criteria['category'];
        }
        
        if (!empty($criteria['context'])) {
            $where_clauses[] = 'context = %s';
            $where_values[] = $criteria['context'];
        }
        
        if (!empty($criteria['severity_min'])) {
            $where_clauses[] = 'severity >= %d';
            $where_values[] = $criteria['severity_min'];
        }
        
        if (!empty($criteria['date_from'])) {
            $where_clauses[] = 'timestamp >= %s';
            $where_values[] = $criteria['date_from'];
        }
        
        if (!empty($criteria['date_to'])) {
            $where_clauses[] = 'timestamp <= %s';
            $where_values[] = $criteria['date_to'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $sql = "SELECT * FROM {$table_name} {$where_sql} ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        $where_values[] = $limit;
        $where_values[] = $offset;
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        // Decode JSON data
        foreach ($results as &$result) {
            if (!empty($result['data'])) {
                $decoded_data = json_decode($result['data'], true);
                if ($decoded_data) {
                    $result = array_merge($result, $decoded_data);
                }
                unset($result['data']);
            }
        }
        
        return $results;
    }
    
    /**
     * Get error statistics
     *
     * @param array $criteria Search criteria
     * @return array Error statistics
     */
    public static function get_error_statistics(array $criteria = []): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cpb_error_reports';
        
        $where_clauses = [];
        $where_values = [];
        
        if (!empty($criteria['date_from'])) {
            $where_clauses[] = 'timestamp >= %s';
            $where_values[] = $criteria['date_from'];
        }
        
        if (!empty($criteria['date_to'])) {
            $where_clauses[] = 'timestamp <= %s';
            $where_values[] = $criteria['date_to'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        // Get counts by category
        $category_sql = "SELECT category, COUNT(*) as count FROM {$table_name} {$where_sql} GROUP BY category";
        if (!empty($where_values)) {
            $category_sql = $wpdb->prepare($category_sql, $where_values);
        }
        $category_stats = $wpdb->get_results($category_sql, ARRAY_A);
        
        // Get counts by context
        $context_sql = "SELECT context, COUNT(*) as count FROM {$table_name} {$where_sql} GROUP BY context";
        if (!empty($where_values)) {
            $context_sql = $wpdb->prepare($context_sql, $where_values);
        }
        $context_stats = $wpdb->get_results($context_sql, ARRAY_A);
        
        // Get total count
        $total_sql = "SELECT COUNT(*) as total FROM {$table_name} {$where_sql}";
        if (!empty($where_values)) {
            $total_sql = $wpdb->prepare($total_sql, $where_values);
        }
        $total_count = $wpdb->get_var($total_sql);
        
        return [
            'total_errors' => (int) $total_count,
            'by_category' => $category_stats,
            'by_context' => $context_stats
        ];
    }
    
    /**
     * Clear old error reports
     *
     * @param int $days_to_keep Number of days to keep
     * @return int Number of deleted reports
     */
    public static function clear_old_reports(int $days_to_keep = 30): int {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cpb_error_reports';
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE timestamp < %s",
            $cutoff_date
        ));
        
        return (int) $deleted;
    }
}