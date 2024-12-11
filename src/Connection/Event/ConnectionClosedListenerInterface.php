<?php

namespace Bdf\Prime\Connection\Event;

use Bdf\Prime\Connection\ConnectionInterface;

/**
 * Listener for closed connection
 *
 * @deprecated Since 2.2. Use {@see ConnectionInterface::addConnectionClosedListener()} instead.
 */
interface ConnectionClosedListenerInterface
{
    public const EVENT_NAME = 'onConnectionClosed';

    /**
     * The connection is closed
     *
     * @return void
     * @deprecated Since 2.2. Use {@see ConnectionInterface::addConnectionClosedListener()} instead.
     */
    public function onConnectionClosed();
}
