<?php

namespace Yakupeyisan\CodeIgniterWebSocket\Core;

/**
 * Event Dispatcher
 * 
 * Dispatches WebSocket events
 * 
 * @package Yakupeyisan\CodeIgniterWebSocket\Core
 */
class EventDispatcher
{
    /**
     * Event listeners
     * 
     * @var array
     */
    protected $listeners = [];

    /**
     * Register event listener
     * 
     * @param string $event
     * @param callable $listener
     * @return void
     */
    public function on(string $event, callable $listener): void
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        
        $this->listeners[$event][] = $listener;
    }

    /**
     * Dispatch event
     * 
     * @param string $event
     * @param mixed $payload
     * @return void
     */
    public function dispatch(string $event, $payload = null): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }
        
        foreach ($this->listeners[$event] as $listener) {
            call_user_func($listener, $payload);
        }
    }

    /**
     * Remove event listener
     * 
     * @param string $event
     * @param callable|null $listener
     * @return void
     */
    public function off(string $event, ?callable $listener = null): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }
        
        if ($listener === null) {
            unset($this->listeners[$event]);
            return;
        }
        
        $key = array_search($listener, $this->listeners[$event], true);
        if ($key !== false) {
            unset($this->listeners[$event][$key]);
            $this->listeners[$event] = array_values($this->listeners[$event]);
        }
    }

    /**
     * Check if event has listeners
     * 
     * @param string $event
     * @return bool
     */
    public function hasListeners(string $event): bool
    {
        return isset($this->listeners[$event]) && !empty($this->listeners[$event]);
    }

    /**
     * Get all registered events
     * 
     * @return array
     */
    public function getEvents(): array
    {
        return array_keys($this->listeners);
    }
}

