<?php

namespace Bdf\Prime\Sharding;

use Bdf\Prime\Customer;
use Bdf\Prime\Document;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\Location;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ShardingEmbededDistributionKeyTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->configurePrime();

        $this->prime()->connections()->removeConnection('test');
        $this->prime()->connections()->declareConnection('test', [
            'adapter'           => 'sqlite',
            'memory'            => true,
            'dbname'            => 'TEST',
            'distributionKey'   => 'contact.location.city',
            'shards'    => [
                'shard1' => ['dbname'  => 'TEST_SHARD1'],
                'shard2' => ['dbname'  => 'TEST_SHARD2'],
            ]
        ]);

        $this->primeStart();
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack->declareEntity([User::class, Document::class]);
    }

    /**
     *
     */
    public function test_multiple_embedded()
    {
        $this->pack()->nonPersist(
            Document::entity([
                'id' => 1,
                'customerId'   => '10',
                'uploaderType' => 'user',
                'uploaderId'   => '1',
                'contact' => (object)[
                    'name'     => 'Holmes',
                    'location' => new Location([
                        'address' => '221b Baker Street',
                        'city'    => 'London',
                    ])
                ],
            ])
        );

        $document = Document::where('contact.location.city', 'London')->first();

        $this->assertInstanceOf('Bdf\Prime\Contact', $document->contact);
        $this->assertInstanceOf('Bdf\Prime\Location', $document->contact->location);

        $this->assertEquals('Holmes', $document->contact->name);
        $this->assertEquals('221b Baker Street', $document->contact->location->address);
        $this->assertEquals('London', $document->contact->location->city);
    }
}
