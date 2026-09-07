<?php
/**
 * Section factory for testing
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Tests\Factories;

use Custom_Page_Builder\Models\Section;
use Custom_Page_Builder\Models\SectionFactory as ModelSectionFactory;

/**
 * Factory class for creating test sections
 */
class SectionFactory {
    
    /**
     * Create a test section
     *
     * @param string $type Section type
     * @param array $args Section arguments
     * @return Section
     */
    public function create(string $type, array $args = []) {
        $defaults = [
            'page_id' => 1,
            'section_type' => $type,
            'section_order' => 0,
            'config' => $this->get_default_config($type)
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        return ModelSectionFactory::create_section($type, $args);
    }
    
    /**
     * Create a testimonials section
     *
     * @param array $args Section arguments
     * @return Section
     */
    public function create_testimonials(array $args = []) {
        $config = [
            'title' => 'Customer Testimonials',
            'layout' => 'grid',
            'columns' => 3,
            'testimonials' => [
                [
                    'rating' => 5,
                    'text' => 'Great product!',
                    'author_name' => 'John Doe',
                    'author_title' => 'CEO',
                    'company' => 'Test Corp'
                ]
            ]
        ];
        
        $args['config'] = array_merge($config, $args['config'] ?? []);
        
        return $this->create('testimonials', $args);
    }
    
    /**
     * Create a product grid section
     *
     * @param array $args Section arguments
     * @return Section
     */
    public function create_product_grid(array $args = []) {
        $config = [
            'title' => 'Featured Products',
            'columns' => 4,
            'products' => [
                [
                    'title' => 'Test Product',
                    'price' => '$99.99',
                    'description' => 'Test product description',
                    'image' => ''
                ]
            ]
        ];
        
        $args['config'] = array_merge($config, $args['config'] ?? []);
        
        return $this->create('product_grid', $args);
    }
    
    /**
     * Create a hero banner section
     *
     * @param array $args Section arguments
     * @return Section
     */
    public function create_hero_banner(array $args = []) {
        $config = [
            'title' => 'Welcome to Our Site',
            'subtitle' => 'Discover amazing products',
            'background_image' => '',
            'cta_text' => 'Shop Now',
            'cta_url' => '/shop'
        ];
        
        $args['config'] = array_merge($config, $args['config'] ?? []);
        
        return $this->create('hero_banner', $args);
    }
    
    /**
     * Get default configuration for section type
     *
     * @param string $type Section type
     * @return array
     */
    private function get_default_config(string $type) {
        switch ($type) {
            case 'testimonials':
                return [
                    'title' => 'Testimonials',
                    'testimonials' => []
                ];
                
            case 'product_grid':
                return [
                    'title' => 'Products',
                    'products' => []
                ];
                
            case 'hero_banner':
                return [
                    'title' => 'Hero Banner',
                    'subtitle' => ''
                ];
                
            case 'category_showcase':
                return [
                    'title' => 'Categories',
                    'categories' => []
                ];
                
            case 'content_block':
                return [
                    'title' => 'Content Block',
                    'content' => ''
                ];
                
            default:
                return [
                    'title' => 'Section',
                    'visible' => true
                ];
        }
    }
}