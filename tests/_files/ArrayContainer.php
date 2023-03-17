<?php

namespace PrimeTests;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ArrayContainer implements ContainerInterface
{
    public array $services = [];

    public function get(string $id)
    {
        if (!isset($this->services[$id])) {
            throw new class("Service $id not found") extends \Exception implements NotFoundExceptionInterface {};
        }

        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }

    public function set(string $id, $service): void
    {
        $this->services[$id] = $service;
    }
}
