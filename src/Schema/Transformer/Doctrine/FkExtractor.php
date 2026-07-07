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
    private array $fk = [];

    /**
     * {@inheritdoc}
     */
    public function onForeignKey(ForeignKeyInterface $foreignKey): void
    {
        $this->fk[] = ForeignKeyConstraint::editor()
            ->setUnquotedName($foreignKey->name())
            ->setUnquotedReferencingColumnNames(...$foreignKey->fields())
            ->setUnquotedReferencedTableName($foreignKey->table())
            ->setUnquotedReferencedColumnNames(...$foreignKey->referred())
            ->create()
        ;
    }

    /**
     * Get the doctrine foreign key constraints
     *
     * @return ForeignKeyConstraint[]
     */
    public function all(): array
    {
        return $this->fk;
    }

    /**
     * {@inheritdoc}
     */
    public function onCheck(CheckInterface $check): void
    {
    }
}
