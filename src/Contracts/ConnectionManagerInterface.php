<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Contracts;

use Ratchet\ConnectionInterface;

/**
 * Connection Manager Interface
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Contracts
 */
interface ConnectionManagerInterface
{
    /**
     * Add connection
     * 
     * @param ConnectionInterface $connection
     * @param array $data
     * @return void
     */
    public function add(ConnectionInterface $connection, array $data = []): void;

    /**
     * Remove connection
     * 
     * @param ConnectionInterface $connection
     * @return void
     */
    public function remove(ConnectionInterface $connection): void;

    /**
     * Get connection by resource ID
     * 
     * @param int $resourceId
     * @return ConnectionInterface|null
     */
    public function get(int $resourceId): ?ConnectionInterface;

    /**
     * Get connection by user ID
     * 
     * @param int $userId
     * @return ConnectionInterface|null
     */
    public function getByUserId(int $userId): ?ConnectionInterface;

    /**
     * Get all connections
     * 
     * @return \SplObjectStorage
     */
    public function getAll(): \SplObjectStorage;

    /**
     * Get connection count
     * 
     * @return int
     */
    public function count(): int;

    /**
     * Check if connection exists
     * 
     * @param ConnectionInterface $connection
     * @return bool
     */
    public function has(ConnectionInterface $connection): bool;

    /**
     * Get connection data
     * 
     * @param ConnectionInterface $connection
     * @return array|null
     */
    public function getData(ConnectionInterface $connection): ?array;

    /**
     * Set connection data
     * 
     * @param ConnectionInterface $connection
     * @param array $data
     * @return void
     */
    public function setData(ConnectionInterface $connection, array $data): void;
}

