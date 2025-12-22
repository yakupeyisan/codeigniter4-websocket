<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Core;

use Yakupeyisan\CodeIgniterWebSocket\Contracts\ConnectionManagerInterface;
use Ratchet\ConnectionInterface;

/**
 * Connection Manager
 * 
 * Manages WebSocket connections
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Core
 */
class ConnectionManager implements ConnectionManagerInterface
{
    /**
     * Active connections
     * 
     * @var \SplObjectStorage
     */
    protected $connections;

    /**
     * Connection data storage
     * 
     * @var array
     */
    protected $connectionData = [];

    /**
     * User ID to connection mapping
     * 
     * @var array
     */
    protected $userIdMap = [];

    /**
     * Resource ID to connection mapping
     * 
     * @var array
     */
    protected $resourceIdMap = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->connections = new \SplObjectStorage();
    }

    /**
     * Add connection
     * 
     * @param ConnectionInterface $connection
     * @param array $data
     * @return void
     */
    public function add(ConnectionInterface $connection, array $data = []): void
    {
        $this->connections->attach($connection);
        
        $resourceId = $connection->resourceId;
        $this->resourceIdMap[$resourceId] = $connection;
        
        $defaultData = [
            'resourceId' => $resourceId,
            'userId' => null,
            'ip' => $connection->remoteAddress ?? null,
            'connectedAt' => time(),
            'lastActivity' => time(),
            'rooms' => [],
            'metadata' => []
        ];
        
        $this->connectionData[$resourceId] = array_merge($defaultData, $data);
        
        if (!empty($data['userId'])) {
            $this->userIdMap[$data['userId']] = $resourceId;
        }
    }

    /**
     * Remove connection
     * 
     * @param ConnectionInterface $connection
     * @return void
     */
    public function remove(ConnectionInterface $connection): void
    {
        $resourceId = $connection->resourceId;
        
        if (isset($this->connectionData[$resourceId])) {
            $userId = $this->connectionData[$resourceId]['userId'];
            
            if ($userId && isset($this->userIdMap[$userId])) {
                unset($this->userIdMap[$userId]);
            }
            
            unset($this->connectionData[$resourceId]);
        }
        
        unset($this->resourceIdMap[$resourceId]);
        $this->connections->detach($connection);
    }

    /**
     * Get connection by resource ID
     * 
     * @param int $resourceId
     * @return ConnectionInterface|null
     */
    public function get(int $resourceId): ?ConnectionInterface
    {
        return $this->resourceIdMap[$resourceId] ?? null;
    }

    /**
     * Get connection by user ID
     * 
     * @param int $userId
     * @return ConnectionInterface|null
     */
    public function getByUserId(int $userId): ?ConnectionInterface
    {
        if (!isset($this->userIdMap[$userId])) {
            return null;
        }
        
        $resourceId = $this->userIdMap[$userId];
        return $this->get($resourceId);
    }

    /**
     * Get all connections
     * 
     * @return \SplObjectStorage
     */
    public function getAll(): \SplObjectStorage
    {
        return $this->connections;
    }

    /**
     * Get connection count
     * 
     * @return int
     */
    public function count(): int
    {
        return $this->connections->count();
    }

    /**
     * Check if connection exists
     * 
     * @param ConnectionInterface $connection
     * @return bool
     */
    public function has(ConnectionInterface $connection): bool
    {
        return $this->connections->contains($connection);
    }

    /**
     * Get connection data
     * 
     * @param ConnectionInterface $connection
     * @return array|null
     */
    public function getData(ConnectionInterface $connection): ?array
    {
        $resourceId = $connection->resourceId;
        return $this->connectionData[$resourceId] ?? null;
    }

    /**
     * Set connection data
     * 
     * @param ConnectionInterface $connection
     * @param array $data
     * @return void
     */
    public function setData(ConnectionInterface $connection, array $data): void
    {
        $resourceId = $connection->resourceId;
        
        if (!isset($this->connectionData[$resourceId])) {
            $this->add($connection, $data);
            return;
        }
        
        $this->connectionData[$resourceId] = array_merge(
            $this->connectionData[$resourceId],
            $data
        );
        
        // Update user ID mapping if changed
        if (isset($data['userId'])) {
            $oldUserId = $this->connectionData[$resourceId]['userId'] ?? null;
            if ($oldUserId && $oldUserId !== $data['userId']) {
                unset($this->userIdMap[$oldUserId]);
            }
            $this->userIdMap[$data['userId']] = $resourceId;
        }
    }

    /**
     * Update last activity
     * 
     * @param ConnectionInterface $connection
     * @return void
     */
    public function updateActivity(ConnectionInterface $connection): void
    {
        $resourceId = $connection->resourceId;
        if (isset($this->connectionData[$resourceId])) {
            $this->connectionData[$resourceId]['lastActivity'] = time();
        }
    }

    /**
     * Get connections by IP
     * 
     * @param string $ip
     * @return array
     */
    public function getByIp(string $ip): array
    {
        $connections = [];
        
        foreach ($this->connectionData as $resourceId => $data) {
            if (($data['ip'] ?? null) === $ip) {
                $connection = $this->get($resourceId);
                if ($connection) {
                    $connections[] = $connection;
                }
            }
        }
        
        return $connections;
    }
}

