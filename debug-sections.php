<?php
/**
 * Debug Sections Issue
 * 
 * This script helps debug why sections are not opening when clicked
 */

// Load WordPress
$wp_load_paths = [
    '../../../wp-load.php',
    '../../../../wp-load.php',
    '../../../../../wp-load.php'
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists(__DIR__ . '/' . $path)) {
        require_once __DIR__ . '/' . $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('Could not load WordPress. Please check the path.');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Sections Issue</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-button { padding: 10px 20px; margin: 10px; background: #0073aa; color: white; border: none; cursor: pointer; }
        .test-section { border: 1px solid #ccc; padding: 15px; margin: 10px 0; background: #f9f9f9; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Debug Sections Issue</h1>
    
    <h2>Test 1: Basic JavaScript Functions</h2>
    <button class="test-button" onclick="testBasicJS()">Test Basic JavaScript</button>
    <div id="js-test-result"></div>
    
    <h2>Test 2: Section Creation Functions</h2>
    <button class="test-button" onclick="testSectionFunctions()">Test Section Functions</button>
    <div id="section-test-result"></div>
    
    <h2>Test 3: Add Section Test</h2>
    <button class="test-button" onclick="cpbAddSection('content')">Add Content Section</button>
    <div id="sections-container" style="border: 2px dashed #ccc; padding: 20px; margin: 10px 0;">
        <p>Sections will appear here...</p>
    </div>
    
    <h2>Test 4: Check for JavaScript Errors</h2>
    <div id="error-log"></div>
    
    <script>
    // Capture JavaScript errors
    window.onerror = function(msg, url, lineNo, columnNo, error) {
        const errorDiv = document.getElementById('error-log');
        errorDiv.innerHTML += '<div class="error">JavaScript Error: ' + msg + ' at line ' + lineNo + '</div>';
        return false;
    };
    
    function testBasicJS() {
        const result = document.getElementById('js-test-result');
        try {
            result.innerHTML = '<div class="success">✓ Basic JavaScript is working</div>';
        } catch (e) {
            result.innerHTML = '<div class="error">✗ JavaScript Error: ' + e.message + '</div>';
        }
    }
    
    function testSectionFunctions() {
        const result = document.getElementById('section-test-result');
        let tests = [];
        
        // Test if functions exist
        if (typeof cpbAddSection === 'function') {
            tests.push('✓ cpbAddSection function exists');
        } else {
            tests.push('✗ cpbAddSection function missing');
        }
        
        if (typeof cpbCreateSlideHTML === 'function') {
            tests.push('✓ cpbCreateSlideHTML function exists');
        } else {
            tests.push('✗ cpbCreateSlideHTML function missing');
        }
        
        if (typeof cpbCreateElementHTML === 'function') {
            tests.push('✓ cpbCreateElementHTML function exists');
        } else {
            tests.push('✗ cpbCreateElementHTML function missing');
        }
        
        result.innerHTML = tests.map(test => 
            '<div class="' + (test.startsWith('✓') ? 'success' : 'error') + '">' + test + '</div>'
        ).join('');
    }
    
    // Include the section creation functions from the main plugin
    let sectionIndex = 0;
    
    function cpbAddSection(type) {
        console.log('cpbAddSection called with type:', type);
        
        const container = document.getElementById('sections-container');
        if (!container) {
            console.error('sections-container not found');
            return;
        }
        
        const section = document.createElement('div');
        section.className = 'section-item test-section';
        
        let html = '<h3>Section ' + (sectionIndex + 1) + ' - ' + type.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase()) + '</h3>';
        html += '<input type="hidden" name="sections[' + sectionIndex + '][type]" value="' + type + '">';
        html += '<input type="hidden" name="sections[' + sectionIndex + '][order]" value="' + sectionIndex + '">';
        
        if (type === 'content') {
            html += '<p><label>Title:</label><br><input type="text" name="sections[' + sectionIndex + '][title]" class="regular-text"></p>';
            html += '<p><label>Content:</label><br><textarea name="sections[' + sectionIndex + '][content]" rows="5" class="large-text"></textarea></p>';
        }
        
        html += '<p><button type="button" class="button" onclick="this.parentElement.parentElement.remove()">Remove Section</button></p>';
        
        section.innerHTML = html;
        container.appendChild(section);
        sectionIndex++;
        
        console.log('Section added successfully');
    }
    
    // Test on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Page loaded, running tests...');
        testBasicJS();
        testSectionFunctions();
    });
    </script>
    
    <h2>Instructions</h2>
    <ol>
        <li>Click "Test Basic JavaScript" to verify JS is working</li>
        <li>Click "Test Section Functions" to check if functions exist</li>
        <li>Click "Add Content Section" to test section creation</li>
        <li>Check the browser console (F12) for any errors</li>
        <li>Look for any JavaScript errors in the "Check for JavaScript Errors" section</li>
    </ol>
    
</body>
</html>