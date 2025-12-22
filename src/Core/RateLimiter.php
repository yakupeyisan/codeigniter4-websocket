<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Core;

/**
 * Rate Limiter
 * 
 * Limits message rate per connection
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Core
 */
class RateLimiter
{
    /**
     * Rate limit data: resourceId => [count, resetTime]
     * 
     * @var array
     */
    protected $limits = [];

    /**
     * Maximum messages per minute
     * 
     * @var int
     */
    protected $maxPerMinute;

    /**
     * Constructor
     * 
     * @param int $maxPerMinute
     */
    public function __construct(int $maxPerMinute = 60)
    {
        $this->maxPerMinute = $maxPerMinute;
    }

    /**
     * Check if connection can send message
     * 
     * @param int $resourceId
     * @return bool
     */
    public function canSend(int $resourceId): bool
    {
        $now = time();
        
        if (!isset($this->limits[$resourceId])) {
            $this->limits[$resourceId] = [
                'count' => 0,
                'resetTime' => $now + 60
            ];
        }
        
        $limit = &$this->limits[$resourceId];
        
        // Reset if time window passed
        if ($now >= $limit['resetTime']) {
            $limit['count'] = 0;
            $limit['resetTime'] = $now + 60;
        }
        
        // Check if limit exceeded
        if ($limit['count'] >= $this->maxPerMinute) {
            return false;
        }
        
        // Increment count
        $limit['count']++;
        
        return true;
    }

    /**
     * Get remaining messages for connection
     * 
     * @param int $resourceId
     * @return int
     */
    public function getRemaining(int $resourceId): int
    {
        if (!isset($this->limits[$resourceId])) {
            return $this->maxPerMinute;
        }
        
        $limit = $this->limits[$resourceId];
        $now = time();
        
        if ($now >= $limit['resetTime']) {
            return $this->maxPerMinute;
        }
        
        return max(0, $this->maxPerMinute - $limit['count']);
    }

    /**
     * Reset rate limit for connection
     * 
     * @param int $resourceId
     * @return void
     */
    public function reset(int $resourceId): void
    {
        unset($this->limits[$resourceId]);
    }

    /**
     * Clean up old rate limit data
     * 
     * @return void
     */
    public function cleanup(): void
    {
        $now = time();
        
        foreach ($this->limits as $resourceId => $limit) {
            if ($now >= $limit['resetTime']) {
                unset($this->limits[$resourceId]);
            }
        }
    }
}

