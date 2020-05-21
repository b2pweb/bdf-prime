<?php

namespace Bdf\Prime\Exception;

/**
 * Exception raised on type errors
 */
class TypeException extends \LogicException
{
    /**
     * @var string
     */
    private $type;


    /**
     * TypeException constructor.
     *
     * @param string $type
     * @param int $message
     * @param int $code
     * @param null $previous
     */
    public function __construct($type, $message, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->type = $type;
    }

    /**
     * @return string
     */
    public function type()
    {
        return $this->type;
    }
}
