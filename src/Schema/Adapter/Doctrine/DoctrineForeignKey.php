<?php

namespace Bdf\Prime\Schema\Adapter\Doctrine;

use Bdf\Prime\Schema\Constraint\ConstraintVisitorInterface;
use Bdf\Prime\Schema\Constraint\ForeignKeyInterface;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\ForeignKeyConstraint\MatchType;
use Doctrine\DBAL\Schema\ForeignKeyConstraint\ReferentialAction;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;

use function array_map;

/**
 * Adapt doctrine foreign key to prime foreign key
 */
final readonly class DoctrineForeignKey implements ForeignKeyInterface
{
    public function __construct(
        private ForeignKeyConstraint $fk,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->fk->getObjectName()->toString();
    }

    /**
     * {@inheritdoc}
     */
    public function visit(ConstraintVisitorInterface $visitor): void
    {
        $visitor->onForeignKey($this);
    }

    /**
     * {@inheritdoc}
     */
    public function fields(): array
    {
        return array_map(static fn (UnqualifiedName $name) => $name->toString(), $this->fk->getReferencingColumnNames());
    }

    /**
     * {@inheritdoc}
     */
    public function match(): string
    {
        return match ($this->fk->getMatchType()) {
            MatchType::PARTIAL => self::MATCH_PARTIAL,
            MatchType::FULL => self::MATCH_FULL,
            default => self::MATCH_SIMPLE,
        };
    }

    /**
     * {@inheritdoc}
     */
    public function table(): string
    {
        return $this->fk->getReferencedTableName()->toString();
    }

    /**
     * {@inheritdoc}
     */
    public function referred(): array
    {
        return array_map(static fn (UnqualifiedName $name) => $name->toString(), $this->fk->getReferencedColumnNames());
    }

    /**
     * {@inheritdoc}
     */
    public function onDelete(): string
    {
        $action = $this->fk->getOnDeleteAction();

        // The default on prime 1 and 2 is RESTRICT, but doctrine dbal v4 use NO ACTION.
        // Change it to RESTRICT for compatiblity purposes
        return $action === ReferentialAction::NO_ACTION ? self::MODE_RESTRICT : $action->toSQL();
    }

    /**
     * {@inheritdoc}
     */
    public function onUpdate(): string
    {
        $action = $this->fk->getOnUpdateAction();

        // The default on prime 1 and 2 is RESTRICT, but doctrine dbal v4 use NO ACTION.
        // Change it to RESTRICT for compatiblity purposes
        return $action === ReferentialAction::NO_ACTION ? self::MODE_RESTRICT : $action->toSQL();
    }
}
