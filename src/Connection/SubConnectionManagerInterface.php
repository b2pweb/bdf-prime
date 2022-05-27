<?php

namespace Bdf\Prime\Connection;

/**
 * Base type for connection which handle sub connection, like sharding or master slave
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
    public function getConnection(string $name): ConnectionInterface;
}
