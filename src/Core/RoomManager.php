<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Core;

use Yakupeyisan\CodeIgniterWebSocket\Contracts\RoomManagerInterface;
use Ratchet\ConnectionInterface;

/**
 * Room Manager
 * 
 * Manages WebSocket rooms/channels
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Core
 */
class RoomManager implements RoomManagerInterface
{
    /**
     * Room storage: room => [resourceId => ConnectionInterface]
     * 
     * @var array
     */
    protected $rooms = [];

    /**
     * Connection rooms: resourceId => [room1, room2, ...]
     * 
     * @var array
     */
    protected $connectionRooms = [];

    /**
     * Room data storage
     * 
     * @var array
     */
    protected $roomData = [];

    /**
     * Join room
     * 
     * @param ConnectionInterface $connection
     * @param string $room
     * @param array $data
     * @return bool
     */
    public function join(ConnectionInterface $connection, string $room, array $data = []): bool
    {
        $resourceId = $connection->resourceId;
        
        // Initialize room if not exists
        if (!isset($this->rooms[$room])) {
            $this->rooms[$room] = [];
            $this->roomData[$room] = [
                'createdAt' => time(),
                'metadata' => []
            ];
        }
        
        // Add connection to room if not already in
        if (!isset($this->rooms[$room][$resourceId])) {
            $this->rooms[$room][$resourceId] = $connection;
            
            if (!isset($this->connectionRooms[$resourceId])) {
                $this->connectionRooms[$resourceId] = [];
            }
            
            if (!in_array($room, $this->connectionRooms[$resourceId])) {
                $this->connectionRooms[$resourceId][] = $room;
            }
            
            // Merge room metadata
            if (!empty($data)) {
                if (!isset($this->roomData[$room]['connections'][$resourceId])) {
                    $this->roomData[$room]['connections'][$resourceId] = [];
                }
                $this->roomData[$room]['connections'][$resourceId] = array_merge(
                    $this->roomData[$room]['connections'][$resourceId],
                    $data
                );
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Leave room
     * 
     * @param ConnectionInterface $connection
     * @param string $room
     * @return bool
     */
    public function leave(ConnectionInterface $connection, string $room): bool
    {
        $resourceId = $connection->resourceId;
        
        if (isset($this->rooms[$room][$resourceId])) {
            unset($this->rooms[$room][$resourceId]);
            
            // Remove room from connection's room list
            if (isset($this->connectionRooms[$resourceId])) {
                $key = array_search($room, $this->connectionRooms[$resourceId]);
                if ($key !== false) {
                    unset($this->connectionRooms[$resourceId][$key]);
                    $this->connectionRooms[$resourceId] = array_values($this->connectionRooms[$resourceId]);
                }
            }
            
            // Remove connection data from room
            if (isset($this->roomData[$room]['connections'][$resourceId])) {
                unset($this->roomData[$room]['connections'][$resourceId]);
            }
            
            // Clean up empty room
            if (empty($this->rooms[$room])) {
                unset($this->rooms[$room]);
                unset($this->roomData[$room]);
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Get room connections
     * 
     * @param string $room
     * @return array
     */
    public function getConnections(string $room): array
    {
        if (!isset($this->rooms[$room])) {
            return [];
        }
        
        return array_values($this->rooms[$room]);
    }

    /**
     * Get connection rooms
     * 
     * @param ConnectionInterface $connection
     * @return array
     */
    public function getRooms(ConnectionInterface $connection): array
    {
        $resourceId = $connection->resourceId;
        return $this->connectionRooms[$resourceId] ?? [];
    }

    /**
     * Check if connection is in room
     * 
     * @param ConnectionInterface $connection
     * @param string $room
     * @return bool
     */
    public function isInRoom(ConnectionInterface $connection, string $room): bool
    {
        $resourceId = $connection->resourceId;
        return isset($this->rooms[$room][$resourceId]);
    }

    /**
     * Get all rooms
     * 
     * @return array
     */
    public function getAllRooms(): array
    {
        return array_keys($this->rooms);
    }

    /**
     * Get room count
     * 
     * @param string $room
     * @return int
     */
    public function getRoomCount(string $room): int
    {
        return isset($this->rooms[$room]) ? count($this->rooms[$room]) : 0;
    }

    /**
     * Remove connection from all rooms
     * 
     * @param ConnectionInterface $connection
     * @return void
     */
    public function removeFromAllRooms(ConnectionInterface $connection): void
    {
        $resourceId = $connection->resourceId;
        
        if (!isset($this->connectionRooms[$resourceId])) {
            return;
        }
        
        $rooms = $this->connectionRooms[$resourceId];
        
        foreach ($rooms as $room) {
            $this->leave($connection, $room);
        }
    }

    /**
     * Get room data
     * 
     * @param string $room
     * @return array|null
     */
    public function getRoomData(string $room): ?array
    {
        return $this->roomData[$room] ?? null;
    }

    /**
     * Set room metadata
     * 
     * @param string $room
     * @param array $metadata
     * @return void
     */
    public function setRoomMetadata(string $room, array $metadata): void
    {
        if (!isset($this->roomData[$room])) {
            $this->roomData[$room] = [
                'createdAt' => time(),
                'metadata' => []
            ];
        }
        
        $this->roomData[$room]['metadata'] = array_merge(
            $this->roomData[$room]['metadata'],
            $metadata
        );
    }
}

