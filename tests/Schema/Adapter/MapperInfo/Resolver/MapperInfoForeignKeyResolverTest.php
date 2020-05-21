<?php

namespace Bdf\Prime\Schema\Adapter\MapperInfo\Resolver;

use Bdf\Prime\Customer;
use Bdf\Prime\Mapper\Info\MapperInfo;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\Constraint\ForeignKey;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MapperInfoForeignKeyResolverTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var MapperInfoForeignKeyResolver
     */
    private $resolver;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->resolver = new MapperInfoForeignKeyResolver($this->prime());
    }

    /**
     *
     */
    public function test_fromRelation_not_belongsTo()
    {
        $info = new MapperInfo(Customer::repository()->mapper());

        $this->assertSame([], $this->resolver->fromRelation($info, $info->property('packs')));
    }

    /**
     *
     */
    public function test_fromRelation_belongsTo()
    {
        $info = new MapperInfo(Customer::repository()->mapper());

        $this->assertEquals([
            new ForeignKey(['parent_id'], 'customer_', ['id_'], 'parent')
        ], $this->resolver->fromRelation($info, $info->property('parent')));
    }
}
