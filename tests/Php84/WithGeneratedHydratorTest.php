<?php

namespace Php84;

use Bdf\Prime\Bench\HydratorGeneration;
use Bdf\Prime\Entity\Hydrator\HydratorGenerator;
use Bdf\Prime\Prime;
use Php84\Fixtures\EntityWithAsymmetricVisibility;
use Php84\Fixtures\EntityWithGetterProperty;
use Php84\Fixtures\EntityWithPropertyHook;

class WithGeneratedHydratorTest extends PropertyHookTest
{
    use HydratorGeneration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpGeneratedHydrators(EntityWithPropertyHook::class, EntityWithGetterProperty::class, EntityWithAsymmetricVisibility::class);
    }

    public function test_should_filter_virtual_property_hydration()
    {
        $mapper = Prime::service()->repository(EntityWithGetterProperty::class)->mapper();
        $generator = new HydratorGenerator(Prime::service(), $mapper, EntityWithGetterProperty::class);

        $hydrator = $generator->generate();

        $this->assertStringContainsString("\$values['normalizedEmail'] = \$object->normalizedEmail;", $hydrator);
        $this->assertStringContainsString("\$data['normalizedEmail'] = \$object->normalizedEmail;", $hydrator);
        $this->assertStringContainsString('return $object->normalizedEmail;', $hydrator);

        $this->assertStringNotContainsString('$object->normalizedEmail = $value;', $hydrator);
        $this->assertStringNotContainsString('$object->normalizedEmail = $data[', $hydrator);
    }

    public function test_should_handle_asymmetric_visibility()
    {
        $mapper = Prime::service()->repository(EntityWithAsymmetricVisibility::class)->mapper();
        $generator = new HydratorGenerator(Prime::service(), $mapper, EntityWithAsymmetricVisibility::class);

        $hydrator = $generator->generate();

        $this->assertStringContainsString(<<<'PHP'
    final public function hydrate($object, array $data): void
    {
        if (array_key_exists('id', $data)) {
            try {
                $object->id = $data['id'];
            } catch (\TypeError $e) {
                throw new \Bdf\Prime\Entity\Hydrator\Exception\InvalidTypeException($e, 'integer');
            }
        }
        
        if (array_key_exists('name', $data)) {
            try {
                $object->name = $data['name'];
            } catch (\TypeError $e) {
                throw new \Bdf\Prime\Entity\Hydrator\Exception\InvalidTypeException($e, 'string');
            }
        }
        
        if (array_key_exists('secret', $data)) {
            try {
                $object->setSecret($data['secret']);
            } catch (\TypeError $e) {
                throw new \Bdf\Prime\Entity\Hydrator\Exception\InvalidTypeException($e, 'string');
            }
        }
        
        
    }

PHP
, $hydrator
);
        $this->assertStringContainsString(<<<'PHP'
    final public function hydrateOne($object, string $attribute, $value): void
    {
        switch ($attribute) {
            case 'id':
                try {
                    $object->id = $value;
                } catch (\TypeError $e) {
                    throw new \Bdf\Prime\Entity\Hydrator\Exception\InvalidTypeException($e, 'integer');
                }
                break;
            case 'name':
                try {
                    $object->name = $value;
                } catch (\TypeError $e) {
                    throw new \Bdf\Prime\Entity\Hydrator\Exception\InvalidTypeException($e, 'string');
                }
                break;
            case 'secret':
                try {
                    $object->setSecret($value);
                } catch (\TypeError $e) {
                    throw new \Bdf\Prime\Entity\Hydrator\Exception\InvalidTypeException($e, 'string');
                }
                break;
            default:
                throw new \Bdf\Prime\Entity\Hydrator\Exception\FieldNotDeclaredException('Php84\Fixtures\EntityWithAsymmetricVisibility', $attribute);
        }
    }

PHP
, $hydrator
);

        $this->assertStringContainsString(<<<'PHP'
    final public function extract($object, array $attributes = []): array
    {
        if (empty($attributes)) {
            $values = [];
            
            try { $values['id'] = $object->id; } catch (\Error $e) { /** Ignore not initialized properties */ }
            try { $values['name'] = $object->name; } catch (\Error $e) { /** Ignore not initialized properties */ }
            try { $values['secret'] = $object->secret; } catch (\Error $e) { /** Ignore not initialized properties */ }
            
            return $values;
        } else {
            $attributes = array_flip($attributes);
            $values = [];
            
            if (isset($attributes['id'])) {
                try {
                    $values['id'] = $object->id;
                } catch (\Error $e) {
                    // Ignore not initialized properties
                }
            }
            if (isset($attributes['name'])) {
                try {
                    $values['name'] = $object->name;
                } catch (\Error $e) {
                    // Ignore not initialized properties
                }
            }
            if (isset($attributes['secret'])) {
                try {
                    $values['secret'] = $object->secret;
                } catch (\Error $e) {
                    // Ignore not initialized properties
                }
            }
            
            
            return $values;
        }
    }

PHP
, $hydrator
);
    }
}
