<?php

namespace Bdf\Prime\Behaviors;

use Bdf\Prime\Events;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\Types\TypeInterface;

/**
 * Softdeleteable
 *
 * The soft deleteable behavior allows you to “soft delete” objects,
 * filtering them at SELECT time by marking them as with a timestamp,
 * but not explicitly removing them from the database.
 *
 * @package Bdf\Prime\Behaviors
 */
class SoftDeleteable implements BehaviorInterface
{
    /**
     * The deleted at info.
     * Contains keys 'name' and 'alias'
     *
     * @var array
     */
    protected $deleted;

    /**
     * The property type
     *
     * @var string
     */
    protected $type;

    /**
     * Softdeleteable constructor.
     *
     * Set deletedAt infos.
     * Could be a string: will be considered as the property name
     * Could be an array: should contains ['name', 'alias']
     *
     * @param bool|string|array $deleted
     * @param string            $type
     */
    public function __construct($deleted = true, $type = TypeInterface::DATETIME)
    {
        $this->type = $type;
        $this->deleted = $this->getFieldInfos($deleted);
    }

    /**
     * Get the field infos from option
     *
     * @param mixed $field
     *
     * @return null|array
     */
    private function getFieldInfos($field)
    {
        if ($field === true) {
            return ['name' => 'deletedAt', 'alias' => 'deleted_at'];
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
    public function changeSchema(FieldBuilder $builder)
    {
        if (!isset($builder[$this->deleted['name']])) {
            $builder->add($this->deleted['name'], $this->type)->nillable();

            if (isset($this->deleted['alias'])) {
                $builder->alias($this->deleted['alias']);
            }
        }
    }

    /**
     * Before delete
     *
     * We stop the before delete event and update the deleted at date.
     *
     * @param object                 $entity
     * @param RepositoryInterface    $repository
     */
    public function beforeDelete($entity, $repository)
    {
        // If the current delete is without constraints, we skip the soft delete management
        if ($repository->isWithoutConstraints()) {
            return true;
        }

        $now = $this->createDate($this->deleted['name'], $repository);

        $repository->hydrateOne($entity, $this->deleted['name'], $now);
        $count = $repository->update($entity, [$this->deleted['name']]);

        $repository->notify(Events::POST_DELETE, [$entity, $repository, $count]);

        // Returns false to skip the delete management
        return false;
    }

    /**
     * Get the field infos from option
     *
     * @return string $name
     * @return RepositoryInterface $repository
     */
    private function createDate($name, $repository)
    {
        if ($this->type === TypeInterface::BIGINT) {
            return time();
        }

        $className = $repository->mapper()->info()->property($name)->phpType();
        return new $className;
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe($notifier)
    {
        $notifier->deleting([$this, 'beforeDelete']);
    }
    
    /**
     * {@inheritdoc}
     */
    public function constraints()
    {
        return [$this->deleted['name'] => null];
    }
}
