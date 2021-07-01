## Prime

Prime is a Data mapper ORM based on doctrine DBAL. 
The goal of prime is to lightweight usage of data mapper and doctrine DBAL.

[![Build Status](https://travis-ci.com/b2pweb/bdf-prime.svg?branch=master)](https://travis-ci.com/b2pweb/bdf-prime)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/b2pweb/bdf-prime/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/b2pweb/bdf-prime/?branch=master)
[![Packagist Version](https://img.shields.io/packagist/v/b2pweb/bdf-prime.svg)](https://packagist.org/packages/b2pweb/bdf-prime)
[![Total Downloads](https://img.shields.io/packagist/dt/b2pweb/bdf-prime.svg)](https://packagist.org/packages/b2pweb/bdf-prime)
[![Type Coverage](https://shepherd.dev/github/b2pweb/bdf-prime/coverage.svg)](https://shepherd.dev/github/b2pweb/bdf-prime)


### Installation with Composer

```bash
composer require b2pweb/prime
```

### Basic usage

```PHP
<?php
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\ServiceLocator;

// declare your connexion manager
$connexions = new ConnectionManager();
$connexions->declareConnection('myDB', [
    'adapter' => 'mysql',
    'host'    => 'localhost',
]);

// Use the service locator to locate your repositories
$manager = new ServiceLocator($connexions);
$repository = $manager->repository(User::class);
/** @var User $user */
$user = $repository->get(1);
$user->setName('john');
$repository->save($user);
```

### Create your connections

```PHP
<?php
use Bdf\Prime\ConnectionManager;

// declare your connexion manager
$connexions = new ConnectionManager();
// MySQL
$connexions->declareConnection('mysql', 'mysql://user:password@localhost/database');
// Sqlite
$connexions->declareConnection('sqlite', 'sqlite://path/to/database.sqlite');
```

You can also use [DBAL-compatible configuration arrays](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html) instead of DSN strings if you prefer:

```PHP
<?php
use Bdf\Prime\ConnectionManager;

$connexions = new ConnectionManager();
$connexions->declareConnection('mysql', [
    'dbname'   => 'mydb',
    'user'     => 'user',
    'password' => 'secret',
    'host'     => 'localhost',
    'driver'   => 'pdo_mysql',
    // OR
    'adapter'  => 'mysql',
]);
```

#### Available options

| Option              | Type       | Description  |
|---------------------|------------|--------------|
| `driver`            | string     | The driver name (ex: `pdo_mysql`, `mysqli`). See doctrine drivers for more informations. |
| `adapter`           | string     | PDO adapter.  `pdo_` prefix will be added. |
| `dbname`            | string     | The database name. |
| `host`              | string     | The host name.  |
| `port`              | string     | The connection port. |
| `user`              | string     | The user credentials. |
| `password`          | string     | The password credentials. |
| `path`              | string     | The file path used by sqlite to store data. |
| `url`               | string     | The DSN string. All data extract from the dsn will be erased the others options. |
| `driverOptions`     | array      | The driver options. |
| `memory`            | bool       | The sqlite option for memory. |
| `unix_socket`       | string     | The unix socket file. |
| `charset`           | string     | The client charset. |
| `server_version`    | string     |  |
| `wrapper_class`     | string     |  |
| `driver_class`      | string     |  |
| `platform_service`  | string     |  |
| `logging`           | bool       |  |
| `shards`            | array      | Sharding connection: contains an array with shard name in key and options in value. The shard options will be merged onto the master connection. |
| `read`              | array      | Master/slave connection: contains an array of options for the read connection. |


### Create your mapper

Use the class `Bdf\Prime\Mapper\Mapper` to create your mappers.

#### Declare a mapper

```PHP
<?php

use Bdf\Prime\Mapper\Mapper;

class UserMapper extends Mapper
{
    /**
     * Schema declaration
     * 
     * Definition
     *  - connection         : The connection name declare in connection manager (mandatory).
     *  - database           : The database name.
     *  - table              : The table name (mandatory).
     *  - tableOptions       : The table options (ex: engine => myisam).
     * 
     * <code>
     *  return [
     *     'connection'   => (string),
     *     'database'     => (string),
     *     'table'        => (string),
     *     'tableOptions' => (array),
     *  ];
     * </code>
     * 
     * @return array|null
     */
    public function schema()
    {
        return [
            'connection' => 'myDB',
            'table'      => 'users',
        ];
    }
    
    /**
     * Build fields from this mapper.
     * 
     * @param \Bdf\Prime\Mapper\Builder\FieldBuilder $builder
     */
    public function buildFields($builder)
    {
        $builder
            ->bigint('id')->autoincrement()
            ->string('name')
        ;
    }
    
    /**
     * Array of index
     * 
     * <code>
     *  return [
     *      ['attribute1', 'attribute2']
     *  ];
     * </code>
     * 
     * @return array
     */
    public function indexes()
    {
        return ['name'];
    }
}
```

#### The custom filters

Prime allows custom filters definition. You can define filter alias for complex queries.

```PHP
<?php
    /**
     * Gets custom filters
     * 
     * <code>
     *  return [
     *      'customFilterName' => function(<Bdf\Prime\Query\QueryInterface> $query, <mixed> $value) {
     *          return <void>
     *      },
     *  ];
     * </code>
     * 
     * @return array
     */
    public function filters()
    {
        return [
            'nameLike' => function($query, $value) {
                $query->where('name', ':like', $value.'%');
            },
        ];
    }
...

$users = $repository->where('nameLike', 'john')->all();
```
    
#### The scopes

The scope is a custom method of a repository.

```PHP
<?php
    /**
     * Repository extension
     * returns additionnals methods in repository
     * 
     * <code>
     * return [
     *     'customMethod' => function($repository, $test) {
     *         
     *     },
     * ];
     * 
     * $repository->customMethod('test');
     * </code>
     * @return array
     */
    public function scopes()
    {
        return [
            'countName' => function(\Bdf\Prime\Query\QueryInterface $query, $value) {
                // you can access to the repository using $query->repository()
                
                $query->where('name', ':like', $value);
                return $query->count();
            },
        ];
    }
...

$count = $repository->countName('john');
```

#### The sequence

```PHP
<?php
    /**
     * Sequence definition.
     * 
     * The metadata will build the sequence info using this method if the primary key is defined as sequence (Metadata::PK_SEQUENCE).
     * Definition:
     *  - connection         : The connection name declare in connection manager. The table connection will be used by default.
     *  - table              : The table sequence name.
     *                         The table name with suffix '_seq' will be used by default.
     *  - column             : The sequence column name. Default 'id'.
     *  - tableOptions       : The sequence table options (ex: engine => myisam).
     * 
     * <code>
     *  return [
     *     'connection'   => (string),
     *     'table'        => (string),
     *     'column'       => (string),
     *     'tableOptions' => (array),
     *  ];
     * </code>
     * 
     * @return array
     */
    public function sequence()
    {
        return [
            'connection'   => 'myDB',
            'table'        => 'user_seq',
            'column'       => 'id',
            'tableOptions' => [],
        ];
    }
```

#### The model constraints

You can define a model with default constraints. Those constraints will be applied for all queries on that model.

```PHP
<?php
use Bdf\Prime\Mapper\Mapper;

class EnabledUserMapper extends Mapper
{
    public function buildFields($builder)
    {
        $builder
            ->boolean('enabled')
        ;
    }
    
    /**
     * Register custom event on notifier
     *
     * <code>
     * return [
     *     'attribute' => 'value'
     * ]
     * </code>
     * 
     * @return array
     */
    public function customConstraints()
    {
        return [
            'enabled' => true
        ];
    }
}

EnabledUser::all();
// select * from EnabledUser where enabled = 1;
```

### Active record mode

Prime enable an active record mode by using the class `Bdf\Prime\Entity\Model`.
This class provides shorcut method to the associative repository. 
It also provides a default serialization with `Bdf\Serializer\Serializer`.

```PHP
<?php
use Bdf\Prime\Entity\Model;

class User extends Model
{
    ...
}
```

You have now access to active record method on the user object.

CRUD methods:
```PHP
<?php
$user = new User();
$user->setName('john');
$user->save();
$user->delete();
```

Static shortcuts for all repository methods (event scopes):
```PHP
<?php
$users = User::where('id', '<', 2)->all();
$count = User::countName('john');
```

Access to the repository:
```PHP
<?php
$repository = User::repository();
```

*The prime active record does not manage modified fields.*

### Query builder

### Relations

#### Declare relations

You have access to a relation builder to declare all relations of an entity in the mapper.

```PHP
<?php
    /**
     * Build relations from this mapper.
     *
     * @param \Bdf\Prime\Relations\Builder\RelationBuilder $builder
     */
    public function buildRelations($builder)
    {
        // The property User::customer is an object Customer.
        // The relation join is key Customer::id on User::customerId
        $builder->on('customer')
            ->belongsTo(Customer::class, 'customerId')
            // only where customer.enabled = true
            ->constraints(['enabled' => true])
            // Detach the relation: the attribute 'customer' will not be added on the entity.
            ->detached() 
        ;
    }
```

| *Methods*         | *Description* |
| ------------------| ------------- | 
| `belongsTo`       | `$builder->on('customer')->belongsTo('Customer::id', 'customer.id');` |
| `hasOne`          | `$builder->on('contact')->hasOne('Contact::distantId', 'localId');` |
| `hasMany`         | `$builder->on('documents')->hasMany('Document::distantId', 'localId');` |
| `belongsToMany`   | `$builder->on('packs')->belongsToMany('Pack::id', 'localId')->through('CustomerPack', 'customerId', 'packId');` |
| `morphTo`         |  |
| `morphOne`        |  |
| `morphMany`       |  |
| `inherit`         | `$builder->on('target')->inherit('targetId');` Should be defined in subclasses |
| `custom`          |  |


#### Load relation

```PHP
<?php
// You can load a relation on existing object.
$user->load('customer');

// You can load an object with its relations
$users = User::repository()->with('customer')->all();

```

#### Filter on relation

```PHP
<?php
// You can use the relation name to use automatic join on table.
$users = User::repository()->where('customer.name', 'like', 'Foo%')->all();
// Note: those expressions are different:
$users = User::repository()->where('customer.id', 1)->all(); // Use join
$users = User::repository()->where('customerId', 1)->all(); // Don't join

```

#### Custom relation


### Schema manager

### Migration

### Events

Use the `Mapper::customEvents` method to declare your listeners
```PHP
<?php
    /**
     * Register custom event on notifier
     * The notifier is a repository
     *
     * @param \Bdf\Event\EventNotifier $notifier
     */
    public function customEvents($notifier)
    {
        $notifier->saving(function($entity, $repository, $isNew) {
            // do something
        });
    }
```

The list of prime events:

| *Event*             | *Repository method*        | *context* |
| ------------------- | -------------------------- | ------------- |
| Events::POST_LOAD   | `$repository->loaded()`    |  |
| Events::PRE_SAVE    | `$repository->saving()`    |  |
| Events::POST_SAVE   | `$repository->saved()`     |  |
| Events::PRE_INSERT  | `$repository->inserting()` |  |
| Events::POST_INSERT | `$repository->inserted()`  |  |
| Events::PRE_UPDATE  | `$repository->updating()`  |  |
| Events::POST_UPDATE | `$repository->updated()`   |  |
| Events::PRE_DELETE  | `$repository->deleting()`  |  |
| Events::POST_DELETE | `$repository->deleted()`   |  |

### Behaviors

You can defined the model behaviors in your mapper using the method `Mapper::getDefinedBehaviors`.

```PHP
<?php
    /**
     * Custom definition of behaviors
     *
     * @return \Bdf\Prime\Behaviors\BehaviorInterface[]
     */
    public function getDefinedBehaviors()
    {
        return [
            new \Bdf\Prime\Behaviors\Timestampable()
        ];
    }
```
Available behaviors:

| *Class name*                          | *Description*|
| --------------------------------------| -------------------------- | 
| `Bdf\Prime\Behaviors\Blameable`       | Automatically add user name that has realize update or creation on the entity. |
| `Bdf\Prime\Behaviors\SoftDeletable`   | Mark entity as deleted but keep the data in storage. |
| `Bdf\Prime\Behaviors\Timestampable`   | Automatically add created and updated date time on the entity. |
| `Bdf\Prime\Behaviors\Versionable`     |     |
