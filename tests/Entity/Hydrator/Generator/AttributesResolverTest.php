<?php

namespace Bdf\Prime\Entity\Hydrator\Generator;

use Bdf\Prime\Admin;
use Bdf\Prime\Document;
use Bdf\Prime\Location;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class AttributesResolverTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var AttributesResolver
     */
    private $resolver;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->configurePrime();

        $this->resolver = new AttributesResolver(
            Document::repository()->mapper(),
            Prime::service()
        );
    }

    /**
     *
     */
    public function test_attributes()
    {
        $this->assertCount(7, $this->resolver->attributes());
        $this->assertContainsOnlyInstancesOf(AttributeInfo::class, $this->resolver->attributes());

        $this->assertArrayHasKey('id', $this->resolver->attributes());
        $this->assertArrayHasKey('customerId', $this->resolver->attributes());
        $this->assertArrayHasKey('uploaderType', $this->resolver->attributes());
        $this->assertArrayHasKey('uploaderId', $this->resolver->attributes());
        $this->assertArrayHasKey('contact.name', $this->resolver->attributes());
        $this->assertArrayHasKey('contact.location.address', $this->resolver->attributes());
        $this->assertArrayHasKey('contact.location.city', $this->resolver->attributes());
    }

    /**
     *
     */
    public function test_attribute_simple()
    {
        $attribute = $this->resolver->attribute('id');

        $this->assertEquals('id', $attribute->name());
        $this->assertEquals('id', $attribute->property());
        $this->assertEquals('id_', $attribute->field());
        $this->assertEquals('bigint', $attribute->type());
        $this->assertFalse($attribute->isEmbedded());
        $this->assertEquals(Document::class, $attribute->containerClassName());
        $this->assertFalse($attribute->isTyped());
        $this->assertTrue($attribute->isNullable());
        $this->assertTrue($attribute->isInitializedByDefault());
        $this->assertEquals('id', $attribute->reflection()->getName());
        $this->assertEquals(Document::class, $attribute->reflection()->class);
    }

    /**
     *
     */
    public function test_attribute_embedded()
    {
        $attribute = $this->resolver->attribute('contact.location.address');

        $this->assertEquals('contact.location.address', $attribute->name());
        $this->assertEquals('address', $attribute->property());
        $this->assertEquals('contact_address', $attribute->field());
        $this->assertEquals('string', $attribute->type());
        $this->assertTrue($attribute->isEmbedded());
        $this->assertEquals(Location::class, $attribute->containerClassName());
        $this->assertFalse($attribute->isTyped());
        $this->assertTrue($attribute->isNullable());
        $this->assertTrue($attribute->isInitializedByDefault());
        $this->assertEquals('address', $attribute->reflection()->getName());
        $this->assertEquals(Location::class, $attribute->reflection()->class);

        $this->assertInstanceOf(EmbeddedInfo::class, $attribute->embedded());
        $this->assertEquals('contact.location', $attribute->embedded()->path());
    }

    /**
     *
     */
    public function test_embeddeds()
    {
        $this->assertCount(3, $this->resolver->embeddeds());
        $this->assertContainsOnlyInstancesOf(EmbeddedInfo::class, $this->resolver->embeddeds());

        $this->assertArrayHasKey('contact', $this->resolver->embeddeds());
        $this->assertArrayHasKey('contact.location', $this->resolver->embeddeds());
        $this->assertArrayHasKey('uploader', $this->resolver->embeddeds());
    }

    /**
     *
     */
    public function test_embedded_not_relation()
    {
        $embedded = $this->resolver->embedded('contact.location');

        $this->assertEquals('contact.location', $embedded->path());
        $this->assertEquals('contact', $embedded->rootAttribute());
        $this->assertFalse($embedded->isRoot());
        $this->assertEquals([Location::class], $embedded->classes());
        $this->assertEquals('location', $embedded->property());
        $this->assertEquals('contact', $embedded->parent()->path());
        $this->assertTrue($embedded->parent()->isRoot());
    }

    /**
     *
     */
    public function test_embedded_relation()
    {
        $embedded = $this->resolver->embedded('uploader');

        $this->assertEquals('uploader', $embedded->path());
        $this->assertEquals('uploader', $embedded->rootAttribute());
        $this->assertTrue($embedded->isRoot());
        $this->assertTrue($embedded->isEntity());
        $this->assertEquals([Admin::class], $embedded->classes()); // @todo Metadata::buildMappedEmbedded() ne supporte pas multiple classes
    }

    /**
     *
     */
    public function test_rootAttributes()
    {
        $this->assertCount(6, $this->resolver->rootAttributes());
        $this->assertContainsOnlyInstancesOf(AttributeInfo::class, $this->resolver->attributes());

        $this->assertArrayHasKey('id', $this->resolver->rootAttributes());
        $this->assertArrayHasKey('customerId', $this->resolver->rootAttributes());
        $this->assertArrayHasKey('uploaderType', $this->resolver->rootAttributes());
        $this->assertArrayHasKey('uploaderId', $this->resolver->rootAttributes());
        $this->assertArrayHasKey('contact', $this->resolver->rootAttributes());
        $this->assertArrayHasKey('uploader', $this->resolver->rootAttributes());

        $this->assertTrue($this->resolver->rootAttributes()['uploader']->isEmbedded());
        $this->assertEquals('uploader', $this->resolver->rootAttributes()['uploader']->embedded()->path());
        $this->assertEquals([Admin::class, User::class], $this->resolver->rootAttributes()['uploader']->embedded()->classes());
    }

    /**
     *
     */
    public function test_rootEmbeddeds()
    {
        $this->assertCount(1, $this->resolver->rootEmbeddeds());

        $this->assertTrue($this->resolver->hasRootEmbedded('uploader'));

        $this->assertEquals('uploader', $this->resolver->rootEmbedded('uploader')->path());
        $this->assertEquals([Admin::class, User::class], $this->resolver->rootEmbedded('uploader')->classes());
        $this->assertTrue($this->resolver->rootEmbedded('uploader')->isRoot());
        $this->assertTrue($this->resolver->rootEmbedded('uploader')->isEntity());
    }
}
