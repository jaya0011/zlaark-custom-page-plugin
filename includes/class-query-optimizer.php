<?php
/**
 * Database Query Optimizer class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Query Optimizer class for database performance optimization
 */
class Query_Optimizer {
    
    /**
     * Query log for monitoring
     *
     * @var array
     */
    private static $query_log = [];
    
    /**
     * Enable query logging
     *
     * @var bool
     */
    private static $logging_enabled = false;
    
    /**
     * Initialize query optimization
     */
    public static function init() {
        // Enable query logging if in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            self::enable_logging();
        }
        
        // Add query optimization hooks
        WordPress_Helper::safe_add_action('init', [__CLASS__, 'optimize_database_queries']);
        WordPress_Helper::safe_add_action('admin_init', [__CLASS__, 'check_database_performance']);
    }
    
    /**
     * Enable query logging
     */
    public static function enable_logging() {
        self::$logging_enabled = true;
        
        // Hook into WordPress query logging
        add_filter('query', [__CLASS__, 'log_query']);
    }
    
    /**
     * Disable query logging
     */
    public static function disable_logging() {
        self::$logging_enabled = false;
        remove_filter('query', [__CLASS__, 'log_query']);
    }
    
    /**
     * Log database queries for monitoring
     *
     * @param string $query SQL query
     * @return string
     */
    public static function log_query($query) {
        if (!self::$logging_enabled) {
            return $query;
        }
        
        // Only log our plugin queries
        if (strpos($query, 'custom_page') !== false) {
            $start_time = microtime(true);
            
            // Store query info
            self::$query_log[] = [
                'query' => $query,
                'start_time' => $start_time,
                'backtrace' => wp_debug_backtrace_summary()
            ];
        }
        
        return $query;
    }
    
    /**
     * Get optimized page query with proper pagination
     *
     * @param array $args Query arguments
     * @return array
     */
    public static function get_optimized_pages_query(array $args = []) {
        // Database access handled by Database_Helper
        
        $defaults = [
            'status' => 'published',
            'author_id' => null,
            'search' => null,
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'updated_at',
            'order' => 'DESC',
            'include_meta' => false
        ];
        
        $args = wp_parse_args($args, $defaults);
        $table_name = Installer::get_table_names()['pages'];
        
        // Build WHERE clauses
        $where_clauses = [];
        $where_values = [];
        
        if ($args['status']) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        if ($args['author_id']) {
            $where_clauses[] = 'author_id = %d';
            $where_values[] = $args['author_id'];
        }
        
        if ($args['search']) {
            $where_clauses[] = '(title LIKE %s OR slug LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
        
        // Validate orderby to prevent SQL injection
        $allowed_orderby = ['id', 'title', 'slug', 'status', 'created_at', 'updated_at', 'published_at'];
        if (!in_array($args['orderby'], $allowed_orderby)) {
            $args['orderby'] = 'updated_at';
        }
        
        // Validate order
        $args['order'] = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Build main query
        $sql = "SELECT * FROM {$table_name} {$where_sql} ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return $sql;
    }
    
    /**
     * Get optimized sections query for a page
     *
     * @param int $page_id Page ID
     * @param bool $include_config Whether to include full config
     * @return string
     */
    public static function get_optimized_sections_query(int $page_id, bool $include_config = true) {
        // Database access handled by Database_Helper
        
        $table_name = Installer::get_table_names()['sections'];
        
        if ($include_config) {
            $select = '*';
        } else {
            $select = 'id, page_id, section_type, section_order, created_at, updated_at';
        }
        
        return $wpdb->prepare(
            "SELECT {$select} FROM {$table_name} WHERE page_id = %d ORDER BY section_order ASC",
            $page_id
        );
    }
    
    /**
     * Get batch sections for multiple pages (optimized)
     *
     * @param array $page_ids Array of page IDs
     * @return array
     */
    public static function get_batch_sections(array $page_ids) {
        // Database access handled by Database_Helper
        
        if (empty($page_ids)) {
            return [];
        }
        
        $table_name = Installer::get_table_names()['sections'];
        $placeholders = implode(',', array_fill(0, count($page_ids), '%d'));
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE page_id IN ({$placeholders}) ORDER BY page_id ASC, section_order ASC",
            $page_ids
        );
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        // Group by page_id for easier processing
        $grouped = [];
        foreach ($results as $row) {
            $grouped[$row['page_id']][] = $row;
        }
        
        return $grouped;
    }
    
    /**
     * Optimize database queries
     */
    public static function optimize_database_queries() {
        // Database access handled by Database_Helper
        
        // Add custom indexes if they don't exist
        self::add_missing_indexes();
        
        // Optimize table structure
        self::optimize_table_structure();
    }
    
    /**
     * Add missing database indexes for better performance
     */
    private static function add_missing_indexes() {
        // Database access handled by Database_Helper
        
        $tables = Installer::get_table_names();
        
        // Check and add indexes for pages table
        $pages_indexes = [
            'idx_status_updated' => "ALTER TABLE {$tables['pages']} ADD INDEX idx_status_updated (status, updated_at)",
            'idx_author_status' => "ALTER TABLE {$tables['pages']} ADD INDEX idx_author_status (author_id, status)",
            'idx_title_search' => "ALTER TABLE {$tables['pages']} ADD INDEX idx_title_search (title(50))"
        ];
        
        foreach ($pages_indexes as $index_name => $sql) {
            if (!self::index_exists($tables['pages'], $index_name)) {
                $wpdb->query($sql);
            }
        }
        
        // Check and add indexes for sections table
        $sections_indexes = [
            'idx_type_order' => "ALTER TABLE {$tables['sections']} ADD INDEX idx_type_order (section_type, section_order)",
            'idx_updated_at' => "ALTER TABLE {$tables['sections']} ADD INDEX idx_updated_at (updated_at)"
        ];
        
        foreach ($sections_indexes as $index_name => $sql) {
            if (!self::index_exists($tables['sections'], $index_name)) {
                $wpdb->query($sql);
            }
        }
    }
    
    /**
     * Check if an index exists on a table
     *
     * @param string $table_name Table name
     * @param string $index_name Index name
     * @return bool
     */
    private static function index_exists(string $table_name, string $index_name): bool {
        // Database access handled by Database_Helper
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SHOW INDEX FROM {$table_name} WHERE Key_name = %s",
                $index_name
            )
        );
        
        return $result !== null;
    }
    
    /**
     * Optimize table structure
     */
    private static function optimize_table_structure() {
        // Database access handled by Database_Helper
        
        $tables = Installer::get_table_names();
        
        // Optimize tables
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
    }
    
    /**
     * Check database performance and suggest optimizations
     */
    public static function check_database_performance() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $performance_issues = self::analyze_performance();
        
        if (!empty($performance_issues)) {
            WordPress_Helper::safe_add_action('admin_notices', function() use ($performance_issues) {
                echo '<div class="notice notice-warning"><p>';
                echo '<strong>' . __('Custom Page Builder Performance Issues:', 'custom-page-builder') . '</strong><br>';
                foreach ($performance_issues as $issue) {
                    echo '• ' . esc_html($issue) . '<br>';
                }
                echo '</p></div>';
            });
        }
    }
    
    /**
     * Analyze database performance
     *
     * @return array Performance issues
     */
    private static function analyze_performance(): array {
        // Database access handled by Database_Helper
        
        $issues = [];
        $tables = Installer::get_table_names();
        
        // Check table sizes
        foreach ($tables as $table_name => $table) {
            $size_info = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT 
                        table_rows as row_count,
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
                     FROM information_schema.TABLES 
                     WHERE table_schema = %s AND table_name = %s",
                    DB_NAME,
                    $table
                )
            );
            
            if ($size_info) {
                // Warn if table is getting large
                if ($size_info->size_mb > 100) {
                    $issues[] = sprintf(
                        __('Table %s is large (%s MB) - consider archiving old data', 'custom-page-builder'),
                        $table_name,
                        $size_info->size_mb
                    );
                }
                
                // Warn if too many rows without proper pagination
                if ($size_info->row_count > 10000) {
                    $issues[] = sprintf(
                        __('Table %s has many rows (%s) - ensure proper pagination is used', 'custom-page-builder'),
                        $table_name,
                        number_format($size_info->row_count)
                    );
                }
            }
        }
        
        // Check for slow queries (if logging is enabled)
        if (self::$logging_enabled && !empty(self::$query_log)) {
            $slow_queries = array_filter(self::$query_log, function($log) {
                return isset($log['execution_time']) && $log['execution_time'] > 1.0; // 1 second
            });
            
            if (!empty($slow_queries)) {
                $issues[] = sprintf(
                    __('Found %d slow queries - check query optimization', 'custom-page-builder'),
                    count($slow_queries)
                );
            }
        }
        
        return $issues;
    }
    
    /**
     * Get query statistics
     *
     * @return array
     */
    public static function get_query_stats(): array {
        if (!self::$logging_enabled) {
            return ['error' => 'Query logging is not enabled'];
        }
        
        $total_queries = count(self::$query_log);
        $total_time = 0;
        $slow_queries = 0;
        
        foreach (self::$query_log as $log) {
            if (isset($log['execution_time'])) {
                $total_time += $log['execution_time'];
                if ($log['execution_time'] > 0.5) {
                    $slow_queries++;
                }
            }
        }
        
        return [
            'total_queries' => $total_queries,
            'total_time' => round($total_time, 4),
            'average_time' => $total_queries > 0 ? round($total_time / $total_queries, 4) : 0,
            'slow_queries' => $slow_queries,
            'queries' => self::$query_log
        ];
    }
    
    /**
     * Clear query log
     */
    public static function clear_query_log() {
        self::$query_log = [];
    }
    
    /**
     * Get database table statistics
     *
     * @return array
     */
    public static function get_table_stats(): array {
        // Database access handled by Database_Helper
        
        $tables = Installer::get_table_names();
        $stats = [];
        
        foreach ($tables as $table_name => $table) {
            $table_info = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT 
                        table_rows as row_count,
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb,
                        ROUND((data_length / 1024 / 1024), 2) as data_mb,
                        ROUND((index_length / 1024 / 1024), 2) as index_mb
                     FROM information_schema.TABLES 
                     WHERE table_schema = %s AND table_name = %s",
                    DB_NAME,
                    $table
                )
            );
            
            if ($table_info) {
                $stats[$table_name] = [
                    'rows' => (int) $table_info->row_count,
                    'size_mb' => (float) $table_info->size_mb,
                    'data_mb' => (float) $table_info->data_mb,
                    'index_mb' => (float) $table_info->index_mb
                ];
            }
        }
        
        return $stats;
    }
    
    /**
     * Optimize specific query patterns
     */
    public static function optimize_common_queries() {
        // This method can be called to pre-warm commonly used queries
        // or to set up query result caching for expensive operations
        
        // Example: Pre-calculate frequently accessed data
        self::cache_popular_pages();
        self::cache_section_counts();
    }
    
    /**
     * Cache popular pages data
     */
    private static function cache_popular_pages() {
        if (!class_exists('Custom_Page_Builder\Cache_Manager')) {
            return;
        }
        
        // Get most recently updated published pages
        $pages = \Custom_Page_Builder\Models\CustomPage::get_all([
            'status' => 'published',
            'limit' => 5,
            'orderby' => 'updated_at',
            'order' => 'DESC'
        ]);
        
        foreach ($pages as $page) {
            \Custom_Page_Builder\Cache_Manager::warm_page_cache($page->get_id());
        }
    }
    
    /**
     * Cache section counts for dashboard
     */
    private static function cache_section_counts() {
        // Database access handled by Database_Helper
        
        if (!class_exists('Custom_Page_Builder\Cache_Manager')) {
            return;
        }
        
        $table_name = Installer::get_table_names()['sections'];
        
        // Get section type counts
        $counts = $wpdb->get_results(
            "SELECT section_type, COUNT(*) as count FROM {$table_name} GROUP BY section_type",
            ARRAY_A
        );
        
        $section_counts = [];
        foreach ($counts as $row) {
            $section_counts[$row['section_type']] = (int) $row['count'];
        }
        
        \Custom_Page_Builder\Cache_Manager::set('section_type_counts', $section_counts, 3600);
    }
}