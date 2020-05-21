<?php

namespace Bdf\Prime\Schema\Adapter\Doctrine;

use Bdf\Prime\Schema\Constraint\ConstraintVisitorInterface;
use Bdf\Prime\Schema\Constraint\ForeignKeyInterface;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;

/**
 * Adapt doctrine foreign key to prime foreign key
 */
final class DoctrineForeignKey implements ForeignKeyInterface
{
    /**
     * @var ForeignKeyConstraint
     */
    private $fk;


    /**
     * DoctrineForeignKey constructor.
     *
     * @param ForeignKeyConstraint $fk
     */
    public function __construct(ForeignKeyConstraint $fk)
    {
        $this->fk = $fk;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return $this->fk->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function visit(ConstraintVisitorInterface $visitor)
    {
        $visitor->onForeignKey($this);
    }

    /**
     * {@inheritdoc}
     */
    public function fields()
    {
        return $this->fk->getLocalColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function match()
    {
        if (!$this->fk->hasOption('match')) {
            return self::MATCH_SIMPLE;
        }

        return $this->fk->getOption('match');
    }

    /**
     * {@inheritdoc}
     */
    public function table()
    {
        return $this->fk->getForeignTableName();
    }

    /**
     * {@inheritdoc}
     */
    public function referred()
    {
        return $this->fk->getForeignColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function onDelete()
    {
        if (!$this->fk->hasOption('onDelete')) {
            return self::MODE_RESTRICT;
        }

        return $this->fk->getOption('onDelete');
    }

    /**
     * {@inheritdoc}
     */
    public function onUpdate()
    {
        if (!$this->fk->hasOption('onUpdate')) {
            return self::MODE_RESTRICT;
        }

        return $this->fk->getOption('onUpdate');
    }
}
