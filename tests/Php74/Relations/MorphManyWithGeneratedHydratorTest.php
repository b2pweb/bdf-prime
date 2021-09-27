<?php

namespace Php74\Relations;

use Bdf\Prime\Bench\HydratorGeneration;
use Php74\Admin;
use Php74\Customer;
use Php74\Document;
use Php74\User;

class MorphManyWithGeneratedHydratorTest extends MorphManyTest
{
    use HydratorGeneration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpGeneratedHydrators(
            Admin::class, User::class, Document::class, Customer::class
        );
    }
}
