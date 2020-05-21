<?php

namespace Bdf\Prime\Schema\Constraint;

use Bdf\Prime\Schema\ConstraintInterface;
use Bdf\Prime\Schema\ConstraintSetInterface;

/**
 * Set of constraints
 */
final class ConstraintSet implements ConstraintSetInterface
{
    /**
     * @var ConstraintSet
     */
    private static $blank;

    /**
     * @var ConstraintInterface[]
     */
    private $constraints;


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
    public function apply(ConstraintVisitorInterface $visitor)
    {
        foreach ($this->constraints as $constraint) {
            $constraint->visit($visitor);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->constraints;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        return $this->constraints[strtoupper($name)];
    }

    /**
     * Get an empty set
     *
     * @return self
     */
    public static function blank()
    {
        if (self::$blank === null) {
            self::$blank = new self([]);
        }

        return self::$blank;
    }
}
