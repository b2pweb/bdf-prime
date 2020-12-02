<?php

namespace Bdf\Prime\Query\Compiler\AliasResolver;

use Bdf\Prime\Customer;
use Bdf\Prime\Document;
use Bdf\Prime\Location;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\Preprocessor\OrmPreprocessor;
use Bdf\Prime\Query\Query;
use Bdf\Prime\Query\QueryRepositoryExtension;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\Right;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class AliasResolverTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var AliasResolver
     */
    protected $resolver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $compiler;

    /**
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * @var Query
     */
    protected $query;

    /**
     *
     */
    public function setUp(): void
    {
        $this->primeStart();

        $this->compiler = $this->createMock(CompilerInterface::class);
        $this->repository = Document::repository();
        $this->query = new Query($this->prime()->connection('test'), new OrmPreprocessor($this->repository));
        $this->query->setExtension(new QueryRepositoryExtension($this->repository));
        $this->resolver = new AliasResolver($this->repository, $this->prime()->connection('test')->platform()->types());
        $this->resolver->setQuery($this->query);
    }

    /**
     * This test can works only with exploreBasicExpression (non recursive)
     */
//    public function test_resolve_incomplete_path()
//    {
//        $this->expectException(\LogicException::class);
//
//        $this->resolver->resolve('customer.location');
//    }

    /**
     *
     */
    public function test_resolve_dbal_expression()
    {
        $this->assertEquals('123.456', $this->resolver->resolve('123.456'));
        $this->assertEquals('123', $this->resolver->resolve('123'));
    }

    /**
     *
     */
    public function test_resolve_attribute()
    {
        $this->assertEquals('t0.id_', $this->resolver->resolve('id'));
        $this->assertEquals('t0.id_', $this->resolver->resolve('$t0.id'));

        $this->assertSame($this->repository->metadata(), $this->resolver->getMetadata('t0'));
    }

    /**
     *
     */
    public function test_resolve_attribute_with_type()
    {
        $type = true;
        $this->resolver->resolve('id', $type);

        $this->assertInstanceOf(SqlStringType::class, $type);
    }

    /**
     *
     */
    public function test_resolve_attribute_relation_detached()
    {
        $this->assertEquals('t1.id_', $this->resolver->resolve('customer.id'));

        $this->assertSame($this->repository->metadata(), $this->resolver->getMetadata('t0'));
        $this->assertSame(Customer::repository()->metadata(), $this->resolver->getMetadata('t1'));
    }

    /**
     *
     */
    public function test_resolve_attribute_relation_complex()
    {
        $this->assertEquals('t2.city_', $this->resolver->resolve('customer.location.city'));

        $this->assertSame($this->repository->metadata(), $this->resolver->getMetadata('t0'));
        $this->assertSame(Customer::repository()->metadata(), $this->resolver->getMetadata('t1'));
        $this->assertSame(Location::repository()->metadata(), $this->resolver->getMetadata('t2'));
    }

    /**
     *
     */
    public function test_resolve_attribute_polymorphic()
    {
        $this->assertEquals('t1.name_', $this->resolver->resolve('uploader#user.name'));

        $this->assertSame($this->repository->metadata(), $this->resolver->getMetadata('t0'));
        $this->assertSame(User::repository()->metadata(), $this->resolver->getMetadata('t1'));
    }

    /**
     *
     */
    public function test_resolve_attribute_use_alias()
    {
        $this->resolver->registerMetadata(Customer::repository(), 'my_alias');

        $this->assertEquals('my_alias.name_', $this->resolver->resolve('my_alias>name'));

        // Resolve must register the root repository if it's not set manually
        $this->assertSame($this->repository->metadata(), $this->resolver->getMetadata('t0'));
    }

    /**
     *
     */
    public function test_resolve_attribute_forced_context()
    {
        $this->assertEquals('t2.city_', $this->resolver->resolve('customer.location>city'));

        $this->assertSame($this->repository->metadata(), $this->resolver->getMetadata('t0'));
        $this->assertSame(Customer::repository()->metadata(), $this->resolver->getMetadata('t1'));
        $this->assertSame(Location::repository()->metadata(), $this->resolver->getMetadata('t2'));
    }

    /**
     *
     */
    public function test_resolve_twice_do_not_redeclare()
    {
        $this->assertEquals('t2.city_', $this->resolver->resolve('customer.location.city'));
        $this->assertEquals('t2.city_', $this->resolver->resolve('customer.location.city'));

        $this->assertSame($this->repository->metadata(), $this->resolver->getMetadata('t0'));
        $this->assertSame(Customer::repository()->metadata(), $this->resolver->getMetadata('t1'));
        $this->assertSame(Location::repository()->metadata(), $this->resolver->getMetadata('t2'));
    }

    /**
     *
     */
    public function test_resolve_twice_use_alias()
    {
        $this->assertEquals('t2.city_', $this->resolver->resolve('customer.location.city'));
        $this->assertEquals('t2.city_', $this->resolver->resolve('t2.city'));

        $this->assertSame($this->repository->metadata(), $this->resolver->getMetadata('t0'));
        $this->assertSame(Customer::repository()->metadata(), $this->resolver->getMetadata('t1'));
        $this->assertSame(Location::repository()->metadata(), $this->resolver->getMetadata('t2'));
    }

    /**
     *
     */
    public function test_resolve_twice_static_use_alias()
    {
        $this->assertEquals('t2.city_', $this->resolver->resolve('"customer.location">city'));
        $this->assertEquals('t2.city_', $this->resolver->resolve('"customer.location">city'));

        $this->assertSame($this->repository->metadata(), $this->resolver->getMetadata('t0'));
        $this->assertSame(Customer::repository()->metadata(), $this->resolver->getMetadata('t1'));
        $this->assertSame(Location::repository()->metadata(), $this->resolver->getMetadata('t2'));
    }

    /**
     *
     */
    public function test_registerMetadata_generate_alias()
    {
        $this->assertEquals('t0', $this->resolver->registerMetadata($this->repository, null));
        $this->assertEquals('t1', $this->resolver->registerMetadata(Customer::repository(), null));
        $this->assertEquals('t2', $this->resolver->registerMetadata(Location::repository(), null));

        $this->assertSame($this->repository->metadata(), $this->resolver->getMetadata('t0'));
        $this->assertSame(Customer::repository()->metadata(), $this->resolver->getMetadata('t1'));
        $this->assertSame(Location::repository()->metadata(), $this->resolver->getMetadata('t2'));
    }

    /**
     *
     */
    public function test_registerMetadata_with_custom_alias()
    {
        $this->assertEquals('my_alias', $this->resolver->registerMetadata(Customer::repository(), 'my_alias'));
        $this->assertSame(Customer::repository()->metadata(), $this->resolver->getMetadata('my_alias'));
    }

    /**
     * @see https://github.com/b2pweb/bdf-prime/issues/15
     */
    public function test_registerMetadata_with_custom_alias_for_current_repository()
    {
        $this->assertEquals('my_alias', $this->resolver->registerMetadata($this->repository, 'my_alias'));
        $this->assertSame($this->repository->metadata(), $this->resolver->getMetadata('my_alias'));
        $this->assertEquals('my_alias.customer_id', $this->resolver->resolve('customerId'));
        $this->assertNull($this->resolver->getMetadata('t0'));
    }

    /**
     *
     */
    public function test_registerMetadata_with_entity_name()
    {
        $this->assertEquals('my_alias', $this->resolver->registerMetadata(Customer::class, 'my_alias'));
        $this->assertSame(Customer::repository()->metadata(), $this->resolver->getMetadata('my_alias'));
    }

    /**
     *
     */
    public function test_registerMetadata_repository_not_found()
    {
        $this->assertEquals('my_alias', $this->resolver->registerMetadata('???', 'my_alias'));
        $this->assertFalse($this->resolver->hasAlias('my_alias'));
    }

    /**
     *
     */
    public function test_registerMetadata_with_root_repository_table_name()
    {
        $this->assertEquals('my_alias', $this->resolver->registerMetadata('document_', 'my_alias'));
        $this->assertSame(Document::repository()->metadata(), $this->resolver->getMetadata('my_alias'));
    }

    /**
     *
     */
    public function test_registerMetadata_useQuoteIdentifier()
    {
        $this->resolver->registerMetadata(Right::repository(), null);
        $this->assertTrue($this->query->isQuoteIdentifier());
    }

    /**
     *
     */
    public function test_hasAlias()
    {
        $this->assertFalse($this->resolver->hasAlias('my_alias'));
        $this->resolver->registerMetadata(Customer::repository(), 'my_alias');
        $this->assertTrue($this->resolver->hasAlias('my_alias'));
    }

    /**
     *
     */
    public function test_getPathAlias()
    {
        $this->resolver->resolve('customer.location.city');

        $this->assertEquals('t2', $this->resolver->getPathAlias('customer.location'));
        $this->assertEquals('t1', $this->resolver->getPathAlias('customer'));
        $this->assertEquals('t0', $this->resolver->getPathAlias(''));
    }

    /**
     *
     */
    public function test_reset()
    {
        $this->resolver->registerMetadata(Customer::repository(), 'my_alias');
        $this->resolver->registerMetadata($this->repository, null);

        $this->resolver->reset();

        $this->assertFalse($this->resolver->hasAlias('my_alias'));
        $this->assertFalse($this->resolver->hasAlias('t0'));
    }
}