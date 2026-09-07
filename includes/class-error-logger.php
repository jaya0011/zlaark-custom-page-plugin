<?php
/**
 * Error logging class
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
 * Error logging and monitoring class
 */
class Error_Logger {
    
    /**
     * Log levels
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    
    /**
     * Log file path
     *
     * @var string
     */
    private static $log_file;
    
    /**
     * Maximum log file size in bytes (10MB)
     *
     * @var int
     */
    private static $max_file_size = 10485760;
    
    /**
     * Initialize logger
     */
    public static function init() {
        // Defer initialization if WordPress functions aren't available yet
        if (!function_exists('wp_upload_dir') || !function_exists('wp_mkdir_p')) {
            add_action('init', [self::class, 'delayed_init']);
            return;
        }
        
        self::setup_log_directory();
    }
    
    /**
     * Delayed initialization when WordPress is fully loaded
     */
    public static function delayed_init() {
        self::setup_log_directory();
    }
    
    /**
     * Set up log directory and file path
     */
    private static function setup_log_directory() {
        // Set log file path
        $upload_dir = wp_upload_dir();
        
        // Handle upload directory errors
        if (!$upload_dir || isset($upload_dir['error'])) {
            // Fallback to plugin directory if upload dir is not available
            $log_dir = CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'logs';
        } else {
            $log_dir = $upload_dir['basedir'] . '/custom-page-builder-logs';
        }
        
        // Create log directory if it doesn't exist
        if (!file_exists($log_dir)) {
            if (function_exists('wp_mkdir_p')) {
                wp_mkdir_p($log_dir);
            } else {
                mkdir($log_dir, 0755, true);
            }
            
            // Add .htaccess to protect log files
            $htaccess_content = "Order deny,allow\nDeny from all\n";
            file_put_contents($log_dir . '/.htaccess', $htaccess_content);
        }
        
        self::$log_file = $log_dir . '/error.log';
    }
    
    /**
     * Log an error
     *
     * @param string $level Log level
     * @param string $message Error message
     * @param array $context Additional context data
     * @param \Throwable|null $exception Exception object
     */
    public static function log(string $level, string $message, array $context = [], ?\Throwable $exception = null) {
        // Check if logging is enabled
        if (!self::is_logging_enabled()) {
            return;
        }
        
        // Initialize if not done yet
        if (!self::$log_file) {
            self::init();
        }
        
        // Prepare log entry
        $log_entry = self::format_log_entry($level, $message, $context, $exception);
        
        // Write to file
        self::write_to_file($log_entry);
        
        // Also log to WordPress debug log if WP_DEBUG_LOG is enabled
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log($log_entry);
        }
        
        // Send critical errors to admin email if configured
        if ($level === self::LEVEL_CRITICAL) {
            self::notify_admin($message, $context, $exception);
        }
    }
    
    /**
     * Log debug message
     *
     * @param string $message Debug message
     * @param array $context Additional context
     */
    public static function debug(string $message, array $context = []) {
        self::log(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Log info message
     *
     * @param string $message Info message
     * @param array $context Additional context
     */
    public static function info(string $message, array $context = []) {
        self::log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Log warning message
     *
     * @param string $message Warning message
     * @param array $context Additional context
     */
    public static function warning(string $message, array $context = []) {
        self::log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Log error message
     *
     * @param string $message Error message
     * @param array $context Additional context
     * @param \Throwable|null $exception Exception object
     */
    public static function error(string $message, array $context = [], ?\Throwable $exception = null) {
        self::log(self::LEVEL_ERROR, $message, $context, $exception);
    }
    
    /**
     * Log critical error
     *
     * @param string $message Critical error message
     * @param array $context Additional context
     * @param \Throwable|null $exception Exception object
     */
    public static function critical(string $message, array $context = [], ?\Throwable $exception = null) {
        self::log(self::LEVEL_CRITICAL, $message, $context, $exception);
    }
    
    /**
     * Log PageBuilder exception
     *
     * @param PageBuilderException $exception PageBuilder exception
     * @param string $level Log level
     */
    public static function log_exception(PageBuilderException $exception, string $level = self::LEVEL_ERROR) {
        self::log(
            $level,
            $exception->getMessage(),
            $exception->getContext(),
            $exception
        );
    }
    
    /**
     * Format log entry
     *
     * @param string $level Log level
     * @param string $message Error message
     * @param array $context Additional context
     * @param \Throwable|null $exception Exception object
     * @return string Formatted log entry
     */
    private static function format_log_entry(string $level, string $message, array $context, ?\Throwable $exception = null): string {
        $timestamp = current_time('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $log_data = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'message' => $message,
            'user_id' => $user_id,
            'request_uri' => $request_uri,
            'user_agent' => substr($user_agent, 0, 200), // Truncate long user agents
            'context' => $context
        ];
        
        // Add exception details if provided
        if ($exception) {
            $log_data['exception'] = [
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => self::format_stack_trace($exception->getTrace())
            ];
        }
        
        // Add memory usage and execution time
        $log_data['memory_usage'] = memory_get_usage(true);
        $log_data['peak_memory'] = memory_get_peak_usage(true);
        
        return json_encode($log_data, JSON_UNESCAPED_SLASHES) . "\n";
    }
    
    /**
     * Format stack trace for logging
     *
     * @param array $trace Stack trace
     * @return array Formatted trace
     */
    private static function format_stack_trace(array $trace): array {
        $formatted_trace = [];
        
        foreach (array_slice($trace, 0, 10) as $frame) { // Limit to 10 frames
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
            
            $formatted_trace[] = $formatted_frame;
        }
        
        return $formatted_trace;
    }
    
    /**
     * Write log entry to file
     *
     * @param string $log_entry Formatted log entry
     */
    private static function write_to_file(string $log_entry) {
        if (!self::$log_file) {
            return;
        }
        
        // Check file size and rotate if necessary
        if (file_exists(self::$log_file) && filesize(self::$log_file) > self::$max_file_size) {
            self::rotate_log_file();
        }
        
        // Write to file
        file_put_contents(self::$log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Rotate log file when it gets too large
     */
    private static function rotate_log_file() {
        if (!file_exists(self::$log_file)) {
            return;
        }
        
        $backup_file = self::$log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
        rename(self::$log_file, $backup_file);
        
        // Keep only the last 5 backup files
        $log_dir = dirname(self::$log_file);
        $backup_files = glob($log_dir . '/error.log.*.bak');
        
        if (count($backup_files) > 5) {
            // Sort by modification time and remove oldest files
            usort($backup_files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            $files_to_remove = array_slice($backup_files, 0, count($backup_files) - 5);
            foreach ($files_to_remove as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Notify admin of critical errors
     *
     * @param string $message Error message
     * @param array $context Error context
     * @param \Throwable|null $exception Exception object
     */
    private static function notify_admin(string $message, array $context, ?\Throwable $exception = null) {
        $settings = get_option('custom_page_builder_settings', []);
        
        // Check if admin notifications are enabled
        if (!($settings['enable_error_notifications'] ?? false)) {
            return;
        }
        
        $admin_email = $settings['error_notification_email'] ?? get_option('admin_email');
        
        if (!$admin_email) {
            return;
        }
        
        $subject = sprintf('[%s] Critical Error in Custom Page Builder', get_bloginfo('name'));
        
        $body = "A critical error occurred in the Custom Page Builder plugin:\n\n";
        $body .= "Error: {$message}\n\n";
        
        if ($exception) {
            $body .= "Exception: " . get_class($exception) . "\n";
            $body .= "File: {$exception->getFile()}:{$exception->getLine()}\n\n";
        }
        
        if (!empty($context)) {
            $body .= "Context:\n" . print_r($context, true) . "\n\n";
        }
        
        $body .= "Time: " . current_time('Y-m-d H:i:s') . "\n";
        $body .= "Site: " . home_url() . "\n";
        
        wp_mail($admin_email, $subject, $body);
    }
    
    /**
     * Check if logging is enabled
     *
     * @return bool
     */
    private static function is_logging_enabled(): bool {
        $settings = get_option('custom_page_builder_settings', []);
        return $settings['enable_error_logging'] ?? true;
    }
    
    /**
     * Get recent log entries
     *
     * @param int $limit Number of entries to return
     * @param string|null $level Filter by log level
     * @return array Log entries
     */
    public static function get_recent_logs(int $limit = 100, ?string $level = null): array {
        if (!self::$log_file || !file_exists(self::$log_file)) {
            return [];
        }
        
        $lines = file(self::$log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = [];
        
        // Process lines in reverse order (newest first)
        $lines = array_reverse($lines);
        
        foreach ($lines as $line) {
            if (count($logs) >= $limit) {
                break;
            }
            
            $log_data = json_decode($line, true);
            if (!$log_data) {
                continue;
            }
            
            // Filter by level if specified
            if ($level && strtolower($log_data['level']) !== strtolower($level)) {
                continue;
            }
            
            $logs[] = $log_data;
        }
        
        return $logs;
    }
    
    /**
     * Clear log file
     *
     * @return bool Success status
     */
    public static function clear_logs(): bool {
        if (!self::$log_file) {
            self::init();
        }
        
        return file_put_contents(self::$log_file, '') !== false;
    }
    
    /**
     * Get log file size
     *
     * @return int File size in bytes
     */
    public static function get_log_file_size(): int {
        if (!self::$log_file || !file_exists(self::$log_file)) {
            return 0;
        }
        
        return filesize(self::$log_file);
    }
}