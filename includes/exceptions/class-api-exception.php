<?php
/**
 * API-related exception class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Exceptions;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Exception thrown for API-related errors
 */
class ApiException extends PageBuilderException {
    
    /**
     * API endpoint that caused the error
     *
     * @var string|null
     */
    protected $endpoint;
    
    /**
     * HTTP method used
     *
     * @var string|null
     */
    protected $method;
    
    /**
     * Constructor
     *
     * @param string $message Error message
     * @param string $error_code Error code
     * @param int $http_status HTTP status code
     * @param string|null $endpoint API endpoint
     * @param string|null $method HTTP method
     * @param array $context Additional context
     */
    public function __construct(
        string $message = 'API error occurred',
        string $error_code = 'API_ERROR',
        int $http_status = 500,
        ?string $endpoint = null,
        ?string $method = null,
        array $context = []
    ) {
        $this->endpoint = $endpoint;
        $this->method = $method;
        
        $api_context = [];
        if ($endpoint) {
            $api_context['endpoint'] = $endpoint;
        }
        if ($method) {
            $api_context['method'] = $method;
        }
        
        parent::__construct(
            $message,
            $error_code,
            $http_status,
            array_merge($context, $api_context)
        );
    }
    
    /**
     * Get endpoint
     *
     * @return string|null
     */
    public function getEndpoint(): ?string {
        return $this->endpoint;
    }
    
    /**
     * Get HTTP method
     *
     * @return string|null
     */
    public function getMethod(): ?string {
        return $this->method;
    }
    
    /**
     * Create exception for authentication failure
     *
     * @param string $endpoint API endpoint
     * @return self
     */
    public static function authenticationFailed(string $endpoint): self {
        return new self(
            'Authentication failed',
            'AUTHENTICATION_FAILED',
            401,
            $endpoint
        );
    }
    
    /**
     * Create exception for authorization failure
     *
     * @param string $endpoint API endpoint
     * @param string $required_capability Required capability
     * @return self
     */
    public static function authorizationFailed(string $endpoint, string $required_capability = ''): self {
        $message = 'Authorization failed';
        if ($required_capability) {
            $message .= sprintf(' (required: %s)', $required_capability);
        }
        
        return new self(
            $message,
            'AUTHORIZATION_FAILED',
            403,
            $endpoint,
            null,
            ['required_capability' => $required_capability]
        );
    }
    
    /**
     * Create exception for rate limiting
     *
     * @param string $endpoint API endpoint
     * @param int $retry_after Seconds to wait before retry
     * @return self
     */
    public static function rateLimited(string $endpoint, int $retry_after = 60): self {
        return new self(
            'Rate limit exceeded',
            'RATE_LIMIT_EXCEEDED',
            429,
            $endpoint,
            null,
            ['retry_after' => $retry_after]
        );
    }
    
    /**
     * Create exception for invalid request
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param string $details Error details
     * @return self
     */
    public static function invalidRequest(string $endpoint, string $method, string $details = ''): self {
        $message = 'Invalid request';
        if ($details) {
            $message .= ': ' . $details;
        }
        
        return new self(
            $message,
            'INVALID_REQUEST',
            400,
            $endpoint,
            $method
        );
    }
}