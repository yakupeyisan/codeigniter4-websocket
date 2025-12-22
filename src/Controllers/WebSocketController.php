<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Controllers;

use CodeIgniter\Controller;
use Yakupeyisan\CodeIgniterWebSocket\Core\WebSocketServer;

/**
 * WebSocket Controller
 * 
 * Default WebSocket controller
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Controllers
 */
class WebSocketController extends Controller
{
    /**
     * Configuration
     * 
     * @var \Yakupeyisan\CodeIgniterWebSocket\Config\Websocket
     */
    protected $config;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->config = config('Websocket');
    }

    /**
     * Start WebSocket server
     * 
     * @return void
     */
    public function start(): void
    {
        $ws = service('websocket');
        $ws->setCallback('auth', [$this, '_auth']);
        $ws->setCallback('event', [$this, '_event']);
        $ws->setCallback('close', [$this, '_close']);
        $ws->start();
    }

    /**
     * WebSocket client page
     * 
     * @param int|null $userId
     * @return string
     */
    public function client(?int $userId = null): string
    {
        return view('websocket/client', ['userId' => $userId]);
    }

    /**
     * Authentication callback
     * 
     * @param object|null $data
     * @return int|false
     */
    public function _auth($data = null)
    {
        // Implement your authentication logic here
        // Return user ID on success, false on failure
        return (!empty($data->user_id)) ? (int)$data->user_id : false;
    }

    /**
     * Event callback
     * 
     * @param object|null $data
     * @return void
     */
    public function _event($data = null): void
    {
        // Handle events here
        log_message('info', 'WebSocket event: ' . json_encode($data));
    }

    /**
     * Close callback
     * 
     * @param \Ratchet\ConnectionInterface $connection
     * @return void
     */
    public function _close($connection): void
    {
        // Handle connection close
        log_message('info', 'WebSocket connection closed: ' . $connection->resourceId);
    }
}

