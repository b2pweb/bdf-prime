<?php

namespace Bdf\Prime\Exception;

use RuntimeException;
use Throwable;

/**
 * Exception for hydrators
 */
class HydratorException extends RuntimeException implements PrimeException
{
    /**
     * @var string
     */
    private $entityClass;

    /**
     * HydratorException constructor.
     *
     * @param string $entityClass
     * @param string $message
     * @param Throwable|null $previous
     */
    public function __construct(string $entityClass, string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($entityClass . ' : ' . $message, 0, $previous);

        $this->entityClass = $entityClass;
    }

    /**
     * Get the entity class name
     *
     * @return string
     */
    public function entityClass(): string
    {
        return $this->entityClass;
    }
}
