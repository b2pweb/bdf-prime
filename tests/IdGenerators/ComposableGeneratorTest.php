<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\ServiceLocator;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 *
 */
class ComposableGeneratorTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->configurePrime();
    }

    /**
     *
     */
    public function test_generation()
    {
        $data = [];

        $generator = new ComposableGenerator([
            new CustomTestGenerator('foo1', 'bar1'),
            new CustomTestGenerator('foo2', 'bar2'),
        ]);
        $generator->generate($data, Prime::service());

        $this->assertEquals('bar1', $data['foo1']);
        $this->assertEquals('bar2', $data['foo2']);
    }

    /**
     *
     */
    public function test_post_generation()
    {
        $data = new stdClass();

        $generator = new ComposableGenerator([
            new CustomTestGenerator('foo1', 'bar1'),
            new CustomTestGenerator('foo2', 'bar2'),
        ]);
        $generator->postProcess($data);

        $this->assertEquals('bar1', $data->foo1);
        $this->assertEquals('bar2', $data->foo2);
    }

    /**
     *
     */
    public function test_set_connection()
    {
        $connection = $this->createMock(SimpleConnection::class);

        $mock = $this->createMock(GeneratorInterface::class);
        $mock->expects($this->once())->method('setCurrentConnection')->with($connection);

        $generator = new ComposableGenerator([$mock]);
        $generator->setCurrentConnection($connection);
    }
}

class CustomTestGenerator implements GeneratorInterface
{
    private $property;
    private $value;

    public function __construct($property, $value)
    {
        $this->property = $property;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array &$data, ServiceLocator $serviceLocator): void
    {
        $data[$this->property] = $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function postProcess($entity): void
    {
        $entity->{$this->property} = $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentConnection(ConnectionInterface $connection): void
    {
    }
}