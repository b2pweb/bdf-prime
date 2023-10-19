<?php

namespace Bdf\Prime\Mapper\Attribute;

use Attribute;
use Bdf\Prime\Mapper\Mapper;

/**
 * Use quote identifier for the mapper
 *
 * @see Mapper::setQuoteIdentifier()
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class UseQuoteIdentifier implements MapperConfigurationInterface
{
    private bool $quoteIdentifier;

    /**
     * @param bool $quoteIdentifier
     */
    public function __construct(bool $quoteIdentifier = true)
    {
        $this->quoteIdentifier = $quoteIdentifier;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Mapper $mapper): void
    {
        $mapper->setQuoteIdentifier($this->quoteIdentifier);
    }
}
