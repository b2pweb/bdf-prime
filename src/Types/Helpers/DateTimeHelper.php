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
     *
     * @var string
     */
    protected $format;

    /**
     * The date timezone
     *
     * Let null manage default timezone
     *
     * @var null|DateTimeZone
     */
    protected $timezone;

    /**
     * Date class name
     *
     * @var string
     */
    protected $className = DateTime::class;

    /**
     * Should reset the other fields of the format
     *
     * @var bool
     */
    protected $resetFields = false;

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, array $fieldOptions = [])
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

        $date = $className::createFromFormat($format, $value, $timezone);

        if ($timezone && $date) {
            $date = $date->setTimezone($timezone);
        }

        return $date;
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value)
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
