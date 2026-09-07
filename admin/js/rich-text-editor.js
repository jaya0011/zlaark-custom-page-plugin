/**
 * Simple Rich Text Editor for Custom Page Builder
 * Adds formatting toolbar to textarea fields
 */

(function($) {
    'use strict';

    // Rich Text Editor Class
    class RichTextEditor {
        constructor(textarea) {
            this.textarea = $(textarea);
            this.wrapper = null;
            this.toolbar = null;
            this.init();
        }

        init() {
            // Wrap textarea
            this.textarea.wrap('<div class="cpb-rich-text-wrapper"></div>');
            this.wrapper = this.textarea.parent();
            
            // Create toolbar
            this.createToolbar();
            
            // Bind events
            this.bindEvents();
        }

        createToolbar() {
            const toolbar = $(`
                <div class="cpb-rich-text-toolbar">
                    <button type="button" class="cpb-format-btn" data-format="bold" title="Bold (Ctrl+B)">
                        <strong>B</strong>
                    </button>
                    <button type="button" class="cpb-format-btn" data-format="italic" title="Italic (Ctrl+I)">
                        <em>I</em>
                    </button>
                    <button type="button" class="cpb-format-btn" data-format="underline" title="Underline (Ctrl+U)">
                        <u>U</u>
                    </button>
                    <span class="cpb-toolbar-separator"></span>
                    <input type="color" class="cpb-color-picker" title="Text Color" value="#000000" />
                    <button type="button" class="cpb-format-btn" data-format="removeColor" title="Remove Color">
                        <span style="text-decoration: line-through;">A</span>
                    </button>
                    <span class="cpb-toolbar-separator"></span>
                    <button type="button" class="cpb-format-btn" data-format="link" title="Insert Link">
                        🔗
                    </button>
                    <button type="button" class="cpb-format-btn" data-format="clear" title="Clear Formatting">
                        ✖
                    </button>
                </div>
            `);
            
            this.wrapper.prepend(toolbar);
            this.toolbar = toolbar;
        }

        bindEvents() {
            const self = this;
            
            // Format buttons
            this.toolbar.find('.cpb-format-btn').on('click', function(e) {
                e.preventDefault();
                const format = $(this).data('format');
                self.applyFormat(format);
            });
            
            // Color picker
            this.toolbar.find('.cpb-color-picker').on('change', function() {
                const color = $(this).val();
                self.applyColor(color);
            });
            
            // Keyboard shortcuts
            this.textarea.on('keydown', function(e) {
                if (e.ctrlKey || e.metaKey) {
                    switch(e.key.toLowerCase()) {
                        case 'b':
                            e.preventDefault();
                            self.applyFormat('bold');
                            break;
                        case 'i':
                            e.preventDefault();
                            self.applyFormat('italic');
                            break;
                        case 'u':
                            e.preventDefault();
                            self.applyFormat('underline');
                            break;
                    }
                }
            });
        }

        applyFormat(format) {
            const textarea = this.textarea[0];
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selectedText = textarea.value.substring(start, end);
            
            if (!selectedText) {
                alert('Please select text to format');
                return;
            }
            
            let formattedText = '';
            
            switch(format) {
                case 'bold':
                    formattedText = `<strong>${selectedText}</strong>`;
                    break;
                case 'italic':
                    formattedText = `<em>${selectedText}</em>`;
                    break;
                case 'underline':
                    formattedText = `<u>${selectedText}</u>`;
                    break;
                case 'link':
                    const url = prompt('Enter URL:', 'https://');
                    if (url) {
                        formattedText = `<a href="${url}">${selectedText}</a>`;
                    } else {
                        return;
                    }
                    break;
                case 'clear':
                    // Remove HTML tags from selected text
                    formattedText = selectedText.replace(/<[^>]*>/g, '');
                    break;
                case 'removeColor':
                    // Remove color spans
                    formattedText = selectedText.replace(/<span[^>]*color[^>]*>(.*?)<\/span>/gi, '$1');
                    break;
                default:
                    return;
            }
            
            this.insertText(formattedText, start, end);
        }

        applyColor(color) {
            const textarea = this.textarea[0];
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selectedText = textarea.value.substring(start, end);
            
            if (!selectedText) {
                alert('Please select text to apply color');
                return;
            }
            
            const formattedText = `<span style="color: ${color};">${selectedText}</span>`;
            this.insertText(formattedText, start, end);
        }

        insertText(text, start, end) {
            const textarea = this.textarea[0];
            const before = textarea.value.substring(0, start);
            const after = textarea.value.substring(end);
            
            textarea.value = before + text + after;
            
            // Set cursor position after inserted text
            const newPosition = start + text.length;
            textarea.setSelectionRange(newPosition, newPosition);
            textarea.focus();
            
            // Trigger change event
            $(textarea).trigger('change');
        }
    }

    // Initialize rich text editors
    function initRichTextEditors() {
        $('.cpb-rich-textarea').each(function() {
            if (!$(this).data('cpb-rich-editor')) {
                new RichTextEditor(this);
                $(this).data('cpb-rich-editor', true);
            }
        });
    }

    // Initialize on document ready
    $(document).ready(function() {
        initRichTextEditors();
        
        // Re-initialize when new items are added dynamically
        $(document).on('DOMNodeInserted', '.testimonial-item, .product-item, .category-item', function() {
            setTimeout(initRichTextEditors, 100);
        });
    });

})(jQuery);
