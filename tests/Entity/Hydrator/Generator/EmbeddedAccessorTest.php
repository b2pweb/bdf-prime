<?php

namespace Bdf\Prime\Entity\Hydrator\Generator;

use Bdf\Prime\Document;
use Bdf\Prime\PolymorphContainer;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Street;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class EmbeddedAccessorTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var EmbeddedAccessor
     */
    private $accessor;

    /**
     * @var AccessorResolver
     */
    private $resolver;

    /**
     * @var AttributesResolver
     */
    private $attributes;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->configurePrime();

        $this->resolver = new AccessorResolver(
            new ClassAccessor(Document::class, ClassAccessor::SCOPE_INHERIT),
            $this->attributes = new AttributesResolver(Document::repository()->mapper(), Prime::service()),
            new CodeGenerator()
        );

        $this->accessor = $this->resolver->embedded($this->attributes->embedded('contact.location'));
    }

    /**
     *
     */
    public function test_getEmbedded()
    {
        $code = $this->accessor->getEmbedded('$target');

        $this->assertEquals(<<<'PHP'
{ //START accessor for contact.location
    try {
        $__tmp_0 = $object->contact;
    } catch (\Error $e) {
        // Ignore not initialized property if embedded is instantiated
        $__tmp_0 = null;
    }
    if ($__tmp_0 === null) {
        $__tmp_0 = $this->__instantiator->instantiate('Bdf\Prime\Contact', 1);
        $object->contact = $__tmp_0;
    }
    
    try {
        $target = $__tmp_0->location;
    } catch (\Error $e) {
        // Ignore not initialized property if embedded is instantiated
        $target = null;
    }
    if ($target === null) {
        $target = $this->__instantiator->instantiate('Bdf\Prime\Location', 1);
        $__tmp_0->location = $target;
    }
} //END accessor for contact.location

PHP
, $code
);
    }

    /**
     *
     */
    public function test_getEmbedded_not_instantiate()
    {
        $code = $this->accessor->getEmbedded('$target', false);

        $this->assertEquals(<<<'PHP'
{ //START accessor for contact.location
    try {
        $__tmp_0 = $object->contact;
    } catch (\Error $e) {
        // Ignore not initialized property if embedded is instantiated
        $__tmp_0 = null;
    }
    if ($__tmp_0 === null) {
        $__tmp_0 = $this->__instantiator->instantiate('Bdf\Prime\Contact', 1);
        $object->contact = $__tmp_0;
    }
    
    $target = $__tmp_0->location;
} //END accessor for contact.location

PHP
, $code
);
    }

    /**
     *
     */
    public function test_getter_setter()
    {
        $this->assertEquals('$emb->address', $this->accessor->getter('$emb', 'address'));
        $this->assertEquals('$emb->address = $val', $this->accessor->setter('$emb', 'address', '$val'));
    }


    /**
     *
     */
    public function test_value_object_getter_setter()
    {
        $this->assertEquals('(($__tmpaf6c196ddcc53f08c8b82e2fe9807f52 = $emb->address) instanceof \Bdf\Prime\Street ? $__tmpaf6c196ddcc53f08c8b82e2fe9807f52->value() : $__tmpaf6c196ddcc53f08c8b82e2fe9807f52)', $this->accessor->getter('$emb', 'address', Street::class));
        $this->assertEquals('$emb->address = (($__tmp244f38266c59587d696aec08a771b803 = $val) !== null ? \Bdf\Prime\Street::from($__tmp244f38266c59587d696aec08a771b803) : $__tmp244f38266c59587d696aec08a771b803)', $this->accessor->valueObjectSetter('$emb', 'address', '$val', Street::class));
        $this->assertEquals('$emb->address = (($__tmp244f38266c59587d696aec08a771b803 = $val) !== null && !$__tmp244f38266c59587d696aec08a771b803 instanceof \Bdf\Prime\Street ? \Bdf\Prime\Street::from($__tmp244f38266c59587d696aec08a771b803) : $__tmp244f38266c59587d696aec08a771b803)', $this->accessor->valueObjectSetter('$emb', 'address', '$val', Street::class, true));
    }

    /**
     *
     */
    public function test_getEmbedded_root_embedded()
    {
        $accessor = $this->resolver->embedded($this->attributes->embedded('contact'));
        $code = $accessor->getEmbedded('$target');

        $this->assertEquals(<<<'PHP'
{ //START accessor for contact
    try {
        $target = $object->contact;
    } catch (\Error $e) {
        // Ignore not initialized property if embedded is instantiated
        $target = null;
    }
    if ($target === null) {
        $target = $this->__instantiator->instantiate('Bdf\Prime\Contact', 1);
        $object->contact = $target;
    }
} //END accessor for contact

PHP
            , $code
        );
    }

    /**
     *
     */
    public function test_getEmbedded_polymorph()
    {
        $this->resolver = new AccessorResolver(
            new ClassAccessor(PolymorphContainer::class, ClassAccessor::SCOPE_INHERIT),
            $this->attributes = new AttributesResolver(PolymorphContainer::repository()->mapper(), Prime::service()),
            new CodeGenerator()
        );

        $this->accessor = $this->resolver->embedded($this->attributes->embedded('embedded'));

        $this->assertEquals(<<<'PHP'
{ //START accessor for embedded
    try {
        $target = $object->embedded();
    } catch (\Error $e) {
        // Ignore not initialized property if embedded is instantiated
        $target = null;
    }
} //END accessor for embedded

PHP
            , $this->accessor->getEmbedded('$target')
        );

        $this->assertEquals(<<<'PHP'
{ //START accessor for embedded
    try {
        $target = $object->embedded();
    } catch (\Error $e) {
        // Ignore not initialized property if embedded is instantiated
        $target = null;
    }
    switch ($dbData['sub_type']) {
        case 'A':
            if (!$target instanceof \Bdf\Prime\PolymorphSubA) {
                $target = $this->__instantiator->instantiate('Bdf\Prime\PolymorphSubA', 1);
                $object->setEmbedded($target);
            }
            break;
        case 'B':
            if (!$target instanceof \Bdf\Prime\PolymorphSubB) {
                $target = $this->__instantiator->instantiate('Bdf\Prime\PolymorphSubB', null);
                $object->setEmbedded($target);
            }
            break;
    }
} //END accessor for embedded

PHP
            , $this->accessor->getEmbedded('$target', true, '$dbData')
        );
    }

    /**
     *
     */
    public function test_getEmbedded_polymorph_deep()
    {
        $this->resolver = new AccessorResolver(
            new ClassAccessor(PolymorphContainer::class, ClassAccessor::SCOPE_INHERIT),
            $this->attributes = new AttributesResolver(PolymorphContainer::repository()->mapper(), Prime::service()),
            new CodeGenerator()
        );

        $this->accessor = $this->resolver->embedded($this->attributes->embedded('embedded.location'));

        $this->assertEquals(<<<'PHP'
{ //START accessor for embedded.location
    try {
        $__tmp_0 = $object->embedded();
    } catch (\Error $e) {
        // Ignore not initialized property if embedded is instantiated
        $__tmp_0 = null;
    }
    switch ($dbData['sub_type']) {
        case 'A':
            if (!$__tmp_0 instanceof \Bdf\Prime\PolymorphSubA) {
                $__tmp_0 = $this->__instantiator->instantiate('Bdf\Prime\PolymorphSubA', 1);
                $object->setEmbedded($__tmp_0);
            }
            break;
        case 'B':
            if (!$__tmp_0 instanceof \Bdf\Prime\PolymorphSubB) {
                $__tmp_0 = $this->__instantiator->instantiate('Bdf\Prime\PolymorphSubB', null);
                $object->setEmbedded($__tmp_0);
            }
            break;
    }
    
    if ($__tmp_0 !== null) {
        if ($__tmp_0 instanceof \Bdf\Prime\PolymorphSubA) {
            try {
                $target = $__tmp_0->location();
            } catch (\Error $e) {
                // Ignore not initialized property if embedded is instantiated
                $target = null;
            }
            if ($target === null) {
                $target = $this->__instantiator->instantiate('Bdf\Prime\Location', 1);
                $__tmp_0->setLocation($target);
            }
        } elseif ($__tmp_0 instanceof \Bdf\Prime\PolymorphSubB) {
            try {
                $target = $__tmp_0->location();
            } catch (\Error $e) {
                // Ignore not initialized property if embedded is instantiated
                $target = null;
            }
            if ($target === null) {
                $target = $this->__instantiator->instantiate('Bdf\Prime\Location', 1);
                $__tmp_0->setLocation($target);
            }
        }
    }
    
} //END accessor for embedded.location

PHP
            , $this->accessor->getEmbedded('$target', true, '$dbData')
        );
    }

    /**
     *
     */
    public function test_getter_setter_nillable_embedded()
    {
        $this->resolver = new AccessorResolver(
            new ClassAccessor(PolymorphContainer::class, ClassAccessor::SCOPE_INHERIT),
            $this->attributes = new AttributesResolver(PolymorphContainer::repository()->mapper(), Prime::service()),
            new CodeGenerator()
        );

        $accessor = $this->resolver->embedded($this->attributes->embedded('embedded.location'));

        $this->assertEquals('($embedded === null ? null : $embedded->city)', $accessor->getter('$embedded', 'city'));
        $this->assertEquals('($embedded === null ? null : $embedded->city = $value)', $accessor->setter('$embedded', 'city', '$value'));
    }

    /**
     *
     */
    public function test_getter_setter_polymorph()
    {
        $this->resolver = new AccessorResolver(
            new ClassAccessor(PolymorphContainer::class, ClassAccessor::SCOPE_INHERIT),
            $this->attributes = new AttributesResolver(PolymorphContainer::repository()->mapper(), Prime::service()),
            new CodeGenerator()
        );

        $accessor = $this->resolver->embedded($this->attributes->embedded('embedded'));

        $this->assertEquals('($embedded instanceof \Bdf\Prime\PolymorphSubA ? $embedded->name() : ($embedded instanceof \Bdf\Prime\PolymorphSubB ? $embedded->name() : null))', $accessor->getter('$embedded', 'name'));
        $this->assertEquals('($embedded instanceof \Bdf\Prime\PolymorphSubA ? $embedded->setName($value) : ($embedded instanceof \Bdf\Prime\PolymorphSubB ? $embedded->setName($value) : null))', $accessor->setter('$embedded', 'name', '$value'));
    }
}
