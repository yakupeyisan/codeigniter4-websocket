<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Middleware;

use Yakupeyisan\CodeIgniterWebSocket\Core\RateLimiter;
use Yakupeyisan\CodeIgniterWebSocket\Middleware\MiddlewareInterface;
use Ratchet\ConnectionInterface;

/**
 * Rate Limit Middleware
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Middleware
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * Rate limiter
     * 
     * @var RateLimiter
     */
    protected $rateLimiter;

    /**
     * Constructor
     * 
     * @param RateLimiter $rateLimiter
     */
    public function __construct(RateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
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
        $resourceId = $connection->resourceId;
        
        if (!$this->rateLimiter->canSend($resourceId)) {
            $remaining = $this->rateLimiter->getRemaining($resourceId);
            $connection->send(json_encode([
                'type' => 'error',
                'message' => 'Rate limit exceeded',
                'remaining' => $remaining,
                'retryAfter' => 60
            ]));
            return false;
        }
        
        return $next($connection, $message);
    }
}

