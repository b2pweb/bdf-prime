<?php

namespace Bdf\Prime\Connection\Extensions;

use Throwable;

/**
 * Trait LostConnection
 */
trait LostConnection
{
    /**
     * The lost connection messages
     *
     * @var array
     */
    private $lostConnectionErrors = [
        'server has gone away',
        'no connection to the server',
        'Lost connection',
        'is dead or not enabled',
        'Error while sending', // E_WARNING on mysqli
        'decryption failed or bad record mac',
        'server closed the connection unexpectedly',
        'SSL connection has been closed unexpectedly',
        'Error writing data to the connection',
        'Resource deadlock avoided',
        'Transaction() on null',
        'child connection forced to terminate due to client_idle_limit',
        'query_wait_timeout',
        'reset by peer',
        'Physical connection is not usable',
        'TCP Provider: Error code 0x68',
        'ORA-03114',
        'Packets out of order. Expected',
        'Adaptive Server connection failed',
        'Communication link failure',
    ];

    /**
     * Determine if the given exception was caused by a lost connection.
     *
     * @param Throwable $exception
     *
     * @return bool
     */
    protected function causedByLostConnection(Throwable $exception)
    {
        $message = $exception->getMessage();

        foreach ($this->lostConnectionErrors as $error) {
            if (strpos($message, $error) !== false) {
                return true;
            }
        }

        return false;
    }
}
