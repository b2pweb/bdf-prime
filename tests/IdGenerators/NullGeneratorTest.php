<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\TestEntity;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class NullGeneratorTest extends TestCase
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
    public function test_generation_id()
    {
        $entity = new TestEntity();
        $data = [];

        $generator = new NullGenerator();
        $generator->generate($data, Prime::service());
        $generator->postProcess($entity);

        $this->assertTrue(empty($data['id']));
        $this->assertTrue(empty($entity->id));
    }
}