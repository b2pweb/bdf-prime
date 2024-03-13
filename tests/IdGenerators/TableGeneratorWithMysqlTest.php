<?php

namespace IdGenerators;

use Bdf\Prime\IdGenerators\TableGeneratorTest;
use Bdf\Prime\IdGenerators\TableUser;

class TableGeneratorWithMysqlTest extends TableGeneratorTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prime()->connections()->removeConnection('test');
        $this->prime()->connections()->declareConnection('test', MYSQL_CONNECTION_DSN);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->prime()->repository(TableUser::class)->schema()->drop();
        $this->unsetPrime();
    }
}
