<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Core;

use Ratchet\ConnectionInterface;

/**
 * Presence Manager
 * 
 * Manages user presence (online/offline status)
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Core
 */
class PresenceManager
{
    /**
     * Presence data: userId => [data]
     * 
     * @var array
     */
    protected $presence = [];

    /**
     * Connection to user ID mapping
     * 
     * @var array
     */
    protected $connectionToUser = [];

    /**
     * Heartbeat intervals
     * 
     * @var array
     */
    protected $heartbeats = [];

    /**
     * Heartbeat timeout in seconds
     * 
     * @var int
     */
    protected $heartbeatTimeout = 60;

    /**
     * Constructor
     * 
     * @param int $heartbeatTimeout
     */
    public function __construct(int $heartbeatTimeout = 60)
    {
        $this->heartbeatTimeout = $heartbeatTimeout;
    }

    /**
     * Set user as online
     * 
     * @param ConnectionInterface $connection
     * @param int $userId
     * @param array $data
     * @return void
     */
    public function setOnline(ConnectionInterface $connection, int $userId, array $data = []): void
    {
        $resourceId = $connection->resourceId;
        
        $this->presence[$userId] = array_merge([
            'userId' => $userId,
            'status' => 'online',
            'lastSeen' => time(),
            'connectedAt' => time(),
            'resourceId' => $resourceId,
            'metadata' => []
        ], $data);
        
        $this->connectionToUser[$resourceId] = $userId;
        $this->updateHeartbeat($resourceId);
    }

    /**
     * Set user as offline
     * 
     * @param ConnectionInterface $connection
     * @return void
     */
    public function setOffline(ConnectionInterface $connection): void
    {
        $resourceId = $connection->resourceId;
        
        if (isset($this->connectionToUser[$resourceId])) {
            $userId = $this->connectionToUser[$resourceId];
            
            if (isset($this->presence[$userId])) {
                $this->presence[$userId]['status'] = 'offline';
                $this->presence[$userId]['lastSeen'] = time();
            }
            
            unset($this->connectionToUser[$resourceId]);
        }
        
        unset($this->heartbeats[$resourceId]);
    }

    /**
     * Update heartbeat
     * 
     * @param int $resourceId
     * @return void
     */
    public function updateHeartbeat(int $resourceId): void
    {
        $this->heartbeats[$resourceId] = time();
        
        if (isset($this->connectionToUser[$resourceId])) {
            $userId = $this->connectionToUser[$resourceId];
            if (isset($this->presence[$userId])) {
                $this->presence[$userId]['lastSeen'] = time();
            }
        }
    }

    /**
     * Get user presence
     * 
     * @param int $userId
     * @return array|null
     */
    public function getPresence(int $userId): ?array
    {
        return $this->presence[$userId] ?? null;
    }

    /**
     * Check if user is online
     * 
     * @param int $userId
     * @return bool
     */
    public function isOnline(int $userId): bool
    {
        if (!isset($this->presence[$userId])) {
            return false;
        }
        
        $presence = $this->presence[$userId];
        
        // Check if heartbeat is still valid
        if (isset($this->connectionToUser[$presence['resourceId']])) {
            $resourceId = $presence['resourceId'];
            if (isset($this->heartbeats[$resourceId])) {
                $lastHeartbeat = $this->heartbeats[$resourceId];
                if ((time() - $lastHeartbeat) > $this->heartbeatTimeout) {
                    return false;
                }
            }
        }
        
        return $presence['status'] === 'online';
    }

    /**
     * Get all online users
     * 
     * @return array
     */
    public function getOnlineUsers(): array
    {
        $online = [];
        
        foreach ($this->presence as $userId => $data) {
            if ($this->isOnline($userId)) {
                $online[$userId] = $data;
            }
        }
        
        return $online;
    }

    /**
     * Get online users count
     * 
     * @return int
     */
    public function getOnlineCount(): int
    {
        return count($this->getOnlineUsers());
    }

    /**
     * Update user metadata
     * 
     * @param int $userId
     * @param array $metadata
     * @return void
     */
    public function updateMetadata(int $userId, array $metadata): void
    {
        if (isset($this->presence[$userId])) {
            $this->presence[$userId]['metadata'] = array_merge(
                $this->presence[$userId]['metadata'],
                $metadata
            );
        }
    }

    /**
     * Clean up stale presence data
     * 
     * @return int Number of cleaned up entries
     */
    public function cleanup(): int
    {
        $cleaned = 0;
        $now = time();
        
        foreach ($this->heartbeats as $resourceId => $lastHeartbeat) {
            if (($now - $lastHeartbeat) > $this->heartbeatTimeout) {
                if (isset($this->connectionToUser[$resourceId])) {
                    $userId = $this->connectionToUser[$resourceId];
                    if (isset($this->presence[$userId])) {
                        $this->presence[$userId]['status'] = 'offline';
                        $this->presence[$userId]['lastSeen'] = $now;
                    }
                }
                
                unset($this->heartbeats[$resourceId]);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
}

