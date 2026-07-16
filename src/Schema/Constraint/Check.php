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
    private mixed $expression;

    private ?string $name;


    /**
     * Check constructor.
     *
     * @param mixed $expression
     * @param string|null $name
     */
    public function __construct(mixed $expression, ?string $name = null)
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
    public function expression(): mixed
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
