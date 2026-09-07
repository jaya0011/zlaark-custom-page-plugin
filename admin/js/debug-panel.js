/**
 * Debug Panel for Product Addition
 * Shows real-time debugging information
 */

(function($) {
    'use strict';

    // Create debug panel
    function createDebugPanel() {
        if ($('#cpb-debug-panel').length) {
            return; // Already exists
        }

        var panelHtml = `
            <div id="cpb-debug-panel" style="position: fixed; bottom: 20px; right: 20px; width: 400px; max-height: 500px; overflow-y: auto; background: #1e1e1e; color: #00ff00; padding: 15px; border-radius: 5px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); z-index: 99999; font-family: 'Courier New', monospace; font-size: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 1px solid #00ff00; padding-bottom: 10px;">
                    <strong style="color: #00ff00; font-size: 14px;">🐛 Debug Console</strong>
                    <button id="cpb-debug-close" style="background: #ff0000; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px; font-size: 11px;">Close</button>
                </div>
                <div id="cpb-debug-logs" style="max-height: 400px; overflow-y: auto;">
                    <div style="color: #00ff00; margin-bottom: 5px;">📋 Debug panel initialized</div>
                    <div style="color: #00ff00; margin-bottom: 5px;">⏰ ${new Date().toLocaleTimeString()}</div>
                </div>
                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #00ff00;">
                    <button id="cpb-debug-clear" style="background: #333; color: #00ff00; border: 1px solid #00ff00; padding: 5px 10px; cursor: pointer; border-radius: 3px; font-size: 11px; width: 100%;">Clear Logs</button>
                </div>
            </div>
        `;

        $('body').append(panelHtml);

        // Bind events
        $('#cpb-debug-close').on('click', function() {
            $('#cpb-debug-panel').fadeOut();
        });

        $('#cpb-debug-clear').on('click', function() {
            $('#cpb-debug-logs').html('<div style="color: #00ff00; margin-bottom: 5px;">📋 Logs cleared</div>');
        });
    }

    // Add log to debug panel
    function debugLog(message, type = 'info') {
        var colors = {
            'info': '#00ff00',
            'success': '#00ff00',
            'error': '#ff0000',
            'warning': '#ffff00',
            'ajax': '#00bfff'
        };

        var icons = {
            'info': 'ℹ️',
            'success': '✅',
            'error': '❌',
            'warning': '⚠️',
            'ajax': '🌐'
        };

        var color = colors[type] || '#00ff00';
        var icon = icons[type] || 'ℹ️';
        var time = new Date().toLocaleTimeString();

        var logHtml = `<div style="color: ${color}; margin-bottom: 5px; padding: 5px; border-left: 3px solid ${color}; background: rgba(0,255,0,0.05);">
            <span style="color: #888;">[${time}]</span> ${icon} ${message}
        </div>`;

        $('#cpb-debug-logs').append(logHtml);
        
        // Auto-scroll to bottom
        var logsContainer = $('#cpb-debug-logs')[0];
        logsContainer.scrollTop = logsContainer.scrollHeight;
    }

    // Initialize on document ready
    $(document).ready(function() {
        // Only show debug panel on custom page builder pages
        if (window.location.href.indexOf('custom-page-builder') !== -1) {
            createDebugPanel();
            debugLog('Debug panel loaded on page: ' + window.location.pathname, 'info');
            
            // Check if CPB_Admin exists
            if (typeof CPB_Admin !== 'undefined') {
                debugLog('CPB_Admin object found', 'success');
            } else {
                debugLog('CPB_Admin object NOT found', 'error');
            }
            
            // Check if cpb_admin exists
            if (typeof cpb_admin !== 'undefined') {
                debugLog('cpb_admin config found', 'success');
                debugLog('AJAX URL: ' + cpb_admin.ajax_url, 'info');
            } else {
                debugLog('cpb_admin config NOT found', 'error');
            }
        }
    });

    // Intercept AJAX calls
    $(document).ajaxSend(function(event, jqxhr, settings) {
        if (settings.url && settings.url.indexOf('admin-ajax.php') !== -1) {
            var data = settings.data || '';
            if (data.indexOf('get_product_template') !== -1) {
                debugLog('AJAX Request: get_product_template', 'ajax');
                debugLog('Request data: ' + data.substring(0, 100) + '...', 'info');
            }
        }
    });

    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url && settings.url.indexOf('admin-ajax.php') !== -1) {
            var data = settings.data || '';
            if (data.indexOf('get_product_template') !== -1) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        debugLog('AJAX Response: SUCCESS', 'success');
                        debugLog('HTML length: ' + (response.data.html ? response.data.html.length : 0) + ' chars', 'info');
                        if (response.data.debug) {
                            debugLog('Has category selector: ' + response.data.debug.has_category_selector, 'info');
                            debugLog('Has tag selector: ' + response.data.debug.has_tag_selector, 'info');
                        }
                    } else {
                        debugLog('AJAX Response: FAILED', 'error');
                        debugLog('Error: ' + (response.data ? response.data.message : 'Unknown'), 'error');
                    }
                } catch (e) {
                    debugLog('Failed to parse AJAX response', 'error');
                }
            }
        }
    });

    $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
        if (settings.url && settings.url.indexOf('admin-ajax.php') !== -1) {
            var data = settings.data || '';
            if (data.indexOf('get_product_template') !== -1) {
                debugLog('AJAX Error: ' + thrownError, 'error');
                debugLog('Status: ' + jqxhr.status, 'error');
            }
        }
    });

    // Expose to global scope
    window.CPB_Debug = {
        log: debugLog,
        show: function() {
            createDebugPanel();
            $('#cpb-debug-panel').fadeIn();
        },
        hide: function() {
            $('#cpb-debug-panel').fadeOut();
        }
    };

})(jQuery);
