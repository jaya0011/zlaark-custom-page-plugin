<?php
/**
 * Page factory for testing
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Tests\Factories;

use Custom_Page_Builder\Models\CustomPage;

/**
 * Factory class for creating test pages
 */
class PageFactory {
    
    /**
     * Create a test page
     *
     * @param array $args Page arguments
     * @return CustomPage
     */
    public function create(array $args = []) {
        $defaults = [
            'title' => 'Test Page ' . wp_rand(1000, 9999),
            'slug' => 'test-page-' . wp_rand(1000, 9999),
            'status' => 'draft',
            'author_id' => get_current_user_id(),
            'meta_data' => []
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        return CustomPage::create($args);
    }
    
    /**
     * Create a published page
     *
     * @param array $args Page arguments
     * @return CustomPage
     */
    public function create_published(array $args = []) {
        $args['status'] = 'published';
        return $this->create($args);
    }
    
    /**
     * Create a scheduled page
     *
     * @param array $args Page arguments
     * @return CustomPage
     */
    public function create_scheduled(array $args = []) {
        $args['status'] = 'scheduled';
        $args['scheduled_at'] = date('Y-m-d H:i:s', strtotime('+1 hour'));
        return $this->create($args);
    }
    
    /**
     * Create multiple pages
     *
     * @param int $count Number of pages to create
     * @param array $args Page arguments
     * @return array Array of CustomPage objects
     */
    public function create_many(int $count, array $args = []) {
        $pages = [];
        
        for ($i = 0; $i < $count; $i++) {
            $page_args = $args;
            $page_args['title'] = ($args['title'] ?? 'Test Page') . ' ' . ($i + 1);
            $page_args['slug'] = ($args['slug'] ?? 'test-page') . '-' . ($i + 1);
            
            $pages[] = $this->create($page_args);
        }
        
        return $pages;
    }
}