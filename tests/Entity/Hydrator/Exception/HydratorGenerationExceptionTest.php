<?php

namespace Bdf\Prime\Entity\Hydrator\Exception;

use PHPUnit\Framework\TestCase;
use stdClass;

/**
 *
 */
class HydratorGenerationExceptionTest extends TestCase
{
    /**
     * 
     */
    public function test_set_get_entity_class()
    {
        $exception = new HydratorGenerationException(stdClass::class);

        $this->assertSame(stdClass::class, $exception->entityClass());
    }
}
