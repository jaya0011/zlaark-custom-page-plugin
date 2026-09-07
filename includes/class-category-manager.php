<?php
/**
 * Category Manager class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages categories for products and content
 */
class Category_Manager {
    
    /**
     * Option name for storing categories
     */
    const OPTION_NAME = 'cpb_categories';
    
    /**
     * Get all categories (from WordPress)
     *
     * @return array
     */
    public static function get_categories(): array {
        // Get WordPress categories
        $wp_categories = get_categories([
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);
        
        $categories = [];
        foreach ($wp_categories as $cat) {
            $categories[] = [
                'id' => (string) $cat->term_id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'count' => $cat->count
            ];
        }
        
        return $categories;
    }
    
    /**
     * Get all WooCommerce product categories
     *
     * @return array
     */
    public static function get_woocommerce_categories(): array {
        // Check if WooCommerce is active
        if (!function_exists('wc_get_product_category_list')) {
            return [];
        }
        
        // Get WooCommerce product categories
        $wc_categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);
        
        if (is_wp_error($wc_categories)) {
            return [];
        }
        
        $categories = [];
        foreach ($wc_categories as $cat) {
            $categories[] = [
                'id' => (string) $cat->term_id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'count' => $cat->count,
                'parent' => $cat->parent,
                'description' => $cat->description
            ];
        }
        
        return $categories;
    }
    
    /**
     * Check if WooCommerce is active
     *
     * @return bool
     */
    public static function is_woocommerce_active(): bool {
        return class_exists('WooCommerce');
    }
    

    /**
     * Add a new category (to WordPress)
     *
     * @param string $name Category name
     * @return array|false Category data or false on failure
     */
    public static function add_category(string $name) {
        $name = trim($name);
        
        if (empty($name)) {
            return false;
        }
        
        // Check if category already exists
        $existing = get_term_by('name', $name, 'category');
        if ($existing) {
            return [
                'id' => (string) $existing->term_id,
                'name' => $existing->name,
                'slug' => $existing->slug,
                'count' => $existing->count
            ];
        }
        
        // Create new WordPress category
        $result = wp_insert_term($name, 'category');
        
        if (is_wp_error($result)) {
            return false;
        }
        
        $term = get_term($result['term_id'], 'category');
        
        return [
            'id' => (string) $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'count' => $term->count
        ];
    }
    
    /**
     * Delete a category (from WordPress)
     *
     * @param string $id Category ID
     * @return bool
     */
    public static function delete_category(string $id): bool {
        $result = wp_delete_term((int) $id, 'category');
        return !is_wp_error($result) && $result;
    }
    
    /**
     * Update a category
     *
     * @param string $id Category ID
     * @param string $name New name
     * @return bool
     */
    public static function update_category(string $id, string $name): bool {
        $name = trim($name);
        
        if (empty($name)) {
            return false;
        }
        
        $categories = self::get_categories();
        $updated = false;
        
        foreach ($categories as $key => $category) {
            if ($category['id'] === $id) {
                $categories[$key]['name'] = sanitize_text_field($name);
                $categories[$key]['slug'] = sanitize_title($name);
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            update_option(self::OPTION_NAME, $categories);
        }
        
        return $updated;
    }
    
    /**
     * Get category by ID (from WordPress)
     *
     * @param string $id Category ID
     * @return array|null
     */
    public static function get_category_by_id(string $id): ?array {
        $term = get_term((int) $id, 'category');
        
        if (is_wp_error($term) || !$term) {
            return null;
        }
        
        return [
            'id' => (string) $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'count' => $term->count
        ];
    }
    
    /**
     * Get categories by IDs (from WordPress)
     *
     * @param array $ids Array of category IDs
     * @return array
     */
    public static function get_categories_by_ids(array $ids): array {
        $result = [];
        
        foreach ($ids as $id) {
            $category = self::get_category_by_id($id);
            if ($category) {
                $result[] = $category;
            }
        }
        
        return $result;
    }
    
    /**
     * Get WooCommerce category by ID
     *
     * @param string $id Category ID
     * @return array|null
     */
    public static function get_woocommerce_category_by_id(string $id): ?array {
        if (!self::is_woocommerce_active()) {
            return null;
        }
        
        $term = get_term((int) $id, 'product_cat');
        
        if (is_wp_error($term) || !$term) {
            return null;
        }
        
        return [
            'id' => (string) $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'count' => $term->count,
            'parent' => $term->parent,
            'description' => $term->description
        ];
    }
    
    /**
     * Get WooCommerce categories by IDs
     *
     * @param array $ids Array of category IDs
     * @return array
     */
    public static function get_woocommerce_categories_by_ids(array $ids): array {
        $result = [];
        
        foreach ($ids as $id) {
            $category = self::get_woocommerce_category_by_id($id);
            if ($category) {
                $result[] = $category;
            }
        }
        
        return $result;
    }
}
