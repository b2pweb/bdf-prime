<?php

namespace Bdf\Prime\Types\Helpers;

use DateTime;
use DateTimeInterface;
use DateTimeZone;

/**
 * Default behavior for date time type
 * The trait should be used in class in TypeInterface.
 */
trait DateTimeHelper
{
    /**
     * The date format
     */
    protected string $format;

    /**
     * The date timezone
     *
     * Let null manage default timezone
     */
    protected ?DateTimeZone $timezone = null;

    /**
     * Date class name
     *
     * @var class-string<DateTimeInterface>
     */
    protected string $className = DateTime::class;

    /**
     * Should reset the other fields of the format
     */
    protected bool $resetFields = false;

    /**
     * {@inheritdoc}
     */
    public function fromDatabase(mixed $value, array $fieldOptions = []): ?DateTimeInterface
    {
        if ($value === null) {
            return null;
        }

        $format = $this->format;

        if ($this->resetFields) {
            $format = '!'.$format;
        }

        $className = $fieldOptions['className'] ?? $this->className;
        $timezone = isset($fieldOptions['timezone']) ? new DateTimeZone($fieldOptions['timezone']) : $this->timezone;

        /** @psalm-suppress UndefinedMethod */
        $date = $className::createFromFormat($format, $value, $timezone);

        // Invalid format, fail-safe return
        // @fixme flag to thow an error instead ?
        if ($date === false) {
            return null;
        }

        if ($timezone && $date) {
            $date = $date->setTimezone($timezone);
        }

        return $date;
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        // If the date is formatted, it can be considered as a simple string
        if (!$value instanceof DateTimeInterface) {
            return $value;
        }

        return $value->format($this->format);
    }

    /**
     * {@inheritdoc}
     */
    public function phpType(): string
    {
        return $this->className[0] !== '\\' ? '\\'.$this->className : $this->className;
    }

    /**
     * Get the date timezone
     *
     * @return DateTimeZone|null
     */
    public function getTimezone(): ?DateTimeZone
    {
        return $this->timezone;
    }
}
