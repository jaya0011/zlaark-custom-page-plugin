<?php
/**
 * Input validation class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

use Custom_Page_Builder\Exceptions\ValidationException;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Input validation and sanitization class
 */
class Input_Validator {
    
    /**
     * Validation rules for different data types
     *
     * @var array
     */
    private static $validation_rules = [
        'page' => [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['string', 'max:255', 'slug'],
            'status' => ['required', 'string', 'in:draft,published,archived,scheduled'],
            'author_id' => ['required', 'integer', 'min:1'],
            'meta_data' => ['array'],
            'scheduled_at' => ['date']
        ],
        'section' => [
            'section_type' => ['required', 'string', 'in:testimonials,product_grid,hero_banner,category_showcase,content_block'],
            'section_order' => ['integer', 'min:0'],
            'config' => ['required', 'array']
        ],
        'testimonial' => [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'text' => ['required', 'string', 'max:1000'],
            'author' => ['required', 'string', 'max:255'],
            'image_id' => ['integer', 'min:1']
        ],
        'product' => [
            'title' => ['required', 'string', 'max:255'],
            'price' => ['string', 'max:50'],
            'description' => ['string', 'max:500'],
            'image_id' => ['integer', 'min:1'],
            'link' => ['url']
        ],
        'hero_banner' => [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['string', 'max:500'],
            'background_image_id' => ['integer', 'min:1'],
            'cta_text' => ['string', 'max:100'],
            'cta_link' => ['url']
        ],
        'category' => [
            'title' => ['required', 'string', 'max:255'],
            'image_id' => ['integer', 'min:1'],
            'link' => ['url']
        ],
        'content_block' => [
            'title' => ['string', 'max:255'],
            'content' => ['required', 'string'],
            'image_id' => ['integer', 'min:1'],
            'layout' => ['string', 'in:single-column,two-column-left,two-column-right']
        ]
    ];
    
    /**
     * Sanitization rules for different field types
     *
     * @var array
     */
    private static $sanitization_rules = [
        'string' => 'text',
        'email' => 'email',
        'url' => 'url',
        'slug' => 'slug',
        'html' => 'html',
        'textarea' => 'textarea',
        'integer' => 'int',
        'float' => 'float',
        'boolean' => 'bool',
        'array' => 'array',
        'json' => 'json'
    ];
    
    /**
     * Validate data against rules
     *
     * @param array $data Data to validate
     * @param string $rule_set Rule set name
     * @param array $custom_rules Custom validation rules
     * @return array Validated and sanitized data
     * @throws ValidationException
     */
    public static function validate(array $data, string $rule_set, array $custom_rules = []): array {
        $rules = $custom_rules ?: (self::$validation_rules[$rule_set] ?? []);
        
        if (empty($rules)) {
            throw new ValidationException(
                sprintf('No validation rules found for rule set: %s', $rule_set),
                [],
                ['rule_set' => $rule_set]
            );
        }
        
        $errors = [];
        $validated_data = [];
        
        // Validate each field
        foreach ($rules as $field => $field_rules) {
            try {
                $validated_data[$field] = self::validate_field($data, $field, $field_rules);
            } catch (ValidationException $e) {
                $errors = array_merge($errors, $e->getValidationErrors());
            }
        }
        
        // Check for unexpected fields
        $allowed_fields = array_keys($rules);
        $unexpected_fields = array_diff(array_keys($data), $allowed_fields);
        
        if (!empty($unexpected_fields)) {
            foreach ($unexpected_fields as $field) {
                $errors[$field] = sprintf('Unexpected field: %s', $field);
            }
        }
        
        if (!empty($errors)) {
            throw new ValidationException(
                'Validation failed',
                $errors,
                ['rule_set' => $rule_set]
            );
        }
        
        return $validated_data;
    }
    
    /**
     * Validate a single field
     *
     * @param array $data Input data
     * @param string $field Field name
     * @param array $rules Field validation rules
     * @return mixed Validated field value
     * @throws ValidationException
     */
    private static function validate_field(array $data, string $field, array $rules): mixed {
        $value = $data[$field] ?? null;
        $errors = [];
        
        // Check required rule first
        if (in_array('required', $rules) && (is_null($value) || $value === '')) {
            $errors[$field] = sprintf('Field %s is required', $field);
            throw new ValidationException('Field validation failed', $errors);
        }
        
        // Skip other validations if field is not required and empty
        if (!in_array('required', $rules) && (is_null($value) || $value === '')) {
            return null;
        }
        
        // Apply validation rules
        foreach ($rules as $rule) {
            if ($rule === 'required') {
                continue; // Already handled
            }
            
            if (!self::apply_validation_rule($value, $rule, $field)) {
                $errors[$field] = self::get_validation_error_message($field, $rule, $value);
            }
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Field validation failed', $errors);
        }
        
        // Sanitize the value
        $sanitized_value = self::sanitize_field_value($value, $rules);
        
        return $sanitized_value;
    }
    
    /**
     * Apply a single validation rule
     *
     * @param mixed $value Field value
     * @param string $rule Validation rule
     * @param string $field Field name
     * @return bool Validation result
     */
    private static function apply_validation_rule($value, string $rule, string $field): bool {
        // Handle rules with parameters (e.g., max:255, min:1)
        if (strpos($rule, ':') !== false) {
            [$rule_name, $parameter] = explode(':', $rule, 2);
            return self::apply_parameterized_rule($value, $rule_name, $parameter);
        }
        
        // Handle simple rules
        switch ($rule) {
            case 'string':
                return is_string($value);
            
            case 'integer':
            case 'int':
                return is_int($value) || (is_string($value) && ctype_digit($value));
            
            case 'float':
                return is_float($value) || is_numeric($value);
            
            case 'boolean':
            case 'bool':
                return is_bool($value) || in_array($value, ['true', 'false', '1', '0', 1, 0], true);
            
            case 'array':
                return is_array($value);
            
            case 'email':
                return is_email($value);
            
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            
            case 'slug':
                return preg_match('/^[a-z0-9-]+$/', $value);
            
            case 'date':
                return self::validate_date($value);
            
            default:
                return true; // Unknown rule, pass validation
        }
    }
    
    /**
     * Apply parameterized validation rule
     *
     * @param mixed $value Field value
     * @param string $rule_name Rule name
     * @param string $parameter Rule parameter
     * @return bool Validation result
     */
    private static function apply_parameterized_rule($value, string $rule_name, string $parameter): bool {
        switch ($rule_name) {
            case 'max':
                if (is_string($value)) {
                    return strlen($value) <= (int) $parameter;
                } elseif (is_numeric($value)) {
                    return $value <= (float) $parameter;
                } elseif (is_array($value)) {
                    return count($value) <= (int) $parameter;
                }
                return true;
            
            case 'min':
                if (is_string($value)) {
                    return strlen($value) >= (int) $parameter;
                } elseif (is_numeric($value)) {
                    return $value >= (float) $parameter;
                } elseif (is_array($value)) {
                    return count($value) >= (int) $parameter;
                }
                return true;
            
            case 'in':
                $allowed_values = explode(',', $parameter);
                return in_array($value, $allowed_values, true);
            
            case 'regex':
                return preg_match($parameter, $value);
            
            default:
                return true; // Unknown rule, pass validation
        }
    }
    
    /**
     * Validate date format
     *
     * @param mixed $value Date value
     * @return bool Validation result
     */
    private static function validate_date($value): bool {
        if (!is_string($value)) {
            return false;
        }
        
        // Try to parse the date
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
        if ($date && $date->format('Y-m-d H:i:s') === $value) {
            return true;
        }
        
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        if ($date && $date->format('Y-m-d') === $value) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get validation error message
     *
     * @param string $field Field name
     * @param string $rule Validation rule
     * @param mixed $value Field value
     * @return string Error message
     */
    private static function get_validation_error_message(string $field, string $rule, $value): string {
        if (strpos($rule, ':') !== false) {
            [$rule_name, $parameter] = explode(':', $rule, 2);
            
            switch ($rule_name) {
                case 'max':
                    return sprintf('Field %s must not exceed %s characters/items', $field, $parameter);
                
                case 'min':
                    return sprintf('Field %s must be at least %s characters/items', $field, $parameter);
                
                case 'in':
                    return sprintf('Field %s must be one of: %s', $field, $parameter);
                
                case 'regex':
                    return sprintf('Field %s format is invalid', $field);
                
                default:
                    return sprintf('Field %s validation failed for rule %s', $field, $rule);
            }
        }
        
        switch ($rule) {
            case 'string':
                return sprintf('Field %s must be a string', $field);
            
            case 'integer':
            case 'int':
                return sprintf('Field %s must be an integer', $field);
            
            case 'float':
                return sprintf('Field %s must be a number', $field);
            
            case 'boolean':
            case 'bool':
                return sprintf('Field %s must be a boolean', $field);
            
            case 'array':
                return sprintf('Field %s must be an array', $field);
            
            case 'email':
                return sprintf('Field %s must be a valid email address', $field);
            
            case 'url':
                return sprintf('Field %s must be a valid URL', $field);
            
            case 'slug':
                return sprintf('Field %s must be a valid slug (lowercase letters, numbers, and hyphens only)', $field);
            
            case 'date':
                return sprintf('Field %s must be a valid date', $field);
            
            default:
                return sprintf('Field %s validation failed', $field);
        }
    }
    
    /**
     * Sanitize field value based on validation rules
     *
     * @param mixed $value Field value
     * @param array $rules Validation rules
     * @return mixed Sanitized value
     */
    private static function sanitize_field_value($value, array $rules) {
        // Remove any existing slashes before sanitizing to prevent double-escaping
        if (is_string($value)) {
            $value = wp_unslash($value);
        }
        
        // Determine sanitization method based on rules
        if (in_array('email', $rules)) {
            return sanitize_email($value);
        } elseif (in_array('url', $rules)) {
            return esc_url_raw($value);
        } elseif (in_array('slug', $rules)) {
            return sanitize_title($value);
        } elseif (in_array('html', $rules)) {
            return wp_kses_post($value);
        } elseif (in_array('integer', $rules) || in_array('int', $rules)) {
            return intval($value);
        } elseif (in_array('float', $rules)) {
            return floatval($value);
        } elseif (in_array('boolean', $rules) || in_array('bool', $rules)) {
            return (bool) $value;
        } elseif (in_array('array', $rules)) {
            return is_array($value) ? $value : [];
        } elseif (in_array('string', $rules)) {
            // Check if it's a textarea-like field
            if (strlen($value) > 255) {
                return sanitize_textarea_field($value);
            } else {
                return sanitize_text_field($value);
            }
        }
        
        // Default sanitization
        return sanitize_text_field($value);
    
    /**
     * Validate file upload
     *
     * @param array $file $_FILES array element
     * @param array $allowed_types Allowed MIME types
     * @param int $max_size Maximum file size in bytes
     * @return bool Validation result
     * @throws ValidationException
     */
    public static function validate_file_upload(array $file, array $allowed_types = [], int $max_size = 0): bool {
        $errors = [];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors['file'] = 'File is too large';
                    break;
                
                case UPLOAD_ERR_PARTIAL:
                    $errors['file'] = 'File upload was interrupted';
                    break;
                
                case UPLOAD_ERR_NO_FILE:
                    $errors['file'] = 'No file was uploaded';
                    break;
                
                case UPLOAD_ERR_NO_TMP_DIR:
                case UPLOAD_ERR_CANT_WRITE:
                case UPLOAD_ERR_EXTENSION:
                    $errors['file'] = 'Server error during file upload';
                    break;
                
                default:
                    $errors['file'] = 'Unknown upload error';
            }
        }
        
        // Check file size
        if ($max_size > 0 && $file['size'] > $max_size) {
            $errors['file'] = sprintf('File size exceeds maximum allowed size of %s', size_format($max_size));
        }
        
        // Check MIME type
        if (!empty($allowed_types)) {
            $file_type = wp_check_filetype($file['name']);
            if (!in_array($file_type['type'], $allowed_types)) {
                $errors['file'] = sprintf('File type not allowed. Allowed types: %s', implode(', ', $allowed_types));
            }
        }
        
        // Security checks
        if (!self::is_safe_filename($file['name'])) {
            $errors['file'] = 'Filename contains unsafe characters';
        }
        
        if (!empty($errors)) {
            throw new ValidationException('File validation failed', $errors);
        }
        
        return true;
    }
    
    /**
     * Check if filename is safe
     *
     * @param string $filename Filename to check
     * @return bool Safety result
     */
    private static function is_safe_filename(string $filename): bool {
        // Check for dangerous extensions
        $dangerous_extensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'pl', 'py', 'jsp', 'asp', 'sh', 'cgi'];
        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($file_extension, $dangerous_extensions)) {
            return false;
        }
        
        // Check for dangerous characters
        if (preg_match('/[<>:"|?*]/', $filename)) {
            return false;
        }
        
        // Check for null bytes
        if (strpos($filename, "\0") !== false) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize rich text content to prevent XSS
     *
     * @param string $content Rich text content
     * @return string Sanitized content
     */
    public static function sanitize_rich_text(string $content): string {
        // Define allowed HTML tags and attributes
        $allowed_tags = [
            'p' => [],
            'br' => [],
            'strong' => [],
            'b' => [],
            'em' => [],
            'i' => [],
            'u' => [],
            'h1' => [],
            'h2' => [],
            'h3' => [],
            'h4' => [],
            'h5' => [],
            'h6' => [],
            'ul' => [],
            'ol' => [],
            'li' => [],
            'a' => ['href' => [], 'title' => [], 'target' => []],
            'img' => ['src' => [], 'alt' => [], 'title' => [], 'width' => [], 'height' => []],
            'blockquote' => [],
            'code' => [],
            'pre' => []
        ];
        
        return wp_kses($content, $allowed_tags);
    }
    
    /**
     * Validate and sanitize section configuration
     *
     * @param string $section_type Section type
     * @param array $config Section configuration
     * @return array Validated configuration
     * @throws ValidationException
     */
    public static function validate_section_config(string $section_type, array $config): array {
        switch ($section_type) {
            case 'testimonials':
                return self::validate_testimonials_config($config);
            
            case 'product_grid':
                return self::validate_product_grid_config($config);
            
            case 'hero_banner':
                return self::validate_hero_banner_config($config);
            
            case 'category_showcase':
                return self::validate_category_showcase_config($config);
            
            case 'content_block':
                return self::validate_content_block_config($config);
            
            default:
                // For unknown section types, just sanitize the basic fields and return
                $validated = [];
                
                // Basic sanitization for common fields
                if (isset($config['title'])) {
                    $validated['title'] = sanitize_text_field($config['title']);
                }
                
                if (isset($config['subtitle'])) {
                    $validated['subtitle'] = sanitize_text_field($config['subtitle']);
                }
                
                if (isset($config['visible'])) {
                    $validated['visible'] = (bool) $config['visible'];
                }
                
                // Pass through other config as-is but sanitized
                foreach ($config as $key => $value) {
                    if (!isset($validated[$key])) {
                        if (is_string($value)) {
                            $validated[$key] = sanitize_text_field($value);
                        } elseif (is_array($value)) {
                            $validated[$key] = self::sanitize_array_recursively($value);
                        } else {
                            $validated[$key] = $value;
                        }
                    }
                }
                
                return $validated;
        }
    }
    
    /**
     * Validate testimonials section configuration
     *
     * @param array $config Configuration data
     * @return array Validated configuration
     * @throws ValidationException
     */
    private static function validate_testimonials_config(array $config): array {
        $validated = [];
        
        // Handle basic fields
        if (isset($config['title'])) {
            $validated['title'] = sanitize_text_field(wp_unslash($config['title']));
        }
        
        if (isset($config['subtitle'])) {
            $validated['subtitle'] = sanitize_text_field(wp_unslash($config['subtitle']));
        }
        
        // Validate testimonials array if present
        if (isset($config['testimonials']) && is_array($config['testimonials'])) {
            $validated['testimonials'] = [];
            foreach ($config['testimonials'] as $testimonial) {
                if (is_array($testimonial)) {
                    $validated_testimonial = [];
                    
                    // Sanitize testimonial fields
                    if (isset($testimonial['text'])) {
                        $validated_testimonial['text'] = sanitize_textarea_field(wp_unslash($testimonial['text']));
                    }
                    
                    if (isset($testimonial['author_name'])) {
                        $validated_testimonial['author_name'] = sanitize_text_field(wp_unslash($testimonial['author_name']));
                    }
                    
                    if (isset($testimonial['author_title'])) {
                        $validated_testimonial['author_title'] = sanitize_text_field(wp_unslash($testimonial['author_title']));
                    }
                    
                    if (isset($testimonial['rating'])) {
                        $validated_testimonial['rating'] = intval($testimonial['rating']);
                    }
                    
                    if (isset($testimonial['image'])) {
                        $validated_testimonial['image'] = esc_url_raw(wp_unslash($testimonial['image']));
                    }
                    
                    $validated['testimonials'][] = $validated_testimonial;
                }
            }
        }
        
        // Validate layout options
        if (isset($config['layout'])) {
            $validated['layout'] = sanitize_text_field($config['layout']);
        }
        
        if (isset($config['columns'])) {
            $validated['columns'] = intval($config['columns']);
        }
        
        if (isset($config['visible'])) {
            $validated['visible'] = (bool) $config['visible'];
        }
        
        // Pass through other fields with basic sanitization
        foreach ($config as $key => $value) {
            if (!isset($validated[$key])) {
                if (is_string($value)) {
                    $validated[$key] = sanitize_text_field($value);
                } elseif (is_array($value)) {
                    $validated[$key] = self::sanitize_array_recursively($value);
                } else {
                    $validated[$key] = $value;
                }
            }
        }
        
        return $validated;
    }
    
    /**
     * Validate product grid section configuration
     *
     * @param array $config Configuration data
     * @return array Validated configuration
     * @throws ValidationException
     */
    private static function validate_product_grid_config(array $config): array {
        $validated = [];
        
        // Handle basic fields
        if (isset($config['title'])) {
            $validated['title'] = sanitize_text_field($config['title']);
        }
        
        if (isset($config['subtitle'])) {
            $validated['subtitle'] = sanitize_text_field($config['subtitle']);
        }
        
        // Validate products array if present
        if (isset($config['products']) && is_array($config['products'])) {
            $validated['products'] = [];
            foreach ($config['products'] as $product) {
                if (is_array($product)) {
                    $validated_product = [];
                    
                    // Sanitize product fields
                    if (isset($product['title'])) {
                        $validated_product['title'] = sanitize_text_field(wp_unslash($product['title']));
                    }
                    
                    if (isset($product['price'])) {
                        $validated_product['price'] = sanitize_text_field(wp_unslash($product['price']));
                    }
                    
                    if (isset($product['description'])) {
                        $validated_product['description'] = sanitize_textarea_field(wp_unslash($product['description']));
                    }
                    
                    if (isset($product['image'])) {
                        $validated_product['image'] = esc_url_raw(wp_unslash($product['image']));
                    }
                    
                    if (isset($product['link'])) {
                        $validated_product['link'] = esc_url_raw(wp_unslash($product['link']));
                    }
                    
                    if (isset($product['button_text'])) {
                        $validated_product['button_text'] = sanitize_text_field(wp_unslash($product['button_text']));
                    }
                    
                    if (isset($product['featured'])) {
                        $validated_product['featured'] = (bool) $product['featured'];
                    }
                    
                    $validated['products'][] = $validated_product;
                }
            }
        }
        
        // Validate grid options
        if (isset($config['columns'])) {
            $validated['columns'] = intval($config['columns']);
        }
        
        if (isset($config['layout'])) {
            $validated['layout'] = sanitize_text_field($config['layout']);
        }
        
        if (isset($config['visible'])) {
            $validated['visible'] = (bool) $config['visible'];
        }
        
        // Pass through other fields with basic sanitization
        foreach ($config as $key => $value) {
            if (!isset($validated[$key])) {
                if (is_string($value)) {
                    $validated[$key] = sanitize_text_field($value);
                } elseif (is_array($value)) {
                    $validated[$key] = self::sanitize_array_recursively($value);
                } else {
                    $validated[$key] = $value;
                }
            }
        }
        
        return $validated;
    }
    
    /**
     * Validate hero banner section configuration
     *
     * @param array $config Configuration data
     * @return array Validated configuration
     * @throws ValidationException
     */
    private static function validate_hero_banner_config(array $config): array {
        $validated = [];
        
        // Handle basic fields
        if (isset($config['title'])) {
            $validated['title'] = sanitize_text_field(wp_unslash($config['title']));
        }
        
        if (isset($config['subtitle'])) {
            $validated['subtitle'] = sanitize_textarea_field(wp_unslash($config['subtitle']));
        }
        
        if (isset($config['background_image'])) {
            $validated['background_image'] = esc_url_raw(wp_unslash($config['background_image']));
        }
        
        if (isset($config['cta_text'])) {
            $validated['cta_text'] = sanitize_text_field(wp_unslash($config['cta_text']));
        }
        
        if (isset($config['cta_url'])) {
            $validated['cta_url'] = esc_url_raw(wp_unslash($config['cta_url']));
        }
        
        if (isset($config['cta_enabled'])) {
            $validated['cta_enabled'] = (bool) $config['cta_enabled'];
        }
        
        if (isset($config['visible'])) {
            $validated['visible'] = (bool) $config['visible'];
        }
        
        // Pass through other fields with basic sanitization
        foreach ($config as $key => $value) {
            if (!isset($validated[$key])) {
                if (is_string($value)) {
                    $validated[$key] = sanitize_text_field($value);
                } elseif (is_array($value)) {
                    $validated[$key] = self::sanitize_array_recursively($value);
                } else {
                    $validated[$key] = $value;
                }
            }
        }
        
        return $validated;
    }
    
    /**
     * Validate category showcase section configuration
     *
     * @param array $config Configuration data
     * @return array Validated configuration
     * @throws ValidationException
     */
    private static function validate_category_showcase_config(array $config): array {
        $validated = [];
        
        // Handle basic fields
        if (isset($config['title'])) {
            $validated['title'] = sanitize_text_field(wp_unslash($config['title']));
        }
        
        if (isset($config['subtitle'])) {
            $validated['subtitle'] = sanitize_text_field(wp_unslash($config['subtitle']));
        }
        
        // Validate categories array if present
        if (isset($config['categories']) && is_array($config['categories'])) {
            $validated['categories'] = [];
            foreach ($config['categories'] as $category) {
                if (is_array($category)) {
                    $validated_category = [];
                    
                    // Sanitize category fields
                    if (isset($category['title'])) {
                        $validated_category['title'] = sanitize_text_field(wp_unslash($category['title']));
                    }
                    
                    if (isset($category['description'])) {
                        $validated_category['description'] = sanitize_textarea_field(wp_unslash($category['description']));
                    }
                    
                    if (isset($category['image'])) {
                        $validated_category['image'] = esc_url_raw(wp_unslash($category['image']));
                    }
                    
                    if (isset($category['link'])) {
                        $validated_category['link'] = esc_url_raw(wp_unslash($category['link']));
                    }
                    
                    if (isset($category['link_target'])) {
                        $validated_category['link_target'] = sanitize_text_field(wp_unslash($category['link_target']));
                    }
                    
                    $validated['categories'][] = $validated_category;
                }
            }
        }
        
        // Validate layout options
        if (isset($config['layout'])) {
            $validated['layout'] = sanitize_text_field($config['layout']);
        }
        
        if (isset($config['columns'])) {
            $validated['columns'] = intval($config['columns']);
        }
        
        if (isset($config['visible'])) {
            $validated['visible'] = (bool) $config['visible'];
        }
        
        // Pass through other fields with basic sanitization
        foreach ($config as $key => $value) {
            if (!isset($validated[$key])) {
                if (is_string($value)) {
                    $validated[$key] = sanitize_text_field($value);
                } elseif (is_array($value)) {
                    $validated[$key] = self::sanitize_array_recursively($value);
                } else {
                    $validated[$key] = $value;
                }
            }
        }
        
        return $validated;
    }
    
    /**
     * Validate content block section configuration
     *
     * @param array $config Configuration data
     * @return array Validated configuration
     * @throws ValidationException
     */
    private static function validate_content_block_config(array $config): array {
        $validated = [];
        
        // Handle basic fields
        if (isset($config['title'])) {
            $validated['title'] = sanitize_text_field(wp_unslash($config['title']));
        }
        
        if (isset($config['content'])) {
            $validated['content'] = self::sanitize_rich_text(wp_unslash($config['content']));
        }
        
        if (isset($config['image'])) {
            $validated['image'] = esc_url_raw(wp_unslash($config['image']));
        }
        
        if (isset($config['visible'])) {
            $validated['visible'] = (bool) $config['visible'];
        }
        
        // Pass through other fields with basic sanitization
        foreach ($config as $key => $value) {
            if (!isset($validated[$key])) {
                if (is_string($value)) {
                    $validated[$key] = sanitize_text_field($value);
                } elseif (is_array($value)) {
                    $validated[$key] = self::sanitize_array_recursively($value);
                } else {
                    $validated[$key] = $value;
                }
            }
        }
        
        return $validated;
    }
    
    /**
     * Recursively sanitize array values
     *
     * @param array $array Array to sanitize
     * @return array Sanitized array
     */
    private static function sanitize_array_recursively(array $array): array {
        $sanitized = [];
        
        foreach ($array as $key => $value) {
            $sanitized_key = sanitize_key($key);
            
            if (is_array($value)) {
                $sanitized[$sanitized_key] = self::sanitize_array_recursively($value);
            } elseif (is_string($value)) {
                // Remove any existing slashes before sanitizing to prevent double-escaping
                $value = wp_unslash($value);
                
                // Check if it looks like a URL
                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    $sanitized[$sanitized_key] = esc_url_raw($value);
                } else {
                    $sanitized[$sanitized_key] = sanitize_text_field($value);
                }
            } else {
                $sanitized[$sanitized_key] = $value;
            }
        }
        
        return $sanitized;
    }
}