<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for plugin activation error handling functions
 */
class PluginActivationTest extends TestCase {
    
    private $test_plugin_path;
    
    protected function setUp(): void {
        parent::setUp();
        $this->test_plugin_path = dirname(__DIR__, 2);
    }
    
    /**
     * Test WordPress function checker functionality
     */
    public function testWordPressFunctionChecker() {
        require_once $this->test_plugin_path . '/includes/class-wordpress-function-checker.php';
        
        $checker = new WordPressFunctionChecker();
        
        // Test with existing PHP function
        $this->assertTrue($checker->checkFunction('function_exists'));
        
        // Test with non-existing function
        $this->assertFalse($checker->checkFunction('non_existing_wp_function'));
        
        // Test WordPress function availability check
        $wp_functions = ['add_action', 'add_filter', 'wp_die', 'register_activation_hook'];
        $results = $checker->checkWordPressFunctions($wp_functions);
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('available', $results);
        $this->assertArrayHasKey('missing', $results);
    }
    
    /**
     * Test error handler functionality
     */
    public function testErrorHandler() {
        require_once $this->test_plugin_path . '/includes/class-error-handler.php';
        
        $error_handler = new ErrorHandler();
        
        // Test error logging
        $test_error = "Test error message";
        $result = $error_handler->logError($test_error, 'test-context');
        
        $this->assertTrue($result);
        
        // Test error categorization
        $this->assertEquals('fatal', $error_handler->categorizeError('Fatal error: Class not found'));
        $this->assertEquals('warning', $error_handler->categorizeError('Warning: Function deprecated'));
        $this->assertEquals('notice', $error_handler->categorizeError('Notice: Undefined variable'));
    }
    
    /**
     * Test activation manager functionality
     */
    public function testActivationManager() {
        require_once $this->test_plugin_path . '/includes/class-activation-manager.php';
        
        $activation_manager = new ActivationManager();
        
        // Test dependency validation
        $dependencies = [
            'files' => ['includes/class-plugin.php'],
            'classes' => ['Plugin'],
            'functions' => ['add_action']
        ];
        
        $validation_result = $activation_manager->validateDependencies($dependencies);
        $this->assertIsArray($validation_result);
        $this->assertArrayHasKey('valid', $validation_result);
        $this->assertArrayHasKey('missing', $validation_result);
        
        // Test safe activation
        $plugin_file = 'test-plugin.php';
        $activation_result = $activation_manager->safeActivation($plugin_file, function() {
            return true; // Mock successful activation
        });
        
        $this->assertIsArray($activation_result);
        $this->assertArrayHasKey('success', $activation_result);
    }
    
    /**
     * Test error logger functionality
     */
    public function testErrorLogger() {
        require_once $this->test_plugin_path . '/includes/class-error-logger.php';
        
        $logger = new ErrorLogger();
        
        // Test log entry creation
        $log_entry = $logger->createLogEntry('test error', 'error', ['context' => 'test']);
        
        $this->assertIsArray($log_entry);
        $this->assertArrayHasKey('message', $log_entry);
        $this->assertArrayHasKey('level', $log_entry);
        $this->assertArrayHasKey('timestamp', $log_entry);
        $this->assertArrayHasKey('context', $log_entry);
        
        // Test log formatting
        $formatted_log = $logger->formatLogEntry($log_entry);
        $this->assertIsString($formatted_log);
        $this->assertStringContainsString('test error', $formatted_log);
    }
    
    /**
     * Test input validator functionality
     */
    public function testInputValidator() {
        require_once $this->test_plugin_path . '/includes/class-input-validator.php';
        
        $validator = new InputValidator();
        
        // Test plugin file validation
        $valid_plugin_file = 'test-plugin.php';
        $invalid_plugin_file = '../../../etc/passwd';
        
        $this->assertTrue($validator->validatePluginFile($valid_plugin_file));
        $this->assertFalse($validator->validatePluginFile($invalid_plugin_file));
        
        // Test class name validation
        $valid_class = 'ValidClassName';
        $invalid_class = '123InvalidClass';
        
        $this->assertTrue($validator->validateClassName($valid_class));
        $this->assertFalse($validator->validateClassName($invalid_class));
    }
    
    /**
     * Test plugin file syntax validation
     */
    public function testPluginSyntaxValidation() {
        $plugin_files = [
            'minimal-test-plugin.php',
            'custom-page-builder-emergency.php',
            'custom-page-builder.php',
            'safe-mode-plugin.php'
        ];
        
        foreach ($plugin_files as $plugin_file) {
            $plugin_path = $this->test_plugin_path . '/' . $plugin_file;
            
            if (file_exists($plugin_path)) {
                // Test PHP syntax
                $output = [];
                $return_code = 0;
                exec("php -l " . escapeshellarg($plugin_path) . " 2>&1", $output, $return_code);
                
                $this->assertEquals(0, $return_code, 
                    "Syntax error in {$plugin_file}: " . implode("\n", $output));
            }
        }
    }
    
    /**
     * Test plugin header validation
     */
    public function testPluginHeaderValidation() {
        $plugin_files = [
            'minimal-test-plugin.php',
            'custom-page-builder-emergency.php', 
            'custom-page-builder.php',
            'safe-mode-plugin.php'
        ];
        
        foreach ($plugin_files as $plugin_file) {
            $plugin_path = $this->test_plugin_path . '/' . $plugin_file;
            
            if (file_exists($plugin_path)) {
                $content = file_get_contents($plugin_path);
                
                // Check for required plugin header
                $this->assertMatchesRegularExpression(
                    '/Plugin Name:\s*(.+)/i',
                    $content,
                    "Missing 'Plugin Name' header in {$plugin_file}"
                );
                
                // Check for ABSPATH security check
                $this->assertMatchesRegularExpression(
                    '/defined\s*\(\s*[\'"]ABSPATH[\'"]\s*\)/',
                    $content,
                    "Missing ABSPATH security check in {$plugin_file}"
                );
            }
        }
    }
    
    /**
     * Test immediate execution detection
     */
    public function testImmediateExecutionDetection() {
        // Test code with immediate execution
        $bad_code = '<?php
        $global_var = new SomeClass();
        some_function();
        ';
        
        $this->assertTrue($this->hasImmediateExecution($bad_code));
        
        // Test code without immediate execution
        $good_code = '<?php
        function init_plugin() {
            $var = new SomeClass();
            some_function();
        }
        add_action("plugins_loaded", "init_plugin");
        ';
        
        $this->assertFalse($this->hasImmediateExecution($good_code));
    }
    
    /**
     * Helper method to detect immediate execution
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
}