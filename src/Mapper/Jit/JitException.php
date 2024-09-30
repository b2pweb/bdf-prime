<?php

namespace Bdf\Prime\Mapper\Jit;

use RuntimeException;
use Throwable;

use function sprintf;

/**
 * Base exception of the JIT compiler
 * Should be thrown only if errors are enabled
 */
class JitException extends RuntimeException
{
    public function __construct(string $mapper, string $method, string $message = '', ?Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Error while JIT compiling method %s::%s(): %s', $mapper, $method, $message),
            0,
            $previous
        );
    }
}
