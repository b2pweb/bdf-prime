<?php

namespace Bdf\Prime\Connection\Event;

/**
 * Listener for closed connection
 */
interface ConnectionClosedListenerInterface
{
    public const EVENT_NAME = 'onConnectionClosed';

    /**
     * The connection is closed
     *
     * @return void
     */
    public function onConnectionClosed();
}
