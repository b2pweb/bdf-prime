<?php

namespace Bdf\Prime\Exception;

/**
 * Exception for hydrators
 */
class HydratorException extends \RuntimeException
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
     * @param \Exception $previous
     */
    public function __construct($entityClass, $message = '', $previous = null)
    {
        parent::__construct($entityClass . ' : ' . $message, 0, $previous);

        $this->entityClass = $entityClass;
    }

    /**
     * Get the entity class name
     *
     * @return string
     */
    public function entityClass()
    {
        return $this->entityClass;
    }
}