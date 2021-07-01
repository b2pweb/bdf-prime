<?php

namespace Bdf\Prime;

use PHPUnit\Framework\TestCase;

/**
 * Tests for complex side effects on prime
 */
class SideEffectsTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->configurePrime();
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    /**
     * Locking database steps :
     * - Create SQLite DB file
     * - Insert data with relation
     * - Request to this data : prepare query and keep the statements
     * - Close connection (the connection will be kept into prepared statements)
     * - Perform a schema migration (will be performed with the new connection)
     * - Perform TWO delete operations into a transaction with save previous saved statements (deleteAll will perform the transaction and the 2 deletions)
     * - Locked database will be raised on the second delete
     *
     * http://192.168.0.187:3000/issues/16688
     *
     * @doesNotPerformAssertions
     */
    public function test_with_sqlite_file_connection_close_with_prepared_statements_stored_into_repository_should_free_statements_to_avoid_lock_database()
    {
        $this->prime()->connections()->removeConnection('test');

        // Create a SQLite file connection
        $file = tempnam(sys_get_temp_dir(), 'sqlite_');

        $this->prime()->connections()->declareConnection('test', [
            'adapter' => 'sqlite',
            'path' => $file
        ]);

        $connection = $this->prime()->connection('test');

        TestEntity::repository()->schema()->migrate();
        TestEmbeddedEntity::repository()->schema()->migrate();

        // Create the data set
        for ($i = 1; $i <= 2; ++$i) {
            (new TestEntity([
                'id'      => $i,
                'name'    => 'e'.$i,
                'foreign' => new TestEmbeddedEntity(['id' => $i])
            ]))->insert();

            (new TestEmbeddedEntity(['id' => $i, 'name' => 'f'.$i]))->insert();
        }

        // Perform a read query : a statement will be prepared and stored into repository
        TestEntity::with('foreign')->get(1)->deleteAll('foreign');

        // Close connection
        $connection->close();

        // Perform a schema change on new connection
        Faction::repository()->schema()->migrate();

        // Perform the two delete operations into a transaction
        TestEntity::with('foreign')->get(2)->deleteAll('foreign');

        unlink($file);
    }
}
