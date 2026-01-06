<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Core;

use Yakupeyisan\CodeIgniterWebSocket\Config\Websocket;
use Yakupeyisan\CodeIgniterWebSocket\Contracts\WebSocketInterface;
use Yakupeyisan\CodeIgniterWebSocket\Core\ConnectionManager;
use Yakupeyisan\CodeIgniterWebSocket\Core\RoomManager;
use Yakupeyisan\CodeIgniterWebSocket\Core\EventDispatcher;
use Yakupeyisan\CodeIgniterWebSocket\Core\PresenceManager;
use Yakupeyisan\CodeIgniterWebSocket\Core\RateLimiter;
use Yakupeyisan\CodeIgniterWebSocket\Core\MessageHandler;
use Yakupeyisan\CodeIgniterWebSocket\Libraries\Authorization;
use Yakupeyisan\CodeIgniterWebSocket\Middleware\MiddlewareInterface;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

/**
 * WebSocket Server
 * 
 * Main WebSocket server implementation
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Core
 */
class WebSocketServer implements WebSocketInterface, MessageComponentInterface
{
    /**
     * Configuration
     * 
     * @var Websocket
     */
    protected $config;

    /**
     * Connection manager
     * 
     * @var ConnectionManager
     */
    protected $connectionManager;

    /**
     * Room manager
     * 
     * @var RoomManager
     */
    protected $roomManager;

    /**
     * Event dispatcher
     * 
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * Presence manager
     * 
     * @var PresenceManager|null
     */
    protected $presenceManager;

    /**
     * Rate limiter
     * 
     * @var RateLimiter
     */
    protected $rateLimiter;

    /**
     * Message handler
     * 
     * @var MessageHandler
     */
    protected $messageHandler;

    /**
     * Callbacks
     * 
     * @var array
     */
    protected $callbacks = [];

    /**
     * Middleware stack
     * 
     * @var array
     */
    protected $middleware = [];

    /**
     * Server instance
     * 
     * @var IoServer|null
     */
    protected $server = null;

    /**
     * IP connection counts
     * 
     * @var array
     */
    protected $ipConnections = [];

    /**
     * Constructor
     * 
     * @param Websocket $config
     */
    public function __construct(Websocket $config)
    {
        $this->config = $config;
        $this->connectionManager = new ConnectionManager();
        $this->roomManager = new RoomManager();
        $this->eventDispatcher = new EventDispatcher();
        $this->rateLimiter = new RateLimiter($config->rateLimitPerMinute);
        $this->messageHandler = new MessageHandler(
            $config->messageHistoryEnabled,
            $config->messageHistoryLimit
        );

        if ($config->presenceEnabled) {
            $this->presenceManager = new PresenceManager($config->presenceHeartbeatInterval);
        }

        // Load middleware from config
        if (!empty($config->middleware)) {
            foreach ($config->middleware as $middlewareClass) {
                if (class_exists($middlewareClass)) {
                    $this->middleware[] = new $middlewareClass();
                }
            }
        }

        // Add default middleware if auth is enabled
        // if ($config->auth) {
        //     $this->addMiddleware(new \Yakupeyisan\CodeIgniterWebSocket\Middleware\AuthMiddleware(
        //         $config->jwtKey,
        //         $config->tokenTimeout
        //     ));
        // }

        // // Add rate limiting middleware
        // $this->addMiddleware(new \Yakupeyisan\CodeIgniterWebSocket\Middleware\RateLimitMiddleware(
        //     $this->rateLimiter
        // ));
    }

    /**
     * Start the WebSocket server
     * 
     * @return void
     */
    public function start(): void
    {
        $server = IoServer::factory(
            new HttpServer(
                new WsServer($this)
            ),
            $this->config->port,
            $this->config->host
        );

        $this->server = $server;

        // Setup timer if enabled
        if ($this->config->timer) {
            $server->loop->addPeriodicTimer($this->config->interval, function () {
                $this->handleTimer();
            });
        }

        // Setup presence heartbeat
        if ($this->config->presenceEnabled && $this->presenceManager) {
            $server->loop->addPeriodicTimer($this->config->presenceHeartbeatInterval, function () {
                $this->presenceManager->cleanup();
            });
        }

        // Setup rate limiter cleanup
        $server->loop->addPeriodicTimer(60, function () {
            $this->rateLimiter->cleanup();
        });

        if ($this->config->debug) {
            $this->log('info', "WebSocket server starting on {$this->config->host}:{$this->config->port}");
        }

        $server->run();
    }

    /**
     * Stop the WebSocket server
     * 
     * @return void
     */
    public function stop(): void
    {
        if ($this->server) {
            $this->server->loop->stop();
        }
    }

    /**
     * Set callback
     * 
     * @param string $type
     * @param callable $callback
     * @return self
     */
    public function setCallback(string $type, callable $callback): self
    {
        if (in_array($type, $this->config->callbacks)) {
            $this->callbacks[$type] = $callback;
        }
        
        return $this;
    }

    /**
     * Add middleware
     * 
     * @param MiddlewareInterface $middleware
     * @return self
     */
    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Broadcast message to all clients
     * 
     * @param mixed $message
     * @param array $exclude
     * @return void
     */
    public function broadcast($message, array $exclude = []): void
    {
        $formatted = $this->messageHandler->format($message);
        
        foreach ($this->connectionManager->getAll() as $connection) {
            if (!in_array($connection->resourceId, $exclude)) {
                $connection->send($formatted);
            }
        }
    }

    /**
     * Send message to specific client
     * 
     * @param int $clientId
     * @param mixed $message
     * @return bool
     */
    public function sendToClient(int $clientId, $message): bool
    {
        $connection = $this->connectionManager->get($clientId);
        
        if ($connection) {
            $connection->send($this->messageHandler->format($message));
            return true;
        }
        
        return false;
    }

    /**
     * Send message to room
     * 
     * @param string $room
     * @param mixed $message
     * @param array $exclude
     * @return void
     */
    public function sendToRoom(string $room, $message, array $exclude = []): void
    {
        $formatted = $this->messageHandler->format($message);
        $connections = $this->roomManager->getConnections($room);
        
        foreach ($connections as $connection) {
            if (!in_array($connection->resourceId, $exclude)) {
                $connection->send($formatted);
            }
        }
    }

    /**
     * Handle connection open
     * 
     * @param ConnectionInterface $connection
     * @return void
     */
    public function onOpen(ConnectionInterface $connection): void
    {
        $ip = $connection->remoteAddress ?? 'unknown';
        
        // Check connection limit per IP
        if (!isset($this->ipConnections[$ip])) {
            $this->ipConnections[$ip] = 0;
        }
        
        if ($this->ipConnections[$ip] >= $this->config->maxConnectionsPerIp) {
            $connection->close(1008, 'Connection limit exceeded');
            return;
        }
        
        $this->ipConnections[$ip]++;
        
        // Add connection
        $this->connectionManager->add($connection, [
            'ip' => $ip
        ]);
        
        $this->eventDispatcher->dispatch('connect', [
            'connection' => $connection,
            'resourceId' => $connection->resourceId
        ]);
        
        if (isset($this->callbacks['connect'])) {
            call_user_func($this->callbacks['connect'], $connection);
        }
        
        if ($this->config->debug) {
            $this->log('info', "New connection: {$connection->resourceId} from {$ip}");
        }
    }

    /**
     * Handle incoming message
     * 
     * @param ConnectionInterface $from
     * @param mixed $msg
     * @return void
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $this->connectionManager->updateActivity($from);
        
        // Convert message to string if needed
        $message = is_string($msg) ? $msg : (string)$msg;
        
        // Parse message
        $data = $this->messageHandler->parse($message);
        
        if ($data === null) {
            $from->send($this->messageHandler->createErrorMessage('Invalid JSON format'));
            return;
        }
        
        // Run middleware
        $next = function ($conn, $msg) use ($from, $data) {
            $this->processMessage($from, $data);
        };
        
        $result = $next($from, $data);
        
        if ($result === false) {
            return; // Middleware blocked the message
        }
    }

    /**
     * Process message
     * 
     * @param ConnectionInterface $connection
     * @param object $data
     * @return void
     */
    protected function processMessage(ConnectionInterface $connection, object $data): void
    {
        
        $this->handleCustomMessage($connection, $data);
        return;
        // Get message type from messageType or type field
        $messageType = $data->messageType ?? $data->type ?? null;
        // Route to appropriate handler based on messageType
        switch ($messageType) {
            case 'socket':
                $this->handleSocket($connection, $data);
                break;
            case 'chat':
                $this->handleChat($connection, $data);
                break;
            case 'roomjoin':
                $this->handleRoomJoin($connection, $data);
                break;
            case 'roomleave':
                $this->handleRoomLeave($connection, $data);
                break;
            case 'roomchat':
                $this->handleRoomChat($connection, $data);
                break;
            case 'typing':
                $this->handleTyping($connection, $data);
                break;
            case 'presence':
                $this->handlePresence($connection, $data);
                break;
            default:
                // For unknown message types, use custom callback
                $this->handleCustomMessage($connection, $data);
                break;
        }
    }

    /**
     * Handle socket connection (authentication)
     * 
     * @param ConnectionInterface $connection
     * @param object $data
     * @return void
     */
    protected function handleSocket(ConnectionInterface $connection, object $data): void
    {
        if (empty($this->callbacks['auth'])) {
            $connection->send($this->messageHandler->createErrorMessage('Authentication callback not set'));
            $connection->close(1008);
            return;
        }
        
        $userId = call_user_func($this->callbacks['auth'], $data);
        
        if (empty($userId) || !is_numeric($userId)) {
            $connection->send($this->messageHandler->createErrorMessage('Authentication failed'));
            $connection->close(1008);
            return;
        }
        
        // Update connection data
        $this->connectionManager->setData($connection, [
            'userId' => $userId
        ]);
        
        // Set presence
        if ($this->presenceManager) {
            $this->presenceManager->setOnline($connection, $userId);
        }
        
        // Generate token if auth is enabled
        if ($this->config->auth) {
            $token = Authorization::generateToken([
                'resourceId' => $connection->resourceId,
                'userId' => $userId,
                'timestamp' => time()
            ], $this->config->jwtKey);
            
            $connection->send($this->messageHandler->createSuccessMessage([
                'token' => $token
            ], 'token'));
        }
        
        $this->eventDispatcher->dispatch('authenticated', [
            'connection' => $connection,
            'userId' => $userId
        ]);
        
        if ($this->config->debug) {
            $this->log('success', "Client {$connection->resourceId} authenticated as user {$userId}");
        }
    }

    /**
     * Handle chat message
     * 
     * @param ConnectionInterface $connection
     * @param object $data
     * @return void
     */
    protected function handleChat(ConnectionInterface $connection, object $data): void
    {
        $connectionData = $this->connectionManager->getData($connection);
        $userId = $connectionData['userId'] ?? null;
        
        $message = [
            'type' => 'chat',
            'userId' => $userId,
            'message' => $data->message ?? '',
            'timestamp' => time()
        ];
        
        if (isset($data->recipient_id)) {
            // Send to specific user
            $recipient = $this->connectionManager->getByUserId($data->recipient_id);
            if ($recipient) {
                $recipient->send($this->messageHandler->format($message));
            }
        } else {
            // Broadcast
            $exclude = isset($data->broadcast) && $data->broadcast ? [] : [$connection->resourceId];
            $this->broadcast($message, $exclude);
        }
        
        $this->eventDispatcher->dispatch('message', [
            'connection' => $connection,
            'message' => $message
        ]);
        
        if (isset($this->callbacks['event'])) {
            call_user_func($this->callbacks['event'], $data);
        }
    }

    /**
     * Handle room join
     * 
     * @param ConnectionInterface $connection
     * @param object $data
     * @return void
     */
    protected function handleRoomJoin(ConnectionInterface $connection, object $data): void
    {
        $room = $data->room ?? null;
        
        if (empty($room)) {
            $connection->send($this->messageHandler->createErrorMessage('Room name required'));
            return;
        }
        
        $joined = $this->roomManager->join($connection, $room, (array)($data->data ?? []));
        
        if ($joined) {
            $connection->send($this->messageHandler->createSuccessMessage([
                'room' => $room,
                'message' => 'Joined room successfully'
            ], 'roomjoin'));
            
            // Notify others in room
            $this->sendToRoom($room, [
                'type' => 'user_joined',
                'room' => $room,
                'userId' => $this->connectionManager->getData($connection)['userId'] ?? null,
                'timestamp' => time()
            ], [$connection->resourceId]);
            
            if (isset($this->callbacks['roomjoin'])) {
                call_user_func($this->callbacks['roomjoin'], $data, $connection);
            }
        }
    }

    /**
     * Handle room leave
     * 
     * @param ConnectionInterface $connection
     * @param object $data
     * @return void
     */
    protected function handleRoomLeave(ConnectionInterface $connection, object $data): void
    {
        $room = $data->room ?? null;
        
        if (empty($room)) {
            $connection->send($this->messageHandler->createErrorMessage('Room name required'));
            return;
        }
        
        $left = $this->roomManager->leave($connection, $room);
        
        if ($left) {
            $connection->send($this->messageHandler->createSuccessMessage([
                'room' => $room,
                'message' => 'Left room successfully'
            ], 'roomleave'));
            
            // Notify others in room
            $this->sendToRoom($room, [
                'type' => 'user_left',
                'room' => $room,
                'userId' => $this->connectionManager->getData($connection)['userId'] ?? null,
                'timestamp' => time()
            ], [$connection->resourceId]);
            
            if (isset($this->callbacks['roomleave'])) {
                call_user_func($this->callbacks['roomleave'], $data, $connection);
            }
        }
    }

    /**
     * Handle room chat
     * 
     * @param ConnectionInterface $connection
     * @param object $data
     * @return void
     */
    protected function handleRoomChat(ConnectionInterface $connection, object $data): void
    {
        $room = $data->room ?? null;
        
        if (empty($room)) {
            $connection->send($this->messageHandler->createErrorMessage('Room name required'));
            return;
        }
        
        if (!$this->roomManager->isInRoom($connection, $room)) {
            $connection->send($this->messageHandler->createErrorMessage('Not in room'));
            return;
        }
        
        $connectionData = $this->connectionManager->getData($connection);
        $userId = $connectionData['userId'] ?? null;
        
        $message = [
            'type' => 'roomchat',
            'room' => $room,
            'userId' => $userId,
            'message' => $data->message ?? '',
            'timestamp' => time()
        ];
        
        // Add to history
        $this->messageHandler->addToHistory($room, $message);
        
        // Send to room
        $this->sendToRoom($room, $message, [$connection->resourceId]);
        
        if (isset($this->callbacks['roomchat'])) {
            call_user_func($this->callbacks['roomchat'], $data, $connection);
        }
    }

    /**
     * Handle typing indicator
     * 
     * @param ConnectionInterface $connection
     * @param object $data
     * @return void
     */
    protected function handleTyping(ConnectionInterface $connection, object $data): void
    {
        $room = $data->room ?? null;
        $connectionData = $this->connectionManager->getData($connection);
        $userId = $connectionData['userId'] ?? null;
        
        if ($room) {
            $this->sendToRoom($room, [
                'type' => 'typing',
                'room' => $room,
                'userId' => $userId,
                'typing' => $data->typing ?? true
            ], [$connection->resourceId]);
        } elseif (isset($data->recipient_id)) {
            $recipient = $this->connectionManager->getByUserId($data->recipient_id);
            if ($recipient) {
                $recipient->send($this->messageHandler->format([
                    'type' => 'typing',
                    'userId' => $userId,
                    'typing' => $data->typing ?? true
                ]));
            }
        }
    }

    /**
     * Handle presence update
     * 
     * @param ConnectionInterface $connection
     * @param object $data
     * @return void
     */
    protected function handlePresence(ConnectionInterface $connection, object $data): void
    {
        if (!$this->presenceManager) {
            return;
        }
        
        $connectionData = $this->connectionManager->getData($connection);
        $userId = $connectionData['userId'] ?? null;
        
        if ($userId) {
            $this->presenceManager->updateHeartbeat($connection->resourceId);
            
            if (isset($data->metadata)) {
                $this->presenceManager->updateMetadata($userId, (array)$data->metadata);
            }
        }
    }

    /**
     * Handle custom message type
     * 
     * @param ConnectionInterface $connection
     * @param object $data
     * @return void
     */
    protected function handleCustomMessage(ConnectionInterface $connection, object $data): void
    {
        if (isset($this->callbacks['custom'])) {
            call_user_func($this->callbacks['custom'], $data, $connection);
        }
    }

    /**
     * Handle connection close
     * 
     * @param ConnectionInterface $connection
     * @return void
     */
    public function onClose(ConnectionInterface $connection): void
    {
        $connectionData = $this->connectionManager->getData($connection);
        $ip = $connectionData['ip'] ?? 'unknown';
        
        // Update IP connection count
        if (isset($this->ipConnections[$ip])) {
            $this->ipConnections[$ip]--;
            if ($this->ipConnections[$ip] <= 0) {
                unset($this->ipConnections[$ip]);
            }
        }
        
        // Remove from presence
        if ($this->presenceManager) {
            $this->presenceManager->setOffline($connection);
        }
        
        // Remove from all rooms
        $this->roomManager->removeFromAllRooms($connection);
        
        // Remove connection
        $this->connectionManager->remove($connection);
        
        // Reset rate limit
        $this->rateLimiter->reset($connection->resourceId);
        
        $this->eventDispatcher->dispatch('disconnect', [
            'connection' => $connection,
            'resourceId' => $connection->resourceId
        ]);
        
        if (isset($this->callbacks['close'])) {
            call_user_func($this->callbacks['close'], $connection);
        }
        
        if ($this->config->debug) {
            $this->log('info', "Connection closed: {$connection->resourceId}");
        }
    }

    /**
     * Handle error
     * 
     * @param ConnectionInterface $connection
     * @param \Exception $e
     * @return void
     */
    public function onError(ConnectionInterface $connection, \Exception $e): void
    {
        $this->log('error', "Error on connection {$connection->resourceId}: {$e->getMessage()}");
        
        $this->eventDispatcher->dispatch('error', [
            'connection' => $connection,
            'error' => $e
        ]);
        
        if (isset($this->callbacks['error'])) {
            call_user_func($this->callbacks['error'], $connection, $e);
        }
        
        $connection->close();
    }

    /**
     * Handle timer
     * 
     * @return void
     */
    protected function handleTimer(): void
    {
        if (isset($this->callbacks['citimer'])) {
            call_user_func($this->callbacks['citimer'], date('Y-m-d H:i:s'));
        }
    }

    /**
     * Log message
     * 
     * @param string $level
     * @param string $message
     * @return void
     */
    protected function log(string $level, string $message): void
    {
        if (!$this->config->loggingEnabled) {
            return;
        }
        
        $allowedLevels = ['debug', 'info', 'warning', 'error'];
        $levelMap = [
            'debug' => 0,
            'info' => 1,
            'warning' => 2,
            'error' => 3
        ];
        
        $configLevel = $levelMap[$this->config->logLevel] ?? 1;
        $messageLevel = $levelMap[$level] ?? 1;
        
        if ($messageLevel >= $configLevel) {
            if (function_exists('log_message')) {
                log_message($level, "[WebSocket] {$message}");
            } else {
                echo "[{$level}] {$message}\n";
            }
        }
    }

    /**
     * Get connection manager
     * 
     * @return ConnectionManager
     */
    public function getConnectionManager(): ConnectionManager
    {
        return $this->connectionManager;
    }

    /**
     * Get room manager
     * 
     * @return RoomManager
     */
    public function getRoomManager(): RoomManager
    {
        return $this->roomManager;
    }

    /**
     * Get event dispatcher
     * 
     * @return EventDispatcher
     */
    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }

    /**
     * Get presence manager
     * 
     * @return PresenceManager|null
     */
    public function getPresenceManager(): ?PresenceManager
    {
        return $this->presenceManager;
    }

    /**
     * Get message handler
     * 
     * @return MessageHandler
     */
    public function getMessageHandler(): MessageHandler
    {
        return $this->messageHandler;
    }
}

