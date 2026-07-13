<?php

namespace Bdf\Prime\Schema\Constraint;

use Bdf\Prime\Schema\ConstraintInterface;
use Bdf\Prime\Schema\ConstraintSetInterface;

/**
 * Set of constraints
 */
final class ConstraintSet implements ConstraintSetInterface
{
    private static ?ConstraintSet $blank = null;

    /**
     * @var ConstraintInterface[]
     */
    private array $constraints;


    /**
     * ConstraintSet constructor.
     *
     * @param ConstraintInterface[] $constraints
     */
    public function __construct(array $constraints)
    {
        $this->constraints = [];

        foreach ($constraints as $constraint) {
            $this->constraints[strtoupper($constraint->name())] = $constraint;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ConstraintVisitorInterface $visitor): static
    {
        foreach ($this->constraints as $constraint) {
            $constraint->visit($visitor);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->constraints;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): ConstraintInterface
    {
        return $this->constraints[strtoupper($name)];
    }

    /**
     * Get an empty set
     *
     * @return self
     */
    public static function blank(): self
    {
        if (self::$blank === null) {
            self::$blank = new self([]);
        }

        return self::$blank;
    }
}
