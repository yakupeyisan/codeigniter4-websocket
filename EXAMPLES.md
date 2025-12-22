# Kullanım Örnekleri

## Temel Kullanım

### 1. Basit Chat Uygulaması

```php
// app/Controllers/ChatController.php
namespace App\Controllers;

use CodeIgniter\Controller;

class ChatController extends Controller
{
    public function start()
    {
        $ws = service('websocket');
        $ws->setCallback('auth', [$this, '_auth']);
        $ws->setCallback('chat', [$this, '_handleChat']);
        $ws->start();
    }

    public function _auth($data)
    {
        // Kullanıcı doğrulama
        $userId = $data->user_id ?? null;
        
        if ($userId && $this->validateUser($userId)) {
            return (int)$userId;
        }
        
        return false;
    }

    public function _handleChat($data)
    {
        // Mesajı veritabanına kaydet
        $this->saveMessage($data);
        
        // Bildirim gönder
        $this->sendNotification($data);
    }
}
```

### 2. Oda Bazlı Chat

```php
// Odaya katılma
$ws = service('websocket');
$ws->setCallback('roomjoin', function($data, $connection) {
    $room = $data->room;
    $userId = $data->user_id;
    
    // Odaya katılma işlemi
    log_message('info', "User {$userId} joined room {$room}");
});

// Oda mesajlaşması
$ws->setCallback('roomchat', function($data, $connection) {
    $room = $data->room;
    $message = $data->message;
    
    // Mesajı odaya gönder
    $ws->sendToRoom($room, [
        'type' => 'message',
        'message' => $message,
        'timestamp' => time()
    ]);
});
```

### 3. Presence System

```php
// Online kullanıcıları listeleme
$ws = service('websocket');
$presence = $ws->getPresenceManager();

$onlineUsers = $presence->getOnlineUsers();

foreach ($onlineUsers as $userId => $data) {
    echo "User {$userId} is online since " . date('Y-m-d H:i:s', $data['connectedAt']);
}

// Kullanıcı online mi kontrol
if ($presence->isOnline($userId)) {
    // Kullanıcı online
}
```

### 4. Broadcast Kullanımı

```php
// Tüm kullanıcılara bildirim
websocket_broadcast([
    'type' => 'notification',
    'title' => 'Sistem Güncellemesi',
    'message' => 'Sistem bakımı 10 dakika içinde başlayacak',
    'timestamp' => time()
]);

// Belirli kullanıcıları hariç tut
websocket_broadcast([
    'type' => 'announcement',
    'message' => 'Yeni özellik eklendi!'
], [123, 456]); // Bu ID'ler hariç
```

### 5. Özel Middleware

```php
// app/Middleware/AdminMiddleware.php
namespace App\Middleware;

use Yakupeyisan\CodeIgniterWebSocket\Middleware\MiddlewareInterface;
use Ratchet\ConnectionInterface;

class AdminMiddleware implements MiddlewareInterface
{
    public function handle(ConnectionInterface $connection, object $message, callable $next)
    {
        // Admin kontrolü
        $connectionData = service('websocket')->getConnectionManager()->getData($connection);
        $userId = $connectionData['userId'] ?? null;
        
        if (!$this->isAdmin($userId)) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => 'Admin access required'
            ]));
            return false;
        }
        
        return $next($connection, $message);
    }
    
    private function isAdmin($userId)
    {
        // Admin kontrol mantığı
        return false;
    }
}

// Middleware'i ekle
$ws = service('websocket');
$ws->addMiddleware(new \App\Middleware\AdminMiddleware());
```

### 6. Event System

```php
$ws = service('websocket');
$dispatcher = $ws->getEventDispatcher();

// Event dinleme
$dispatcher->on('message', function($data) {
    log_message('info', 'Message event: ' . json_encode($data));
});

$dispatcher->on('authenticated', function($data) {
    $userId = $data['userId'];
    log_message('info', "User {$userId} authenticated");
});

// Custom event tetikleme
$dispatcher->dispatch('user_action', [
    'userId' => 123,
    'action' => 'login',
    'timestamp' => time()
]);
```

### 7. Rate Limiting Özelleştirme

```php
// Yapılandırmada
public $rateLimitPerMinute = 30; // Dakikada 30 mesaj

// Özel rate limiter
$rateLimiter = new \Yakupeyisan\CodeIgniterWebSocket\Core\RateLimiter(10);
$ws = service('websocket');
$ws->addMiddleware(new \Yakupeyisan\CodeIgniterWebSocket\Middleware\RateLimitMiddleware($rateLimiter));
```

### 8. Mesaj Geçmişi

```php
// Yapılandırmada aktif et
public $messageHistoryEnabled = true;
public $messageHistoryLimit = 100;

// Geçmişi alma
$ws = service('websocket');
$handler = $ws->getMessageHandler();
$history = $handler->getHistory('room-123', 50); // Son 50 mesaj
```

### 9. Client Tarafı - React Örneği

```javascript
import { useEffect, useState } from 'react';

function WebSocketClient({ userId }) {
    const [ws, setWs] = useState(null);
    const [messages, setMessages] = useState([]);
    const [token, setToken] = useState(null);

    useEffect(() => {
        const websocket = new WebSocket('ws://localhost:8282');
        
        websocket.onopen = () => {
            websocket.send(JSON.stringify({
                type: 'socket',
                user_id: userId
            }));
        };
        
        websocket.onmessage = (e) => {
            const data = JSON.parse(e.data);
            
            if (data.type === 'token') {
                setToken(data.data.token);
            } else if (data.type === 'chat') {
                setMessages(prev => [...prev, data]);
            }
        };
        
        setWs(websocket);
        
        return () => {
            websocket.close();
        };
    }, [userId]);
    
    const sendMessage = (message) => {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type: 'chat',
                message: message,
                token: token
            }));
        }
    };
    
    return (
        <div>
            {messages.map((msg, i) => (
                <div key={i}>{msg.message}</div>
            ))}
            <input onKeyPress={(e) => {
                if (e.key === 'Enter') {
                    sendMessage(e.target.value);
                    e.target.value = '';
                }
            }} />
        </div>
    );
}
```

### 10. Veritabanı Entegrasyonu

```php
// Mesajları veritabanına kaydetme
$ws->setCallback('roomchat', function($data, $connection) use ($db) {
    $db->table('messages')->insert([
        'room' => $data->room,
        'user_id' => $data->user_id,
        'message' => $data->message,
        'created_at' => date('Y-m-d H:i:s')
    ]);
});
```

## İleri Seviye Örnekler

### Real-time Notification System

```php
// Bildirim gönderme
function sendNotification($userId, $message) {
    $ws = service('websocket');
    $connection = $ws->getConnectionManager()->getByUserId($userId);
    
    if ($connection) {
        $ws->sendToClient($connection->resourceId, [
            'type' => 'notification',
            'message' => $message,
            'timestamp' => time()
        ]);
    }
}
```

### Multi-room Support

```php
// Kullanıcıyı birden fazla odaya ekleme
$ws = service('websocket');
$roomManager = $ws->getRoomManager();

$rooms = ['room-1', 'room-2', 'room-3'];
foreach ($rooms as $room) {
    $roomManager->join($connection, $room);
}
```

### Connection Monitoring

```php
// Bağlantı istatistikleri
$ws = service('websocket');
$connectionManager = $ws->getConnectionManager();

$totalConnections = $connectionManager->count();
$onlineUsers = $ws->getPresenceManager()->getOnlineCount();

echo "Total connections: {$totalConnections}\n";
echo "Online users: {$onlineUsers}\n";
```

