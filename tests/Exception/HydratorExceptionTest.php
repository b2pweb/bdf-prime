<?php

namespace Bdf\Prime\Exception;

use PHPUnit\Framework\TestCase;
use stdClass;

/**
 *
 */
class HydratorExceptionTest extends TestCase
{
    /**
     * 
     */
    public function test_set_get_entity_class()
    {
        $exception = new HydratorException(stdClass::class);

        $this->assertSame(stdClass::class, $exception->entityClass());
    }
}