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
     */
    public function setConnection(ConnectionInterface $connection);
}