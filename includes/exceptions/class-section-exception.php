<?php
/**
 * Section-related exception class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Exceptions;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Exception thrown for section-related errors
 */
class SectionException extends PageBuilderException {
    
    /**
     * Section type that caused the error
     *
     * @var string|null
     */
    protected $section_type;
    
    /**
     * Section ID that caused the error
     *
     * @var int|null
     */
    protected $section_id;
    
    /**
     * Constructor
     *
     * @param string $message Error message
     * @param string $error_code Error code
     * @param int $http_status HTTP status code
     * @param string|null $section_type Section type
     * @param int|null $section_id Section ID
     * @param array $context Additional context
     */
    public function __construct(
        string $message = 'Section error occurred',
        string $error_code = 'SECTION_ERROR',
        int $http_status = 400,
        ?string $section_type = null,
        ?int $section_id = null,
        array $context = []
    ) {
        $this->section_type = $section_type;
        $this->section_id = $section_id;
        
        $section_context = [];
        if ($section_type) {
            $section_context['section_type'] = $section_type;
        }
        if ($section_id) {
            $section_context['section_id'] = $section_id;
        }
        
        parent::__construct(
            $message,
            $error_code,
            $http_status,
            array_merge($context, $section_context)
        );
    }
    
    /**
     * Get section type
     *
     * @return string|null
     */
    public function getSectionType(): ?string {
        return $this->section_type;
    }
    
    /**
     * Get section ID
     *
     * @return int|null
     */
    public function getSectionId(): ?int {
        return $this->section_id;
    }
    
    /**
     * Create exception for invalid section type
     *
     * @param string $section_type Invalid section type
     * @return self
     */
    public static function invalidSectionType(string $section_type): self {
        return new self(
            sprintf('Invalid section type: %s', $section_type),
            'INVALID_SECTION_TYPE',
            400,
            $section_type
        );
    }
    
    /**
     * Create exception for section not found
     *
     * @param int $section_id Section ID
     * @return self
     */
    public static function sectionNotFound(int $section_id): self {
        return new self(
            sprintf('Section not found: %d', $section_id),
            'SECTION_NOT_FOUND',
            404,
            null,
            $section_id
        );
    }
    
    /**
     * Create exception for section configuration error
     *
     * @param string $section_type Section type
     * @param string $config_error Configuration error details
     * @return self
     */
    public static function configurationError(string $section_type, string $config_error): self {
        return new self(
            sprintf('Section configuration error for %s: %s', $section_type, $config_error),
            'SECTION_CONFIG_ERROR',
            400,
            $section_type,
            null,
            ['config_error' => $config_error]
        );
    }
}