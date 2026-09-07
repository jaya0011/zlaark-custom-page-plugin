/**
 * Admin JavaScript for Custom Page Builder
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        CPB_Admin.init();
    });

    // Main admin object
    window.CPB_Admin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initSortable();
            this.initMediaUploader();
            this.initSectionPreview();
            this.initSectionConfigTabs();
            this.initKeyboardShortcuts();
            
            // Initialize clipboard
            this.sectionClipboard = null;
            this.previewTimeout = null;
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Page actions
            $(document).on('click', '.cpb-save-page', this.savePage);
            $(document).on('click', '.cpb-delete-page', this.deletePage);
            $(document).on('click', '.cpb-duplicate-page', this.duplicatePage);
            
            // Section actions
            $(document).on('click', '.cpb-add-section-btn', this.showAddSectionModal);
            $(document).on('click', '.cpb-edit-section', this.editSection);
            $(document).on('click', '.cpb-delete-section', this.deleteSection);
            $(document).on('click', '.cpb-copy-section', function(e) {
                e.preventDefault();
                var sectionId = $(this).closest('.cpb-section-item').data('section-id');
                CPB_Admin.copySection(sectionId);
            });
            $(document).on('click', '.cpb-paste-section', function(e) {
                e.preventDefault();
                CPB_Admin.pasteSection();
            });
            
            // Modal actions
            $(document).on('click', '.cpb-modal-close', this.closeModal);
            $(document).on('click', '.cpb-modal-save', this.saveSection);
            $(document).on('click', '.cpb-modal-cancel', this.closeModal);
            
            // Section type change
            $(document).on('change', '#section_type', function() {
                var sectionType = $(this).val();
                CPB_Admin.loadSectionConfig(sectionType);
            });
            
            // Close modal when clicking outside
            $(document).on('click', '.cpb-modal', function(e) {
                if (e.target === this) {
                    CPB_Admin.closeModal();
                }
            });
            
            // Section header toggle
            $(document).on('click', '.cpb-section-header', this.toggleSection);
            
            // Scheduling actions
            $(document).on('click', '.cpb-schedule-page', this.showScheduleModal);
            $(document).on('click', '.cpb-unschedule-page', this.unschedulePage);
            $(document).on('submit', '#cpb-schedule-form', this.schedulePage);
            
            // Revision actions
            $(document).on('click', '.cpb-show-revisions', this.showRevisionsModal);
            $(document).on('click', '.cpb-create-revision', this.createRevision);
            $(document).on('click', '.cpb-restore-revision', this.restoreRevision);
            $(document).on('click', '.cpb-delete-revision', this.deleteRevision);
            $(document).on('click', '.cpb-compare-revisions', this.compareRevisions);
            
            // Auto-save functionality
            this.initAutoSave();
            
            // Product and Category management
            $(document).on('click', '#add-product', function(e) {
                e.preventDefault();
                CPB_Admin.addProduct();
            });
            
            $(document).on('click', '.remove-product', function(e) {
                e.preventDefault();
                CPB_Admin.removeProduct(this);
            });
            
            $(document).on('click', '#add-category', function(e) {
                e.preventDefault();
                CPB_Admin.addCategory();
            });
            
            $(document).on('click', '.remove-category', function(e) {
                e.preventDefault();
                CPB_Admin.removeCategory(this);
            });
            
            $(document).on('click', '#add-testimonial', function(e) {
                e.preventDefault();
                CPB_Admin.addTestimonial();
            });
            
            $(document).on('click', '.remove-testimonial', function(e) {
                e.preventDefault();
                CPB_Admin.removeTestimonial(this);
            });
            
            // Category source toggle for category showcase
            $(document).on('change', '.category-source-radio', function() {
                var source = $(this).val();
                var container = $(this).closest('.category-showcase-section-config');
                
                if (source === 'woocommerce') {
                    container.find('.woocommerce-categories-section').show();
                    container.find('.manual-categories-section').hide();
                } else {
                    container.find('.woocommerce-categories-section').hide();
                    container.find('.manual-categories-section').show();
                }
            });
        },

        /**
         * Initialize sortable sections with enhanced drag-and-drop
         */
        initSortable: function() {
            if ($('.cpb-sections-list').length) {
                $('.cpb-sections-list').sortable({
                    handle: '.cpb-section-header',
                    placeholder: 'cpb-section-placeholder',
                    helper: 'clone',
                    cursor: 'move',
                    tolerance: 'pointer',
                    distance: 5,
                    opacity: 0.8,
                    start: function(event, ui) {
                        ui.helper.addClass('cpb-section-dragging');
                        ui.placeholder.height(ui.item.outerHeight());
                        $('.cpb-sections-list').addClass('cpb-sorting-active');
                    },
                    stop: function(event, ui) {
                        ui.item.removeClass('cpb-section-dragging');
                        $('.cpb-sections-list').removeClass('cpb-sorting-active');
                    },
                    update: this.reorderSections,
                    change: function(event, ui) {
                        // Visual feedback during drag
                        ui.placeholder.addClass('cpb-drop-zone-active');
                    }
                });
                
                // Make sections collapsible for better navigation
                this.initSectionCollapse();
            }
        },

        /**
         * Initialize media uploader
         */
        initMediaUploader: function() {
            // Handle all image upload button variations
            $(document).on('click', '.cpb-upload-image, .upload-image-btn, #upload-content-image, button[id^="upload-"]', function(e) {
                e.preventDefault();
                
                var button = $(this);
                var container = button.closest('.image-upload-field, .config-group');
                
                // Check if button has data-target attribute (for dynamically generated buttons)
                var targetId = button.data('target');
                var targetInput;
                
                if (targetId) {
                    targetInput = $('#' + targetId);
                } else {
                    // Look for visible text input or hidden input
                    targetInput = container.find('input.image-url-input').first();
                    
                    if (!targetInput.length) {
                        targetInput = container.find('input[type="hidden"].image-url-input, input[type="hidden"][id*="image"]').first();
                    }
                    
                    // Fallback: look for any input near the button
                    if (!targetInput.length) {
                        targetInput = button.siblings('input[type="hidden"], input[type="text"]').first();
                    }
                }
                
                // Find preview container
                var previewContainer = container.find('.image-preview, .content-image-preview').first();
                if (!previewContainer.length) {
                    previewContainer = button.siblings('.cpb-image-preview, .image-preview, .content-image-preview').first();
                }
                
                // Find remove button - check for data-target match
                var removeButton;
                if (targetId) {
                    removeButton = container.find('.remove-image-btn[data-target="' + targetId + '"]').first();
                } else {
                    removeButton = container.find('.cpb-remove-image, .remove-image-btn, #remove-content-image, button[id^="remove-"]').first();
                }
                
                // Check if wp.media is available
                if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                    console.error('WordPress Media Library is not loaded');
                    return;
                }
                
                var mediaUploader = wp.media({
                    title: (typeof cpbAdmin !== 'undefined' && cpbAdmin.strings) ? cpbAdmin.strings.selectImage : 'Select Image',
                    button: {
                        text: (typeof cpbAdmin !== 'undefined' && cpbAdmin.strings) ? cpbAdmin.strings.useImage : 'Use Image'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    
                    // Always store the URL in the input
                    targetInput.val(attachment.url);
                    
                    // Show preview
                    if (previewContainer.length) {
                        previewContainer.html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto; border-radius: 4px;" alt="Image preview">').show();
                    } else {
                        // Create preview if it doesn't exist
                        var newPreview = $('<div class="image-preview" style="display:block; margin-top:10px; padding:10px; border:1px solid #ddd; background:#f9f9f9; border-radius: 4px;"><img src="' + attachment.url + '" style="max-width: 200px; height: auto; border-radius: 4px;" alt="Image preview"></div>');
                        
                        // Insert preview after the input or button
                        if (targetInput.length) {
                            targetInput.after(newPreview);
                        } else {
                            button.after(newPreview);
                        }
                        
                        // Update previewContainer reference
                        previewContainer = newPreview;
                    }
                    
                    // Show remove button
                    if (removeButton.length) {
                        removeButton.show();
                    } else {
                        // Create remove button if it doesn't exist
                        var newRemoveButton = $('<button type="button" class="remove-image-btn button" style="margin-left: 10px;">Remove Image</button>');
                        button.after(newRemoveButton);
                    }
                    
                    // Process image through secure image handler
                    CPB_Admin.processImageUpload(attachment.id, targetInput, previewContainer);
                });
                
                mediaUploader.open();
            });
            
            // Handle manual URL input - show preview when user types/pastes URL
            $(document).on('change blur', 'input.image-url-input', function() {
                var input = $(this);
                var url = input.val().trim();
                var previewContainer = input.siblings('.image-preview').first();
                
                if (!previewContainer.length) {
                    previewContainer = input.next('.image-preview');
                }
                
                if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
                    // Show preview for URL
                    if (previewContainer.length) {
                        previewContainer.html('<img src="' + url + '" style="max-width: 200px; height: auto;" alt="Image preview" onerror="this.parentElement.innerHTML=\'<p style=color:red;>Invalid image URL</p>\';">').show();
                    } else {
                        // Create preview if it doesn't exist
                        var newPreview = $('<div class="image-preview" style="display:block; margin-top:10px; padding:10px; border:1px solid #ddd; background:#f9f9f9;"><img src="' + url + '" style="max-width: 200px; height: auto;" alt="Image preview" onerror="this.parentElement.innerHTML=\'<p style=color:red;>Invalid image URL</p>\';"></div>');
                        input.after(newPreview);
                    }
                } else if (!url) {
                    // Clear preview if input is empty
                    if (previewContainer.length) {
                        previewContainer.hide().empty();
                    }
                }
            });
            
            // Handle all remove image button variations
            $(document).on('click', '.cpb-remove-image, .remove-image-btn, #remove-content-image, button[id^="remove-"]', function(e) {
                e.preventDefault();
                
                var button = $(this);
                var container = button.closest('.image-upload-field, .config-group, .product-item, .category-item');
                
                // Check if button has data-target attribute
                var targetId = button.data('target');
                var targetInput;
                
                if (targetId) {
                    targetInput = $('#' + targetId);
                } else {
                    // Look for image input in the same container
                    targetInput = container.find('input.image-url-input').first();
                    
                    if (!targetInput.length) {
                        targetInput = container.find('input[type="hidden"].image-url-input, input[type="hidden"][name*="image"]').first();
                    }
                    
                    // Fallback: look for any hidden input near the button
                    if (!targetInput.length) {
                        targetInput = button.siblings('input[type="hidden"]').first();
                    }
                    
                    // Another fallback: look for text input with image URL
                    if (!targetInput.length) {
                        targetInput = container.find('input[type="text"][name*="image"]').first();
                    }
                }
                
                // Find preview container
                var previewContainer = container.find('.image-preview, .content-image-preview').first();
                if (!previewContainer.length) {
                    previewContainer = button.siblings('.cpb-image-preview, .image-preview, .content-image-preview').first();
                }
                
                // Clear values and data attributes
                if (targetInput.length) {
                    targetInput.val('');
                    targetInput.removeData('secure-urls');
                    targetInput.removeData('fallback-urls');
                    targetInput.removeData('is-protected');
                }
                
                // Clear and hide preview
                if (previewContainer.length) {
                    previewContainer.empty().hide();
                }
                
                // Hide remove button
                button.hide();
                
                // Show upload button if it was hidden
                var uploadButton = container.find('.upload-image-btn, .cpb-upload-image, button[id^="upload-"]').first();
                if (uploadButton.length) {
                    uploadButton.show();
                }
                
                // Remove any secure image indicators
                container.find('.cpb-secure-indicator').remove();
                
                // Show success message
                CPB_Admin.showNotice('success', 'Image removed successfully');
            });
        },

        /**
         * Save page with enhanced validation and feedback
         */
        savePage: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var form = button.closest('form');
            
            // Validate required fields
            if (!CPB_Admin.validatePageForm(form)) {
                return;
            }
            
            // Show loading state
            CPB_Admin.showLoading(button);
            
            // Collect all page and section data
            var pageData = CPB_Admin.serializePageData(form);
            var sectionsData = CPB_Admin.collectSectionsData();
            
            var data = {
                action: 'cpb_admin_action',
                cpb_action: 'save_page',
                nonce: cpb_admin.nonce,
                page_data: pageData,
                sections_data: sectionsData
            };
            
            $.post(cpb_admin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        CPB_Admin.showNotice('success', cpb_admin.strings.save_success || 'Page saved successfully');
                        
                        // Update page ID if it's a new page
                        if (response.data.page_id && !$('#page_id').val()) {
                            $('#page_id').val(response.data.page_id);
                            
                            // Update URL to edit mode
                            var newUrl = window.location.href.replace('custom-page-builder-new', 'custom-page-builder-edit') + '&page_id=' + response.data.page_id;
                            window.history.replaceState({}, '', newUrl);
                        }
                        
                        // Update last saved timestamp
                        CPB_Admin.updateLastSavedTime();
                        
                        // Trigger save event for other components
                        $(document).trigger('cpb:page-saved', [response.data]);
                    } else {
                        CPB_Admin.showNotice('error', response.data.message || cpb_admin.strings.save_error || 'Failed to save page');
                    }
                })
                .fail(function(xhr, status, error) {
                    var errorMessage = cpb_admin.strings.save_error || 'Failed to save page';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    }
                    CPB_Admin.showNotice('error', errorMessage);
                })
                .always(function() {
                    CPB_Admin.hideLoading(button);
                });
        },

        /**
         * Delete page
         */
        deletePage: function(e) {
            e.preventDefault();
            
            // Proceed without confirmation
            
            var button = $(this);
            var pageId = button.data('page-id') || $('#page_id').val();
            
            CPB_Admin.showLoading(button);
            
            var data = {
                action: 'cpb_admin_action',
                cpb_action: 'delete_page',
                nonce: cpb_admin.nonce,
                page_id: pageId
            };
            
            $.post(cpb_admin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        // Redirect to page list
                        window.location.href = 'admin.php?page=custom-page-builder';
                    } else {
                        CPB_Admin.showNotice('error', response.data.message || cpb_admin.strings.save_error);
                    }
                })
                .fail(function() {
                    CPB_Admin.showNotice('error', cpb_admin.strings.save_error);
                })
                .always(function() {
                    CPB_Admin.hideLoading(button);
                });
        },

        /**
         * Duplicate page
         */
        duplicatePage: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var pageId = button.data('page-id');
            
            CPB_Admin.showLoading(button);
            
            var data = {
                action: 'cpb_admin_action',
                cpb_action: 'duplicate_page',
                nonce: cpb_admin.nonce,
                page_id: pageId
            };
            
            $.post(cpb_admin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        // Redirect to edit the new page
                        window.location.href = 'admin.php?page=custom-page-builder-edit&page_id=' + response.data.page_id;
                    } else {
                        CPB_Admin.showNotice('error', response.data.message || cpb_admin.strings.save_error);
                    }
                })
                .fail(function() {
                    CPB_Admin.showNotice('error', cpb_admin.strings.save_error);
                })
                .always(function() {
                    CPB_Admin.hideLoading(button);
                });
        },

        /**
         * Show add section modal
         */
        showAddSectionModal: function(e) {
            e.preventDefault();
            
            // Create modal HTML
            var modalHtml = CPB_Admin.createSectionModal();
            $('body').append(modalHtml);
            
            // Show modal
            $('.cpb-modal').fadeIn();
            
            // Initialize modal functionality
            CPB_Admin.initSectionConfigFields();
        },

        /**
         * Edit section
         */
        editSection: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var sectionItem = button.closest('.cpb-section-item');
            var sectionId = sectionItem.data('section-id');
            var sectionType = sectionItem.data('section-type');
            
            // Get section data
            var sectionData = CPB_Admin.getSectionData(sectionItem);
            
            // Create modal HTML with existing data
            var modalHtml = CPB_Admin.createSectionModal(sectionType, sectionData, sectionId);
            $('body').append(modalHtml);
            
            // Show modal
            $('.cpb-modal').fadeIn();
            
            // Load section configuration if we have a section type
            if (sectionType) {
                CPB_Admin.loadSectionConfig(sectionType, sectionData, sectionId);
            }
            
            // Initialize modal functionality
            CPB_Admin.initSectionConfigFields();
        },

        /**
         * Delete section
         */
        deleteSection: function(e) {
            e.preventDefault();
            
            // Proceed without confirmation
            
            var button = $(this);
            var sectionItem = button.closest('.cpb-section-item');
            var sectionId = sectionItem.data('section-id');
            
            CPB_Admin.showLoading(button);
            
            var data = {
                action: 'cpb_admin_action',
                cpb_action: 'delete_section',
                nonce: cpb_admin.nonce,
                section_id: sectionId
            };
            
            $.post(cpb_admin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        sectionItem.fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        CPB_Admin.showNotice('error', response.data.message || cpb_admin.strings.save_error);
                    }
                })
                .fail(function() {
                    CPB_Admin.showNotice('error', cpb_admin.strings.save_error);
                })
                .always(function() {
                    CPB_Admin.hideLoading(button);
                });
        },

        /**
         * Save section with enhanced validation and real-time updates
         */
        saveSection: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var modal = button.closest('.cpb-modal');
            var form = modal.find('form');
            var sectionId = form.find('#section_id').val();
            var pageId = $('#page_id').val();
            
            // Validate section form
            if (!CPB_Admin.validateSectionForm(form)) {
                return;
            }
            
            CPB_Admin.showLoading(button);
            
            var sectionData = CPB_Admin.serializeSectionData(form);
            
            var data = {
                action: 'cpb_admin_action',
                cpb_action: 'save_section',
                nonce: cpb_admin.nonce,
                section_id: sectionId,
                page_id: pageId,
                section_data: sectionData
            };
            
            $.post(cpb_admin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        // Update or add section in the list with animation
                        if (sectionId) {
                            // Update existing section
                            var sectionItem = $('.cpb-section-item[data-section-id="' + sectionId + '"]');
                            sectionItem.fadeOut(200, function() {
                                $(this).replaceWith(response.data.section_html);
                                $('.cpb-section-item[data-section-id="' + sectionId + '"]').fadeIn(200);
                            });
                        } else {
                            // Add new section with animation
                            var newSection = $(response.data.section_html).hide();
                            $('.cpb-sections-list').append(newSection);
                            newSection.fadeIn(300);
                            
                            // Remove empty state if it exists
                            $('.cpb-empty-sections').fadeOut();
                        }
                        
                        CPB_Admin.closeModal();
                        CPB_Admin.showNotice('success', cpb_admin.strings.save_success || 'Section saved successfully');
                        
                        // Trigger section saved event
                        $(document).trigger('cpb:section-saved', [response.data]);
                        
                        // Re-initialize sortable if needed
                        CPB_Admin.initSortable();
                    } else {
                        CPB_Admin.showNotice('error', response.data.message || cpb_admin.strings.save_error || 'Failed to save section');
                    }
                })
                .fail(function(xhr, status, error) {
                    var errorMessage = cpb_admin.strings.save_error || 'Failed to save section';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    }
                    CPB_Admin.showNotice('error', errorMessage);
                })
                .always(function() {
                    CPB_Admin.hideLoading(button);
                });
        },

        /**
         * Reorder sections
         */
        reorderSections: function(event, ui) {
            var sectionIds = [];
            $('.cpb-section-item').each(function() {
                sectionIds.push($(this).data('section-id'));
            });
            
            var data = {
                action: 'cpb_admin_action',
                cpb_action: 'reorder_sections',
                nonce: cpb_admin.nonce,
                page_id: $('#page_id').val(),
                section_order: sectionIds
            };
            
            $.post(cpb_admin.ajax_url, data)
                .fail(function() {
                    CPB_Admin.showNotice('error', cpb_admin.strings.save_error);
                    // Revert the sort
                    $('.cpb-sections-list').sortable('cancel');
                });
        },

        /**
         * Toggle section content
         */
        toggleSection: function(e) {
            // Don't toggle if clicking on buttons
            if ($(e.target).is('button') || $(e.target).closest('button').length) {
                return;
            }
            
            var header = $(this);
            var sectionItem = header.closest('.cpb-section-item');
            var content = sectionItem.find('.cpb-section-content');
            
            content.slideToggle();
        },

        /**
         * Close modal
         */
        closeModal: function() {
            $('.cpb-modal').fadeOut(function() {
                $(this).remove();
            });
        },

        /**
         * Show loading state
         */
        showLoading: function(element) {
            element.prop('disabled', true);
            element.addClass('cpb-loading');
            
            if (!element.find('.cpb-spinner').length) {
                element.append(' <span class="cpb-spinner"></span>');
            }
        },

        /**
         * Hide loading state
         */
        hideLoading: function(element) {
            element.prop('disabled', false);
            element.removeClass('cpb-loading');
            element.find('.cpb-spinner').remove();
        },

        /**
         * Show notice
         */
        showNotice: function(type, message) {
            var notice = $('<div class="cpb-notice ' + type + '">' + message + '</div>');
            $('.cpb-page-builder').prepend(notice);
            
            // Auto-hide success notices
            if (type === 'success') {
                setTimeout(function() {
                    notice.fadeOut(function() {
                        $(this).remove();
                    });
                }, 3000);
            }
        },

        /**
         * Serialize page data
         */
        serializePageData: function(form) {
            var data = {};
            
            form.find('input, select, textarea').each(function() {
                var field = $(this);
                var name = field.attr('name');
                var value = field.val();
                
                if (!name) return;
                
                // Handle checkboxes
                if (field.is(':checkbox')) {
                    value = field.is(':checked') ? (field.val() || '1') : '';
                }
                
                // Handle radio buttons
                if (field.is(':radio') && !field.is(':checked')) {
                    return;
                }
                
                // For page data, we typically don't have nested structures
                // but we'll use the same function for consistency
                CPB_Admin.setNestedValue(data, name, value);
            });
            
            return data;
        },

        /**
         * Serialize section data
         */
        serializeSectionData: function(form) {
            var data = {};
            
            // Get all form elements
            var formElements = form.find('input, select, textarea');
            
            formElements.each(function() {
                var field = $(this);
                var name = field.attr('name');
                var value = field.val();
                
                if (!name) return;
                
                // Handle checkboxes
                if (field.is(':checkbox')) {
                    value = field.is(':checked') ? (field.val() || '1') : '';
                }
                
                // Handle radio buttons
                if (field.is(':radio') && !field.is(':checked')) {
                    return;
                }
                
                // Parse nested field names like config[products][0][title]
                CPB_Admin.setNestedValue(data, name, value);
            });
            
            return data;
        },
        
        /**
         * Set nested value in object using dot notation or bracket notation
         */
        setNestedValue: function(obj, path, value) {
            // Convert bracket notation to dot notation
            // config[products][0][title] -> config.products.0.title
            var normalizedPath = path.replace(/\[(\w+)\]/g, '.$1').replace(/^\./, '');
            var keys = normalizedPath.split('.');
            var current = obj;
            
            for (var i = 0; i < keys.length - 1; i++) {
                var key = keys[i];
                
                // Check if next key is numeric (array index)
                var nextKey = keys[i + 1];
                var isNextKeyNumeric = !isNaN(parseInt(nextKey));
                
                if (!(key in current)) {
                    current[key] = isNextKeyNumeric ? [] : {};
                }
                
                current = current[key];
            }
            
            var lastKey = keys[keys.length - 1];
            
            // Handle empty values for arrays
            if (value === '' && Array.isArray(current)) {
                return;
            }
            
            current[lastKey] = value;
        },

        /**
         * Get section data from DOM
         */
        getSectionData: function(sectionItem) {
            if (!sectionItem || !sectionItem.length) {
                return {};
            }

            var cachedConfig = sectionItem.data('section-config');
            if (cachedConfig && typeof cachedConfig === 'object') {
                return cachedConfig;
            }

            var rawConfig = sectionItem.attr('data-section-config');
            if (!rawConfig) {
                return {};
            }

            try {
                var parsed = JSON.parse(rawConfig);
                // Store for subsequent lookups on the same element
                sectionItem.data('section-config', parsed);
                return parsed;
            } catch (error) {
                console.error('Failed to parse section config JSON', error, rawConfig);
                return {};
            }
        },

        /**
         * Create section modal HTML
         */
        createSectionModal: function(sectionType, sectionData, sectionId) {
            sectionType = sectionType || '';
            sectionData = sectionData || {};
            sectionId = sectionId || '';
            
            var modalHtml = '<div class="cpb-modal">' +
                '<div class="cpb-modal-content">' +
                    '<div class="cpb-modal-header">' +
                        '<h2>' + (sectionId ? 'Edit Section' : 'Add Section') + '</h2>' +
                        '<button class="cpb-modal-close">&times;</button>' +
                    '</div>' +
                    '<div class="cpb-modal-body">' +
                        '<form id="cpb-section-form">' +
                            '<input type="hidden" id="section_id" name="section_id" value="' + sectionId + '">' +
                            '<div class="cpb-form-field">' +
                                '<label for="section_type">Section Type</label>' +
                                '<select id="section_type" name="section_type" ' + (sectionId ? 'disabled' : '') + '>' +
                                    '<option value="">Select Section Type</option>' +
                                    '<option value="testimonials"' + (sectionType === 'testimonials' ? ' selected' : '') + '>Testimonials</option>' +
                                    '<option value="product_grid"' + (sectionType === 'product_grid' ? ' selected' : '') + '>Product Grid</option>' +
                                    '<option value="hero_banner"' + (sectionType === 'hero_banner' ? ' selected' : '') + '>Hero Banner</option>' +
                                    '<option value="category_showcase"' + (sectionType === 'category_showcase' ? ' selected' : '') + '>Category Showcase</option>' +
                                    '<option value="content_block"' + (sectionType === 'content_block' ? ' selected' : '') + '>Content Block</option>' +
                                '</select>' +
                            '</div>' +
                            '<div id="section-config-container">' +
                                // Section-specific configuration will be loaded here
                            '</div>' +
                        '</form>' +
                    '</div>' +
                    '<div class="cpb-modal-footer">' +
                        '<button class="cpb-btn cpb-btn-secondary cpb-modal-cancel">Cancel</button>' +
                        '<button class="cpb-btn cpb-btn-primary cpb-modal-save">Save Section</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
            
            return modalHtml;
        },

        /**
         * Load section configuration form
         */
        loadSectionConfig: function(sectionType, sectionData, sectionId) {
            if (!sectionType) {
                $('#section-config-container').empty();
                return;
            }

            // Show loading
            $('#section-config-container').html('<div class="cpb-loading-config"><span class="cpb-spinner"></span> Loading configuration...</div>');

            // Load section configuration via AJAX
            var data = {
                action: 'cpb_admin_action',
                cpb_action: 'load_section_config',
                nonce: cpb_admin.nonce,
                section_type: sectionType,
                section_data: sectionData || {}
            };

            if (sectionId) {
                data.section_id = sectionId;
            }

            $.post(cpb_admin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        $('#section-config-container').html(response.data.config_html);
                        
                        // Initialize any special fields (color pickers, media uploaders, etc.)
                        CPB_Admin.initSectionConfigFields();
                    } else {
                        $('#section-config-container').html('<div class="cpb-notice error">Failed to load section configuration.</div>');
                    }
                })
                .fail(function() {
                    $('#section-config-container').html('<div class="cpb-notice error">Failed to load section configuration.</div>');
                });
        },

        /**
         * Initialize section configuration fields
         */
        initSectionConfigFields: function() {
            // Initialize color pickers
            if ($.fn.wpColorPicker) {
                $('.cpb-color-picker').wpColorPicker();
            }

            // Re-initialize media uploader for dynamically loaded content
            this.initMediaUploader();

            // Initialize repeatable fields
            this.initRepeatableFields();

            // Initialize conditional fields
            this.initConditionalFields();

            // Initialize WooCommerce category/tag selectors display
            setTimeout(function() {
                $('.wc-category-selector-wrapper').each(function() {
                    var wrapper = $(this);
                    var hiddenInput = wrapper.find('.cpb-selected-wc-categories');
                    var val = hiddenInput.val();

                    // If hidden input is empty but a radio is checked, sync hidden input
                    if ((!val || val === '[]' || val === '') && wrapper.find('.wc-category-radio:checked').length) {
                        var checkedVal = wrapper.find('.wc-category-radio:checked').val();
                        hiddenInput.val(JSON.stringify([parseInt(checkedVal, 10)]));
                    }

                    // If 'all' is present, ensure UI reflects disabled list
                    var parsed = JSON.parse(hiddenInput.val() || '[]');
                    if (parsed.indexOf('all') !== -1) {
                        wrapper.find('.wc-category-all-checkbox').prop('checked', true);
                        wrapper.find('.wc-categories-list').css({'opacity': '0.5', 'pointer-events': 'none'});
                    } else {
                        wrapper.find('.wc-categories-list').css({'opacity': '1', 'pointer-events': 'auto'});
                    }

                    // Update visible display
                    updateWcCategoryDisplay(wrapper);
                });

                $('.wc-tag-selector-wrapper').each(function() {
                    var wrapper = $(this);
                    var hiddenInput = wrapper.find('.cpb-selected-wc-tags');
                    var val = hiddenInput.val();

                    // If hidden input is empty but a radio is checked, sync hidden input
                    if ((!val || val === '[]' || val === '') && wrapper.find('.wc-tag-radio:checked').length) {
                        var checkedVal = wrapper.find('.wc-tag-radio:checked').val();
                        hiddenInput.val(JSON.stringify([parseInt(checkedVal, 10)]));
                    }

                    // If 'all' is present, ensure UI reflects disabled list
                    var parsed = JSON.parse(hiddenInput.val() || '[]');
                    if (parsed.indexOf('all') !== -1) {
                        wrapper.find('.wc-tag-all-checkbox').prop('checked', true);
                        wrapper.find('.wc-tags-list').css({'opacity': '0.5', 'pointer-events': 'none'});
                    } else {
                        wrapper.find('.wc-tags-list').css({'opacity': '1', 'pointer-events': 'auto'});
                    }

                    // Update visible display
                    updateWcTagDisplay(wrapper);
                });

                // Helper functions for display update
                function updateWcCategoryDisplay(wrapper) {
                    var hiddenInput = wrapper.find('.cpb-selected-wc-categories');
                    var displayContainer = wrapper.find('.selected-wc-categories-tags');
                    var selected = JSON.parse(hiddenInput.val() || '[]');
                    
                    if (selected.includes('all')) {
                        displayContainer.html('<span class="category-tag" style="display: inline-block; background: #4caf50; color: white; padding: 5px 10px; margin: 2px; border-radius: 3px; font-size: 12px;">✨ All Categories</span>');
                    } else if (selected.length > 0) {
                        var html = '';
                        var checked = wrapper.find('.wc-category-radio:checked');
                        if (checked.length) {
                            var label = checked.closest('label').find('strong').text();
                            html = '<span class="category-tag" style="display: inline-block; background: #0073aa; color: white; padding: 5px 10px; margin: 2px; border-radius: 3px; font-size: 12px;">' + label + '</span>';
                        }
                        displayContainer.html(html);
                    } else {
                        displayContainer.html('<span style="color: #999; font-style: italic;">No categories selected</span>');
                    }
                }

                function updateWcTagDisplay(wrapper) {
                    var hiddenInput = wrapper.find('.cpb-selected-wc-tags');
                    var displayContainer = wrapper.find('.selected-wc-tags-tags');
                    var selected = JSON.parse(hiddenInput.val() || '[]');
                    
                    if (selected.includes('all')) {
                        displayContainer.html('<span class="tag-tag" style="display: inline-block; background: #10b981; color: white; padding: 5px 10px; margin: 2px; border-radius: 3px; font-size: 12px;">✨ All Tags</span>');
                    } else if (selected.length > 0) {
                        var html = '';
                        var checked = wrapper.find('.wc-tag-radio:checked');
                        if (checked.length) {
                            var label = checked.closest('label').find('strong').text();
                            html = '<span class="tag-tag" style="display: inline-block; background: #10b981; color: white; padding: 5px 10px; margin: 2px; border-radius: 3px; font-size: 12px;">' + label + '</span>';
                        }
                        displayContainer.html(html);
                    } else {
                        displayContainer.html('<span style="color: #999; font-style: italic;">No tags selected</span>');
                    }
                }
            }, 100); // Small delay to ensure DOM is fully rendered
        },

        /**
         * Initialize repeatable fields (like testimonials, products, etc.)
         */
        initRepeatableFields: function() {
            // Add item buttons
            $(document).on('click', '.cpb-add-item', function(e) {
                e.preventDefault();
                
                var button = $(this);
                var container = button.siblings('.cpb-repeatable-container');
                var template = container.find('.cpb-item-template').html();
                var index = container.find('.cpb-repeatable-item').length;
                
                // Replace placeholder index with actual index
                template = template.replace(/\{\{INDEX\}\}/g, index);
                
                container.find('.cpb-repeatable-items').append(template);
                
                // Re-initialize media uploaders for new item
                CPB_Admin.initMediaUploader();
            });

            // Remove item buttons
            $(document).on('click', '.cpb-remove-item', function(e) {
                e.preventDefault();
                
                $(this).closest('.cpb-repeatable-item').remove();
                
                // Re-index remaining items
                CPB_Admin.reindexRepeatableItems();
            });

            // Sortable items
            $('.cpb-repeatable-items').sortable({
                handle: '.cpb-item-handle',
                placeholder: 'cpb-item-placeholder',
                update: function() {
                    CPB_Admin.reindexRepeatableItems();
                }
            });
        },

        /**
         * Re-index repeatable items after add/remove/sort
         */
        reindexRepeatableItems: function() {
            $('.cpb-repeatable-items').each(function() {
                $(this).find('.cpb-repeatable-item').each(function(index) {
                    var item = $(this);
                    
                    // Update all name attributes
                    item.find('[name]').each(function() {
                        var name = $(this).attr('name');
                        name = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', name);
                    });
                    
                    // Update item number display
                    item.find('.cpb-item-number').text(index + 1);
                });
            });
        },

        /**
         * Initialize conditional fields
         */
        initConditionalFields: function() {
            $(document).on('change', '[data-conditional]', function() {
                var field = $(this);
                var conditions = field.data('conditional');
                var value = field.val();
                
                if (typeof conditions === 'object') {
                    $.each(conditions, function(targetSelector, expectedValue) {
                        var targets = $(targetSelector);
                        
                        if (value == expectedValue) {
                            targets.show();
                        } else {
                            targets.hide();
                        }
                    });
                }
            });
            
            // Trigger initial state
            $('[data-conditional]').trigger('change');
        },

        /**
         * Initialize section preview
         */
        initSectionPreview: function() {
            // Update preview when form fields change
            $(document).on('input change', '#cpb-section-form input, #cpb-section-form select, #cpb-section-form textarea', function() {
                CPB_Admin.updateSectionPreview();
            });
        },

        /**
         * Update section preview
         */
        updateSectionPreview: function() {
            var previewContainer = $('.cpb-section-preview .preview-content');
            if (previewContainer.length === 0) return;

            var formData = CPB_Admin.serializeSectionData($('#cpb-section-form'));
            var sectionType = $('#section_type').val();

            // Generate preview based on section type and data
            var previewHtml = CPB_Admin.generateSectionPreview(sectionType, formData);
            previewContainer.html(previewHtml);
        },

        /**
         * Generate section preview HTML
         */
        generateSectionPreview: function(sectionType, data) {
            switch (sectionType) {
                case 'testimonials':
                    return CPB_Admin.generateTestimonialsPreview(data);
                case 'hero_banner':
                    return CPB_Admin.generateHeroBannerPreview(data);
                case 'product_grid':
                    return CPB_Admin.generateProductGridPreview(data);
                case 'category_showcase':
                    return CPB_Admin.generateCategoryShowcasePreview(data);
                case 'content_block':
                    return CPB_Admin.generateContentBlockPreview(data);
                default:
                    return '<p>Preview not available for this section type.</p>';
            }
        },

        /**
         * Generate testimonials preview
         */
        generateTestimonialsPreview: function(data) {
            var html = '<div class="testimonials-preview">';
            
            if (data.title) {
                html += '<h3>' + CPB_Admin.escapeHtml(data.title) + '</h3>';
            }
            
            if (data.subtitle) {
                html += '<p class="subtitle">' + CPB_Admin.escapeHtml(data.subtitle) + '</p>';
            }
            
            var testimonials = data.testimonials || [];
            if (testimonials.length > 0) {
                html += '<div class="testimonials-grid">';
                testimonials.slice(0, 3).forEach(function(testimonial, index) {
                    html += '<div class="testimonial-card">';
                    if (testimonial.rating) {
                        html += '<div class="rating">' + '★'.repeat(parseInt(testimonial.rating)) + '</div>';
                    }
                    if (testimonial.text) {
                        html += '<p>"' + CPB_Admin.escapeHtml(testimonial.text.substring(0, 100)) + '..."</p>';
                    }
                    if (testimonial.author_name) {
                        html += '<div class="author">- ' + CPB_Admin.escapeHtml(testimonial.author_name) + '</div>';
                    }
                    html += '</div>';
                });
                html += '</div>';
            } else {
                html += '<p><em>No testimonials added yet.</em></p>';
            }
            
            html += '</div>';
            return html;
        },

        /**
         * Generate hero banner preview
         */
        generateHeroBannerPreview: function(data) {
            var html = '<div class="hero-banner-preview">';
            
            if (data.title) {
                html += '<h2>' + CPB_Admin.escapeHtml(data.title) + '</h2>';
            }
            
            if (data.subtitle) {
                html += '<p class="subtitle">' + CPB_Admin.escapeHtml(data.subtitle) + '</p>';
            }
            
            if (data.description) {
                html += '<p>' + CPB_Admin.escapeHtml(data.description) + '</p>';
            }
            
            if (data.button_text) {
                html += '<button class="preview-button">' + CPB_Admin.escapeHtml(data.button_text) + '</button>';
            }
            
            html += '</div>';
            return html;
        },

        /**
         * Generate generic preview for other section types
         */
        generateProductGridPreview: function(data) {
            return '<div class="generic-preview"><h4>Product Grid</h4><p>Title: ' + (data.title || 'No title') + '</p></div>';
        },

        generateCategoryShowcasePreview: function(data) {
            return '<div class="generic-preview"><h4>Category Showcase</h4><p>Title: ' + (data.title || 'No title') + '</p></div>';
        },

        generateContentBlockPreview: function(data) {
            return '<div class="generic-preview"><h4>Content Block</h4><p>Title: ' + (data.title || 'No title') + '</p></div>';
        },

        /**
         * Process image upload through secure image handler
         */
        processImageUpload: function(attachmentId, targetInput, previewContainer) {
            // Show processing indicator
            if (previewContainer.length) {
                previewContainer.append('<div class="cpb-image-processing"><span class="cpb-spinner"></span> Processing image...</div>');
            }
            
            var data = {
                action: 'cpb_admin_action',
                cpb_action: 'process_image_upload',
                nonce: cpb_admin.nonce,
                attachment_id: attachmentId
            };
            
            $.post(cpb_admin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        // Store additional image data as data attributes
                        targetInput.data('secure-urls', response.data.secure_urls);
                        targetInput.data('fallback-urls', response.data.fallback_urls);
                        targetInput.data('is-protected', response.data.is_protected);
                        
                        // Show success indicator if secure image plugin is active
                        if (response.data.secure_image_plugin_active && response.data.is_protected) {
                            if (previewContainer.length) {
                                previewContainer.append('<div class="cpb-secure-indicator" title="Image is protected by secure image plugin"><span class="dashicons dashicons-lock"></span></div>');
                            }
                        }
                        
                        // Show warning if secure image plugin is not active
                        if (!response.data.secure_image_plugin_active) {
                            CPB_Admin.showNotice('warning', 'Secure Image plugin is not active. Images will be served without protection.');
                        }
                    } else {
                        CPB_Admin.showNotice('error', response.data.message || 'Failed to process image');
                    }
                })
                .fail(function() {
                    CPB_Admin.showNotice('error', 'Failed to process image');
                })
                .always(function() {
                    // Remove processing indicator
                    if (previewContainer.length) {
                        previewContainer.find('.cpb-image-processing').remove();
                    }
                });
        },

        /**
         * Show schedule modal
         */
        showScheduleModal: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var pageId = button.data('page-id');
            
            // Set page ID in modal
            $('#schedule-page-id').val(pageId);
            
            // Set minimum date to current time
            var now = new Date();
            var minDateTime = now.toISOString().slice(0, 16);
            $('#scheduled-date').attr('min', minDateTime);
            
            // Show modal
            $('#cpb-schedule-modal').fadeIn();
        },

        /**
         * Schedule page
         */
        schedulePage: function(e) {
            e.preventDefault();
            
            var form = $(this);
            var pageId = form.find('#schedule-page-id').val();
            var scheduledDate = form.find('#scheduled-date').val();
            var submitButton = form.find('button[type="submit"]');
            
            if (!scheduledDate) {
                CPB_Admin.showNotice('error', 'Please select a publication date and time.');
                return;
            }
            
            CPB_Admin.showLoading(submitButton);
            
            var data = {
                action: 'cpb_admin_action',
                cpb_action: 'schedule_page',
                nonce: cpb_admin.nonce,
                page_id: pageId,
                scheduled_date: scheduledDate
            };
            
            $.post(cpb_admin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        CPB_Admin.showNotice('success', response.data.message || 'Page scheduled successfully');
                        $('#cpb-schedule-modal').fadeOut();
                        
                        // Reload page to show updated status
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        CPB_Admin.showNotice('error', response.data.message || 'Failed to schedule page');
                    }
                })
                .fail(function() {
                    CPB_Admin.showNotice('error', 'Failed to schedule page');
                })
                .always(function() {
                    CPB_Admin.hideLoading(submitButton);
                });
        },

        /**
         * Unschedule page
         */
        unschedulePage: function(e) {
            e.preventDefault();
            
            // Proceed without confirmation
            
            var button = $(this);
            var pageId = button.data('page-id');
            
            CPB_Admin.showLoading(button);
            
            var data = {
                action: 'cpb_admin_action',
                cpb_action: 'unschedule_page',
                nonce: cpb_admin.nonce,
                page_id: pageId
            };
            
            $.post(cpb_admin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        CPB_Admin.showNotice('success', response.data.message || 'Page unscheduled successfully');
                        
                        // Reload page to show updated status
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        CPB_Admin.showNotice('error', response.data.message || 'Failed to unschedule page');
                    }
                })
                .fail(function() {
                    CPB_Admin.showNotice('error', 'Failed to unschedule page');
                })
                .always(function() {
                    CPB_Admin.hideLoading(button);
                });
        },

        /**
         * Initialize section collapse functionality
         */
        initSectionCollapse: function() {
            $(document).on('click', '.cpb-section-toggle', function(e) {
                e.preventDefault();
                var sectionItem = $(this).closest('.cpb-section-item');
                var content = sectionItem.find('.cpb-section-content');
                var icon = $(this).find('.cpb-toggle-icon');
                
                content.slideToggle(200, function() {
                    if (content.is(':visible')) {
                        icon.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
                        sectionItem.addClass('cpb-section-expanded');
                    } else {
                        icon.removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
                        sectionItem.removeClass('cpb-section-expanded');
                    }
                });
            });
        },

        /**
         * Validate page form
         */
        validatePageForm: function(form) {
            var isValid = true;
            var errors = [];
            
            // Check required fields
            var title = form.find('#page_title').val().trim();
            if (!title) {
                errors.push('Page title is required');
                form.find('#page_title').addClass('cpb-field-error');
                isValid = false;
            } else {
                form.find('#page_title').removeClass('cpb-field-error');
            }
            
            // Validate slug format
            var slug = form.find('#page_slug').val().trim();
            if (slug && !/^[a-z0-9-]+$/.test(slug)) {
                errors.push('Page slug can only contain lowercase letters, numbers, and hyphens');
                form.find('#page_slug').addClass('cpb-field-error');
                isValid = false;
            } else {
                form.find('#page_slug').removeClass('cpb-field-error');
            }
            
            if (!isValid) {
                CPB_Admin.showNotice('error', errors.join('<br>'));
            }
            
            return isValid;
        },

        /**
         * Validate section form
         */
        validateSectionForm: function(form) {
            var isValid = true;
            var errors = [];
            
            // Check section type is selected
            var sectionType = form.find('#section_type').val();
            if (!sectionType) {
                errors.push('Please select a section type');
                form.find('#section_type').addClass('cpb-field-error');
                isValid = false;
            } else {
                form.find('#section_type').removeClass('cpb-field-error');
            }
            
            // Validate section-specific fields
            switch (sectionType) {
                case 'testimonials':
                    isValid = CPB_Admin.validateTestimonialsSection(form, errors) && isValid;
                    break;
                case 'hero_banner':
                    isValid = CPB_Admin.validateHeroBannerSection(form, errors) && isValid;
                    break;
                case 'product_grid':
                    isValid = CPB_Admin.validateProductGridSection(form, errors) && isValid;
                    break;
                case 'category_showcase':
                    isValid = CPB_Admin.validateCategoryShowcaseSection(form, errors) && isValid;
                    break;
                case 'content_block':
                    isValid = CPB_Admin.validateContentBlockSection(form, errors) && isValid;
                    break;
            }
            
            if (!isValid) {
                CPB_Admin.showNotice('error', errors.join('<br>'));
            }
            
            return isValid;
        },

        /**
         * Validate testimonials section
         */
        validateTestimonialsSection: function(form, errors) {
            var isValid = true;
            var testimonials = form.find('.cpb-repeatable-item');
            
            if (testimonials.length === 0) {
                errors.push('At least one testimonial is required');
                isValid = false;
            }
            
            testimonials.each(function(index) {
                var item = $(this);
                var text = item.find('[name*="[text]"]').val().trim();
                var author = item.find('[name*="[author_name]"]').val().trim();
                
                if (!text) {
                    errors.push('Testimonial #' + (index + 1) + ' text is required');
                    item.find('[name*="[text]"]').addClass('cpb-field-error');
                    isValid = false;
                }
                
                if (!author) {
                    errors.push('Testimonial #' + (index + 1) + ' author name is required');
                    item.find('[name*="[author_name]"]').addClass('cpb-field-error');
                    isValid = false;
                }
            });
            
            return isValid;
        },

        /**
         * Validate hero banner section
         */
        validateHeroBannerSection: function(form, errors) {
            var isValid = true;
            var title = form.find('[name="title"]').val().trim();
            
            if (!title) {
                errors.push('Hero banner title is required');
                form.find('[name="title"]').addClass('cpb-field-error');
                isValid = false;
            }
            
            return isValid;
        },

        /**
         * Validate product grid section
         */
        validateProductGridSection: function(form, errors) {
            var isValid = true;
            var products = form.find('.cpb-repeatable-item');
            
            if (products.length === 0) {
                errors.push('At least one product is required');
                isValid = false;
            }
            
            return isValid;
        },

        /**
         * Validate category showcase section
         */
        validateCategoryShowcaseSection: function(form, errors) {
            var isValid = true;
            var categories = form.find('.cpb-repeatable-item');
            
            if (categories.length === 0) {
                errors.push('At least one category is required');
                isValid = false;
            }
            
            return isValid;
        },

        /**
         * Validate content block section
         */
        validateContentBlockSection: function(form, errors) {
            var isValid = true;
            var content = form.find('[name="content"]').val().trim();
            
            if (!content) {
                errors.push('Content block content is required');
                form.find('[name="content"]').addClass('cpb-field-error');
                isValid = false;
            }
            
            return isValid;
        },

        /**
         * Collect all sections data from the page
         */
        collectSectionsData: function() {
            var sectionsData = [];
            
            $('.cpb-section-item').each(function(index) {
                var sectionItem = $(this);
                var sectionId = sectionItem.data('section-id');
                var sectionType = sectionItem.data('section-type');
                
                sectionsData.push({
                    id: sectionId,
                    type: sectionType,
                    order: index
                });
            });
            
            return sectionsData;
        },

        /**
         * Update category numbers after add/remove
         */
        updateCategoryNumbers: function() {
            $('#categories-list .category-item').each(function(index) {
                $(this).find('.category-number').text(index + 1);
            });
        },
        
        /**
         * Update product numbers after add/remove
         */
        updateProductNumbers: function() {
            $('#products-list .product-item').each(function(index) {
                $(this).find('.product-number').text(index + 1);
            });
        },

        /**
         * Add new product to product grid
         */
        addProduct: function() {
            console.log('🔵 addProduct() called');
            
            var container = $('#products-list');
            console.log('🔵 Container found:', container.length > 0);
            
            var index = container.find('.product-item').length;
            console.log('🔵 Current product count:', index);
            
            // Show loading indicator
            var loadingHtml = '<div class="product-loading" style="background: #fff3cd; padding: 20px; margin: 10px 0; border: 2px solid #ffc107; border-radius: 5px; text-align: center;">' +
                '<p style="margin: 0; font-size: 16px; font-weight: bold;">⏳ Loading product form with category selectors...</p>' +
                '<p style="margin: 10px 0 0 0; color: #666;">Please wait while we fetch the complete product template.</p>' +
                '</div>';
            container.append(loadingHtml);
            
            // Make AJAX call to get the complete product template with category selectors
            var data = {
                action: 'cpb_admin_action',
                cpb_action: 'get_product_template',
                nonce: cpb_admin.nonce,
                index: index
            };
            
            console.log('🔵 Sending AJAX request:', data);
            console.log('🔵 AJAX URL:', cpb_admin.ajax_url);
            
            $.post(cpb_admin.ajax_url, data)
                .done(function(response) {
                    console.log('✅ AJAX response received:', response);
                    
                    // Remove loading indicator
                    container.find('.product-loading').remove();
                    
                    if (response.success) {
                        console.log('✅ Response success = true');
                        console.log('✅ HTML length:', response.data.html.length);
                        
                        // Show success message
                        var successMsg = '<div class="product-success-msg" style="background: #d4edda; padding: 15px; margin: 10px 0; border: 2px solid #28a745; border-radius: 5px;">' +
                            '<p style="margin: 0; color: #155724; font-weight: bold;">✅ Product form loaded successfully with category selectors!</p>' +
                            '</div>';
                        container.append(successMsg);
                        setTimeout(function() {
                            container.find('.product-success-msg').fadeOut(function() { $(this).remove(); });
                        }, 3000);
                        
                        container.append(response.data.html);
                        CPB_Admin.updateProductNumbers();
                        CPB_Admin.initMediaUploader();
                        
                        console.log('✅ Product added to DOM');
                    } else {
                        console.error('❌ Response success = false');
                        console.error('❌ Error message:', response.data ? response.data.message : 'No error message');
                        
                        // Show error message
                        var errorMsg = '<div class="product-error-msg" style="background: #f8d7da; padding: 15px; margin: 10px 0; border: 2px solid #dc3545; border-radius: 5px;">' +
                            '<p style="margin: 0; color: #721c24; font-weight: bold;">❌ AJAX returned error</p>' +
                            '<p style="margin: 5px 0 0 0; color: #721c24;">Error: ' + (response.data ? response.data.message : 'Unknown error') + '</p>' +
                            '<p style="margin: 5px 0 0 0; color: #721c24; font-size: 12px;">Using fallback template without category selectors.</p>' +
                            '</div>';
                        container.append(errorMsg);
                        
                        // Fallback to basic template if AJAX fails
                        CPB_Admin.addProductFallback(index);
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('❌ AJAX request failed');
                    console.error('❌ Status:', status);
                    console.error('❌ Error:', error);
                    console.error('❌ Response:', xhr.responseText);
                    
                    // Remove loading indicator
                    container.find('.product-loading').remove();
                    
                    // Show detailed error message
                    var errorMsg = '<div class="product-error-msg" style="background: #f8d7da; padding: 15px; margin: 10px 0; border: 2px solid #dc3545; border-radius: 5px;">' +
                        '<p style="margin: 0; color: #721c24; font-weight: bold;">❌ AJAX Request Failed</p>' +
                        '<p style="margin: 5px 0 0 0; color: #721c24;">Status: ' + status + '</p>' +
                        '<p style="margin: 5px 0 0 0; color: #721c24;">Error: ' + error + '</p>' +
                        '<p style="margin: 5px 0 0 0; color: #721c24; font-size: 12px;">Check browser console for details. Using fallback template.</p>' +
                        '</div>';
                    container.append(errorMsg);
                    
                    // Fallback to basic template if AJAX fails
                    CPB_Admin.addProductFallback(index);
                });
        },
        
        /**
         * Fallback method to add product with basic template (no category selectors)
         */
        addProductFallback: function(index) {
            var container = $('#products-list');
            
            var productHtml = `
                <div class="product-item" data-index="${index}">
                    <div class="product-header">
                        <span class="product-number">${index + 1}</span>
                        <button type="button" class="remove-product button">🗑️ Remove</button>
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label>Product Image</label>
                            <div class="image-upload-field">
                                <input type="hidden" name="config[products][${index}][image]" 
                                       value="" class="image-url-input" />
                                <div class="image-button-group">
                                    <button type="button" class="upload-image-btn button">📷 Select Image</button>
                                    <button type="button" class="remove-image-btn button" style="display:none;">🗑️ Remove Image</button>
                                </div>
                                <div class="image-preview" style="display:none;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="config-group">
                        <label>Product Title</label>
                        <input type="text" name="config[products][${index}][title]" 
                               value="" placeholder="Product Name" />
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label>Price</label>
                            <input type="text" name="config[products][${index}][price]" 
                                   value="" placeholder="$99.99" />
                        </div>
                        
                        <div class="config-group">
                            <label>Sale Price (Optional)</label>
                            <input type="text" name="config[products][${index}][sale_price]" 
                                   value="" placeholder="$79.99" />
                        </div>
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label>Badge (Optional)</label>
                            <input type="text" name="config[products][${index}][badge]" 
                                   value="" placeholder="Sale, New, Featured" />
                        </div>
                        
                        <div class="config-group">
                            <label>SKU (Optional)</label>
                            <input type="text" name="config[products][${index}][sku]" 
                                   value="" placeholder="Product SKU/Code" />
                        </div>
                    </div>
                    
                    <div class="config-group">
                        <label>Description</label>
                        <textarea name="config[products][${index}][description]" rows="3" 
                                  class="cpb-rich-textarea"
                                  placeholder="Product description..."></textarea>
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label>Product Link</label>
                            <input type="url" name="config[products][${index}][link]" 
                                   value="" placeholder="https://example.com/product" />
                        </div>
                        
                        <div class="config-group">
                            <label>Button Text</label>
                            <input type="text" name="config[products][${index}][button_text]" 
                                   value="View Product" />
                        </div>
                    </div>
                    
                    <div class="config-group">
                        <label>
                            <input type="checkbox" name="config[products][${index}][featured]" value="1" />
                            Featured Product
                        </label>
                    </div>
                    
                    <div class="config-group" style="background: #fff3cd; padding: 15px; border: 2px solid #ffc107; margin: 15px 0; border-radius: 5px;">
                        <p style="margin: 0; color: #856404;">
                            ⚠️ <strong>Note:</strong> Category and tag selectors are not available when adding products dynamically. 
                            Please save the section and re-edit it to access full category management features.
                        </p>
                    </div>
                </div>
            `;
            
            container.append(productHtml);
            this.updateProductNumbers();
            this.initMediaUploader();
        },

        /**
         * Remove product from product grid
         */
        removeProduct: function(element) {
            $(element).closest('.product-item').remove();
            this.updateProductNumbers();
        },

        /**
         * Add new category to category showcase
         */
        addCategory: function() {
            var container = $('#categories-list');
            var index = container.find('.category-item').length;
            
            var categoryHtml = `
                <div class="category-item" data-index="${index}">
                    <div class="category-header">
                        <span class="category-number">${index + 1}</span>
                        <button type="button" class="remove-category button">🗑️ Remove</button>
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label>Category Title</label>
                            <input type="text" name="config[categories][${index}][title]" 
                                   value="" placeholder="Category Name" />
                        </div>
                        
                        <div class="config-group">
                            <label>Link URL</label>
                            <input type="url" name="config[categories][${index}][link]" 
                                   value="" placeholder="https://example.com/category" />
                        </div>
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label>Link Target</label>
                            <select name="config[categories][${index}][link_target]">
                                <option value="_self">Same Window</option>
                                <option value="_blank">New Window</option>
                            </select>
                        </div>
                        
                        <div class="config-group">
                            <label>Category Image</label>
                            <div class="image-upload-field">
                                <input type="hidden" name="config[categories][${index}][image]" 
                                       value="" class="image-url-input" />
                                <div class="image-button-group">
                                    <button type="button" class="upload-image-btn button">📷 Select Image</button>
                                    <button type="button" class="remove-image-btn button" style="display:none;">🗑️ Remove Image</button>
                                </div>
                                <div class="image-preview" style="display:none;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="config-group">
                        <label>Description (Optional)</label>
                        <textarea name="config[categories][${index}][description]" rows="2" 
                                  placeholder="Brief category description..."></textarea>
                    </div>
                </div>
            `;
            
            container.append(categoryHtml);
            this.updateCategoryNumbers();
            
            // Re-initialize media uploader for the new item
            this.initMediaUploader();
        },

        /**
         * Remove category from category showcase
         */
        removeCategory: function(element) {
            $(element).closest('.category-item').remove();
            this.updateCategoryNumbers();
        },

        /**
         * Add new testimonial to testimonials section
         */
        addTestimonial: function() {
            var container = $('#testimonials-list');
            var index = container.find('.testimonial-item').length;
            
            var testimonialHtml = `
                <div class="testimonial-item" data-index="${index}">
                    <div class="testimonial-header">
                        <span class="testimonial-number">${index + 1}</span>
                        <button type="button" class="remove-testimonial button">🗑️ Remove</button>
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label>Rating (1-5 stars)</label>
                            <select name="config[testimonials][${index}][rating]">
                                <option value="1">1 Star</option>
                                <option value="2">2 Stars</option>
                                <option value="3">3 Stars</option>
                                <option value="4">4 Stars</option>
                                <option value="5" selected>5 Stars</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="config-group">
                        <label>Testimonial Text</label>
                        <textarea name="config[testimonials][${index}][text]" rows="3" 
                                  placeholder="Enter testimonial text..."></textarea>
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label>Author Name</label>
                            <input type="text" name="config[testimonials][${index}][author_name]" 
                                   value="" placeholder="John Doe" />
                        </div>
                        
                        <div class="config-group">
                            <label>Author Title</label>
                            <input type="text" name="config[testimonials][${index}][author_title]" 
                                   value="" placeholder="CEO, Company Name" />
                        </div>
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label>Author Image</label>
                            <div class="image-upload-field">
                                <input type="hidden" name="config[testimonials][${index}][image]" 
                                       value="" class="image-url-input" />
                                <div class="image-button-group">
                                    <button type="button" class="upload-image-btn button">📷 Select Image</button>
                                    <button type="button" class="remove-image-btn button" style="display:none;">🗑️ Remove Image</button>
                                </div>
                                <div class="image-preview" style="display:none;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            container.append(testimonialHtml);
            this.updateTestimonialNumbers();
            
            // Re-initialize media uploader for the new item
            this.initMediaUploader();
        },

        /**
         * Remove testimonial from testimonials section
         */
        removeTestimonial: function(element) {
            $(element).closest('.testimonial-item').remove();
            this.updateTestimonialNumbers();
        },

        /**
         * Update testimonial numbers after add/remove
         */
        updateTestimonialNumbers: function() {
            $('#testimonials-list .testimonial-item').each(function(index) {
                $(this).find('.testimonial-number').text(index + 1);
            });
        },

        /**
         * Update last saved time indicator
         */
        updateLastSavedTime: function() {
            var now = new Date();
            var timeString = now.toLocaleTimeString();
            
            var indicator = $('.cpb-last-saved');
            if (!indicator.length) {
                indicator = $('<div class="cpb-last-saved">Last saved: <span class="time"></span></div>');
                $('.cpb-page-actions').after(indicator);
            }
            
            indicator.find('.time').text(timeString);
            indicator.fadeIn();
        },

        /**
         * Enhanced real-time preview updates
         */
        updateSectionPreview: function() {
            var previewContainer = $('.cpb-section-preview .preview-content');
            if (previewContainer.length === 0) return;

            var formData = CPB_Admin.serializeSectionData($('#cpb-section-form'));
            var sectionType = $('#section_type').val();

            // Show loading state
            previewContainer.addClass('cpb-preview-loading');

            // Debounce preview updates
            clearTimeout(CPB_Admin.previewTimeout);
            CPB_Admin.previewTimeout = setTimeout(function() {
                var previewHtml = CPB_Admin.generateSectionPreview(sectionType, formData);
                previewContainer.html(previewHtml).removeClass('cpb-preview-loading');
            }, 300);
        },

        /**
         * Enhanced section configuration with tabs
         */
        initSectionConfigTabs: function() {
            $(document).on('click', '.cpb-config-tab', function(e) {
                e.preventDefault();
                
                var tab = $(this);
                var tabId = tab.data('tab');
                var container = tab.closest('.cpb-modal-body');
                
                // Update active tab
                container.find('.cpb-config-tab').removeClass('active');
                tab.addClass('active');
                
                // Show corresponding content
                container.find('.cpb-config-tab-content').removeClass('active');
                container.find('.cpb-config-tab-content[data-tab="' + tabId + '"]').addClass('active');
            });
        },

        /**
         * Copy section functionality
         */
        copySection: function(sectionId) {
            var sectionItem = $('.cpb-section-item[data-section-id="' + sectionId + '"]');
            var sectionData = CPB_Admin.getSectionData(sectionItem);
            
            // Store in clipboard
            CPB_Admin.sectionClipboard = {
                type: sectionItem.data('section-type'),
                data: sectionData
            };
            
            CPB_Admin.showNotice('success', 'Section copied to clipboard');
            
            // Enable paste button if it exists
            $('.cpb-paste-section').prop('disabled', false);
        },

        /**
         * Paste section functionality
         */
        pasteSection: function() {
            if (!CPB_Admin.sectionClipboard) {
                CPB_Admin.showNotice('error', 'No section in clipboard');
                return;
            }
            
            var pageId = $('#page_id').val();
            if (!pageId) {
                CPB_Admin.showNotice('error', 'Please save the page first');
                return;
            }
            
            var data = {
                action: 'cpb_admin_action',
                cpb_action: 'paste_section',
                nonce: cpb_admin.nonce,
                page_id: pageId,
                section_type: CPB_Admin.sectionClipboard.type,
                section_data: CPB_Admin.sectionClipboard.data
            };
            
            $.post(cpb_admin.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        var newSection = $(response.data.section_html).hide();
                        $('.cpb-sections-list').append(newSection);
                        newSection.fadeIn(300);
                        
                        CPB_Admin.showNotice('success', 'Section pasted successfully');
                    } else {
                        CPB_Admin.showNotice('error', response.data.message || 'Failed to paste section');
                    }
                })
                .fail(function() {
                    CPB_Admin.showNotice('error', 'Failed to paste section');
                });
        },

        /**
         * Keyboard shortcuts
         */
        initKeyboardShortcuts: function() {
            $(document).on('keydown', function(e) {
                // Ctrl+S or Cmd+S to save
                if ((e.ctrlKey || e.metaKey) && e.which === 83) {
                    e.preventDefault();
                    $('.cpb-save-page').trigger('click');
                }
                
                // Ctrl+D or Cmd+D to duplicate section (when section is selected)
                if ((e.ctrlKey || e.metaKey) && e.which === 68) {
                    var selectedSection = $('.cpb-section-item.cpb-section-selected');
                    if (selectedSection.length) {
                        e.preventDefault();
                        CPB_Admin.copySection(selectedSection.data('section-id'));
                        CPB_Admin.pasteSection();
                    }
                }
                
                // Escape to close modal
                if (e.which === 27) {
                    CPB_Admin.closeModal();
                }
            });
        },

        /**
         * Escape HTML for safe display
         */
        escapeHtml: function(text) {
            if (!text) return '';
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };

})(jQuery);
/**
 *
 Page Revisions functionality
 */
var CPB_Revisions = {
    
    /**
     * Initialize revisions functionality
     */
    init: function() {
        this.bindEvents();
        this.loadRevisions();
        this.initAutoSave();
    },
    
    /**
     * Bind revision events
     */
    bindEvents: function() {
        // Revision management
        $(document).on('click', '.cpb-create-revision', this.showCreateRevisionModal);
        $(document).on('click', '.cpb-restore-revision', this.restoreRevision);
        $(document).on('click', '.cpb-delete-revision', this.deleteRevision);
        $(document).on('click', '.cpb-compare-revisions', this.compareRevisions);
        $(document).on('change', '#show-autosaves', this.toggleAutosaves);
        
        // Revision selection
        $(document).on('change', '.cpb-revision-checkbox', this.handleRevisionSelection);
        $(document).on('click', '.cpb-compare-selected', this.compareSelectedRevisions);
        $(document).on('click', '.cpb-delete-selected', this.deleteSelectedRevisions);
        
        // Modal events
        $(document).on('submit', '#cpb-create-revision-form', this.createRevision);
        $(document).on('click', '.cpb-modal-cancel, .cpb-modal-close', this.closeModal);
    },
    
    /**
     * Load revisions list
     */
    loadRevisions: function() {
        var pageId = $('#revision-page-id').val() || $('.cpb-create-revision').data('page-id');
        var includeAutosaves = $('#show-autosaves').is(':checked');
        
        if (!pageId) {
            return;
        }
        
        $('.cpb-revisions-list').html('<div class="cpb-loading-revisions"><span class="spinner is-active"></span> Loading revisions...</div>');
        
        var data = {
            action: 'cpb_admin_action',
            cpb_action: 'get_page_revisions',
            nonce: cpb_admin.nonce,
            page_id: pageId,
            include_autosaves: includeAutosaves,
            limit: 50
        };
        
        $.post(cpb_admin.ajax_url, data)
            .done(function(response) {
                if (response.success) {
                    CPB_Revisions.renderRevisionsList(response.data.revisions);
                } else {
                    $('.cpb-revisions-list').html('<div class="cpb-notice error">Failed to load revisions.</div>');
                }
            })
            .fail(function() {
                $('.cpb-revisions-list').html('<div class="cpb-notice error">Failed to load revisions.</div>');
            });
    },
    
    /**
     * Render revisions list
     */
    renderRevisionsList: function(revisions) {
        if (!revisions || revisions.length === 0) {
            $('.cpb-revisions-list').html('<div class="cpb-no-revisions">No revisions found.</div>');
            return;
        }
        
        var html = '<div class="cpb-revisions-table">';
        html += '<div class="cpb-revisions-header">';
        html += '<div class="cpb-revision-select"><input type="checkbox" class="cpb-select-all-revisions"></div>';
        html += '<div class="cpb-revision-info">Revision</div>';
        html += '<div class="cpb-revision-date">Date</div>';
        html += '<div class="cpb-revision-author">Author</div>';
        html += '<div class="cpb-revision-actions">Actions</div>';
        html += '</div>';
        
        $.each(revisions, function(index, revision) {
            html += CPB_Revisions.renderRevisionRow(revision, index === 0);
        });
        
        html += '</div>';
        $('.cpb-revisions-list').html(html);
    },
    
    /**
     * Render single revision row
     */
    renderRevisionRow: function(revision, isCurrent) {
        var html = '<div class="cpb-revision-row' + (isCurrent ? ' cpb-current-revision' : '') + '" data-revision-id="' + revision.id + '">';
        
        // Checkbox (not for current revision)
        html += '<div class="cpb-revision-select">';
        if (!isCurrent) {
            html += '<input type="checkbox" class="cpb-revision-checkbox" value="' + revision.id + '">';
        }
        html += '</div>';
        
        // Revision info
        html += '<div class="cpb-revision-info">';
        if (isCurrent) {
            html += '<strong>Current</strong>';
        } else {
            html += 'Revision #' + revision.id;
        }
        
        if (revision.is_autosave) {
            html += ' <span class="cpb-autosave-badge">Autosave</span>';
        }
        
        if (revision.revision_note) {
            html += '<div class="cpb-revision-note">' + CPB_Admin.escapeHtml(revision.revision_note) + '</div>';
        }
        html += '</div>';
        
        // Date
        html += '<div class="cpb-revision-date">' + revision.formatted_date + '</div>';
        
        // Author
        html += '<div class="cpb-revision-author">' + CPB_Admin.escapeHtml(revision.created_by.name) + '</div>';
        
        // Actions
        html += '<div class="cpb-revision-actions">';
        if (!isCurrent) {
            html += '<button type="button" class="button cpb-restore-revision" data-revision-id="' + revision.id + '">Restore</button> ';
            html += '<button type="button" class="button cpb-delete-revision" data-revision-id="' + revision.id + '">Delete</button>';
        }
        html += '</div>';
        
        html += '</div>';
        return html;
    },
    
    /**
     * Show create revision modal
     */
    showCreateRevisionModal: function(e) {
        e.preventDefault();
        $('#cpb-create-revision-modal').fadeIn();
        $('#revision-note').focus();
    },
    
    /**
     * Create revision
     */
    createRevision: function(e) {
        e.preventDefault();
        
        var form = $(this);
        var pageId = form.find('#revision-page-id').val();
        var revisionNote = form.find('#revision-note').val();
        var submitButton = $('.cpb-modal-save');
        
        CPB_Admin.showLoading(submitButton);
        
        var data = {
            action: 'cpb_admin_action',
            cpb_action: 'create_revision',
            nonce: cpb_admin.nonce,
            page_id: pageId,
            revision_note: revisionNote
        };
        
        $.post(cpb_admin.ajax_url, data)
            .done(function(response) {
                if (response.success) {
                    CPB_Admin.showNotice('success', response.data.message || 'Revision created successfully');
                    $('#cpb-create-revision-modal').fadeOut();
                    form[0].reset();
                    CPB_Revisions.loadRevisions();
                } else {
                    CPB_Admin.showNotice('error', response.data.message || 'Failed to create revision');
                }
            })
            .fail(function() {
                CPB_Admin.showNotice('error', 'Failed to create revision');
            })
            .always(function() {
                CPB_Admin.hideLoading(submitButton);
            });
    },
    
    /**
     * Restore revision
     */
    restoreRevision: function(e) {
        e.preventDefault();
        
        // Proceed without confirmation
        
        var button = $(this);
        var revisionId = button.data('revision-id');
        var pageId = $('#revision-page-id').val() || $('.cpb-create-revision').data('page-id');
        
        CPB_Admin.showLoading(button);
        
        var data = {
            action: 'cpb_admin_action',
            cpb_action: 'restore_from_revision',
            nonce: cpb_admin.nonce,
            page_id: pageId,
            revision_id: revisionId
        };
        
        $.post(cpb_admin.ajax_url, data)
            .done(function(response) {
                if (response.success) {
                    CPB_Admin.showNotice('success', response.data.message || 'Page restored successfully');
                    CPB_Revisions.loadRevisions();
                } else {
                    CPB_Admin.showNotice('error', response.data.message || 'Failed to restore revision');
                }
            })
            .fail(function() {
                CPB_Admin.showNotice('error', 'Failed to restore revision');
            })
            .always(function() {
                CPB_Admin.hideLoading(button);
            });
    },
    
    /**
     * Delete revision
     */
    deleteRevision: function(e) {
        e.preventDefault();
        
        // Proceed without confirmation
        
        var button = $(this);
        var revisionId = button.data('revision-id');
        
        CPB_Admin.showLoading(button);
        
        var data = {
            action: 'cpb_admin_action',
            cpb_action: 'delete_revision',
            nonce: cpb_admin.nonce,
            revision_id: revisionId
        };
        
        $.post(cpb_admin.ajax_url, data)
            .done(function(response) {
                if (response.success) {
                    CPB_Admin.showNotice('success', response.data.message || 'Revision deleted successfully');
                    CPB_Revisions.loadRevisions();
                } else {
                    CPB_Admin.showNotice('error', response.data.message || 'Failed to delete revision');
                }
            })
            .fail(function() {
                CPB_Admin.showNotice('error', 'Failed to delete revision');
            })
            .always(function() {
                CPB_Admin.hideLoading(button);
            });
    },
    
    /**
     * Compare revisions
     */
    compareRevisions: function(e) {
        e.preventDefault();
        
        var selectedRevisions = $('.cpb-revision-checkbox:checked');
        if (selectedRevisions.length !== 2) {
            CPB_Admin.showNotice('error', 'Please select exactly 2 revisions to compare.');
            return;
        }
        
        var revision1Id = $(selectedRevisions[0]).val();
        var revision2Id = $(selectedRevisions[1]).val();
        
        CPB_Revisions.showComparisonModal(revision1Id, revision2Id);
    },
    
    /**
     * Show comparison modal
     */
    showComparisonModal: function(revision1Id, revision2Id) {
        $('#cpb-revision-comparison-modal').fadeIn();
        
        var data = {
            action: 'cpb_admin_action',
            cpb_action: 'compare_revisions',
            nonce: cpb_admin.nonce,
            revision1_id: revision1Id,
            revision2_id: revision2Id
        };
        
        $.post(cpb_admin.ajax_url, data)
            .done(function(response) {
                if (response.success) {
                    CPB_Revisions.renderComparison(response.data.comparison);
                } else {
                    $('.cpb-comparison-container').html('<div class="cpb-notice error">Failed to load comparison.</div>');
                }
            })
            .fail(function() {
                $('.cpb-comparison-container').html('<div class="cpb-notice error">Failed to load comparison.</div>');
            });
    },
    
    /**
     * Render comparison
     */
    renderComparison: function(comparison) {
        var html = '<div class="cpb-comparison-results">';
        
        if (comparison.changes && comparison.changes.length > 0) {
            html += '<h3>Changes Found:</h3>';
            html += '<div class="cpb-changes-list">';
            
            $.each(comparison.changes, function(index, change) {
                html += '<div class="cpb-change-item">';
                html += '<strong>' + CPB_Admin.escapeHtml(change.field) + ':</strong><br>';
                html += '<div class="cpb-change-old">- ' + CPB_Admin.escapeHtml(change.old_value) + '</div>';
                html += '<div class="cpb-change-new">+ ' + CPB_Admin.escapeHtml(change.new_value) + '</div>';
                html += '</div>';
            });
            
            html += '</div>';
        } else {
            html += '<div class="cpb-no-changes">No differences found between the selected revisions.</div>';
        }
        
        html += '</div>';
        $('.cpb-comparison-container').html(html);
    },
    
    /**
     * Toggle autosaves display
     */
    toggleAutosaves: function() {
        CPB_Revisions.loadRevisions();
    },
    
    /**
     * Handle revision selection
     */
    handleRevisionSelection: function() {
        var selectedCount = $('.cpb-revision-checkbox:checked').length;
        
        $('.cpb-compare-selected').prop('disabled', selectedCount !== 2);
        $('.cpb-delete-selected').prop('disabled', selectedCount === 0);
        
        if (selectedCount > 0) {
            $('.cpb-revision-actions').show();
        } else {
            $('.cpb-revision-actions').hide();
        }
    },
    
    /**
     * Compare selected revisions
     */
    compareSelectedRevisions: function(e) {
        e.preventDefault();
        CPB_Revisions.compareRevisions(e);
    },
    
    /**
     * Delete selected revisions
     */
    deleteSelectedRevisions: function(e) {
        e.preventDefault();
        
        var selectedRevisions = $('.cpb-revision-checkbox:checked');
        if (selectedRevisions.length === 0) {
            return;
        }
        
        // Proceed without confirmation
        
        var button = $(this);
        CPB_Admin.showLoading(button);
        
        var revisionIds = [];
        selectedRevisions.each(function() {
            revisionIds.push($(this).val());
        });
        
        // Delete revisions one by one
        var deletePromises = [];
        $.each(revisionIds, function(index, revisionId) {
            var data = {
                action: 'cpb_admin_action',
                cpb_action: 'delete_revision',
                nonce: cpb_admin.nonce,
                revision_id: revisionId
            };
            deletePromises.push($.post(cpb_admin.ajax_url, data));
        });
        
        $.when.apply($, deletePromises)
            .done(function() {
                CPB_Admin.showNotice('success', 'Selected revisions deleted successfully');
                CPB_Revisions.loadRevisions();
            })
            .fail(function() {
                CPB_Admin.showNotice('error', 'Failed to delete some revisions');
                CPB_Revisions.loadRevisions();
            })
            .always(function() {
                CPB_Admin.hideLoading(button);
            });
    },
    
    /**
     * Initialize auto-save functionality
     */
    initAutoSave: function() {
        // Only initialize on page builder pages
        if (!$('#cpb-page-form').length) {
            return;
        }
        
        var autoSaveInterval = 30000; // 30 seconds
        var lastSaveData = '';
        
        setInterval(function() {
            var currentData = JSON.stringify(CPB_Admin.serializePageData($('#cpb-page-form')));
            
            // Only save if data has changed
            if (currentData !== lastSaveData) {
                CPB_Revisions.createAutoSave();
                lastSaveData = currentData;
            }
        }, autoSaveInterval);
        
        // Also save on form changes (debounced)
        var saveTimeout;
        $('#cpb-page-form').on('change input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                CPB_Revisions.createAutoSave();
            }, 5000); // 5 seconds after last change
        });
    },
    
    /**
     * Create auto-save
     */
    createAutoSave: function() {
        var pageId = $('#page_id').val();
        
        if (!pageId || pageId === '0') {
            return; // Don't auto-save new pages
        }
        
        var data = {
            action: 'cpb_admin_action',
            cpb_action: 'create_autosave',
            nonce: cpb_admin.nonce,
            page_id: pageId
        };
        
        $.post(cpb_admin.ajax_url, data)
            .done(function(response) {
                if (response.success) {
                    // Show subtle indicator
                    CPB_Revisions.showAutoSaveIndicator();
                }
            })
            .fail(function() {
                // Silently fail for auto-saves
            });
    },
    
    /**
     * Show auto-save indicator
     */
    showAutoSaveIndicator: function() {
        var indicator = $('.cpb-autosave-indicator');
        if (!indicator.length) {
            indicator = $('<div class="cpb-autosave-indicator">Auto-saved</div>');
            $('.cpb-page-builder').append(indicator);
        }
        
        indicator.fadeIn().delay(2000).fadeOut();
    },
    
    /**
     * Close modal
     */
    closeModal: function() {
        $('.cpb-modal').fadeOut();
    }
};

// Initialize revisions on page builder pages
jQuery(document).ready(function($) {
    // Initialize auto-save on page builder pages
    if ($('#cpb-page-form').length) {
        CPB_Revisions.initAutoSave();
    }
    
});
