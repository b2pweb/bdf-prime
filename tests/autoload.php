<?php

require_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/_files/PrimeTestCase.php';
include_once __DIR__ . '/_files/TestEntities.php';
include_once __DIR__ . '/_files/HydratorGeneration.php';
include_once __DIR__ . '/_files/DummyPlatform.php';
include_once __DIR__ . '/_files/SchemaAssertion.php';
include_once __DIR__ . '/_files/MyCustomRelation.php';
include_once __DIR__ . '/_files/ForeignInRelation.php';
include_once __DIR__ . '/Entity/_files/embedded.php';

\Bdf\PHPUnit\DeprecationErrorHandler::register();
SebastianBergmann\Comparator\Factory::getInstance()->register(new \Bdf\PHPUnit\Comparator\DateTimeComparator());
