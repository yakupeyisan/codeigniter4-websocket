<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Core;

use Ratchet\ConnectionInterface;

/**
 * Message Handler
 * 
 * Handles incoming WebSocket messages
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Core
 */
class MessageHandler
{
    /**
     * Message history storage
     * 
     * @var array
     */
    protected $messageHistory = [];

    /**
     * Maximum history per room
     * 
     * @var int
     */
    protected $historyLimit;

    /**
     * History enabled
     * 
     * @var bool
     */
    protected $historyEnabled;

    /**
     * Constructor
     * 
     * @param bool $historyEnabled
     * @param int $historyLimit
     */
    public function __construct(bool $historyEnabled = false, int $historyLimit = 100)
    {
        $this->historyEnabled = $historyEnabled;
        $this->historyLimit = $historyLimit;
    }

    /**
     * Parse message
     * 
     * @param string $message
     * @return object|null
     */
    public function parse(string $message): ?object
    {
        if (!$this->isValidJson($message)) {
            return null;
        }
        
        $data = json_decode($message);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $data;
    }

    /**
     * Validate JSON
     * 
     * @param string $string
     * @return bool
     */
    public function isValidJson(string $string): bool
    {
        if (!is_string($string)) {
            return false;
        }
        
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Add message to history
     * 
     * @param string $room
     * @param array $message
     * @return void
     */
    public function addToHistory(string $room, array $message): void
    {
        if (!$this->historyEnabled) {
            return;
        }
        
        if (!isset($this->messageHistory[$room])) {
            $this->messageHistory[$room] = [];
        }
        
        $this->messageHistory[$room][] = array_merge($message, [
            'timestamp' => time()
        ]);
        
        // Limit history size
        if (count($this->messageHistory[$room]) > $this->historyLimit) {
            array_shift($this->messageHistory[$room]);
        }
    }

    /**
     * Get message history
     * 
     * @param string $room
     * @param int $limit
     * @return array
     */
    public function getHistory(string $room, int $limit = 50): array
    {
        if (!isset($this->messageHistory[$room])) {
            return [];
        }
        
        $history = $this->messageHistory[$room];
        return array_slice($history, -$limit);
    }

    /**
     * Clear message history
     * 
     * @param string|null $room
     * @return void
     */
    public function clearHistory(?string $room = null): void
    {
        if ($room === null) {
            $this->messageHistory = [];
        } else {
            unset($this->messageHistory[$room]);
        }
    }

    /**
     * Format message for sending
     * 
     * @param mixed $data
     * @return string
     */
    public function format($data): string
    {
        if (is_string($data)) {
            return $data;
        }
        
        return json_encode($data);
    }

    /**
     * Create error message
     * 
     * @param string $message
     * @param int|null $code
     * @return string
     */
    public function createErrorMessage(string $message, ?int $code = null): string
    {
        $error = [
            'type' => 'error',
            'message' => $message
        ];
        
        if ($code !== null) {
            $error['code'] = $code;
        }
        
        return $this->format($error);
    }

    /**
     * Create success message
     * 
     * @param mixed $data
     * @param string|null $type
     * @return string
     */
    public function createSuccessMessage($data, ?string $type = null): string
    {
        $message = [
            'type' => $type ?? 'success',
            'data' => $data
        ];
        
        return $this->format($message);
    }
}

