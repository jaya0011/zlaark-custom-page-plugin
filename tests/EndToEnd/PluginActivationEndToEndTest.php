<?php

use PHPUnit\Framework\TestCase;

/**
 * End-to-end tests for plugin activation workflow
 */
class PluginActivationEndToEndTest extends TestCase {
    
    private $test_plugin_path;
    private $activation_results = [];
    
    protected function setUp(): void {
        parent::setUp();
        $this->test_plugin_path = dirname(__DIR__, 2);
        $this->setupWordPressEnvironment();
    }
    
    /**
     * Setup WordPress environment for testing
     */
    private function setupWordPressEnvironment() {
        // Define WordPress constants
        if (!defined('ABSPATH')) {
            define('ABSPATH', $this->test_plugin_path . '/');
        }
        
        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        }
        
        if (!defined('WP_DEBUG_LOG')) {
            define('WP_DEBUG_LOG', true);
        }
        
        // Mock WordPress functions
        $this->mockWordPressFunctions();
    }
    
    /**
     * Mock WordPress functions for testing
     */
    private function mockWordPressFunctions() {
        if (!function_exists('add_action')) {
            function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
                return true;
            }
        }
        
        if (!function_exists('add_filter')) {
            function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
                return true;
            }
        }
        
        if (!function_exists('register_activation_hook')) {
            function register_activation_hook($file, $callback) {
                if (is_callable($callback)) {
                    try {
                        call_user_func($callback);
                        return true;
                    } catch (Exception $e) {
                        return false;
                    }
                }
                return true;
            }
        }
        
        if (!function_exists('wp_die')) {
            function wp_die($message, $title = '', $args = []) {
                throw new Exception("WordPress Error: {$message}");
            }
        }
        
        if (!function_exists('error_log')) {
            function error_log($message) {
                return true;
            }
        }
        
        if (!function_exists('plugin_dir_path')) {
            function plugin_dir_path($file) {
                return dirname($file) . '/';
            }
        }
        
        if (!function_exists('plugin_dir_url')) {
            function plugin_dir_url($file) {
                return 'http://example.com/wp-content/plugins/' . basename(dirname($file)) . '/';
            }
        }
    }
    
    /**
     * Test complete plugin activation workflow
     */
    public function testCompletePluginActivationWorkflow() {
        $plugin_files = [
            'minimal-test-plugin.php',
            'custom-page-builder-emergency.php',
            'custom-page-builder.php',
            'safe-mode-plugin.php'
        ];
        
        $successful_activations = 0;
        $total_plugins = 0;
        
        foreach ($plugin_files as $plugin_file) {
            $plugin_path = $this->test_plugin_path . '/' . $plugin_file;
            
            if (!file_exists($plugin_path)) {
                continue;
            }
            
            $total_plugins++;
            $activation_result = $this->testSinglePluginActivation($plugin_path);
            
            $this->activation_results[$plugin_file] = $activation_result;
            
            if ($activation_result['success']) {
                $successful_activations++;
            }
        }
        
        // Assert that at least 75% of plugins activate successfully
        $success_rate = $total_plugins > 0 ? ($successful_activations / $total_plugins) : 0;
        $this->assertGreaterThanOrEqual(0.75, $success_rate, 
            "Plugin activation success rate should be at least 75%. Got: " . ($success_rate * 100) . "%");
    }
    
    /**
     * Test single plugin activation
     */
    private function testSinglePluginActivation($plugin_path) {
        $plugin_file = basename($plugin_path);
        $result = [
            'success' => false,
            'error' => null,
            'warnings' => [],
            'functionality_verified' => false
        ];
        
        try {
            // Step 1: Test plugin loading
            $load_result = $this->loadPluginSafely($plugin_path);
            if (!$load_result['success']) {
                $result['error'] = $load_result['error'];
                return $result;
            }
            
            // Step 2: Test activation hook execution
            $activation_result = $this->testActivationHook($plugin_path);
            if (!$activation_result['success']) {
                $result['warnings'][] = 'Activation hook issues: ' . $activation_result['error'];
            }
            
            // Step 3: Test plugin functionality
            $functionality_result = $this->testPluginFunctionality($plugin_path);
            $result['functionality_verified'] = $functionality_result['success'];
            
            if (!$functionality_result['success']) {
                $result['warnings'][] = 'Functionality issues: ' . $functionality_result['error'];
            }
            
            $result['success'] = true;
            
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Load plugin safely and catch errors
     */
    private function loadPluginSafely($plugin_path) {
        $result = ['success' => false, 'error' => null];
        
        // Capture output and errors
        ob_start();
        $error_handler_set = false;
        
        try {
            // Set custom error handler
            set_error_handler(function($severity, $message, $file, $line) {
                throw new ErrorException($message, 0, $severity, $file, $line);
            });
            $error_handler_set = true;
            
            // Include the plugin
            include_once $plugin_path;
            
            $result['success'] = true;
            
        } catch (ParseError $e) {
            $result['error'] = "Parse error: " . $e->getMessage();
        } catch (Error $e) {
            $result['error'] = "Fatal error: " . $e->getMessage();
        } catch (Exception $e) {
            $result['error'] = "Exception: " . $e->getMessage();
        } finally {
            if ($error_handler_set) {
                restore_error_handler();
            }
            
            $output = ob_get_clean();
            if (!empty($output) && $result['success']) {
                $result['error'] = "Unexpected output: " . trim($output);
                $result['success'] = false;
            }
        }
        
        return $result;
    }
    
    /**
     * Test activation hook execution
     */
    private function testActivationHook($plugin_path) {
        $result = ['success' => true, 'error' => null];
        
        $content = file_get_contents($plugin_path);
        
        // Check if activation hook is registered
        if (!preg_match('/register_activation_hook\s*\(/', $content)) {
            $result['error'] = 'No activation hook registered';
            $result['success'] = false;
            return $result;
        }
        
        // Extract activation callback
        preg_match('/register_activation_hook\s*\(\s*[^,]+,\s*[\'"]?([^\'"]+)[\'"]?\s*\)/', $content, $matches);
        
        if (!empty($matches[1])) {
            $callback = $matches[1];
            
            if (function_exists($callback)) {
                try {
                    call_user_func($callback);
                } catch (Exception $e) {
                    $result['error'] = 'Activation callback failed: ' . $e->getMessage();
                    $result['success'] = false;
                }
            } else {
                $result['error'] = "Activation callback function '{$callback}' not found";
                $result['success'] = false;
            }
        }
        
        return $result;
    }
    
    /**
     * Test plugin functionality after activation
     */
    private function testPluginFunctionality($plugin_path) {
        $result = ['success' => true, 'error' => null];
        
        $content = file_get_contents($plugin_path);
        
        // Test 1: Check for proper WordPress integration
        if (!preg_match('/add_action\s*\(/', $content) && !preg_match('/add_filter\s*\(/', $content)) {
            $result['error'] = 'No WordPress hooks found - plugin may not integrate properly';
            $result['success'] = false;
            return $result;
        }
        
        // Test 2: Check for class/function definitions
        if (!preg_match('/class\s+\w+/', $content) && !preg_match('/function\s+\w+/', $content)) {
            $result['error'] = 'No classes or functions defined - plugin may not have functionality';
            $result['success'] = false;
            return $result;
        }
        
        // Test 3: Check for immediate execution (should not have)
        if ($this->hasImmediateExecution($content)) {
            $result['error'] = 'Plugin has immediate execution code - may cause activation issues';
            $result['success'] = false;
            return $result;
        }
        
        // Test 4: Check for error handling
        if (!preg_match('/try\s*\{|function_exists\s*\(|class_exists\s*\(/', $content)) {
            $result['error'] = 'No error handling detected - plugin may be fragile';
            // This is a warning, not a failure
        }
        
        return $result;
    }
    
    /**
     * Check for immediate execution code
     */
    private function hasImmediateExecution($content) {
        // Remove comments and strings
        $clean_content = preg_replace('/\/\*.*?\*\//s', '', $content);
        $clean_content = preg_replace('/\/\/.*$/m', '', $clean_content);
        $clean_content = preg_replace('/[\'"].*?[\'"]/', '""', $clean_content);
        
        // Look for immediate execution patterns
        $immediate_patterns = [
            '/\$\w+\s*=\s*new\s+\w+\s*\(/m',
            '/\w+\s*\(\s*\)\s*;/m',
            '/global\s+\$\w+\s*;.*?\$\w+/m',
        ];
        
        foreach ($immediate_patterns as $pattern) {
            if (preg_match($pattern, $clean_content)) {
                $matches = [];
                preg_match_all($pattern, $clean_content, $matches, PREG_OFFSET_CAPTURE);
                
                foreach ($matches[0] as $match) {
                    $position = $match[1];
                    $before_code = substr($clean_content, 0, $position);
                    
                    $function_opens = preg_match_all('/(?:function|class)\s+\w+.*?\{/', $before_code);
                    $function_closes = preg_match_all('/^\s*\}\s*$/m', $before_code);
                    
                    if ($function_opens > $function_closes) {
                        continue;
                    }
                    
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Test plugin deactivation workflow
     */
    public function testPluginDeactivationWorkflow() {
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
            
            // Check for deactivation hook
            if (preg_match('/register_deactivation_hook\s*\(/', $content)) {
                // Extract deactivation callback
                preg_match('/register_deactivation_hook\s*\(\s*[^,]+,\s*[\'"]?([^\'"]+)[\'"]?\s*\)/', $content, $matches);
                
                if (!empty($matches[1])) {
                    $callback = $matches[1];
                    
                    if (function_exists($callback)) {
                        try {
                            call_user_func($callback);
                            $this->assertTrue(true, "Deactivation callback executed successfully for {$plugin_file}");
                        } catch (Exception $e) {
                            $this->fail("Deactivation callback failed for {$plugin_file}: " . $e->getMessage());
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Test error recovery mechanisms
     */
    public function testErrorRecoveryMechanisms() {
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
            
            // Check for error handling mechanisms
            $has_try_catch = preg_match('/try\s*\{.*?catch\s*\(/s', $content);
            $has_function_exists = preg_match('/function_exists\s*\(/', $content);
            $has_class_exists = preg_match('/class_exists\s*\(/', $content);
            $has_file_exists = preg_match('/file_exists\s*\(/', $content);
            $has_error_logging = preg_match('/error_log\s*\(/', $content);
            
            $error_handling_score = 0;
            if ($has_try_catch) $error_handling_score++;
            if ($has_function_exists) $error_handling_score++;
            if ($has_class_exists) $error_handling_score++;
            if ($has_file_exists) $error_handling_score++;
            if ($has_error_logging) $error_handling_score++;
            
            // Plugin should have at least 2 error handling mechanisms
            $this->assertGreaterThanOrEqual(2, $error_handling_score,
                "Plugin {$plugin_file} should have at least 2 error handling mechanisms. Found: {$error_handling_score}");
        }
    }
    
    /**
     * Test overall plugin ecosystem health
     */
    public function testPluginEcosystemHealth() {
        $total_plugins = count($this->activation_results);
        $successful_plugins = 0;
        $plugins_with_functionality = 0;
        
        foreach ($this->activation_results as $plugin_file => $result) {
            if ($result['success']) {
                $successful_plugins++;
            }
            
            if ($result['functionality_verified']) {
                $plugins_with_functionality++;
            }
        }
        
        // At least 75% should activate successfully
        $success_rate = $total_plugins > 0 ? ($successful_plugins / $total_plugins) : 0;
        $this->assertGreaterThanOrEqual(0.75, $success_rate,
            "Plugin ecosystem health: {$successful_plugins}/{$total_plugins} plugins activate successfully");
        
        // At least 50% should have verified functionality
        $functionality_rate = $total_plugins > 0 ? ($plugins_with_functionality / $total_plugins) : 0;
        $this->assertGreaterThanOrEqual(0.5, $functionality_rate,
            "Plugin ecosystem health: {$plugins_with_functionality}/{$total_plugins} plugins have verified functionality");
    }
    
    /**
     * Generate end-to-end test report
     */
    public function testGenerateEndToEndReport() {
        $report = "=== End-to-End Plugin Activation Test Report ===\n\n";
        
        $total_plugins = count($this->activation_results);
        $successful_activations = 0;
        $functionality_verified = 0;
        
        foreach ($this->activation_results as $plugin_file => $result) {
            $report .= "Plugin: {$plugin_file}\n";
            $report .= "Status: " . ($result['success'] ? "✓ SUCCESS" : "✗ FAILED") . "\n";
            $report .= "Functionality: " . ($result['functionality_verified'] ? "✓ VERIFIED" : "✗ NOT VERIFIED") . "\n";
            
            if ($result['success']) $successful_activations++;
            if ($result['functionality_verified']) $functionality_verified++;
            
            if ($result['error']) {
                $report .= "Error: {$result['error']}\n";
            }
            
            if (!empty($result['warnings'])) {
                $report .= "Warnings:\n";
                foreach ($result['warnings'] as $warning) {
                    $report .= "  - {$warning}\n";
                }
            }
            
            $report .= "\n";
        }
        
        $report .= "=== SUMMARY ===\n";
        $report .= "Total Plugins: {$total_plugins}\n";
        $report .= "Successful Activations: {$successful_activations}\n";
        $report .= "Functionality Verified: {$functionality_verified}\n";
        
        $success_rate = $total_plugins > 0 ? round(($successful_activations / $total_plugins) * 100, 1) : 0;
        $report .= "Success Rate: {$success_rate}%\n";
        
        // This test always passes - it's just generating a report
        $this->assertTrue(true, $report);
    }
}