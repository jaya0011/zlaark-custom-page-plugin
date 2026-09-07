<?php
/**
 * Base exception class for Custom Page Builder
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Exceptions;

use Exception;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base exception class for all Custom Page Builder exceptions
 */
class PageBuilderException extends Exception {
    
    /**
     * Error context data
     *
     * @var array
     */
    protected $context;
    
    /**
     * Error code for API responses
     *
     * @var string
     */
    protected $error_code;
    
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $http_status;
    
    /**
     * Constructor
     *
     * @param string $message Error message
     * @param string $error_code Error code for API responses
     * @param int $http_status HTTP status code
     * @param array $context Additional context data
     * @param Exception|null $previous Previous exception
     */
    public function __construct(
        string $message = '',
        string $error_code = 'GENERAL_ERROR',
        int $http_status = 500,
        array $context = [],
        ?Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        
        $this->error_code = $error_code;
        $this->http_status = $http_status;
        $this->context = $context;
    }
    
    /**
     * Get error code
     *
     * @return string
     */
    public function getErrorCode(): string {
        return $this->error_code;
    }
    
    /**
     * Get HTTP status code
     *
     * @return int
     */
    public function getHttpStatus(): int {
        return $this->http_status;
    }
    
    /**
     * Get error context
     *
     * @return array
     */
    public function getContext(): array {
        return $this->context;
    }
    
    /**
     * Add context data
     *
     * @param string $key Context key
     * @param mixed $value Context value
     * @return self
     */
    public function addContext(string $key, $value): self {
        $this->context[$key] = $value;
        return $this;
    }
    
    /**
     * Convert to array for API responses
     *
     * @return array
     */
    public function toArray(): array {
        return [
            'success' => false,
            'error' => [
                'code' => $this->error_code,
                'message' => $this->getMessage(),
                'context' => $this->context,
                'file' => $this->getFile(),
                'line' => $this->getLine()
            ]
        ];
    }
    
    /**
     * Convert to WP_Error
     *
     * @return \WP_Error
     */
    public function toWpError(): \WP_Error {
        return new \WP_Error(
            $this->error_code,
            $this->getMessage(),
            [
                'status' => $this->http_status,
                'context' => $this->context
            ]
        );
    }
}