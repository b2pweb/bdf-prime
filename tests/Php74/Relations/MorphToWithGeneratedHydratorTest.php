<?php

namespace Php74\Relations;

use Bdf\Prime\Bench\HydratorGeneration;
use Php74\Admin;
use Php74\Commit;
use Php74\Company;
use Php74\Customer;
use Php74\Developer;
use Php74\Document;
use Php74\Faction;
use Php74\Integrator;
use Php74\Project;
use Php74\User;

class MorphToWithGeneratedHydratorTest extends MorphToTest
{
    use HydratorGeneration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpGeneratedHydrators(
            Admin::class, User::class, Document::class, Customer::class, Faction::class,
            Developer::class, Project::class, Company::class, Integrator::class, Commit::class
        );
    }
}
