<?php

namespace Bdf\Prime\Connection;

/**
 * ConnectionAwareInterface
 * 
 * @package Bdf\Prime\Connection
 */
interface ConnectionAwareInterface
{
    /**
     * Set the connection
     * 
     * @param ConnectionInterface $connection
     *
     * @return void
     */
    public function setConnection(ConnectionInterface $connection): void;
}
