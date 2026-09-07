/**
 * IMPORT/EXPORT DEBUG SCRIPT
 * 
 * Copy and paste this entire script into your browser console
 * while on the "Page Builder > All Pages" screen in WordPress admin
 */

(function() {
    console.log('='.repeat(80));
    console.log('IMPORT/EXPORT DEBUG SCRIPT');
    console.log('='.repeat(80));
    
    var results = {
        passed: [],
        failed: [],
        warnings: []
    };
    
    function test(name, condition, failMessage) {
        if (condition) {
            results.passed.push(name);
            console.log('✓ PASS:', name);
            return true;
        } else {
            results.failed.push({name: name, message: failMessage});
            console.error('✗ FAIL:', name, '-', failMessage);
            return false;
        }
    }
    
    function warn(name, message) {
        results.warnings.push({name: name, message: message});
        console.warn('⚠ WARNING:', name, '-', message);
    }
    
    console.log('\n1. CHECKING DEPENDENCIES...\n');
    
    test(
        'jQuery is loaded',
        typeof jQuery !== 'undefined',
        'jQuery is not defined. WordPress should load it automatically.'
    );
    
    test(
        'cpbAdmin is defined',
        typeof cpbAdmin !== 'undefined',
        'cpbAdmin is not defined. The script localization may not be working.'
    );
    
    if (typeof cpbAdmin !== 'undefined') {
        test(
            'cpbAdmin has ajaxUrl',
            cpbAdmin.ajaxUrl !== undefined,
            'cpbAdmin.ajaxUrl is missing'
        );
        
        test(
            'cpbAdmin has nonce',
            cpbAdmin.nonce !== undefined,
            'cpbAdmin.nonce is missing'
        );
        
        console.log('cpbAdmin object:', cpbAdmin);
    }
    
    console.log('\n2. CHECKING HTML ELEMENTS...\n');
    
    if (typeof jQuery !== 'undefined') {
        var $ = jQuery;
        
        test(
            'Import button exists',
            $('#cpb-import-page-btn').length > 0,
            'Import button not found. Check if you are on the "All Pages" screen.'
        );
        
        var exportCount = $('.cpb-export-page').length;
        test(
            'Export buttons exist',
            exportCount > 0,
            'No export buttons found. You may not have any pages yet.'
        );
        console.log('   Found', exportCount, 'export button(s)');
        
        test(
            'Import modal exists',
            $('#cpb-import-modal').length > 0,
            'Import modal HTML not found in page'
        );
        
        test(
            'Import form exists',
            $('#cpb-import-form').length > 0,
            'Import form not found'
        );
        
        test(
            'Import file input exists',
            $('#cpb-import-file').length > 0,
            'File input not found'
        );
    }
    
    console.log('\n3. CHECKING SCRIPTS...\n');
    
    if (typeof jQuery !== 'undefined') {
        var $ = jQuery;
        
        var importExportScript = $('script[src*="import-export.js"]');
        test(
            'import-export.js is loaded',
            importExportScript.length > 0,
            'import-export.js script tag not found in page'
        );
        
        if (importExportScript.length > 0) {
            console.log('   Script URL:', importExportScript.attr('src'));
        }
        
        var adminScript = $('script[src*="admin.js"]');
        test(
            'admin.js is loaded',
            adminScript.length > 0,
            'admin.js script tag not found'
        );
    }
    
    console.log('\n4. CHECKING EVENT HANDLERS...\n');
    
    if (typeof jQuery !== 'undefined') {
        var $ = jQuery;
        
        var importBtn = $('#cpb-import-page-btn')[0];
        if (importBtn) {
            var events = $._data(importBtn, 'events');
            test(
                'Import button has click handler',
                events && events.click && events.click.length > 0,
                'No click event attached to import button'
            );
            if (events && events.click) {
                console.log('   Click handlers:', events.click.length);
            }
        }
        
        var exportBtn = $('.cpb-export-page')[0];
        if (exportBtn) {
            var exportEvents = $._data(exportBtn, 'events');
            if (!exportEvents || !exportEvents.click) {
                warn(
                    'Export button click handler',
                    'Export uses delegated events, which is normal. Will check document instead.'
                );
                
                // Check for delegated events on document
                var docEvents = $._data(document, 'events');
                if (docEvents && docEvents.click) {
                    var hasDelegated = false;
                    docEvents.click.forEach(function(handler) {
                        if (handler.selector && handler.selector.indexOf('cpb-export-page') !== -1) {
                            hasDelegated = true;
                        }
                    });
                    test(
                        'Export has delegated click handler',
                        hasDelegated,
                        'No delegated click handler found for export'
                    );
                }
            }
        }
    }
    
    console.log('\n5. TESTING FUNCTIONALITY...\n');
    
    if (typeof jQuery !== 'undefined') {
        var $ = jQuery;
        
        console.log('Attempting to show import modal...');
        $('#cpb-import-modal').css('display', 'flex').addClass('active');
        
        setTimeout(function() {
            var isVisible = $('#cpb-import-modal').is(':visible');
            test(
                'Modal can be shown',
                isVisible,
                'Modal is not visible after attempting to show it'
            );
            
            if (isVisible) {
                console.log('   ✓ Modal is now visible on screen');
                console.log('   Hiding modal in 3 seconds...');
                setTimeout(function() {
                    $('#cpb-import-modal').css('display', 'none').removeClass('active');
                    console.log('   Modal hidden');
                }, 3000);
            }
        }, 100);
    }
    
    console.log('\n' + '='.repeat(80));
    console.log('SUMMARY');
    console.log('='.repeat(80));
    console.log('Passed:', results.passed.length);
    console.log('Failed:', results.failed.length);
    console.log('Warnings:', results.warnings.length);
    
    if (results.failed.length > 0) {
        console.log('\n❌ FAILED TESTS:');
        results.failed.forEach(function(fail) {
            console.log('  -', fail.name + ':', fail.message);
        });
    }
    
    if (results.warnings.length > 0) {
        console.log('\n⚠️  WARNINGS:');
        results.warnings.forEach(function(warn) {
            console.log('  -', warn.name + ':', warn.message);
        });
    }
    
    if (results.failed.length === 0) {
        console.log('\n✅ ALL TESTS PASSED!');
        console.log('If import/export still doesn\'t work, try:');
        console.log('1. Hard refresh the page (Ctrl+Shift+R)');
        console.log('2. Clear browser cache');
        console.log('3. Check browser console for JavaScript errors');
        console.log('4. Check Network tab for failed file loads');
    } else {
        console.log('\n❌ SOME TESTS FAILED');
        console.log('Please fix the failed tests above and try again.');
        console.log('Common fixes:');
        console.log('1. Make sure you are on the "Page Builder > All Pages" screen');
        console.log('2. Hard refresh the page (Ctrl+Shift+R)');
        console.log('3. Clear all caches');
        console.log('4. Check if files exist in the correct location');
    }
    
    console.log('\n' + '='.repeat(80));
    console.log('For more help, see: IMPORT_EXPORT_TESTING.md');
    console.log('='.repeat(80));
    
})();
