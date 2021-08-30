<?php

namespace Php74\Relations;

use Bdf\Prime\Bench\HydratorGeneration;
use Php74\Admin;
use Php74\Commit;
use Php74\Company;
use Php74\Customer;
use Php74\Developer;
use Php74\Document;
use Php74\Project;
use Php74\User;

class HasManyWithGeneratedHydratorTest extends HasManyTest
{
    use HydratorGeneration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpGeneratedHydrators(
            Admin::class, Customer::class, User::class, Document::class, Project::class, Company::class,
            Developer::class, Commit::class
        );
    }
}
