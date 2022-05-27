<?php

namespace Bdf\Prime\Schema\Transformer\Doctrine;

use Bdf\Prime\Schema\Constraint\CheckInterface;
use Bdf\Prime\Schema\Constraint\ConstraintVisitorInterface;
use Bdf\Prime\Schema\Constraint\ForeignKeyInterface;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;

/**
 * Transform prime column to doctrine column
 */
final class FkExtractor implements ConstraintVisitorInterface
{
    /**
     * @var ForeignKeyConstraint[]
     */
    private $fk = [];

    /**
     * {@inheritdoc}
     */
    public function onForeignKey(ForeignKeyInterface $foreignKey)
    {
        $this->fk[] = new ForeignKeyConstraint(
            $foreignKey->fields(),
            $foreignKey->table(),
            $foreignKey->referred(),
            $foreignKey->name()
        );
    }

    /**
     * Get the doctrine foreign key constraints
     *
     * @return ForeignKeyConstraint[]
     */
    public function all()
    {
        return $this->fk;
    }

    /**
     * {@inheritdoc}
     */
    public function onCheck(CheckInterface $check)
    {
    }
}
