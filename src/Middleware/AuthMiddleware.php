<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Middleware;

use Yakupeyisan\CodeIgniterWebSocket\Libraries\Authorization;
use Yakupeyisan\CodeIgniterWebSocket\Middleware\MiddlewareInterface;
use Ratchet\ConnectionInterface;

/**
 * Authentication Middleware
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Middleware
 */
class AuthMiddleware implements MiddlewareInterface
{
    /**
     * JWT key
     * 
     * @var string
     */
    protected $jwtKey;

    /**
     * Token timeout in minutes
     * 
     * @var int
     */
    protected $tokenTimeout;

    /**
     * Constructor
     * 
     * @param string $jwtKey
     * @param int $tokenTimeout
     */
    public function __construct(string $jwtKey, int $tokenTimeout = 60)
    {
        $this->jwtKey = $jwtKey;
        $this->tokenTimeout = $tokenTimeout;
    }

    /**
     * Handle incoming message
     * 
     * @param ConnectionInterface $connection
     * @param object $message
     * @param callable $next
     * @return mixed
     */
    public function handle(ConnectionInterface $connection, object $message, callable $next)
    {
        // Skip auth for socket type (initial connection)
        if (isset($message->messageType) && $message->messageType === 'socket') {
            return $next($connection, $message);
        }
        
        // Check for token
        if (!isset($message->token) || empty($message->token)) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => 'Authentication required'
            ]));
            return false;
        }
        
        // Validate token
        $token = Authorization::validateTimestamp($message->token, $this->jwtKey, $this->tokenTimeout);
        
        if ($token === false) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => 'Invalid or expired token'
            ]));
            return false;
        }
        
        return $next($connection, $message);
    }
}

