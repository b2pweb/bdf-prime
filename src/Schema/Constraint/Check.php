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
     * @var string|null
     */
    private $name;


    /**
     * Check constructor.
     *
     * @param mixed $expression
     * @param string|null $name
     */
    public function __construct($expression, ?string $name = null)
    {
        $this->expression = $expression;
        $this->name       = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
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
    public function visit(ConstraintVisitorInterface $visitor): void
    {
        $visitor->onCheck($this);
    }
}
