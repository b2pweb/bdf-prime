<?php

namespace Bdf\Prime\Entity\Hydrator\Exception;

use LogicException;
use Throwable;

/**
 * Exception raised when the entity hydrator cannot be generated
 */
class HydratorGenerationException extends LogicException implements HydratorException
{
    /**
     * @var class-string
     */
    private $entityClass;

    /**
     * HydratorException constructor.
     *
     * @param class-string $entityClass
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
     * @return class-string
     */
    public function entityClass(): string
    {
        return $this->entityClass;
    }
}
