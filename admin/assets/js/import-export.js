/**
 * Import/Export functionality for Custom Page Builder
 */

(function($) {
    'use strict';

    // Wait for both DOM and cpbAdmin to be ready
    function initImportExport() {
        console.log('CPB Import/Export: Initializing...');
        console.log('CPB Import/Export: jQuery available?', typeof $ !== 'undefined');
        console.log('CPB Import/Export: cpbAdmin available?', typeof cpbAdmin !== 'undefined');
        
        // If cpbAdmin is not available yet, wait a bit
        if (typeof cpbAdmin === 'undefined') {
            console.log('CPB Import/Export: Waiting for cpbAdmin...');
            setTimeout(initImportExport, 100);
            return;
        }
        
        console.log('CPB Import/Export: Script fully loaded and ready');
        
        // Check if elements exist
        console.log('CPB Import/Export: Import button exists?', $('#cpb-import-page-btn').length > 0);
        console.log('CPB Import/Export: Export buttons exist?', $('.cpb-export-page').length);
        console.log('CPB Import/Export: Modal exists?', $('#cpb-import-modal').length > 0);
        
        // Show import modal
        $('#cpb-import-page-btn').on('click', function(e) {
            e.preventDefault();
            console.log('CPB Import/Export: Import button clicked');
            var modal = $('#cpb-import-modal');
            console.log('CPB Import/Export: Modal element found?', modal.length > 0);
            modal.css('display', 'flex').addClass('active');
        });
        
        // Hide import modal
        $('#cpb-import-cancel').on('click', function(e) {
            e.preventDefault();
            console.log('CPB Import/Export: Cancel button clicked');
            $('#cpb-import-modal').css('display', 'none').removeClass('active');
            $('#cpb-import-form')[0].reset();
            $('#cpb-import-result').html('');
        });
        
        // Close modal when clicking outside
        $('#cpb-import-modal').on('click', function(e) {
            if (e.target === this) {
                console.log('CPB Import/Export: Clicked outside modal, closing');
                $(this).css('display', 'none').removeClass('active');
                $('#cpb-import-form')[0].reset();
                $('#cpb-import-result').html('');
            }
        });
        
        // Handle import form submission
        $('#cpb-import-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData();
            var fileInput = $('#cpb-import-file')[0];
            
            if (!fileInput.files.length) {
                alert('Please select a file to import');
                return;
            }
            
            // Check if cpbAdmin is defined
            if (typeof cpbAdmin === 'undefined') {
                console.error('CPB Import/Export: cpbAdmin is not defined');
                alert('Error: Admin configuration not loaded. Please refresh the page.');
                return;
            }
            
            formData.append('action', 'cpb_import_page');
            formData.append('nonce', cpbAdmin.nonce);
            formData.append('import_file', fileInput.files[0]);
            formData.append('update_existing', $('#cpb-update-existing').is(':checked') ? 'true' : 'false');
            
            console.log('CPB Import/Export: Submitting import form');
            
            // Show loading state
            $('#cpb-import-result').html('<p>Importing... Please wait.</p>');
            
            $.ajax({
                url: cpbAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('CPB Import/Export: Import response received', response);
                    if (response.success) {
                        $('#cpb-import-result').html(
                            '<div class="notice notice-success"><p>' + response.data.message + '</p></div>'
                        );
                        
                        // Show detailed results if available
                        if (response.data.results) {
                            var resultsHtml = '<div style="margin-top: 10px;">';
                            
                            if (response.data.results.success.length > 0) {
                                resultsHtml += '<p><strong>Successfully imported:</strong></p><ul>';
                                response.data.results.success.forEach(function(item) {
                                    resultsHtml += '<li>' + item.title + ' (ID: ' + item.id + ')</li>';
                                });
                                resultsHtml += '</ul>';
                            }
                            
                            if (response.data.results.errors.length > 0) {
                                resultsHtml += '<p><strong>Errors:</strong></p><ul>';
                                response.data.results.errors.forEach(function(item) {
                                    resultsHtml += '<li>' + item.title + ': ' + item.error + '</li>';
                                });
                                resultsHtml += '</ul>';
                            }
                            
                            resultsHtml += '</div>';
                            $('#cpb-import-result').append(resultsHtml);
                        }
                        
                        console.log('CPB Import/Export: Import successful, reloading page in 2 seconds');
                        // Reload page after 2 seconds
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        console.error('CPB Import/Export: Import failed', response.data.message);
                        $('#cpb-import-result').html(
                            '<div class="notice notice-error"><p>' + response.data.message + '</p></div>'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    console.error('CPB Import/Export: Import AJAX error', xhr, status, error);
                    $('#cpb-import-result').html(
                        '<div class="notice notice-error"><p>Error: ' + error + '</p></div>'
                    );
                }
            });
        });
        
        // Handle export button click
        $(document).on('click', '.cpb-export-page', function(e) {
            e.preventDefault();
            console.log('CPB Import/Export: Export button clicked');
            
            var pageId = $(this).data('page-id');
            var pageSlug = $(this).data('page-slug');
            
            console.log('CPB Import/Export: Page ID:', pageId, 'Slug:', pageSlug);
            
            if (!pageId) {
                alert('Invalid page ID');
                return;
            }
            
            // Check if cpbAdmin is defined
            if (typeof cpbAdmin === 'undefined') {
                console.error('CPB Import/Export: cpbAdmin is not defined');
                alert('Error: Admin configuration not loaded. Please refresh the page.');
                return;
            }
            
            // Show loading state
            var $link = $(this);
            var originalText = $link.text();
            $link.text('Exporting...');
            
            console.log('CPB Import/Export: Sending AJAX request');
            
            $.ajax({
                url: cpbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cpb_export_page',
                    nonce: cpbAdmin.nonce,
                    page_id: pageId
                },
                success: function(response) {
                    console.log('CPB Import/Export: Export response received', response);
                    $link.text(originalText);
                    
                    if (response.success) {
                        console.log('CPB Import/Export: Export successful, creating download');
                        // Create download link
                        var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(response.data.data, null, 2));
                        var downloadAnchorNode = document.createElement('a');
                        downloadAnchorNode.setAttribute("href", dataStr);
                        downloadAnchorNode.setAttribute("download", response.data.filename);
                        document.body.appendChild(downloadAnchorNode);
                        downloadAnchorNode.click();
                        downloadAnchorNode.remove();
                        console.log('CPB Import/Export: Download triggered');
                        
                        // Show success message
                        var notice = $('<div class="notice notice-success is-dismissible"><p>Page exported successfully!</p></div>');
                        $('.wrap h1').after(notice);
                        setTimeout(function() {
                            notice.fadeOut(function() {
                                $(this).remove();
                            });
                        }, 3000);
                    } else {
                        console.error('CPB Import/Export: Export failed', response.data.message);
                        alert('Export failed: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('CPB Import/Export: AJAX error', xhr, status, error);
                    $link.text(originalText);
                    alert('Export failed: ' + error);
                }
            });
        });
    }
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        console.log('CPB Import/Export: DOM ready, starting initialization');
        initImportExport();
    });

})(jQuery);
