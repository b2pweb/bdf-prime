<?php

namespace Bdf\Prime\Logger;

use Bdf\Prime\Connection\ConnectionInterface;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 *
 */
class PsrDecoratorTest extends TestCase
{
    /**
     * 
     */
    public function test_without_logger_should_not_failed()
    {
        $logger = new PsrDecorator();
        $logger->startQuery('SELET 1');

        $this->assertNull($logger->stopQuery());
    }
    
    /**
     * 
     */
    public function test_get_logger()
    {
        $psr = $this->createMock(LoggerInterface::class);

        $logger = new PsrDecorator($psr);
        $this->assertSame($psr, $logger->getLogger());
    }

    /**
     *
     */
    public function test_set_get_connection_name()
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('getName')->willReturn('foo');

        $logger = new PsrDecorator();
        $logger->setConnection($connection);
        $this->assertEquals('foo', $logger->getConnectionName());
    }

    /**
     *
     */
    public function test_start_query()
    {
        $query = 'SELECT 1';

        $psr = $this->createMock(LoggerInterface::class);
        $psr->expects($this->once())->method('debug')->with($query);

        $logger = new PsrDecorator($psr);
        $logger->startQuery($query);
    }

    /**
     *
     */
    public function test_start_query_with_connection_name()
    {
        $query = 'SELECT 1';

        $psr = $this->createMock(LoggerInterface::class);
        $psr->expects($this->once())->method('debug')->with('[foo] '.$query);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('getName')->willReturn('foo');

        $logger = new PsrDecorator($psr);
        $logger->setConnection($connection);
        $logger->startQuery($query);
    }

    /**
     * @dataProvider getQueryParam
     */
    public function test_start_with_param($value, $type, $stringValue, $stringType)
    {
        $query = 'SELECT ?';
        $extra = " : [0 => ($stringType) $stringValue]";

        $psr = $this->createMock(LoggerInterface::class);
        $psr->expects($this->once())->method('debug')->with($query.$extra);
        
        $logger = new PsrDecorator($psr);
        $logger->startQuery($query, [$value], [$type]);
    }
    
    public function getQueryParam()
    {
        return [
            [1,     PDO::PARAM_INT,  '1',                       'PDOInt'],
            ['1',   PDO::PARAM_STR,  "'1'",                     'PDOString'],
            [false, PDO::PARAM_BOOL, 'false',                   'PDOBool'],
            [null,  PDO::PARAM_NULL, 'NULL',                    'PDONull'],
            [1,     'float',         '1',                       'float'],  //PDO ne connait pas le type float, d'ou le string '1'
            [1.0,   null,            var_export(1.0, true),     ''], //fix php7 export retourne 1.0 la où php5.6 retourne 1
        ];
    }
}