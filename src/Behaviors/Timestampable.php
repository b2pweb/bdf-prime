<?php

namespace Bdf\Prime\Behaviors;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Repository\RepositoryEventsSubscriberInterface;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\Types\TypeInterface;

/**
 * Timestampable
 *
 * The timestampable behavior allows you to keep track of the date of creation and last update of your model objects.
 *
 * @template E as object
 * @extends Behavior<E>
 */
final class Timestampable extends Behavior
{
    /**
     * The created at info.
     * Contains keys 'name' and 'alias'
     *
     * @var array
     */
    private $createdAt;

    /**
     * The updated at info.
     * Contains keys 'name' and 'alias'
     *
     * @var array
     */
    private $updatedAt;

    /**
     * The property type
     *
     * @var string
     */
    private $type;

    /**
     * Timestampable constructor.
     *
     * Set createdAt and updatedAt infos.
     * Could be a string: will be considered as the property name
     * Could be an array: should contains ['name', 'alias']
     *
     * Set to 'null|false' to deactivate the field management.
     *
     * @param bool|string|array $createdAt
     * @param bool|string|array $updatedAt
     * @param string            $type
     */
    public function __construct($createdAt = true, $updatedAt = true, $type = TypeInterface::DATETIME)
    {
        $this->type = $type;

        $this->createdAt = $this->getFieldInfos($createdAt, [
            'name'  => 'createdAt',
            'alias' => 'created_at',
        ]);

        $this->updatedAt = $this->getFieldInfos($updatedAt, [
            'name'  => 'updatedAt',
            'alias' => 'updated_at',
        ]);
    }

    /**
     * Get the field infos from option
     *
     * @param bool|string|array{0:string,1:string} $field
     * @param array $default
     *
     * @return null|array
     */
    private function getFieldInfos($field, array $default): ?array
    {
        if ($field === true) {
            return $default;
        }

        if (!$field) {
            return null;
        }

        if (is_string($field)) {
            return ['name'  => $field];
        }

        return [
            'name'  => $field[0],
            'alias' => $field[1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function changeSchema(FieldBuilder $builder): void
    {
        if ($this->createdAt !== null && !isset($builder[$this->createdAt['name']])) {
            $builder->add($this->createdAt['name'], $this->type)->nillable();

            if (isset($this->createdAt['alias'])) {
                $builder->alias($this->createdAt['alias']);
            }
        }

        if ($this->updatedAt !== null && !isset($builder[$this->updatedAt['name']])) {
            $builder->add($this->updatedAt['name'], $this->type)->nillable();

            if (isset($this->updatedAt['alias'])) {
                $builder->alias($this->updatedAt['alias']);
            }
        }
    }

    /**
     * Before insert
     *
     * we set the new date created on the entity
     *
     * @param E $entity
     * @param RepositoryInterface<E> $repository
     *
     * @return void
     */
    public function beforeInsert($entity, RepositoryInterface $repository): void
    {
        $now = $this->createDate($this->createdAt['name'], $repository);
        $repository->mapper()->hydrateOne($entity, $this->createdAt['name'], $now);
    }

    /**
     * Before update
     *
     * we set the new date updated on entity
     *
     * @param E $entity
     * @param RepositoryInterface<E> $repository
     * @param null|\ArrayObject $attributes
     *
     * @return void
     */
    public function beforeUpdate($entity, RepositoryInterface $repository, $attributes): void
    {
        if ($attributes !== null) {
            $attributes->append($this->updatedAt['name']);
        }

        $now = $this->createDate($this->updatedAt['name'], $repository);
        $repository->mapper()->hydrateOne($entity, $this->updatedAt['name'], $now);
    }

    /**
     * Get the field infos from option
     *
     * @param string $name
     * @param RepositoryInterface<E> $repository
     *
     * @return int|\DateTimeInterface
     */
    private function createDate(string $name, RepositoryInterface $repository)
    {
        if ($this->type === TypeInterface::BIGINT) {
            return time();
        }

        /** @psalm-suppress UndefinedInterfaceMethod */
        $className = $repository->mapper()->info()->property($name)->phpType();
        return new $className();
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(RepositoryEventsSubscriberInterface $notifier): void
    {
        if ($this->createdAt !== null) {
            $notifier->inserting([$this, 'beforeInsert']);
        }

        if ($this->updatedAt !== null) {
            $notifier->updating([$this, 'beforeUpdate']);
        }
    }
}
