<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Autoload;

/**
 * WebSocket Publish Command
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Commands
 */
class WebSocketPublish extends BaseCommand
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
    protected $name = 'websocket:publish';

    /**
     * The Command's Description
     * 
     * @var string
     */
    protected $description = 'Publish WebSocket configuration and resources';

    /**
     * The Command's Usage
     * 
     * @var string
     */
    protected $usage = 'websocket:publish [options]';

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
    protected $options = [];

    /**
     * Source path
     * 
     * @var string
     */
    protected $sourcePath;

    /**
     * Run the command
     * 
     * @param array $params
     */
    public function run(array $params)
    {
        $this->determineSourcePath();
        
        // Publish Config
        if (CLI::prompt('Publish Config file?', ['y', 'n']) == 'y') {
            $this->publishConfig();
        }
        
        // Publish Controller
        if (CLI::prompt('Publish Controller?', ['y', 'n']) == 'y') {
            $this->publishController();
        }
        
        // Publish Views
        if (CLI::prompt('Publish Views?', ['y', 'n']) == 'y') {
            $this->publishViews();
        }
    }

    /**
     * Publish config
     * 
     * @return void
     */
    protected function publishConfig(): void
    {
        $path = "{$this->sourcePath}/Config/Websocket.php";
        
        if (!file_exists($path)) {
            CLI::error("Config file not found: {$path}");
            return;
        }
        
        $content = file_get_contents($path);
        $content = str_replace(
            'namespace Yakupeyisan\CodeIgniterWebSocket\Config',
            'namespace Config',
            $content
        );
        $content = str_replace(
            'extends BaseConfig',
            'extends \Yakupeyisan\CodeIgniterWebSocket\Config\Websocket',
            $content
        );
        
        $this->writeFile('Config/Websocket.php', $content);
    }

    /**
     * Publish controller
     * 
     * @return void
     */
    protected function publishController(): void
    {
        $path = "{$this->sourcePath}/Controllers/WebSocketController.php";
        
        if (!file_exists($path)) {
            CLI::write('Creating default controller...', 'yellow');
            $content = $this->getDefaultControllerContent();
        } else {
            $content = file_get_contents($path);
            $content = $this->replaceNamespace(
                $content,
                'Yakupeyisan\CodeIgniterWebSocket\Controllers',
                'Controllers'
            );
        }
        
        $this->writeFile('Controllers/WebSocketController.php', $content);
    }

    /**
     * Publish views
     * 
     * @return void
     */
    protected function publishViews(): void
    {
        $viewPath = "{$this->sourcePath}/Views";
        
        if (!is_dir($viewPath)) {
            CLI::write('Creating default view...', 'yellow');
            $this->writeFile('Views/websocket/client.php', $this->getDefaultViewContent());
            return;
        }
        
        $map = directory_map($viewPath);
        $prefix = '';
        
        foreach ($map as $key => $view) {
            if (is_array($view)) {
                $oldPrefix = $prefix;
                $prefix .= $key;
                
                foreach ($view as $file) {
                    $this->publishView($file, $prefix);
                }
                
                $prefix = $oldPrefix;
                continue;
            }
            
            $this->publishView($view, $prefix);
        }
    }

    /**
     * Publish view
     * 
     * @param string $view
     * @param string $prefix
     * @return void
     */
    protected function publishView(string $view, string $prefix = ''): void
    {
        $path = "{$this->sourcePath}/Views/{$prefix}{$view}";
        
        if (!file_exists($path)) {
            return;
        }
        
        $content = file_get_contents($path);
        $this->writeFile("Views/WebSocket/{$prefix}{$view}", $content);
    }

    /**
     * Get default controller content
     * 
     * @return string
     */
    protected function getDefaultControllerContent(): string
    {
        $namespace = defined('APP_NAMESPACE') ? APP_NAMESPACE : 'App';
        
        return <<<PHP
<?php

namespace {$namespace}\Controllers;

use CodeIgniter\Controller;
use Yakupeyisan\CodeIgniterWebSocket\Core\WebSocketServer;

class WebSocketController extends Controller
{
    protected \$config;

    public function __construct()
    {
        \$this->config = config('Websocket');
    }

    public function start()
    {
        \$ws = service('websocket');
        \$ws->setCallback('auth', [\$this, '_auth']);
        \$ws->setCallback('event', [\$this, '_event']);
        \$ws->start();
    }

    public function client(\$userId = null)
    {
        return view('websocket/client', ['userId' => \$userId]);
    }

    public function _auth(\$datas = null)
    {
        // Implement your authentication logic here
        return (!empty(\$datas->user_id)) ? \$datas->user_id : false;
    }

    public function _event(\$datas = null)
    {
        // Handle events here
        log_message('info', 'WebSocket event: ' . json_encode(\$datas));
    }
}
PHP;
    }

    /**
     * Get default view content
     * 
     * @return string
     */
    protected function getDefaultViewContent(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>WebSocket Client</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        #messages { border: 1px solid #ccc; padding: 10px; height: 400px; overflow-y: auto; margin-bottom: 10px; }
        .message { margin: 5px 0; padding: 5px; background: #f5f5f5; }
        input, button { padding: 8px; margin: 5px 0; }
        button { cursor: pointer; }
        .status { padding: 10px; margin: 10px 0; }
        .connected { background: #d4edda; }
        .disconnected { background: #f8d7da; }
    </style>
</head>
<body>
    <h1>WebSocket Client</h1>
    <div id="status" class="status disconnected">Disconnected</div>
    <div id="messages"></div>
    <input type="text" id="message" placeholder="Type your message...">
    <input type="text" id="recipient" placeholder="Recipient ID (optional)">
    <button id="send">Send</button>
    <button id="connect">Connect</button>
    <button id="disconnect">Disconnect</button>

    <script>
        var ws = null;
        var userId = <?php echo $userId ?? 'null'; ?>;
        var token = null;

        function connect() {
            var host = window.location.hostname;
            var port = '8282';
            ws = new WebSocket('ws://' + host + ':' + port);

            ws.onopen = function() {
                updateStatus('Connected', true);
                if (userId) {
                    ws.send(JSON.stringify({
                        type: 'socket',
                        user_id: userId
                    }));
                }
            };

            ws.onmessage = function(e) {
                var data = JSON.parse(e.data);
                if (data.type === 'token') {
                    token = data.data.token;
                }
                addMessage(data);
            };

            ws.onclose = function() {
                updateStatus('Disconnected', false);
            };

            ws.onerror = function(error) {
                console.error('WebSocket error:', error);
            };
        }

        function disconnect() {
            if (ws) {
                ws.close();
                ws = null;
            }
        }

        function sendMessage() {
            if (!ws || ws.readyState !== WebSocket.OPEN) {
                alert('Not connected');
                return;
            }

            var message = document.getElementById('message').value;
            var recipient = document.getElementById('recipient').value;

            var data = {
                type: 'chat',
                message: message,
                token: token
            };

            if (recipient) {
                data.recipient_id = recipient;
            }

            ws.send(JSON.stringify(data));
            document.getElementById('message').value = '';
        }

        function addMessage(data) {
            var messages = document.getElementById('messages');
            var div = document.createElement('div');
            div.className = 'message';
            div.textContent = JSON.stringify(data, null, 2);
            messages.appendChild(div);
            messages.scrollTop = messages.scrollHeight;
        }

        function updateStatus(text, connected) {
            var status = document.getElementById('status');
            status.textContent = text;
            status.className = 'status ' + (connected ? 'connected' : 'disconnected');
        }

        document.getElementById('connect').addEventListener('click', connect);
        document.getElementById('disconnect').addEventListener('click', disconnect);
        document.getElementById('send').addEventListener('click', sendMessage);
        document.getElementById('message').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    </script>
</body>
</html>
HTML;
    }

    /**
     * Determine source path
     * 
     * @return void
     */
    protected function determineSourcePath(): void
    {
        $this->sourcePath = realpath(__DIR__ . '/../');
        
        if ($this->sourcePath === false || empty($this->sourcePath)) {
            CLI::error('Unable to determine source path');
            exit(EXIT_ERROR);
        }
    }

    /**
     * Replace namespace
     * 
     * @param string $contents
     * @param string $originalNamespace
     * @param string $newNamespace
     * @return string
     */
    protected function replaceNamespace(string $contents, string $originalNamespace, string $newNamespace): string
    {
        $appNamespace = APP_NAMESPACE;
        $originalNamespace = "namespace {$originalNamespace}";
        $newNamespace = "namespace {$appNamespace}\\{$newNamespace}";
        
        return str_replace($originalNamespace, $newNamespace, $contents);
    }

    /**
     * Write file
     * 
     * @param string $path
     * @param string $content
     * @return void
     */
    protected function writeFile(string $path, string $content): void
    {
        $config = new Autoload();
        $appPath = $config->psr4[APP_NAMESPACE];
        
        $directory = dirname($appPath . $path);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        try {
            write_file($appPath . $path, $content);
            CLI::write(CLI::color('  created: ', 'green') . $path);
        } catch (\Exception $e) {
            CLI::error("Failed to write file: {$path}");
            CLI::error($e->getMessage());
        }
    }
}

