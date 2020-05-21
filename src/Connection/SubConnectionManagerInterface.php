<?php

namespace Bdf\Prime\Connection;

/**
 * SubConnectionManagerInterface
 *
 * @author admin
 */
interface SubConnectionManagerInterface
{
    /**
     * Get a connection by its name
     * 
     * @param string $name
     * 
     * @return ConnectionInterface
     */
    public function getConnection($name);
}