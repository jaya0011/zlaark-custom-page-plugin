<?php
/**
 * Cache Manager class for handling WordPress transient caching
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cache Manager class for API response caching and performance optimization
 */
class Cache_Manager {
    
    /**
     * Cache key prefix
     *
     * @var string
     */
    const CACHE_PREFIX = 'cpb_cache_';
    
    /**
     * Default cache expiration (1 hour)
     *
     * @var int
     */
    const DEFAULT_EXPIRATION = 3600;
    
    /**
     * Cache statistics
     *
     * @var array
     */
    private static $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0
    ];
    
    /**
     * Get cached data
     *
     * @param string $key Cache key
     * @return mixed|false Cached data or false if not found
     */
    public static function get(string $key) {
        $cache_key = self::CACHE_PREFIX . $key;
        $data = get_transient($cache_key);
        
        if ($data !== false) {
            self::$stats['hits']++;
            return $data;
        }
        
        self::$stats['misses']++;
        return false;
    }
    
    /**
     * Set cached data
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $expiration Cache expiration in seconds
     * @return bool Success status
     */
    public static function set(string $key, $data, int $expiration = self::DEFAULT_EXPIRATION): bool {
        $cache_key = self::CACHE_PREFIX . $key;
        $result = set_transient($cache_key, $data, $expiration);
        
        if ($result) {
            self::$stats['sets']++;
        }
        
        return $result;
    }
    
    /**
     * Delete cached data
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public static function delete(string $key): bool {
        $cache_key = self::CACHE_PREFIX . $key;
        $result = delete_transient($cache_key);
        
        if ($result) {
            self::$stats['deletes']++;
        }
        
        return $result;
    }
    
    /**
     * Clear all plugin caches
     *
     * @return int Number of caches cleared
     */
    public static function clear_all(): int {
        global $wpdb;
        
        $prefix = '_transient_' . self::CACHE_PREFIX;
        $timeout_prefix = '_transient_timeout_' . self::CACHE_PREFIX;
        
        // Delete transients and their timeouts
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $prefix . '%',
                $timeout_prefix . '%'
            )
        );
        
        return $deleted;
    }
    
    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public static function get_stats(): array {
        return self::$stats;
    }
    
    /**
     * Reset cache statistics
     */
    public static function reset_stats(): void {
        self::$stats = [
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0
        ];
    }
    
    /**
     * Get cache key for page data
     *
     * @param int $page_id Page ID
     * @param bool $include_sections Whether to include sections
     * @return string Cache key
     */
    public static function get_page_cache_key(int $page_id, bool $include_sections = true): string {
        $key = "page_{$page_id}";
        if ($include_sections) {
            $key .= '_with_sections';
        }
        return $key;
    }
    
    /**
     * Get cache key for page list
     *
     * @param array $args Query arguments
     * @return string Cache key
     */
    public static function get_page_list_cache_key(array $args): string {
        $key_parts = [
            'page_list',
            'status_' . ($args['status'] ?? 'published'),
            'limit_' . ($args['limit'] ?? 20),
            'offset_' . ($args['offset'] ?? 0),
            'orderby_' . ($args['orderby'] ?? 'updated_at'),
            'order_' . ($args['order'] ?? 'DESC')
        ];
        
        if (!empty($args['search'])) {
            $key_parts[] = 'search_' . md5($args['search']);
        }
        
        if (!empty($args['author_id'])) {
            $key_parts[] = 'author_' . $args['author_id'];
        }
        
        return implode('_', $key_parts);
    }
    
    /**
     * Get cache key for sections by page
     *
     * @param int $page_id Page ID
     * @return string Cache key
     */
    public static function get_sections_cache_key(int $page_id): string {
        return "sections_page_{$page_id}";
    }
    
    /**
     * Invalidate page-related caches
     *
     * @param int $page_id Page ID
     */
    public static function invalidate_page_caches(int $page_id): void {
        // Clear specific page cache
        self::delete(self::get_page_cache_key($page_id, true));
        self::delete(self::get_page_cache_key($page_id, false));
        
        // Clear sections cache
        self::delete(self::get_sections_cache_key($page_id));
        
        // Clear page list caches (we need to clear all variations)
        self::invalidate_page_list_caches();
        
        // Fire action hook for custom cache invalidation
        do_action('cpb_page_cache_invalidated', $page_id);
    }
    
    /**
     * Invalidate all page list caches
     */
    public static function invalidate_page_list_caches(): void {
        global $wpdb;
        
        $prefix = '_transient_' . self::CACHE_PREFIX . 'page_list';
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $prefix . '%'
            )
        );
        
        // Also clear timeout entries
        $timeout_prefix = '_transient_timeout_' . self::CACHE_PREFIX . 'page_list';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $timeout_prefix . '%'
            )
        );
    }
    
    /**
     * Warm cache for frequently accessed pages
     *
     * @param array $page_ids Array of page IDs to warm
     */
    public static function warm_cache(array $page_ids = []): void {
        if (empty($page_ids)) {
            // Get most recently updated published pages
            $pages = \Custom_Page_Builder\Models\CustomPage::get_all([
                'status' => 'published',
                'limit' => 10,
                'orderby' => 'updated_at',
                'order' => 'DESC'
            ]);
            
            $page_ids = array_map(function($page) {
                return $page->get_id();
            }, $pages);
        }
        
        foreach ($page_ids as $page_id) {
            self::warm_page_cache($page_id);
        }
        
        // Fire action hook for custom cache warming
        do_action('cpb_cache_warmed', $page_ids);
    }
    
    /**
     * Warm cache for a specific page
     *
     * @param int $page_id Page ID
     */
    public static function warm_page_cache(int $page_id): void {
        $page = \Custom_Page_Builder\Models\CustomPage::find($page_id);
        if (!$page || $page->get_status() !== 'published') {
            return;
        }
        
        // Cache page data without sections
        $cache_key = self::get_page_cache_key($page_id, false);
        self::set($cache_key, $page->to_api_response(), self::DEFAULT_EXPIRATION);
        
        // Cache sections separately
        $section_manager = new \Custom_Page_Builder\Admin\Section_Manager();
        $sections = $section_manager->get_sections_by_page($page_id);
        
        $sections_data = [];
        foreach ($sections as $section) {
            $sections_data[] = $section->to_api_response();
        }
        
        $sections_cache_key = self::get_sections_cache_key($page_id);
        self::set($sections_cache_key, $sections_data, self::DEFAULT_EXPIRATION);
        
        // Cache complete page with sections
        $page->set_sections($sections_data);
        $complete_cache_key = self::get_page_cache_key($page_id, true);
        self::set($complete_cache_key, $page->to_api_response(), self::DEFAULT_EXPIRATION);
    }
    
    /**
     * Get cache size information
     *
     * @return array Cache size information
     */
    public static function get_cache_info(): array {
        global $wpdb;
        
        $prefix = '_transient_' . self::CACHE_PREFIX;
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) as count, SUM(LENGTH(option_value)) as size 
                 FROM {$wpdb->options} 
                 WHERE option_name LIKE %s",
                $prefix . '%'
            ),
            ARRAY_A
        );
        
        return [
            'count' => (int) ($result['count'] ?? 0),
            'size_bytes' => (int) ($result['size'] ?? 0),
            'size_mb' => round(((int) ($result['size'] ?? 0)) / 1024 / 1024, 2)
        ];
    }
    
    /**
     * Check if caching is enabled
     *
     * @return bool
     */
    public static function is_caching_enabled(): bool {
        $settings = WordPress_Helper::safe_get_option('custom_page_builder_settings', []);
        return $settings['enable_caching'] ?? true;
    }
    
    /**
     * Get cache expiration time for different content types
     *
     * @param string $type Cache type (page, list, sections)
     * @return int Expiration time in seconds
     */
    public static function get_cache_expiration(string $type): int {
        $settings = WordPress_Helper::safe_get_option('custom_page_builder_settings', []);
        
        $expirations = [
            'page' => $settings['cache_expiration_page'] ?? 3600, // 1 hour
            'list' => $settings['cache_expiration_list'] ?? 1800, // 30 minutes
            'sections' => $settings['cache_expiration_sections'] ?? 3600, // 1 hour
        ];
        
        return $expirations[$type] ?? self::DEFAULT_EXPIRATION;
    }
}