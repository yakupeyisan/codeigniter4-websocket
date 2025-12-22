<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Exceptions;

use CodeIgniter\Exceptions\ExceptionInterface;
use CodeIgniter\Exceptions\FrameworkException;

/**
 * WebSocket Exception
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Exceptions
 */
class WebSocketException extends FrameworkException implements ExceptionInterface
{
    /**
     * Missing configuration
     * 
     * @param string $key
     * @return static
     */
    public static function forMissingConfig(string $key)
    {
        return new static("Missing WebSocket configuration: {$key}");
    }

    /**
     * Invalid callback
     * 
     * @param string $type
     * @return static
     */
    public static function forInvalidCallback(string $type)
    {
        return new static("Invalid callback type: {$type}");
    }

    /**
     * Connection error
     * 
     * @param string $message
     * @return static
     */
    public static function forConnectionError(string $message)
    {
        return new static("Connection error: {$message}");
    }

    /**
     * Authentication failed
     * 
     * @return static
     */
    public static function forAuthenticationFailed()
    {
        return new static("Authentication failed");
    }

    /**
     * Rate limit exceeded
     * 
     * @return static
     */
    public static function forRateLimitExceeded()
    {
        return new static("Rate limit exceeded");
    }
}

