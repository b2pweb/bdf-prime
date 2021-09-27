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
use Php74\Project;
use Php74\User;

class BelongsToWithGeneratedHydratorTest extends BelongsToTest
{
    use HydratorGeneration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpGeneratedHydrators(
            Faction::class, Admin::class, User::class, Customer::class, Document::class,
            Project::class, Company::class, Developer::class, Commit::class
        );
    }
}
