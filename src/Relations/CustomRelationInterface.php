<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Base type for define custom relations
 *
 * @template L as object
 * @template R as object
 *
 * @extends RelationInterface<L, R>
 */
interface CustomRelationInterface extends RelationInterface
{
    /**
     * Create the relation defined by user
     *
     * @param RepositoryInterface $repository The local (owner) repository
     * @param string $relationName The relation name (i.e. RelationBuilder::on())
     * @param array $relationMeta The relation options
     *
     * @return static
     */
    public static function make(RepositoryInterface $repository, string $relationName, array $relationMeta): RelationInterface;
}
