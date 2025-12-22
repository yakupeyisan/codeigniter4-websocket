<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Contracts;

use Ratchet\ConnectionInterface;

/**
 * Room Manager Interface
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Contracts
 */
interface RoomManagerInterface
{
    /**
     * Join room
     * 
     * @param ConnectionInterface $connection
     * @param string $room
     * @param array $data
     * @return bool
     */
    public function join(ConnectionInterface $connection, string $room, array $data = []): bool;

    /**
     * Leave room
     * 
     * @param ConnectionInterface $connection
     * @param string $room
     * @return bool
     */
    public function leave(ConnectionInterface $connection, string $room): bool;

    /**
     * Get room connections
     * 
     * @param string $room
     * @return array
     */
    public function getConnections(string $room): array;

    /**
     * Get connection rooms
     * 
     * @param ConnectionInterface $connection
     * @return array
     */
    public function getRooms(ConnectionInterface $connection): array;

    /**
     * Check if connection is in room
     * 
     * @param ConnectionInterface $connection
     * @param string $room
     * @return bool
     */
    public function isInRoom(ConnectionInterface $connection, string $room): bool;

    /**
     * Get all rooms
     * 
     * @return array
     */
    public function getAllRooms(): array;

    /**
     * Get room count
     * 
     * @param string $room
     * @return int
     */
    public function getRoomCount(string $room): int;

    /**
     * Remove connection from all rooms
     * 
     * @param ConnectionInterface $connection
     * @return void
     */
    public function removeFromAllRooms(ConnectionInterface $connection): void;
}

