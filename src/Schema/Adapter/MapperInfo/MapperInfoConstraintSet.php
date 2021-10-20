<?php

namespace Bdf\Prime\Schema\Adapter\MapperInfo;

use Bdf\Prime\Mapper\Info\MapperInfo;
use Bdf\Prime\Schema\Adapter\MapperInfo\Resolver\MapperInfoResolverInterface;
use Bdf\Prime\Schema\Constraint\ConstraintSet;
use Bdf\Prime\Schema\Constraint\ConstraintVisitorInterface;
use Bdf\Prime\Schema\ConstraintInterface;
use Bdf\Prime\Schema\ConstraintSetInterface;

/**
 * ConstraintSet object using MapperInfo for resolve constraints
 */
final class MapperInfoConstraintSet implements ConstraintSetInterface
{
    /**
     * @var MapperInfo
     */
    private $info;

    /**
     * @var MapperInfoResolverInterface[]
     */
    private $resolvers;

    /**
     * @var ConstraintSet|null
     */
    private $constraints;


    /**
     * MapperInfoConstraintSet constructor.
     *
     * @param MapperInfo $info
     * @param MapperInfoResolverInterface[] $resolvers
     */
    public function __construct(MapperInfo $info, array $resolvers)
    {
        $this->info      = $info;
        $this->resolvers = $resolvers;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ConstraintVisitorInterface $visitor)
    {
        $this->loadConstraints()->apply($visitor);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->loadConstraints()->all();
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): ConstraintInterface
    {
        return $this->loadConstraints()->get($name);
    }

    private function loadConstraints(): ConstraintSet
    {
        if ($this->constraints !== null) {
            return $this->constraints;
        }

        $constraints = [];

        foreach ($this->info->relations() as $relation) {
            foreach ($this->resolvers as $resolver) {
                $constraints = array_merge(
                    $constraints,
                    $resolver->fromRelation($this->info, $relation)
                );
            }
        }

        return $this->constraints = new ConstraintSet($constraints);
    }
}
