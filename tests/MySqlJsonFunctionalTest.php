<?php

namespace Bdf\Prime;

class MySqlJsonFunctionalTest extends JsonFunctionalTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prime()->connections()->removeConnection('test');
        $this->prime()->connections()->declareConnection('test', MYSQL_CONNECTION_PARAMETERS);
    }

    protected function tearDown(): void
    {
        EntityWithJson::repository()->schema()->drop();

        parent::tearDown();
    }

    public function test_schema()
    {
        $this->assertSame([
            'CREATE TABLE test_json (id INT AUTO_INCREMENT NOT NULL, data LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', object LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB'
        ], EntityWithJson::repository()->schema(true)->diff());
    }
}
