<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Contracts;

/**
 * WebSocket Interface
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Contracts
 */
interface WebSocketInterface
{
    /**
     * Start the WebSocket server
     * 
     * @return void
     */
    public function start(): void;

    /**
     * Stop the WebSocket server
     * 
     * @return void
     */
    public function stop(): void;

    /**
     * Set callback
     * 
     * @param string $type
     * @param callable $callback
     * @return self
     */
    public function setCallback(string $type, callable $callback): self;

    /**
     * Broadcast message to all clients
     * 
     * @param mixed $message
     * @param array $exclude
     * @return void
     */
    public function broadcast($message, array $exclude = []): void;

    /**
     * Send message to specific client
     * 
     * @param int $clientId
     * @param mixed $message
     * @return bool
     */
    public function sendToClient(int $clientId, $message): bool;

    /**
     * Send message to room
     * 
     * @param string $room
     * @param mixed $message
     * @param array $exclude
     * @return void
     */
    public function sendToRoom(string $room, $message, array $exclude = []): void;
}

