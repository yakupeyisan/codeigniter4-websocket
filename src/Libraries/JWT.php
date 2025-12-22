<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Libraries;

/**
 * JSON Web Token implementation
 * 
 * Based on: http://tools.ietf.org/html/draft-ietf-oauth-json-web-token-06
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Libraries
 */
class JWT
{
    /**
     * Decodes a JWT string into a PHP object.
     *
     * @param string $jwt The JWT
     * @param string|null $key The secret key
     * @param bool $verify Don't skip verification process
     *
     * @return object|false The JWT's payload as a PHP object or false on failure
     */
    public static function decode(string $jwt, ?string $key = null, bool $verify = true)
    {
        $tks = explode('.', $jwt);
        
        if (count($tks) != 3) {
            return false;
        }
        
        list($headb64, $bodyb64, $cryptob64) = $tks;
        
        $header = self::jsonDecode(self::urlsafeB64Decode($headb64));
        if ($header === null) {
            return false;
        }
        
        $payload = self::jsonDecode(self::urlsafeB64Decode($bodyb64));
        if ($payload === null) {
            return false;
        }
        
        $sig = self::urlsafeB64Decode($cryptob64);
        
        if ($verify) {
            if (empty($header->alg)) {
                return false;
            }
            
            if ($sig != self::sign("$headb64.$bodyb64", $key, $header->alg)) {
                return false;
            }
        }
        
        return $payload;
    }

    /**
     * Converts and signs a PHP object or array into a JWT string.
     *
     * @param object|array $payload PHP object or array
     * @param string $key The secret key
     * @param string $algo The signing algorithm (HS256, HS384, HS512)
     *
     * @return string A signed JWT
     */
    public static function encode($payload, string $key, string $algo = 'HS256'): string
    {
        $header = ['typ' => 'JWT', 'alg' => $algo];
        
        $segments = [];
        $segments[] = self::urlsafeB64Encode(self::jsonEncode($header));
        $segments[] = self::urlsafeB64Encode(self::jsonEncode($payload));
        
        $signing_input = implode('.', $segments);
        $signature = self::sign($signing_input, $key, $algo);
        $segments[] = self::urlsafeB64Encode($signature);
        
        return implode('.', $segments);
    }

    /**
     * Sign a string with a given key and algorithm.
     *
     * @param string $msg The message to sign
     * @param string $key The secret key
     * @param string $method The signing algorithm
     *
     * @return string An encrypted message
     */
    public static function sign(string $msg, string $key, string $method = 'HS256'): string
    {
        $methods = [
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512',
        ];
        
        if (empty($methods[$method])) {
            throw new \DomainException('Algorithm not supported');
        }
        
        return hash_hmac($methods[$method], $msg, $key, true);
    }

    /**
     * Decode a JSON string into a PHP object.
     *
     * @param string $input JSON string
     *
     * @return object|null Object representation of JSON string
     */
    public static function jsonDecode(string $input)
    {
        $obj = json_decode($input);
        
        if (function_exists('json_last_error') && $errno = json_last_error()) {
            self::handleJsonError($errno);
        } elseif ($obj === null && $input !== 'null') {
            return null;
        }
        
        return $obj;
    }

    /**
     * Encode a PHP object into a JSON string.
     *
     * @param object|array $input A PHP object or array
     *
     * @return string JSON representation of the PHP object or array
     */
    public static function jsonEncode($input): string
    {
        $json = json_encode($input);
        
        if (function_exists('json_last_error') && $errno = json_last_error()) {
            self::handleJsonError($errno);
        } elseif ($json === 'null' && $input !== null) {
            throw new \DomainException('Null result with non-null input');
        }
        
        return $json;
    }

    /**
     * Decode a string with URL-safe Base64.
     *
     * @param string $input A Base64 encoded string
     *
     * @return string A decoded string
     */
    public static function urlsafeB64Decode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $input The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     */
    public static function urlsafeB64Encode(string $input): string
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * Helper method to create a JSON error.
     *
     * @param int $errno An error number from json_last_error()
     *
     * @return void
     */
    private static function handleJsonError(int $errno): void
    {
        $messages = [
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON'
        ];
        
        throw new \DomainException(
            isset($messages[$errno])
                ? $messages[$errno]
                : 'Unknown JSON error: ' . $errno
        );
    }
}

