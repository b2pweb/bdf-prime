<?php

namespace Bdf\Prime\Behaviors;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Repository\RepositoryEventsSubscriberInterface;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\Types\TypeInterface;

/**
 * Blameable
 *
 * The blameable behavior automates the update of username or user reference fields on the entities or documents.
 * It works similar to timestampable behavior.
 * It simply inserts the current user id into the fields created_by and updated_by.
 * That way every time a model gets created, updated or deleted, you can see who did it (or who blame for that).
 *
 * @template E as object
 * @extends Behavior<E>
 */
class Blameable extends Behavior
{
    /**
     * The user resolver
     *
     * @var callable
     */
    protected $userResolver;

    /**
     * The created by info.
     * Contains keys 'name' and 'alias'
     *
     * @var array
     */
    protected $createdBy;

    /**
     * The updated by info.
     * Contains keys 'name' and 'alias'
     *
     * @var array
     */
    protected $updatedBy;

    /**
     * The property type
     *
     * @var string
     */
    protected $type;

    /**
     * Blameable constructor.
     *
     * Set createdBy and updatedBy infos.
     * Could be a string: will be considered as the property name
     * Could be an array: should contains ['name', 'alias']
     *
     * Set to 'null|false' to deactivate the field management.
     *
     * @param callable $userResolver  The user name/identifier resolver
     * @param bool|string|array $createdBy
     * @param bool|string|array $updatedBy
     * @param string            $type
     */
    public function __construct(callable $userResolver, $createdBy = true, $updatedBy = true, $type = TypeInterface::STRING)
    {
        $this->userResolver = $userResolver;
        $this->type = $type;

        $this->createdBy = $this->getFieldInfos($createdBy, [
            'name'  => 'createdBy',
            'alias' => 'created_by',
        ]);

        $this->updatedBy = $this->getFieldInfos($updatedBy, [
            'name'  => 'updatedBy',
            'alias' => 'updated_by',
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
        if ($this->createdBy !== null && !isset($builder[$this->createdBy['name']])) {
            $builder->add($this->createdBy['name'], $this->type)->nillable();

            if (isset($this->createdBy['alias'])) {
                $builder->alias($this->createdBy['alias']);
            }
        }

        if ($this->updatedBy !== null && !isset($builder[$this->updatedBy['name']])) {
            $builder->add($this->updatedBy['name'], $this->type)->nillable();

            if (isset($this->updatedBy['alias'])) {
                $builder->alias($this->updatedBy['alias']);
            }
        }
    }

    /**
     * Before insert
     *
     * we set the user that created the entity
     *
     * @param E $entity
     * @param RepositoryInterface<E> $repository
     *
     * @return void
     */
    public function beforeInsert($entity, RepositoryInterface $repository): void
    {
        $resolver = $this->userResolver;
        $repository->mapper()->hydrateOne($entity, $this->createdBy['name'], $resolver());
    }

    /**
     * Before update
     *
     * we set the user that updated the entity
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
            $attributes->append($this->updatedBy['name']);
        }

        $resolver = $this->userResolver;
        $repository->mapper()->hydrateOne($entity, $this->updatedBy['name'], $resolver());
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(RepositoryEventsSubscriberInterface $notifier): void
    {
        if ($this->createdBy !== null) {
            $notifier->inserting([$this, 'beforeInsert']);
        }

        if ($this->updatedBy !== null) {
            $notifier->updating([$this, 'beforeUpdate']);
        }
    }
}
