# CodeIgniter 4 WebSocket Library

Gelişmiş, modüler ve özellik açısından zengin WebSocket kütüphanesi CodeIgniter 4 için. Ratchet WebSocket teknolojisini kullanarak güçlü gerçek zamanlı uygulamalar geliştirmenize olanak sağlar.

## Özellikler

### Temel Özellikler
- ✅ Ratchet WebSocket desteği
- ✅ JWT tabanlı kimlik doğrulama
- ✅ Oda/Kanal (Room/Channel) yönetimi
- ✅ Gerçek zamanlı mesajlaşma
- ✅ Broadcast desteği
- ✅ Callback sistemi

### Gelişmiş Özellikler
- 🔥 **Modüler Mimari**: Kolayca genişletilebilir yapı
- 🔥 **Middleware Desteği**: Özel middleware'ler ekleyebilirsiniz
- 🔥 **Event System**: Olay tabanlı mimari
- 🔥 **Presence System**: Kullanıcı online/offline durumu takibi
- 🔥 **Rate Limiting**: Mesaj gönderme hızı sınırlama
- 🔥 **Connection Management**: Gelişmiş bağlantı yönetimi
- 🔥 **Room Management**: Oda bazlı mesajlaşma
- 🔥 **Message History**: Mesaj geçmişi saklama (opsiyonel)
- 🔥 **IP-based Connection Limiting**: IP bazlı bağlantı sınırlama
- 🔥 **SSL/TLS Desteği**: Güvenli bağlantılar
- 🔥 **CORS Yapılandırması**: Cross-origin desteği
- 🔥 **Logging**: Detaylı loglama sistemi
- 🔥 **Metrics**: Performans metrikleri (opsiyonel)

## Kurulum

### Composer ile Kurulum

```bash
composer require Yakupeyisan/codeigniter4-websocket
```

### Yapılandırma Dosyasını Yayınlama

```bash
php spark websocket:publish
```

Bu komut size şunları soracak:
- Config dosyası yayınlama
- Controller yayınlama
- Views yayınlama

## Hızlı Başlangıç

### 1. Yapılandırma

`app/Config/Websocket.php` dosyasını düzenleyin:

```php
<?php

namespace Config;

use Yakupeyisan\CodeIgniterWebSocket\Config\Websocket as BaseWebsocket;

class Websocket extends BaseWebsocket
{
    public $host = '0.0.0.0';
    public $port = 8282;
    public $auth = true;
    public $jwtKey = 'your-secret-key-here';
    public $debug = true;
}
```

### 2. Controller Oluşturma

`app/Controllers/WebSocketController.php`:

```php
<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class WebSocketController extends Controller
{
    public function start()
    {
        $ws = service('websocket');
        $ws->setCallback('auth', [$this, '_auth']);
        $ws->setCallback('event', [$this, '_event']);
        $ws->start();
    }

    public function _auth($data)
    {
        // Kimlik doğrulama mantığınızı buraya yazın
        // Örnek: Veritabanından kullanıcı kontrolü
        if (!empty($data->user_id)) {
            return (int)$data->user_id;
        }
        return false;
    }

    public function _event($data)
    {
        // Olay işleme mantığınızı buraya yazın
        log_message('info', 'WebSocket event: ' . json_encode($data));
    }
}
```

### 3. Route Ekleme

`app/Config/Routes.php`:

```php
$routes->get('websocket/start', 'WebSocketController::start');
$routes->get('websocket/client/(:num)', 'WebSocketController::client/$1');
```

### 4. WebSocket Sunucusunu Başlatma

```bash
php spark websocket:start
```

Veya özel host/port ile:

```bash
php spark websocket:start --host=127.0.0.1 --port=8282 --debug
```

### 5. Client Tarafı

HTML/JavaScript örneği:

```html
<!DOCTYPE html>
<html>
<head>
    <title>WebSocket Client</title>
</head>
<body>
    <div id="messages"></div>
    <input type="text" id="message" placeholder="Mesajınız...">
    <button onclick="sendMessage()">Gönder</button>

    <script>
        var ws = new WebSocket('ws://localhost:8282');
        var token = null;

        ws.onopen = function() {
            // Kimlik doğrulama
            ws.send(JSON.stringify({
                type: 'socket',
                user_id: 1
            }));
        };

        ws.onmessage = function(e) {
            var data = JSON.parse(e.data);
            
            if (data.type === 'token') {
                token = data.data.token;
            } else if (data.type === 'chat') {
                document.getElementById('messages').innerHTML += 
                    '<div>' + data.message + '</div>';
            }
        };

        function sendMessage() {
            var message = document.getElementById('message').value;
            
            ws.send(JSON.stringify({
                type: 'chat',
                message: message,
                token: token
            }));
        }
    </script>
</body>
</html>
```

## Kullanım Örnekleri

### Oda (Room) Kullanımı

```php
// Server tarafı
$ws = service('websocket');

// Odaya mesaj gönderme
$ws->sendToRoom('room-123', [
    'type' => 'message',
    'message' => 'Merhaba!',
    'userId' => 1
]);
```

```javascript
// Client tarafı
// Odaya katılma
ws.send(JSON.stringify({
    type: 'roomjoin',
    room: 'room-123',
    token: token
}));

// Odaya mesaj gönderme
ws.send(JSON.stringify({
    type: 'roomchat',
    room: 'room-123',
    message: 'Merhaba!',
    token: token
}));
```

### Broadcast

```php
// Tüm bağlantılara mesaj gönderme
websocket_broadcast([
    'type' => 'notification',
    'message' => 'Sistem bakımı başlıyor'
]);

// Belirli bağlantıları hariç tutma
websocket_broadcast([
    'type' => 'notification',
    'message' => 'Yeni mesaj'
], [123, 456]); // Bu ID'ler hariç
```

### Presence System

```php
$ws = service('websocket');
$presence = $ws->getPresenceManager();

// Online kullanıcıları alma
$onlineUsers = $presence->getOnlineUsers();

// Kullanıcı online mı kontrol etme
if ($presence->isOnline($userId)) {
    // Kullanıcı online
}
```

### Event System

```php
$ws = service('websocket');
$dispatcher = $ws->getEventDispatcher();

// Event dinleme
$dispatcher->on('message', function($data) {
    log_message('info', 'Message received: ' . json_encode($data));
});

// Event tetikleme
$dispatcher->dispatch('custom_event', ['data' => 'value']);
```

### Middleware Kullanımı

```php
// Özel middleware oluşturma
namespace App\Middleware;

use Yakupeyisan\CodeIgniterWebSocket\Middleware\MiddlewareInterface;
use Ratchet\ConnectionInterface;

class CustomMiddleware implements MiddlewareInterface
{
    public function handle(ConnectionInterface $connection, object $message, callable $next)
    {
        // Ön işlemler
        if (/* some condition */) {
            return false; // Mesajı engelle
        }
        
        // Sonraki middleware'e geç
        return $next($connection, $message);
    }
}

// Middleware ekleme
$ws = service('websocket');
$ws->addMiddleware(new \App\Middleware\CustomMiddleware());
```

## Helper Fonksiyonlar

```php
// WebSocket servisini alma
$ws = websocket_service();

// Broadcast
websocket_broadcast($message, $exclude = []);

// Belirli client'a gönderme
websocket_send_to_client($clientId, $message);

// Odaya gönderme
websocket_send_to_room($room, $message, $exclude = []);

// Bağlantı sayısı
$count = websocket_connection_count();

// Oda bağlantı sayısı
$roomCount = websocket_room_count('room-123');
```

## Yapılandırma Seçenekleri

```php
public $host = '0.0.0.0';                    // Sunucu host
public $port = 8282;                         // Sunucu port
public $ssl = false;                         // SSL/TLS aktif
public $sslCertPath = null;                 // SSL sertifika yolu
public $sslKeyPath = null;                  // SSL key yolu
public $auth = false;                        // Kimlik doğrulama aktif
public $jwtKey = 'secret-key';              // JWT secret key
public $tokenTimeout = 60;                  // Token timeout (dakika)
public $debug = false;                      // Debug modu
public $timer = false;                      // Timer aktif
public $interval = 1;                       // Timer interval (saniye)
public $maxConnectionsPerIp = 10;           // IP başına max bağlantı
public $rateLimitPerMinute = 60;             // Dakikada max mesaj
public $presenceEnabled = true;            // Presence sistemi
public $presenceHeartbeatInterval = 30;    // Heartbeat interval
public $messageHistoryEnabled = false;     // Mesaj geçmişi
public $messageHistoryLimit = 100;          // Geçmiş limiti
public $corsEnabled = true;                // CORS aktif
public $corsOrigins = ['*'];                // CORS origins
public $loggingEnabled = true;             // Logging aktif
public $logLevel = 'info';                 // Log seviyesi
```

## Callback'ler

Desteklenen callback türleri:

- `auth` - Kimlik doğrulama
- `event` - Mesaj olayları
- `close` - Bağlantı kapanma
- `citimer` - Timer olayları
- `roomjoin` - Odaya katılma
- `roomleave` - Odadan ayrılma
- `roomchat` - Oda mesajlaşması
- `connect` - Bağlantı açılma
- `disconnect` - Bağlantı kapanma
- `error` - Hata olayları
- `presence` - Presence güncellemeleri
- `typing` - Yazma göstergesi
- `read` - Okundu bilgisi

## Güvenlik

- JWT tabanlı kimlik doğrulama
- Rate limiting
- IP bazlı bağlantı sınırlama
- SSL/TLS desteği
- CORS yapılandırması

## Performans

- Verimli bağlantı yönetimi
- Oda bazlı mesajlaşma
- Rate limiting ile aşırı yüklenmeyi önleme
- Mesaj geçmişi için opsiyonel depolama

## Lisans

MIT License

## Katkıda Bulunma

Katkılarınızı bekliyoruz! Lütfen pull request göndermeden önce:

1. Fork edin
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Commit edin (`git commit -m 'Add amazing feature'`)
4. Push edin (`git push origin feature/amazing-feature`)
5. Pull Request açın

## Destek

Sorularınız için issue açabilir veya dokümantasyonu inceleyebilirsiniz.

## Teşekkürler

- [Ratchet](http://socketo.me/) - WebSocket kütüphanesi
- [CodeIgniter 4](https://codeigniter.com/) - PHP framework

