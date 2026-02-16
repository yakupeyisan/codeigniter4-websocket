<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Yakupeyisan\CodeIgniterWebSocket\Core\WebSocketServer;
use Yakupeyisan\CodeIgniterWebSocket\Config\Websocket;
use Config\Services;

/**
 * WebSocket Start Command
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Commands
 */
class WebSocketStart extends BaseCommand
{
    /**
     * The Command's Group
     * 
     * @var string
     */
    protected $group = 'WebSocket';

    /**
     * The Command's Name
     * 
     * @var string
     */
    protected $name = 'websocket:start';

    /**
     * The Command's Description
     * 
     * @var string
     */
    protected $description = 'Start the WebSocket server';

    /**
     * The Command's Usage
     * 
     * @var string
     */
    protected $usage = 'websocket:start [options]';

    /**
     * The Command's Arguments
     * 
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     * 
     * @var array
     */
    protected $options = [
        '--host' => 'Server host (default: from config)',
        '--port' => 'Server port (default: from config)',
        '--debug' => 'Enable debug mode',
    ];

    /**
     * Actually execute a command.
     * 
     * @param array $params
     */
    public function run(array $params)
    {
        $config = config('Websocket');
        
        // Override config from options (support both --port 2525 and --port=2525)
        $host = $this->getOptionValue('host');
        if ($host !== null) {
            $config->host = $host;
        }
        
        $port = $this->getOptionValue('port');
        if ($port !== null) {
            $config->port = (int) $port;
        }
        
        if (CLI::getOption('debug')) {
            $config->debug = true;
        }
        
        CLI::write('Starting WebSocket server...', 'green');
        CLI::write("Host: {$config->host}", 'yellow');
        CLI::write("Port: {$config->port}", 'yellow');
        CLI::write('Press Ctrl+C to stop the server', 'yellow');
        CLI::newLine();
        
        // Use service to get WebSocket server instance
        $server = Services::websocket($config, false);
        
        // Try to load callbacks from Websocket controller if it exists
        if (class_exists('\App\Controllers\Websocket')) {
            $controller = new \App\Controllers\Websocket();
            if (method_exists($controller, '_open')) {
                $server->setCallback('connect', [$controller, '_open']);
            }
            if (method_exists($controller, '_event')) {
                $server->setCallback('event', [$controller, '_event']);
            }
            if (method_exists($controller, '_close')) {
                $server->setCallback('close', [$controller, '_close']);
            }
            if (method_exists($controller, '_custom')) {
                $server->setCallback('custom', [$controller, '_custom']);
            }
        }
        
        $server->start();
    }

    /**
     * Get option value supporting both --name value and --name=value formats.
     * CodeIgniter CLI stores --port=2525 as key "port=2525", so getOption('port') returns null.
     *
     * @param string $name Option name
     * @return string|null
     */
    private function getOptionValue(string $name): ?string
    {
        $value = CLI::getOption($name);
        if ($value !== null && $value !== true) {
            return (string) $value;
        }
        foreach (CLI::getOptions() as $key => $val) {
            if (strpos($key, $name . '=') === 0) {
                return substr($key, strlen($name) + 1) ?: null;
            }
        }
        return null;
    }
}

