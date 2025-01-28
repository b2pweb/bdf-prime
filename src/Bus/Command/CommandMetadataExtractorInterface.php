<?php

namespace Bdf\Prime\Bus\Command;

use Bdf\Prime\Bus\Command\WriteQuery\WriteQueryInterface;

/**
 * Base type for extract values used by {@see WriteQueryInterface} from command object
 */
interface CommandMetadataExtractorInterface
{
    /**
     * Extract the "where" criteria from the command
     *
     * @param object $command The command to extract
     * @return iterable<string, mixed>
     */
    public function criteria(object $command): iterable;

    /**
     * Extract the values from the command (e.g. SET clause for update)
     *
     * @param object $command The command to extract
     * @return array<string, mixed>
     */
    public function values(object $command): array;
}
