<?php

namespace Clock;

use Bdf\Prime\Clock\Converter;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class ConverterTest extends TestCase
{
    public function test_cast_to_same_class()
    {
        $date = new DateTimeImmutable();
        $this->assertSame($date, Converter::castToClass($date, DateTimeImmutable::class));

        $date = new MyDateImmutable();
        $this->assertSame($date, Converter::castToClass($date, MyDateImmutable::class));
    }

    public function test_cast_to_mutable()
    {
        $date = new DateTimeImmutable('2020-01-01 12:15:26.14587', new DateTimeZone('America/Lima'));

        $converted = Converter::castToClass($date, DateTime::class);
        $this->assertSame(DateTime::class, get_class($converted));
        $this->assertSame('2020-01-01 12:15:26.145870', $converted->format('Y-m-d H:i:s.u'));
        $this->assertSame('America/Lima', $converted->getTimezone()->getName());

        $converted = Converter::castToClass($date, MyDateMutable::class);
        $this->assertSame(MyDateMutable::class, get_class($converted));
        $this->assertSame('2020-01-01 12:15:26.145870', $converted->format('Y-m-d H:i:s.u'));
        $this->assertSame('America/Lima', $converted->getTimezone()->getName());
    }

    public function test_cast_to_immutable()
    {
        $date = new DateTimeImmutable('2020-01-01 12:15:26.14587', new DateTimeZone('America/Lima'));

        $converted = Converter::castToClass($date, MyDateImmutable::class);
        $this->assertSame(MyDateImmutable::class, get_class($converted));
        $this->assertSame('2020-01-01 12:15:26.145870', $converted->format('Y-m-d H:i:s.u'));
        $this->assertSame('America/Lima', $converted->getTimezone()->getName());
    }
}

class MyDateImmutable extends DateTimeImmutable
{
}

class MyDateMutable extends DateTime
{
}
