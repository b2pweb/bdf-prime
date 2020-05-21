<?php

namespace Bdf\Prime\Schema\Constraint;

use Bdf\Prime\Schema\Util\Name;

/**
 * Basic implementation of ForeignKeyInterface
 */
final class ForeignKey implements ForeignKeyInterface
{
    /**
     * @var string[]
     */
    private $fields;

    /**
     * @var string
     */
    private $table;

    /**
     * @var string[]
     */
    private $referred;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $onDelete;

    /**
     * @var string
     */
    private $onUpdate;

    /**
     * @var string
     */
    private $match;


    /**
     * ForeignKey constructor.
     *
     * @param string[] $fields
     * @param string $table
     * @param string[] $referred
     * @param string $name
     * @param string $onDelete
     * @param string $onUpdate
     * @param string $match
     */
    public function __construct(array $fields, $table, array $referred, $name = null, $onDelete = self::MODE_RESTRICT, $onUpdate = self::MODE_RESTRICT, $match = self::MATCH_SIMPLE)
    {
        $this->fields   = $fields;
        $this->table    = $table;
        $this->referred = $referred;
        $this->name     = $name;
        $this->onDelete = $onDelete;
        $this->onUpdate = $onUpdate;
        $this->match    = $match;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        if (!$this->name) {
            $this->name = Name::generate('fk', array_merge([$this->table], $this->fields()));
        }

        return $this->name;
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
        return $this->fields;
    }

    /**
     * {@inheritdoc}
     */
    public function match()
    {
        return $this->match;
    }

    /**
     * {@inheritdoc}
     */
    public function table()
    {
        return $this->table;
    }

    /**
     * {@inheritdoc}
     */
    public function referred()
    {
        return $this->referred;
    }

    /**
     * {@inheritdoc}
     */
    public function onDelete()
    {
        return $this->onDelete;
    }

    /**
     * {@inheritdoc}
     */
    public function onUpdate()
    {
        return $this->onUpdate;
    }
}
