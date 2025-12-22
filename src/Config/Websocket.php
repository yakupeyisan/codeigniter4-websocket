<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * WebSocket Configuration
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Config
 */
class Websocket extends BaseConfig
{
    /**
     * Server host
     * 
     * @var string
     */
    public $host = '0.0.0.0';

    /**
     * Server port
     * 
     * @var int
     */
    public $port = 8282;

    /**
     * Enable SSL/TLS
     * 
     * @var bool
     */
    public $ssl = false;

    /**
     * SSL certificate path
     * 
     * @var string|null
     */
    public $sslCertPath = null;

    /**
     * SSL private key path
     * 
     * @var string|null
     */
    public $sslKeyPath = null;

    /**
     * Enable authentication
     * 
     * @var bool
     */
    public $auth = false;

    /**
     * JWT secret key
     * 
     * @var string
     */
    public $jwtKey = 'Q9FZrT2SxL8P0GmCWy7JHkN5AEdU6B';

    /**
     * JWT token timeout in minutes
     * 
     * @var int
     */
    public $tokenTimeout = 60;

    /**
     * Enable debug mode
     * 
     * @var bool
     */
    public $debug = false;

    /**
     * Enable timer
     * 
     * @var bool
     */
    public $timer = false;

    /**
     * Timer interval in seconds
     * 
     * @var int
     */
    public $interval = 1;

    /**
     * Maximum connections per IP
     * 
     * @var int
     */
    public $maxConnectionsPerIp = 10;

    /**
     * Rate limiting: messages per minute
     * 
     * @var int
     */
    public $rateLimitPerMinute = 60;

    /**
     * Enable presence system
     * 
     * @var bool
     */
    public $presenceEnabled = true;

    /**
     * Presence heartbeat interval in seconds
     * 
     * @var int
     */
    public $presenceHeartbeatInterval = 30;

    /**
     * Enable message history
     * 
     * @var bool
     */
    public $messageHistoryEnabled = false;

    /**
     * Message history limit per room
     * 
     * @var int
     */
    public $messageHistoryLimit = 100;

    /**
     * Enable CORS
     * 
     * @var bool
     */
    public $corsEnabled = true;

    /**
     * Allowed CORS origins
     * 
     * @var array
     */
    public $corsOrigins = ['*'];

    /**
     * Allowed CORS methods
     * 
     * @var array
     */
    public $corsMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];

    /**
     * Allowed CORS headers
     * 
     * @var array
     */
    public $corsHeaders = ['Content-Type', 'Authorization'];

    /**
     * Enable logging
     * 
     * @var bool
     */
    public $loggingEnabled = true;

    /**
     * Log level (debug, info, warning, error)
     * 
     * @var string
     */
    public $logLevel = 'info';

    /**
     * Available callbacks
     * 
     * @var array
     */
    public $callbacks = [
        'auth',
        'event',
        'close',
        'citimer',
        'roomjoin',
        'roomleave',
        'roomchat',
        'connect',
        'disconnect',
        'error',
        'presence',
        'typing',
        'read'
    ];

    /**
     * Middleware classes
     * 
     * @var array
     */
    public $middleware = [];

    /**
     * Event handlers
     * 
     * @var array
     */
    public $eventHandlers = [];

    /**
     * Enable metrics collection
     * 
     * @var bool
     */
    public $metricsEnabled = false;

    /**
     * Metrics update interval in seconds
     * 
     * @var int
     */
    public $metricsInterval = 60;
}

