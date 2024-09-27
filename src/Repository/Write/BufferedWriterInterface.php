<?php

namespace Bdf\Prime\Repository\Write;

use Bdf\Prime\Query\Contract\WriteOperation;

/**
 * Writer for perform bulk operation
 *
 * All write operations are stacked unless `BufferedWriterInterface::flush()` is called
 *
 * <code>
 * // Simple bulk write
 * $writer = ...;
 *
 * $writer->insert($entity1);
 * $writer->update($entity2, ['attributes' => ['foo', 'bar']]);
 * $writer->insert($entity3, ['ignore' => 'true']);
 *
 * $writer->flush(); // 3
 *
 * // Bulk write with flush
 * foreach ($walker as $entity) {
 *     $writer->update($entity);
 *
 *     // Save by chunk of 150 entities
 *     if ($writer->pending() >= 150) {
 *         $writer->flush();
 *     }
 * }
 *
 * $writer->flush(); // Flush remaining operations. Checking pending is not required here.
 * </code>
 *
 * @template E as object
 * @extends WriterInterface<E>
 */
interface BufferedWriterInterface extends WriterInterface
{
    /**
     * Apply all pending operations
     * If there is no pending operation, this method will do nothing and return 0
     *
     * @return int Applied operations / affected rows
     */
    #[WriteOperation]
    public function flush(): int;

    /**
     * Get count of write operations waiting for application
     *
     * @return int
     */
    public function pending(): int;

    /**
     * Clear pending operations
     */
    public function clear(): void;
}
