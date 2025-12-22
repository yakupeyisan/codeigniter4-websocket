<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Middleware;

use Ratchet\ConnectionInterface;

/**
 * Middleware Interface
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Middleware
 */
interface MiddlewareInterface
{
    /**
     * Handle incoming message
     * 
     * @param ConnectionInterface $connection
     * @param object $message
     * @param callable $next
     * @return mixed
     */
    public function handle(ConnectionInterface $connection, object $message, callable $next);
}

