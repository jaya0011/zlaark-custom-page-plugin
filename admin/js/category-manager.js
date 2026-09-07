/**
 * Category Manager JavaScript
 * Handles category selection and management UI
 */

(function($) {
    'use strict';

    const CategoryManager = {
        categories: [],
        
        init: function() {
            this.loadCategories();
            this.bindEvents();
        },
        
        loadCategories: function() {
            $.ajax({
                url: cpb_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'cpb_get_categories',
                    nonce: cpb_admin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.categories = response.data.categories;
                        this.renderCategorySelectors();
                    }
                }
            });
        },
        
        bindEvents: function() {
            // Add new category button
            $(document).on('click', '.cpb-add-category-btn', (e) => {
                e.preventDefault();
                const container = $(e.target).closest('.cpb-category-selector');
                this.showAddCategoryModal(container);
            });
            
            // Category checkbox change
            $(document).on('change', '.cpb-category-checkbox', (e) => {
                this.updateSelectedCategories($(e.target).closest('.cpb-category-selector'));
            });
            
            // Delete category
            $(document).on('click', '.cpb-delete-category', (e) => {
                e.preventDefault();
                const categoryId = $(e.target).data('category-id');
                this.deleteCategory(categoryId);
            });
        },
        
        renderCategorySelectors: function() {
            $('.cpb-category-selector').each((index, element) => {
                this.renderCategorySelector($(element));
            });
        },
        
        renderCategorySelector: function($container) {
            const selectedIds = this.getSelectedCategories($container);
            const $list = $container.find('.cpb-category-list');
            
            if ($list.length === 0) return;
            
            $list.empty();
            
            this.categories.forEach(category => {
                const isChecked = selectedIds.includes(category.id);
                const $item = $(`
                    <div class="cpb-category-item">
                        <label>
                            <input type="checkbox" 
                                   class="cpb-category-checkbox" 
                                   value="${category.id}" 
                                   ${isChecked ? 'checked' : ''} />
                            <span>${category.name}</span>
                        </label>
                    </div>
                `);
                $list.append($item);
            });
        },
        
        getSelectedCategories: function($container) {
            const $input = $container.find('.cpb-selected-categories');
            const value = $input.val();
            
            if (!value) return [];
            
            try {
                return JSON.parse(value);
            } catch (e) {
                return [];
            }
        },
        
        updateSelectedCategories: function($container) {
            const selectedIds = [];
            
            $container.find('.cpb-category-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            const $input = $container.find('.cpb-selected-categories');
            $input.val(JSON.stringify(selectedIds));
            
            this.updateCategoryDisplay($container, selectedIds);
        },
        
        updateCategoryDisplay: function($container, selectedIds) {
            const $display = $container.find('.cpb-selected-categories-display');
            
            if (selectedIds.length === 0) {
                $display.html('<em>No categories selected</em>');
                return;
            }
            
            const selectedNames = this.categories
                .filter(cat => selectedIds.includes(cat.id))
                .map(cat => cat.name);
            
            $display.html(selectedNames.join(', '));
        },
        
        showAddCategoryModal: function($container) {
            const name = prompt('Enter new category name:');
            
            if (!name || name.trim() === '') {
                return;
            }
            
            this.addCategory(name.trim(), $container);
        },
        
        addCategory: function(name, $container) {
            $.ajax({
                url: cpb_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'cpb_add_category',
                    nonce: cpb_admin.nonce,
                    name: name
                },
                success: (response) => {
                    if (response.success) {
                        this.categories.push(response.data.category);
                        this.renderCategorySelectors();
                        
                        // Auto-select the new category
                        if ($container) {
                            const selectedIds = this.getSelectedCategories($container);
                            selectedIds.push(response.data.category.id);
                            $container.find('.cpb-selected-categories').val(JSON.stringify(selectedIds));
                            this.renderCategorySelector($container);
                        }
                        
                        alert('Category added successfully!');
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Failed to add category. Please try again.');
                }
            });
        },
        
        deleteCategory: function(categoryId) {
            if (!confirm('Are you sure you want to delete this category?')) {
                return;
            }
            
            $.ajax({
                url: cpb_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'cpb_delete_category',
                    nonce: cpb_admin.nonce,
                    id: categoryId
                },
                success: (response) => {
                    if (response.success) {
                        this.categories = this.categories.filter(cat => cat.id !== categoryId);
                        this.renderCategorySelectors();
                        alert('Category deleted successfully!');
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Failed to delete category. Please try again.');
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        CategoryManager.init();
        
        // Re-initialize when new items are added dynamically
        $(document).on('DOMNodeInserted', '.product-item, .content-block-section-config', function() {
            setTimeout(() => CategoryManager.renderCategorySelectors(), 100);
        });
    });

})(jQuery);
