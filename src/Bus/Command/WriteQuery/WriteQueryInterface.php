<?php

namespace Bdf\Prime\Bus\Command\WriteQuery;

use Bdf\Prime\Bus\Command\CommandMetadataExtractorInterface;
use Bdf\Prime\Bus\Command\WriteMethod\WriteMethodInterface;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Base attribute type for prime command using a raw query for perform the write operations.
 *
 * Unlike {@see WriteMethodInterface}, those write are not performed on entities, so events will not be triggered,
 * and it must be placed on the class level instead of the property level.
 *
 * Using multiple write query on the same command is undefined behavior.
 */
interface WriteQueryInterface
{
    /**
     * Get the entity class name, used to resolve the repository
     * which will be used for create the query.
     *
     * @return class-string
     */
    public function entity(): string;

    /**
     * Build and execute the write query
     *
     * @param RepositoryInterface $repository The repository, resolved from {@see WriteQueryInterface::entity()}.
     * @param object $command The command to execute
     * @param CommandMetadataExtractorInterface $extractor Extractor for command metadata
     */
    public function execute(RepositoryInterface $repository, object $command, CommandMetadataExtractorInterface $extractor): void;
}
