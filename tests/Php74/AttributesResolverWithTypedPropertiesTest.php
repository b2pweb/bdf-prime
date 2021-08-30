<?php

namespace Php74;

use Bdf\Prime\Entity\Hydrator\Generator\AttributesResolver;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

/**
 * Class AttributesResolverWithTypedPropertiesTest
 */
class AttributesResolverWithTypedPropertiesTest extends TestCase
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
    public function test_attribute_typed_nullable_with_default()
    {
        $attribute = $this->resolver->attribute('id');

        $this->assertTrue($attribute->isTyped());
        $this->assertTrue($attribute->isNullable());
        $this->assertTrue($attribute->isInitializedByDefault());
    }

    /**
     *
     */
    public function test_attribute_typed_nullable_without_default()
    {
        $attribute = $this->resolver->attribute('uploaderId');

        $this->assertTrue($attribute->isTyped());
        $this->assertTrue($attribute->isNullable());
        $this->assertFalse($attribute->isInitializedByDefault());
    }

    /**
     *
     */
    public function test_attribute_typed_not_nullable()
    {
        $attribute = $this->resolver->attribute('customerId');

        $this->assertTrue($attribute->isTyped());
        $this->assertFalse($attribute->isNullable());
        $this->assertFalse($attribute->isInitializedByDefault());
    }

    /**
     *
     */
    public function test_embedded_typed_nullable_with_default()
    {
        $attribute = $this->resolver->attribute('contact.name');

        $this->assertTrue($attribute->isTyped());
        $this->assertTrue($attribute->isNullable());
        $this->assertTrue($attribute->isInitializedByDefault());
    }
}
