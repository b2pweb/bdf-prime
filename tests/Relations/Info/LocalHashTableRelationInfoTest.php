<?php

namespace Bdf\Prime\Relations\Info;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class LocalHashTableRelationInfoTest extends TestCase
{
    /**
     *
     */
    public function test_isLoaded()
    {
        $info = new LocalHashTableRelationInfo();

        $entity = new \stdClass();
        $other = new \stdClass();

        $this->assertFalse($info->isLoaded($entity));

        $info->markAsLoaded($entity);
        $this->assertTrue($info->isLoaded($entity));
        $this->assertFalse($info->isLoaded($other));

        $info->clear($entity);
        $this->assertFalse($info->isLoaded($entity));
    }
}
