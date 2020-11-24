<?php

namespace Bdf\Prime\Exception;

use Bdf\Prime\Types\TypeInterface;
use LogicException;
use Throwable;

/**
 * Exception raised on type errors
 */
class TypeException extends LogicException implements PrimeException
{
    /**
     * @var string
     */
    private $type;


    /**
     * TypeException constructor.
     *
     * @param string $type
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $type, string $message, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->type = $type;
    }

    /**
     * Get the type name
     *
     * @see TypeInterface::name()
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }
}
