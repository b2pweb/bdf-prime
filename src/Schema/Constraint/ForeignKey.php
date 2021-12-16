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
     * @var string|null
     */
    private $name;

    /**
     * @var ForeignKeyInterface::MODE_*
     */
    private $onDelete;

    /**
     * @var ForeignKeyInterface::MODE_*
     */
    private $onUpdate;

    /**
     * @var ForeignKeyInterface::MATCH_*
     */
    private $match;


    /**
     * ForeignKey constructor.
     *
     * @param string[] $fields
     * @param string $table
     * @param string[] $referred
     * @param string|null $name
     * @param ForeignKeyInterface::MODE_* $onDelete
     * @param ForeignKeyInterface::MODE_* $onUpdate
     * @param ForeignKeyInterface::MATCH_* $match
     */
    public function __construct(array $fields, string $table, array $referred, ?string $name = null, string $onDelete = self::MODE_RESTRICT, string $onUpdate = self::MODE_RESTRICT, string $match = self::MATCH_SIMPLE)
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
    public function name(): string
    {
        if (!$this->name) {
            $this->name = Name::generate('fk', array_merge([$this->table], $this->fields()));
        }

        return $this->name;
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
        return $this->fields;
    }

    /**
     * {@inheritdoc}
     */
    public function match(): string
    {
        return $this->match;
    }

    /**
     * {@inheritdoc}
     */
    public function table(): string
    {
        return $this->table;
    }

    /**
     * {@inheritdoc}
     */
    public function referred(): array
    {
        return $this->referred;
    }

    /**
     * {@inheritdoc}
     */
    public function onDelete(): string
    {
        return $this->onDelete;
    }

    /**
     * {@inheritdoc}
     */
    public function onUpdate(): string
    {
        return $this->onUpdate;
    }
}
