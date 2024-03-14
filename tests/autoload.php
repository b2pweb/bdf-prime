<?php

define('MYSQL_HOST', getenv('MYSQL_HOST') ?: '127.0.0.1');
define('MYSQL_USER', getenv('MYSQL_USER') ?: 'root');
define('MYSQL_PASSWORD', getenv('MYSQL_PASSWORD') ?: '');
define('MYSQL_DBNAME', getenv('MYSQL_DBNAME') ?: 'test');
const MYSQL_CONNECTION_PARAMETERS = [
    'adapter' => 'mysql',
    'dbname' => MYSQL_DBNAME,
    'user' => MYSQL_USER,
    'password' => MYSQL_PASSWORD,
    'host' => MYSQL_HOST,
];
const MYSQL_CONNECTION_DSN = 'mysql://' . MYSQL_USER . ':' . MYSQL_PASSWORD . '@' . MYSQL_HOST . '/' . MYSQL_DBNAME;

require_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/_files/PrimeTestCase.php';
include_once __DIR__ . '/_files/TestEntities.php';
include_once __DIR__ . '/_files/HydratorGeneration.php';
include_once __DIR__ . '/_files/DummyPlatform.php';
include_once __DIR__ . '/_files/SchemaAssertion.php';
include_once __DIR__ . '/_files/MyCustomRelation.php';
include_once __DIR__ . '/_files/ForeignInRelation.php';
include_once __DIR__ . '/_files/TestClock.php';
include_once __DIR__ . '/Entity/_files/embedded.php';

date_default_timezone_set('Europe/Paris');

SebastianBergmann\Comparator\Factory::getInstance()->register(new \Bdf\PHPUnit\Comparator\DateTimeComparator());
