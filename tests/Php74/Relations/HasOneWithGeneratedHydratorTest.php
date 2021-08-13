<?php

namespace Php74\Relations;

use Bdf\Prime\Bench\HydratorGeneration;
use Php74\Commit;
use Php74\Company;
use Php74\Customer;
use Php74\Developer;
use Php74\Location;
use Php74\Project;

class HasOneWithGeneratedHydratorTest extends HasOneTest
{
    use HydratorGeneration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpGeneratedHydrators(
            Customer::class, Project::class, Company::class,
            Developer::class, Commit::class, Location::class
        );
    }
}
