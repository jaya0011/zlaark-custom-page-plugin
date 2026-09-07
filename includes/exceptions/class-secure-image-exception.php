<?php
/**
 * Secure image-related exception class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Exceptions;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Exception thrown for secure image-related errors
 */
class SecureImageException extends PageBuilderException {
    
    /**
     * Image ID that caused the error
     *
     * @var int|null
     */
    protected $image_id;
    
    /**
     * Image operation that failed
     *
     * @var string|null
     */
    protected $operation;
    
    /**
     * Constructor
     *
     * @param string $message Error message
     * @param string $error_code Error code
     * @param int $http_status HTTP status code
     * @param int|null $image_id Image ID
     * @param string|null $operation Image operation
     * @param array $context Additional context
     */
    public function __construct(
        string $message = 'Secure image error occurred',
        string $error_code = 'SECURE_IMAGE_ERROR',
        int $http_status = 500,
        ?int $image_id = null,
        ?string $operation = null,
        array $context = []
    ) {
        $this->image_id = $image_id;
        $this->operation = $operation;
        
        $image_context = [];
        if ($image_id) {
            $image_context['image_id'] = $image_id;
        }
        if ($operation) {
            $image_context['operation'] = $operation;
        }
        
        parent::__construct(
            $message,
            $error_code,
            $http_status,
            array_merge($context, $image_context)
        );
    }
    
    /**
     * Get image ID
     *
     * @return int|null
     */
    public function getImageId(): ?int {
        return $this->image_id;
    }
    
    /**
     * Get operation
     *
     * @return string|null
     */
    public function getOperation(): ?string {
        return $this->operation;
    }
    
    /**
     * Create exception for plugin not active
     *
     * @return self
     */
    public static function pluginNotActive(): self {
        return new self(
            'Secure image plugin is not active',
            'SECURE_IMAGE_PLUGIN_INACTIVE',
            503,
            null,
            'check_plugin'
        );
    }
    
    /**
     * Create exception for image processing failure
     *
     * @param int $image_id Image ID
     * @param string $details Error details
     * @return self
     */
    public static function processingFailed(int $image_id, string $details = ''): self {
        $message = sprintf('Failed to process image %d', $image_id);
        if ($details) {
            $message .= ': ' . $details;
        }
        
        return new self(
            $message,
            'IMAGE_PROCESSING_FAILED',
            500,
            $image_id,
            'process'
        );
    }
    
    /**
     * Create exception for URL generation failure
     *
     * @param int $image_id Image ID
     * @param string $size Image size
     * @return self
     */
    public static function urlGenerationFailed(int $image_id, string $size = 'full'): self {
        return new self(
            sprintf('Failed to generate secure URL for image %d (size: %s)', $image_id, $size),
            'URL_GENERATION_FAILED',
            500,
            $image_id,
            'generate_url',
            ['size' => $size]
        );
    }
    
    /**
     * Create exception for invalid image
     *
     * @param int $image_id Image ID
     * @return self
     */
    public static function invalidImage(int $image_id): self {
        return new self(
            sprintf('Invalid or non-existent image: %d', $image_id),
            'INVALID_IMAGE',
            404,
            $image_id,
            'validate'
        );
    }
}