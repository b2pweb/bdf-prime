<?php

namespace Bdf\Prime\Entity\Hydrator\Generator;

use Bdf\Prime\Contact;
use Bdf\Prime\Document;
use Bdf\Prime\Location;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\TestEmbeddedEntity;
use Bdf\Prime\TestEntity;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class AccessorResolverTest extends TestCase
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
    public function test_get_current_class()
    {
        $resolver = new AccessorResolver(
            $accessor = new ClassAccessor(TestEntity::class, ClassAccessor::SCOPE_INHERIT),
            new AttributesResolver(TestEntity::repository()->mapper(), Prime::service()),
            new CodeGenerator()
        );

        $this->assertSame($accessor, $resolver->get(TestEntity::class));
    }

    /**
     *
     */
    public function test_get_relation_class()
    {
        $resolver = new AccessorResolver(
            new ClassAccessor(TestEntity::class, ClassAccessor::SCOPE_INHERIT),
            new AttributesResolver(TestEntity::repository()->mapper(), Prime::service()),
            new CodeGenerator()
        );

        $accessor = $resolver->get(TestEmbeddedEntity::class);

        $this->assertEquals(TestEmbeddedEntity::class, $accessor->className());
        $this->assertSame($accessor, $resolver->get(TestEmbeddedEntity::class));
    }

    /**
     *
     */
    public function test_embedded()
    {
        $resolver = new AccessorResolver(
            $baseAccessor = new ClassAccessor(Document::class, ClassAccessor::SCOPE_INHERIT),
            $attributes = new AttributesResolver(Document::repository()->mapper(), Prime::service()),
            $code = new CodeGenerator()
        );

        $accessor = $resolver->embedded($attributes->embedded('contact.location'));

        $this->assertEquals(new EmbeddedAccessor(
            $code,
            $attributes->embedded('contact.location'),
            [$resolver->get(Location::class)],
            $resolver->embedded($attributes->embedded('contact'))
        ), $accessor);

        $this->assertEquals(
            new EmbeddedAccessor(
                $code,
                $attributes->embedded('contact'),
                [$resolver->get(Contact::class)],
                $baseAccessor
            ),
            $resolver->embedded($attributes->embedded('contact'))
        );
    }
}
