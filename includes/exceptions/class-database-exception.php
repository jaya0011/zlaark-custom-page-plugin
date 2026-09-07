<?php
/**
 * Database-related exception class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Exceptions;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Exception thrown for database-related errors
 */
class DatabaseException extends PageBuilderException {
    
    /**
     * Database operation that failed
     *
     * @var string|null
     */
    protected $operation;
    
    /**
     * Table name involved in the operation
     *
     * @var string|null
     */
    protected $table_name;
    
    /**
     * Constructor
     *
     * @param string $message Error message
     * @param string|null $operation Database operation
     * @param string|null $table_name Table name
     * @param array $context Additional context
     */
    public function __construct(
        string $message = 'Database error occurred',
        ?string $operation = null,
        ?string $table_name = null,
        array $context = []
    ) {
        $this->operation = $operation;
        $this->table_name = $table_name;
        
        $db_context = [];
        if ($operation) {
            $db_context['operation'] = $operation;
        }
        if ($table_name) {
            $db_context['table'] = $table_name;
        }
        
        parent::__construct(
            $message,
            'DATABASE_ERROR',
            500,
            array_merge($context, $db_context)
        );
    }
    
    /**
     * Get database operation
     *
     * @return string|null
     */
    public function getOperation(): ?string {
        return $this->operation;
    }
    
    /**
     * Get table name
     *
     * @return string|null
     */
    public function getTableName(): ?string {
        return $this->table_name;
    }
    
    /**
     * Create exception for insert failure
     *
     * @param string $table_name Table name
     * @param string $details Error details
     * @return self
     */
    public static function insertFailed(string $table_name, string $details = ''): self {
        $message = sprintf('Failed to insert record into %s', $table_name);
        if ($details) {
            $message .= ': ' . $details;
        }
        
        return new self($message, 'INSERT', $table_name);
    }
    
    /**
     * Create exception for update failure
     *
     * @param string $table_name Table name
     * @param int|string $record_id Record ID
     * @param string $details Error details
     * @return self
     */
    public static function updateFailed(string $table_name, $record_id, string $details = ''): self {
        $message = sprintf('Failed to update record %s in %s', $record_id, $table_name);
        if ($details) {
            $message .= ': ' . $details;
        }
        
        return new self(
            $message,
            'UPDATE',
            $table_name,
            ['record_id' => $record_id]
        );
    }
    
    /**
     * Create exception for delete failure
     *
     * @param string $table_name Table name
     * @param int|string $record_id Record ID
     * @param string $details Error details
     * @return self
     */
    public static function deleteFailed(string $table_name, $record_id, string $details = ''): self {
        $message = sprintf('Failed to delete record %s from %s', $record_id, $table_name);
        if ($details) {
            $message .= ': ' . $details;
        }
        
        return new self(
            $message,
            'DELETE',
            $table_name,
            ['record_id' => $record_id]
        );
    }
    
    /**
     * Create exception for query failure
     *
     * @param string $query_type Query type (SELECT, etc.)
     * @param string $table_name Table name
     * @param string $details Error details
     * @return self
     */
    public static function queryFailed(string $query_type, string $table_name, string $details = ''): self {
        $message = sprintf('Failed to execute %s query on %s', $query_type, $table_name);
        if ($details) {
            $message .= ': ' . $details;
        }
        
        return new self($message, $query_type, $table_name);
    }
}