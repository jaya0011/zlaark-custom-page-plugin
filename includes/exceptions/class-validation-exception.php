<?php
/**
 * Validation exception class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Exceptions;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Exception thrown when validation fails
 */
class ValidationException extends PageBuilderException {
    
    /**
     * Validation errors
     *
     * @var array
     */
    protected $validation_errors;
    
    /**
     * Constructor
     *
     * @param string $message Error message
     * @param array $validation_errors Validation errors
     * @param array $context Additional context
     */
    public function __construct(
        string $message = 'Validation failed',
        array $validation_errors = [],
        array $context = []
    ) {
        $this->validation_errors = $validation_errors;
        
        parent::__construct(
            $message,
            'VALIDATION_ERROR',
            400,
            array_merge($context, ['validation_errors' => $validation_errors])
        );
    }
    
    /**
     * Get validation errors
     *
     * @return array
     */
    public function getValidationErrors(): array {
        return $this->validation_errors;
    }
    
    /**
     * Add validation error
     *
     * @param string $field Field name
     * @param string $message Error message
     * @return self
     */
    public function addValidationError(string $field, string $message): self {
        $this->validation_errors[$field] = $message;
        $this->context['validation_errors'] = $this->validation_errors;
        return $this;
    }
    
    /**
     * Check if field has validation error
     *
     * @param string $field Field name
     * @return bool
     */
    public function hasValidationError(string $field): bool {
        return isset($this->validation_errors[$field]);
    }
}