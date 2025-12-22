<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Libraries;

use Yakupeyisan\CodeIgniterWebSocket\Libraries\JWT;

/**
 * Authorization Library
 * 
 * Handles JWT token generation and validation
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Libraries
 */
class Authorization
{
    /**
     * Validate token with timestamp
     * 
     * @param string $token
     * @param string $key
     * @param int $timeout Minutes
     * @return object|false
     */
    public static function validateTimestamp(string $token, string $key, int $timeout = 60)
    {
        $decoded = self::validateToken($token, $key);
        
        if ($decoded === false) {
            return false;
        }
        
        if (isset($decoded->timestamp) && (time() - $decoded->timestamp) > ($timeout * 60)) {
            return false;
        }
        
        return $decoded;
    }

    /**
     * Validate token
     * 
     * @param string $token
     * @param string $key
     * @return object|false
     */
    public static function validateToken(string $token, string $key)
    {
        try {
            return JWT::decode($token, $key);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate token
     * 
     * @param mixed $data
     * @param string $key
     * @return string
     */
    public static function generateToken($data, string $key): string
    {
        $payload = is_array($data) || is_object($data) ? $data : ['data' => $data];
        
        if (!isset($payload->timestamp) && !isset($payload['timestamp'])) {
            if (is_array($payload)) {
                $payload['timestamp'] = time();
            } else {
                $payload->timestamp = time();
            }
        }
        
        return JWT::encode($payload, $key);
    }
}

