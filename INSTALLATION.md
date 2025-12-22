# Kurulum Kılavuzu

## Adım 1: Composer ile Kurulum

```bash
composer require Yakupeyisan/codeigniter4-websocket
```

## Adım 2: Yapılandırma Dosyasını Yayınlama

```bash
php spark websocket:publish
```

Bu komut size şunları soracak:
- **Config file?** (y/n) - Yapılandırma dosyasını yayınla
- **Controller?** (y/n) - Örnek controller'ı yayınla
- **Views?** (y/n) - Örnek view dosyalarını yayınla

## Adım 3: Service Kaydı

`app/Config/Services.php` dosyanıza şu metodu ekleyin:

```php
<?php

namespace Config;

use CodeIgniter\Config\BaseService;

class Services extends BaseService
{
    // ... mevcut servisleriniz ...

    public static function websocket(?\Yakupeyisan\CodeIgniterWebSocket\Config\Websocket $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('websocket', $config);
        }

        if (empty($config)) {
            $config = config('Websocket');
        }

        return new \Yakupeyisan\CodeIgniterWebSocket\Core\WebSocketServer($config);
    }
}
```

## Adım 4: Yapılandırma

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
    public $jwtKey = 'your-secret-key-change-this';
    public $debug = true;
}
```

## Adım 5: Route Ekleme

`app/Config/Routes.php` dosyanıza route ekleyin:

```php
$routes->get('websocket/start', 'WebSocketController::start');
$routes->get('websocket/client/(:num)', 'WebSocketController::client/$1');
```

## Adım 6: WebSocket Sunucusunu Başlatma

```bash
php spark websocket:start
```

Veya özel parametrelerle:

```bash
php spark websocket:start --host=127.0.0.1 --port=8282 --debug
```

## Adım 7: Test Etme

Tarayıcınızda şu URL'yi açın:

```
http://localhost:8080/websocket/client/1
```

Bu sayfa bir WebSocket client örneği içerir.

## Sorun Giderme

### Port Zaten Kullanılıyor

Eğer port zaten kullanılıyorsa, farklı bir port kullanın:

```bash
php spark websocket:start --port=8283
```

### PHP Socket Extension

PHP socket extension'ının yüklü olduğundan emin olun:

```bash
php -m | grep sockets
```

Yüklü değilse:

```bash
# Ubuntu/Debian
sudo apt-get install php-sockets

# CentOS/RHEL
sudo yum install php-sockets
```

### Firewall

Firewall'unuzun WebSocket portunu açtığından emin olun.

### SSL/TLS Kullanımı

SSL/TLS kullanmak için:

```php
// app/Config/Websocket.php
public $ssl = true;
public $sslCertPath = '/path/to/cert.pem';
public $sslKeyPath = '/path/to/key.pem';
```

## Production Ortamı

Production ortamında:

1. `debug` modunu kapatın
2. Güçlü bir `jwtKey` kullanın
3. SSL/TLS kullanın
4. Rate limiting ayarlarını optimize edin
5. Logging seviyesini ayarlayın

```php
public $debug = false;
public $jwtKey = 'very-strong-secret-key-here';
public $ssl = true;
public $rateLimitPerMinute = 30;
public $logLevel = 'warning';
```

