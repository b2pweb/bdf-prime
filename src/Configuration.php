<?php

namespace Bdf\Prime;

use Bdf\Prime\Connection\Middleware\ConfigurationAwareMiddlewareInterface;
use Bdf\Prime\Platform\PlatformTypeInterface;
use Bdf\Prime\Types\TypesRegistry;
use Bdf\Prime\Types\TypesRegistryInterface;
use Doctrine\DBAL\Configuration as BaseConfiguration;

/**
 * Configuration
 *
 * @psalm-suppress InternalClass
 */
class Configuration extends BaseConfiguration
{
    /**
     * @var TypesRegistryInterface|null
     */
    private ?TypesRegistryInterface $types;

    /**
     * @var array<class-string<PlatformTypeInterface>|PlatformTypeInterface>
     */
    private array $platformTypes = [];

    /**
     * The connection name
     */
    private ?string $name = null;

    /**
     * Set configuration
     *
     * @param array $options
     * @psalm-suppress DeprecatedMethod
     */
    public function __construct(array $options = [])
    {
        if (isset($options['logger'])) {
            $this->setSQLLogger($options['logger']);

            /** @psalm-suppress InternalProperty */
            unset($options['logger']);
        }

        foreach ($options as $name => $value) {
            $this->$name = $value;
        }
    }

    /**
     * Register a custom platform type
     *
     * Note: use this with caution, override platform behavior is unlikely a good idea
     *
     * @param class-string<PlatformTypeInterface>|PlatformTypeInterface $platformType Type instance or class name
     * @param string|null $alias Type alias
     * @return void
     */
    public function addPlatformType($platformType, ?string $alias = null): void
    {
        if ($alias !== null) {
            $this->platformTypes[$alias] = $platformType;
        } else {
            $this->platformTypes[] = $platformType;
        }
    }

    /**
     * Set common type registry
     *
     * @param TypesRegistryInterface $types
     * @psalm-assert TypesRegistryInterface $this->types
     */
    public function setTypes(TypesRegistryInterface $types): void
    {
        $this->types = $types;
    }

    /**
     * Get common types registry
     *
     * @return TypesRegistryInterface
     */
    public function getTypes(): TypesRegistryInterface
    {
        if (!isset($this->types)) {
            $this->setTypes(new TypesRegistry());
        }

        return $this->types;
    }

    /**
     * Get custom platform types
     *
     * @return array<class-string<PlatformTypeInterface>|PlatformTypeInterface>
     */
    public function getPlatformTypes(): array
    {
        return $this->platformTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function getMiddlewares(): array
    {
        $configuredMiddlewares = [];

        foreach (parent::getMiddlewares() as $middleware) {
            if ($middleware instanceof ConfigurationAwareMiddlewareInterface) {
                $middleware = $middleware->withConfiguration($this);
            }

            $configuredMiddlewares[] = $middleware;
        }

        return $configuredMiddlewares;
    }

    /**
     * Get the connection name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Define the connection name on the configuration
     * This method will return a new instance of the configuration
     *
     * @param string $name The connection name
     *
     * @return static The new configuration instance
     */
    public function withName(string $name): self
    {
        if ($this->name === $name) {
            return $this;
        }

        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }
}
