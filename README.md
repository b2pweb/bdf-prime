## Prime

Prime is a Data mapper ORM based on doctrine DBAL. 
The goal of prime is to lightweight usage of data mapper and doctrine DBAL.

[![build](https://github.com/b2pweb/bdf-prime/actions/workflows/php.yml/badge.svg)](https://github.com/b2pweb/bdf-prime/actions/workflows/php.yml)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/b2pweb/bdf-prime/badges/quality-score.png?b=2.0)](https://scrutinizer-ci.com/g/b2pweb/bdf-prime/?branch=2.0)
[![codecov](https://codecov.io/github/b2pweb/bdf-prime/branch/2.1/graph/badge.svg?token=VOFSPEWYKX)](https://codecov.io/github/b2pweb/bdf-prime)
[![Packagist Version](https://img.shields.io/packagist/v/b2pweb/bdf-prime.svg)](https://packagist.org/packages/b2pweb/bdf-prime)
[![Total Downloads](https://img.shields.io/packagist/dt/b2pweb/bdf-prime.svg)](https://packagist.org/packages/b2pweb/bdf-prime)
[![Type Coverage](https://shepherd.dev/github/b2pweb/bdf-prime/coverage.svg)](https://shepherd.dev/github/b2pweb/bdf-prime)


### Getting Started

See [Wiki](https://github.com/b2pweb/bdf-prime/wiki) for more information

```bash
composer require b2pweb/bdf-prime
```

```PHP
<?php

use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Builder\IndexBuilder;
use Bdf\Prime\Query\Expression\Like;
use Bdf\Prime\ServiceLocator;

// Declare your entity
class User extends Model
{
    public $id;
    public $firstName;
    public $lastName;
    public $email;

    public function __construct(array $data) 
    {
        $this->import($data);
    }
}

// Declare the data mapper for the entity
class UserMapper extends Mapper
{
    public function schema(): array
    {
        return [
            'connection' => 'myDB',
            'table'      => 'users',
        ];
    }
    
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('id')->autoincrement()
            ->string('firstName')
            ->string('lastName')
            ->string('email')
        ;
    }

    public function buildIndexes(IndexBuilder $builder): void
    {
        $builder->add()->on('name');
    }
}

// Declare your connections
$connexions = new ConnectionManager();
$connexions->declareConnection('myDB', 'mysql://myuser:mypassword@localhost');

// Use the service locator to locate your repositories
$manager = new ServiceLocator($connexions);
Locatorizable::configure($manager);
$repository = $manager->repository(User::class);

// Get and update an entity
$user = User::findById(1);
$user->setFirstName('john')->save();

// Use a query builder for searching entities 
User::where('firstName', 'john')->orWhere('email', (new Like('john%'))->startsWith())->all();
```
