<?php

namespace Php74\Relations;

use Bdf\Prime\Bench\HydratorGeneration;
use Php74\Customer;
use Php74\CustomerControlTask;
use Php74\Document;
use Php74\DocumentControlTask;
use Php74\DocumentEager;
use Php74\Task;
use Php74\User;

class ByInheritanceWithGeneratedHydratorTest extends ByInheritanceTest
{
    use HydratorGeneration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpGeneratedHydrators(
            DocumentEager::class, DocumentControlTask::class, CustomerControlTask::class,
            Document::class, Customer::class, User::class, Task::class
        );
    }
}
