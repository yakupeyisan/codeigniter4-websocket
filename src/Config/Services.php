<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Config;

use CodeIgniter\Config\BaseService;
use Yakupeyisan\CodeIgniterWebSocket\Config\Websocket;
use Yakupeyisan\CodeIgniterWebSocket\Core\WebSocketServer;

/**
 * WebSocket Services Configuration
 * 
 * Add this to your app/Config/Services.php file:
 * 
 * public static function websocket(?Websocket $config = null, bool $getShared = true)
 * {
 *     if ($getShared) {
 *         return static::getSharedInstance('websocket', $config);
 *     }
 * 
 *     if (empty($config)) {
 *         $config = config('Websocket');
 *     }
 * 
 *     return new \Yakupeyisan\CodeIgniterWebSocket\Core\WebSocketServer($config);
 * }
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Config
 */
class Services extends BaseService
{
    /**
     * Get WebSocket server instance
     * 
     * @param Websocket|null $config
     * @param bool $getShared
     * @return WebSocketServer
     */
    public static function websocket(?Websocket $config = null, bool $getShared = true): WebSocketServer
    {
        if ($getShared) {
            return static::getSharedInstance('websocket', $config);
        }

        if (empty($config)) {
            $config = config('Websocket');
        }

        return new WebSocketServer($config);
    }
}

