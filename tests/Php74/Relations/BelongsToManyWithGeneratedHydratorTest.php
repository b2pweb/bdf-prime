<?php

namespace Php74\Relations;

use Bdf\Prime\Bench\HydratorGeneration;
use Bdf\Prime\Company;
use Php74\Commit;
use Php74\Customer;
use Php74\CustomerPack;
use Php74\Developer;
use Php74\Integrator;
use Php74\Pack;
use Php74\Project;
use Php74\ProjectIntegrator;

class BelongsToManyWithGeneratedHydratorTest extends BelongsToManyTest
{
    use HydratorGeneration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpGeneratedHydrators(
            Customer::class, Pack::class, CustomerPack::class, Project::class, Company::class,
            Integrator::class, ProjectIntegrator::class, Commit::class, Developer::class
        );
    }
}
