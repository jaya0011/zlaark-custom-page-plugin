<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for WordPress hooks functionality
 */
class WordPressHooksTest extends TestCase {
    
    private $test_plugin_path;
    private $mock_hooks = [];
    
    protected function setUp(): void {
        parent::setUp();
        $this->test_plugin_path = dirname(__DIR__, 2);
        $this->setupWordPressMocks();
    }
    
    /**
     * Setup WordPress function mocks for testing
     */
    private function setupWordPressMocks() {
        // Mock WordPress functions if they don't exist
        if (!function_exists('add_action')) {
            function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
                global $wp_hooks_test_instance;
                if ($wp_hooks_test_instance) {
                    $wp_hooks_test_instance->addMockHook('action', $hook, $callback, $priority, $accepted_args);
                }
                return true;
            }
        }
        
        if (!function_exists('add_filter')) {
            function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
                global $wp_hooks_test_instance;
                if ($wp_hooks_test_instance) {
                    $wp_hooks_test_instance->addMockHook('filter', $hook, $callback, $priority, $accepted_args);
                }
                return true;
            }
        }
        
        if (!function_exists('register_activation_hook')) {
            function register_activation_hook($file, $callback) {
                global $wp_hooks_test_instance;
                if ($wp_hooks_test_instance) {
                    $wp_hooks_test_instance->addMockHook('activation', $file, $callback);
                }
                return true;
            }
        }
        
        if (!function_exists('wp_die')) {
            function wp_die($message, $title = '', $args = []) {
                throw new Exception("WordPress Error: {$message}");
            }
        }
        
        // Set global reference for mock tracking
        global $wp_hooks_test_instance;
        $wp_hooks_test_instance = $this;
    }
    
    /**
     * Add mock hook for testing
     */
    public function addMockHook($type, $hook, $callback, $priority = 10, $accepted_args = 1) {
        if (!isset($this->mock_hooks[$type])) {
            $this->mock_hooks[$type] = [];
        }
        
        $this->mock_hooks[$type][] = [
            'hook' => $hook,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args
        ];
    }
    
    /**
     * Test minimal test plugin WordPress hooks integration
     */
    public function testMinimalTestPluginHooks() {
        $plugin_path = $this->test_plugin_path . '/minimal-test-plugin.php';
        
        if (!file_exists($plugin_path)) {
            $this->markTestSkipped('minimal-test-plugin.php not found');
        }
        
        // Clear previous hooks
        $this->mock_hooks = [];
        
        // Include the plugin
        include_once $plugin_path;
        
        // Verify hooks were registered
        $this->assertNotEmpty($this->mock_hooks, 'No WordPress hooks registered');
        
        // Check for plugins_loaded hook
        $plugins_loaded_found = false;
        if (isset($this->mock_hooks['action'])) {
            foreach ($this->mock_hooks['action'] as $hook) {
                if ($hook['hook'] === 'plugins_loaded') {
                    $plugins_loaded_found = true;
                    break;
                }
            }
        }
        
        $this->assertTrue($plugins_loaded_found, 'plugins_loaded hook not found');
    }
    
    /**
     * Test emergency plugin WordPress hooks integration
     */
    public function testEmergencyPluginHooks() {
        $plugin_path = $this->test_plugin_path . '/custom-page-builder-emergency.php';
        
        if (!file_exists($plugin_path)) {
            $this->markTestSkipped('custom-page-builder-emergency.php not found');
        }
        
        // Clear previous hooks
        $this->mock_hooks = [];
        
        // Include the plugin
        include_once $plugin_path;
        
        // Verify hooks were registered
        $this->assertNotEmpty($this->mock_hooks, 'No WordPress hooks registered');
        
        // Check for proper hook usage
        $has_action_hooks = isset($this->mock_hooks['action']) && !empty($this->mock_hooks['action']);
        $has_activation_hooks = isset($this->mock_hooks['activation']) && !empty($this->mock_hooks['activation']);
        
        $this->assertTrue($has_action_hooks || $has_activation_hooks, 
            'No action or activation hooks found');
    }
    
    /**
     * Test main plugin WordPress hooks integration
     */
    public function testMainPluginHooks() {
        $plugin_path = $this->test_plugin_path . '/custom-page-builder.php';
        
        if (!file_exists($plugin_path)) {
            $this->markTestSkipped('custom-page-builder.php not found');
        }
        
        // Clear previous hooks
        $this->mock_hooks = [];
        
        // Include the plugin
        include_once $plugin_path;
        
        // Verify hooks were registered
        $this->assertNotEmpty($this->mock_hooks, 'No WordPress hooks registered');
        
        // Check for activation hook
        $activation_hook_found = false;
        if (isset($this->mock_hooks['activation'])) {
            foreach ($this->mock_hooks['activation'] as $hook) {
                if (strpos($hook['hook'], 'custom-page-builder.php') !== false) {
                    $activation_hook_found = true;
                    break;
                }
            }
        }
        
        $this->assertTrue($activation_hook_found, 'Activation hook not found for main plugin');
    }
    
    /**
     * Test safe mode plugin WordPress hooks integration
     */
    public function testSafeModePluginHooks() {
        $plugin_path = $this->test_plugin_path . '/safe-mode-plugin.php';
        
        if (!file_exists($plugin_path)) {
            $this->markTestSkipped('safe-mode-plugin.php not found');
        }
        
        // Clear previous hooks
        $this->mock_hooks = [];
        
        // Include the plugin
        include_once $plugin_path;
        
        // Verify hooks were registered (safe mode should have minimal hooks)
        $total_hooks = 0;
        foreach ($this->mock_hooks as $type => $hooks) {
            $total_hooks += count($hooks);
        }
        
        // Safe mode should have at least one hook but not too many
        $this->assertGreaterThanOrEqual(1, $total_hooks, 'Safe mode plugin should have at least one hook');
        $this->assertLessThanOrEqual(5, $total_hooks, 'Safe mode plugin should have minimal hooks');
    }
    
    /**
     * Test hook callback validation
     */
    public function testHookCallbackValidation() {
        $plugin_files = [
            'minimal-test-plugin.php',
            'custom-page-builder-emergency.php',
            'custom-page-builder.php',
            'safe-mode-plugin.php'
        ];
        
        foreach ($plugin_files as $plugin_file) {
            $plugin_path = $this->test_plugin_path . '/' . $plugin_file;
            
            if (!file_exists($plugin_path)) {
                continue;
            }
            
            // Clear previous hooks
            $this->mock_hooks = [];
            
            // Include the plugin
            include_once $plugin_path;
            
            // Validate all registered callbacks
            foreach ($this->mock_hooks as $type => $hooks) {
                foreach ($hooks as $hook) {
                    $callback = $hook['callback'];
                    
                    if (is_string($callback)) {
                        $this->assertTrue(
                            function_exists($callback),
                            "Callback function '{$callback}' does not exist in {$plugin_file}"
                        );
                    } elseif (is_array($callback) && count($callback) === 2) {
                        $class = $callback[0];
                        $method = $callback[1];
                        
                        if (is_string($class)) {
                            $this->assertTrue(
                                class_exists($class),
                                "Callback class '{$class}' does not exist in {$plugin_file}"
                            );
                            
                            if (class_exists($class)) {
                                $this->assertTrue(
                                    method_exists($class, $method),
                                    "Callback method '{$method}' does not exist in class '{$class}' in {$plugin_file}"
                                );
                            }
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Test hook priority validation
     */
    public function testHookPriorityValidation() {
        $plugin_files = [
            'minimal-test-plugin.php',
            'custom-page-builder-emergency.php',
            'custom-page-builder.php',
            'safe-mode-plugin.php'
        ];
        
        foreach ($plugin_files as $plugin_file) {
            $plugin_path = $this->test_plugin_path . '/' . $plugin_file;
            
            if (!file_exists($plugin_path)) {
                continue;
            }
            
            // Clear previous hooks
            $this->mock_hooks = [];
            
            // Include the plugin
            include_once $plugin_path;
            
            // Validate hook priorities
            foreach ($this->mock_hooks as $type => $hooks) {
                foreach ($hooks as $hook) {
                    $priority = $hook['priority'];
                    
                    $this->assertIsInt($priority, 
                        "Hook priority should be integer in {$plugin_file}");
                    $this->assertGreaterThanOrEqual(1, $priority,
                        "Hook priority should be >= 1 in {$plugin_file}");
                    $this->assertLessThanOrEqual(100, $priority,
                        "Hook priority should be <= 100 in {$plugin_file}");
                }
            }
        }
    }
    
    /**
     * Test WordPress function availability checks
     */
    public function testWordPressFunctionAvailabilityChecks() {
        $plugin_files = [
            'minimal-test-plugin.php',
            'custom-page-builder-emergency.php',
            'custom-page-builder.php',
            'safe-mode-plugin.php'
        ];
        
        foreach ($plugin_files as $plugin_file) {
            $plugin_path = $this->test_plugin_path . '/' . $plugin_file;
            
            if (!file_exists($plugin_path)) {
                continue;
            }
            
            $content = file_get_contents($plugin_path);
            
            // Check if WordPress functions are used with availability checks
            $wp_functions = ['add_action', 'add_filter', 'register_activation_hook', 'wp_die'];
            
            foreach ($wp_functions as $wp_function) {
                if (preg_match('/' . preg_quote($wp_function) . '\s*\(/', $content)) {
                    // Function is used, check if there's a function_exists check
                    $has_check = preg_match('/function_exists\s*\(\s*[\'"]' . preg_quote($wp_function) . '[\'"]\s*\)/', $content);
                    
                    $this->assertTrue($has_check,
                        "WordPress function '{$wp_function}' used without availability check in {$plugin_file}");
                }
            }
        }
    }
    
    protected function tearDown(): void {
        // Clean up global reference
        global $wp_hooks_test_instance;
        $wp_hooks_test_instance = null;
        
        parent::tearDown();
    }
}