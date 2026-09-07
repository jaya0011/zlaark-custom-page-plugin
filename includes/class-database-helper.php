<?php
/**
 * Database Helper class for safe database operations
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Helper class that provides safe database access methods
 */
class Database_Helper {
    
    /**
     * Get WordPress database instance safely
     *
     * @return \wpdb|null
     * @throws Database_Exception If database is not available
     */
    public static function get_wpdb() {
        global $wpdb;
        
        if (!$wpdb) {
            throw new Database_Exception(
                __('Database connection not available', 'custom-page-builder'),
                'get_connection'
            );
        }
        
        return $wpdb;
    }
    
    /**
     * Get table name with prefix
     *
     * @param string $table_name Base table name
     * @return string Full table name with prefix
     * @throws Database_Exception If database is not available
     */
    public static function get_table_name(string $table_name): string {
        $wpdb = self::get_wpdb();
        return $wpdb->prefix . $table_name;
    }
    
    /**
     * Safe database insert
     *
     * @param string $table_name Table name (without prefix)
     * @param array $data Data to insert
     * @return int Insert ID
     * @throws Database_Exception If insert fails
     */
    public static function safe_insert(string $table_name, array $data): int {
        $wpdb = self::get_wpdb();
        $full_table_name = self::get_table_name($table_name);
        
        $result = $wpdb->insert($full_table_name, $data);
        
        if ($result === false) {
            throw new Database_Exception(
                sprintf(__('Failed to insert data into %s table', 'custom-page-builder'), $table_name),
                'insert',
                $table_name,
                ['wpdb_error' => $wpdb->last_error]
            );
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Safe database update
     *
     * @param string $table_name Table name (without prefix)
     * @param array $data Data to update
     * @param array $where Where conditions
     * @return int Number of rows affected
     * @throws Database_Exception If update fails
     */
    public static function safe_update(string $table_name, array $data, array $where): int {
        $wpdb = self::get_wpdb();
        $full_table_name = self::get_table_name($table_name);
        
        $result = $wpdb->update($full_table_name, $data, $where);
        
        if ($result === false) {
            throw new Database_Exception(
                sprintf(__('Failed to update data in %s table', 'custom-page-builder'), $table_name),
                'update',
                $table_name,
                ['wpdb_error' => $wpdb->last_error]
            );
        }
        
        return $result;
    }
    
    /**
     * Safe database delete
     *
     * @param string $table_name Table name (without prefix)
     * @param array $where Where conditions
     * @return int Number of rows affected
     * @throws Database_Exception If delete fails
     */
    public static function safe_delete(string $table_name, array $where): int {
        $wpdb = self::get_wpdb();
        $full_table_name = self::get_table_name($table_name);
        
        $result = $wpdb->delete($full_table_name, $where);
        
        if ($result === false) {
            throw new Database_Exception(
                sprintf(__('Failed to delete data from %s table', 'custom-page-builder'), $table_name),
                'delete',
                $table_name,
                ['wpdb_error' => $wpdb->last_error]
            );
        }
        
        return $result;
    }
    
    /**
     * Safe database query
     *
     * @param string $query SQL query
     * @param array $args Query arguments for preparation
     * @return array|null Query results
     * @throws Database_Exception If query fails
     */
    public static function safe_query(string $query, array $args = []): ?array {
        $wpdb = self::get_wpdb();
        
        // Prepare query if arguments provided
        if (!empty($args)) {
            $query = $wpdb->prepare($query, $args);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        if ($wpdb->last_error) {
            throw new Database_Exception(
                sprintf(__('Database query failed: %s', 'custom-page-builder'), $wpdb->last_error),
                'query',
                null,
                ['query' => $query, 'wpdb_error' => $wpdb->last_error]
            );
        }
        
        return $results;
    }
    
    /**
     * Safe get single row
     *
     * @param string $query SQL query
     * @param array $args Query arguments for preparation
     * @return array|null Single row result
     * @throws Database_Exception If query fails
     */
    public static function safe_get_row(string $query, array $args = []): ?array {
        $wpdb = self::get_wpdb();
        
        // Prepare query if arguments provided
        if (!empty($args)) {
            $query = $wpdb->prepare($query, $args);
        }
        
        $result = $wpdb->get_row($query, ARRAY_A);
        
        if ($wpdb->last_error) {
            throw new Database_Exception(
                sprintf(__('Database query failed: %s', 'custom-page-builder'), $wpdb->last_error),
                'get_row',
                null,
                ['query' => $query, 'wpdb_error' => $wpdb->last_error]
            );
        }
        
        return $result;
    }
    
    /**
     * Safe get single variable
     *
     * @param string $query SQL query
     * @param array $args Query arguments for preparation
     * @return mixed Single variable result
     * @throws Database_Exception If query fails
     */
    public static function safe_get_var(string $query, array $args = []) {
        $wpdb = self::get_wpdb();
        
        // Prepare query if arguments provided
        if (!empty($args)) {
            $query = $wpdb->prepare($query, $args);
        }
        
        $result = $wpdb->get_var($query);
        
        if ($wpdb->last_error) {
            throw new Database_Exception(
                sprintf(__('Database query failed: %s', 'custom-page-builder'), $wpdb->last_error),
                'get_var',
                null,
                ['query' => $query, 'wpdb_error' => $wpdb->last_error]
            );
        }
        
        return $result;
    }
    
    /**
     * Check if table exists
     *
     * @param string $table_name Table name (without prefix)
     * @return bool True if table exists
     */
    public static function table_exists(string $table_name): bool {
        try {
            $wpdb = self::get_wpdb();
            $full_table_name = self::get_table_name($table_name);
            
            $result = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $full_table_name
            ));
            
            return $result === $full_table_name;
        } catch (Database_Exception $e) {
            return false;
        }
    }
    
    /**
     * Get database charset and collation
     *
     * @return array Charset and collation info
     */
    public static function get_charset_collate(): array {
        try {
            $wpdb = self::get_wpdb();
            return [
                'charset' => $wpdb->charset,
                'collate' => $wpdb->collate,
                'charset_collate' => $wpdb->get_charset_collate()
            ];
        } catch (Database_Exception $e) {
            return [
                'charset' => 'utf8mb4',
                'collate' => 'utf8mb4_unicode_ci',
                'charset_collate' => 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            ];
        }
    }
}