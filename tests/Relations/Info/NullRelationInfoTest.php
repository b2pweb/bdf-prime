<?php

namespace Bdf\Prime\Relations\Info;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class NullRelationInfoTest extends TestCase
{
    /**
     *
     */
    public function test_instance()
    {
        $this->assertInstanceOf(NullRelationInfo::class, NullRelationInfo::instance());
        $this->assertSame(NullRelationInfo::instance(), NullRelationInfo::instance());
    }

    /**
     *
     */
    public function test_isLoaded()
    {
        $info = new NullRelationInfo();
        $entity = new \stdClass();

        $this->assertFalse($info->isLoaded($entity));

        $info->markAsLoaded($entity);
        $this->assertFalse($info->isLoaded($entity));

        $info->clear($entity);
        $this->assertFalse($info->isLoaded($entity));
    }
}
