<?php

/**
 * WebSocket Helper Functions
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Helpers
 */

if (!function_exists('websocket_service')) {
    /**
     * Get WebSocket service instance
     * 
     * @return \Yakupeyisan\CodeIgniterWebSocket\Core\WebSocketServer
     */
    function websocket_service()
    {
        return service('websocket');
    }
}

if (!function_exists('websocket_broadcast')) {
    /**
     * Broadcast message to all connections
     * 
     * @param mixed $message
     * @param array $exclude
     * @return void
     */
    function websocket_broadcast($message, array $exclude = [])
    {
        websocket_service()->broadcast($message, $exclude);
    }
}

if (!function_exists('websocket_send_to_client')) {
    /**
     * Send message to specific client
     * 
     * @param int $clientId
     * @param mixed $message
     * @return bool
     */
    function websocket_send_to_client(int $clientId, $message): bool
    {
        return websocket_service()->sendToClient($clientId, $message);
    }
}

if (!function_exists('websocket_send_to_room')) {
    /**
     * Send message to room
     * 
     * @param string $room
     * @param mixed $message
     * @param array $exclude
     * @return void
     */
    function websocket_send_to_room(string $room, $message, array $exclude = [])
    {
        websocket_service()->sendToRoom($room, $message, $exclude);
    }
}

if (!function_exists('websocket_valid_json')) {
    /**
     * Check if string is valid JSON
     * 
     * @param string $string
     * @return bool
     */
    function websocket_valid_json(string $string): bool
    {
        if (!is_string($string)) {
            return false;
        }
        
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

if (!function_exists('websocket_connection_count')) {
    /**
     * Get connection count
     * 
     * @return int
     */
    function websocket_connection_count(): int
    {
        return websocket_service()->getConnectionManager()->count();
    }
}

if (!function_exists('websocket_room_count')) {
    /**
     * Get room connection count
     * 
     * @param string $room
     * @return int
     */
    function websocket_room_count(string $room): int
    {
        return websocket_service()->getRoomManager()->getRoomCount($room);
    }
}

