<?php

namespace Php74;

use Bdf\Prime\Bench\HydratorGeneration;

/**
 * Class GeneratedHydratorWithTypedPropertiesTest
 */
class GeneratedHydratorWithTypedPropertiesTest extends ArrayHydratorWithTypedPropertiesTest
{
    use HydratorGeneration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hydrator = $this->createGeneratedHydrator(SimpleEntity::class);
    }

    /**
     *
     */
    public function test_hydrate_without_setter_invalid_type_should_raise_InvalidTypeException()
    {
        $this->hydrator = $this->createGeneratedHydrator(WithoutSetter::class);

        parent::test_hydrate_without_setter_invalid_type_should_raise_InvalidTypeException();
    }

    /**
     *
     */
    public function test_simple_hydrate_and_extract_without_setters()
    {
        $this->hydrator = $this->createGeneratedHydrator(WithoutSetter::class);

        parent::test_simple_hydrate_and_extract_without_setters();
    }
}
