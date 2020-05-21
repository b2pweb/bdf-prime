<?php

namespace Bdf\Prime\Schema\Constraint;

use Bdf\Prime\Schema\Util\Name;

/**
 * Basic implementation of CheckInterface constraint
 */
final class Check implements CheckInterface
{
    /**
     * @var mixed
     */
    private $expression;

    /**
     * @var string
     */
    private $name;


    /**
     * Check constructor.
     *
     * @param mixed $expression
     * @param string $name
     */
    public function __construct($expression, $name = null)
    {
        $this->expression = $expression;
        $this->name       = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        if (!$this->name) {
            $this->name = Name::serialized('chk', $this->expression);
        }

        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function expression()
    {
        return $this->expression;
    }

    /**
     * {@inheritdoc}
     */
    public function visit(ConstraintVisitorInterface $visitor)
    {
        $visitor->onCheck($this);
    }
}
