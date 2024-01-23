<?php

namespace Bdf\Prime\Connection\Middleware\Explain\Platform;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Factory for create explain platform
 */
final class ExplainPlatformFactory
{
    /**
     * @var list<class-string<ExplainPlatformInterface>>
     */
    private array $platforms;

    /**
     * @param list<class-string<ExplainPlatformInterface>>|null $platforms
     */
    public function __construct(?array $platforms = null)
    {
        $this->platforms = $platforms ?? [SqliteExplainPlatform::class, MysqlExplainPlatform::class];
    }

    /**
     * Create the corresponding explain platform for the given doctrine platform
     *
     * @param AbstractPlatform $platform The doctrine platform
     *
     * @return ExplainPlatformInterface The explain platform. If no explain platform is found, a {@see NullExplainPlatform} is returned
     */
    public function createExplainPlatform(AbstractPlatform $platform): ExplainPlatformInterface
    {
        /** @var class-string<ExplainPlatformInterface> $explainPlatform */
        foreach ($this->platforms as $explainPlatform) {
            if ($explainPlatform::supports($platform)) {
                return new $explainPlatform($platform);
            }
        }

        return new NullExplainPlatform();
    }
}
