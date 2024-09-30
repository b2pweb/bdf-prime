<?php

namespace Bdf\Prime\Mapper\Jit;

use Bdf\Prime\Mapper\Mapper;
use Closure;

use Psr\SimpleCache\CacheInterface;

use ReflectionClass;

use Throwable;

use function array_key_exists;
use function class_exists;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function get_class;
use function is_array;
use function is_dir;
use function is_object;
use function md5_file;
use function mkdir;
use function str_replace;
use function strtr;
use function var_export;

/**
 * Handle JIT configuration
 */
final class JitManager
{
    private const COMPILED_FILE = 'compiled.php';

    private CacheInterface $hookMetadataCache;
    private string $directory;
    private bool $checkFileChecksum = true;
    private int $minCount = 2;
    private bool $ignoreErrors;

    /**
     * @param CacheInterface $hookMetadataCache The storage for the hooks metadata. Will be used to collect queries and arguments across requests
     * @param string $directory The directory where to store the compiled hooks and closures (generated PHP code)
     * @param bool $checkFileChecksum If true, a checksum will be computed on the mapper file to ensure that generated code will be invalidated if the mapper file changes. Set to false on production.
     * @param int $minCount The minimum number of calls to a method before inlining the SQL query. Should be at least 2 to ensure that the query is consistent.
     * @param bool $ignoreErrors If true, the JIT will ignore all errors and will not throw exceptions. Should be set to false on production.
     */
    public function __construct(CacheInterface $hookMetadataCache, string $directory, bool $checkFileChecksum = true, int $minCount = 3, bool $ignoreErrors = true)
    {
        $this->hookMetadataCache = $hookMetadataCache;
        $this->directory = $directory;
        $this->checkFileChecksum = $checkFileChecksum;
        $this->minCount = $minCount;
        $this->ignoreErrors = $ignoreErrors;
    }

    /**
     * Create the JIT handler for the given mapper
     *
     * @param Mapper $mapper The mapper
     *
     * @return MapperJit
     */
    public function forMapper(Mapper $mapper): MapperJit
    {
        return new MapperJit($this, $mapper);
    }

    /**
     * Check if errors should be ignored instead of thrown
     */
    public function ignoreErrors(): bool
    {
        return $this->ignoreErrors;
    }

    /**
     * Raise an error if the errors are not ignored
     *
     * @param object|class-string $mapper
     * @param string $method
     * @param string $message
     * @param Throwable|null $previous
     *
     * @return null|never Returns null if the errors are ignored
     * @throws JitException
     */
    public function raiseError($mapper, string $method, string $message = '', ?Throwable $previous = null)
    {
        if ($this->ignoreErrors) {
            return null;
        }

        throw new JitException(is_object($mapper) ? get_class($mapper) : $mapper, $method, $message, $previous);
    }

    /**
     * Load all hooks saved for the given mapper
     *
     * @param Mapper $mapper The mapper
     *
     * @return array<string, JitQueryHook>
     * @internal Used by MapperJit
     */
    public function loadHooks(Mapper $mapper): array
    {
        $key = $this->hookMetadataKey($mapper);
        $metadata = $this->hookMetadataCache->get($key);

        if (!is_array($metadata)) {
            return [];
        }

        $hooks = [];

        foreach ($metadata as $methodName => $hookMetadata) {
            // Check the existence of all required keys (the value may be null, so isset cannot be used)
            if (
                !array_key_exists('hookClass', $hookMetadata)
                || !array_key_exists('hookFile', $hookMetadata)
                || !array_key_exists('count', $hookMetadata)
                || !array_key_exists('query', $hookMetadata)
                || !array_key_exists('argumentsMapping', $hookMetadata)
                || !array_key_exists('constants', $hookMetadata)
                || !array_key_exists('invalid', $hookMetadata)
                || !array_key_exists('reason', $hookMetadata)
            ) {
                continue;
            }

            $hookClass = $hookMetadata['hookClass'];

            if (!class_exists($hookClass, false)) {
                require_once $hookMetadata['hookFile'];
            }

            if (!class_exists($hookClass, false)) {
                continue;
            }

            $count = $hookMetadata['count'];
            $query = $hookMetadata['query'];
            $mapping = $hookMetadata['argumentsMapping'];
            $constants = $hookMetadata['constants'];
            $invalid = $hookMetadata['invalid'];
            $reason = $hookMetadata['reason'];

            /** @var JitQueryHook $hook */
            $hook = new $hookClass($mapper, $this->ignoreErrors);

            $hook->count = $count;
            $hook->query = $query;
            $hook->argumentsMapping = $mapping;
            $hook->constants = $constants;
            $hook->invalid = $invalid;
            $hook->reason = $reason;

            $hooks[$methodName] = $hook;
        }

        return $hooks;
    }

    /**
     * Save the metadata of the hooks
     *
     * @param Mapper $mapper
     * @param array<string, JitQueryHook> $hooks
     *
     * @return void
     * @internal Used by MapperJit
     */
    public function saveHooks(Mapper $mapper, array $hooks): void
    {
        $key = $this->hookMetadataKey($mapper);

        $metadata = [];

        foreach ($hooks as $methodName => $hook) {
            $metadata[$methodName] = [
                'hookClass' => get_class($hook),
                'hookFile' => $this->directory($mapper) . DIRECTORY_SEPARATOR . strtr(get_class($hook), '\\', '_') . '.php',
                'count' => $hook->count,
                'query' => $hook->query,
                'argumentsMapping' => $hook->argumentsMapping,
                'constants' => $hook->constants,
                'invalid' => $hook->invalid,
                'reason' => $hook->reason,
            ];
        }

        $this->hookMetadataCache->set($key, $metadata);
    }

    /**
     * Save the hook code in a file, and compile it (so the class will be available)
     *
     * @param Mapper $mapper
     * @param string $hookClassName The fully qualified class name of the hook
     * @param string $code The code of the hook file
     *
     * @return void
     * @internal Used by MapperJit
     */
    public function saveAndCompileHook(Mapper $mapper, string $hookClassName, string $code): void
    {
        $file = $this->directory($mapper) . DIRECTORY_SEPARATOR . strtr($hookClassName, '\\', '_') . '.php';
        file_put_contents($file, $code);

        if (!class_exists($hookClassName)) {
            require_once $file;
        }
    }

    /**
     * Load all previously compiled closures, with inlined queries
     *
     * Note: all closures will be bound to the given mapper
     *
     * @param Mapper $mapper
     *
     * @return array<string, Closure>
     * @internal Used by MapperJit
     */
    public function loadCompiled(Mapper $mapper): array
    {
        $file = $this->directory($mapper) . DIRECTORY_SEPARATOR . self::COMPILED_FILE;

        if (!file_exists($file)) {
            return [];
        }

        /** @var array<string, Closure> $closures */
        $closures = require $file;
        $boundClosures = [];

        foreach ($closures as $methodName => $closure) {
            $boundClosures[$methodName] = $closure->bindTo($mapper);
        }

        return $boundClosures;
    }

    /**
     * Save a compiled closure code
     *
     * @param Mapper $mapper
     * @param string $methodName The closure identifier
     * @param string $code The code of the closure
     *
     * @return void
     * @internal Used by MapperJit
     */
    public function saveCompiled(Mapper $mapper, string $methodName, string $code): void
    {
        $file = $this->directory($mapper) . DIRECTORY_SEPARATOR . self::COMPILED_FILE;
        $newRow = var_export($methodName, true) . ' => ' . $code . ",\n";

        if (!file_exists($file)) {
            file_put_contents($file, "<?php\n\nreturn [\n{$newRow}\n];");
        } else {
            // @todo may be unsafe
            $content = file_get_contents($file);
            $content = str_replace('];', $newRow . PHP_EOL . '];', $content);
            file_put_contents($file, $content);
        }
    }

    /**
     * Verify if the hook is safe to be used for inlining SQL queries
     *
     * @param JitQueryHook $hook
     *
     * @return bool
     */
    public function ready(JitQueryHook $hook): bool
    {
        return !$hook->invalid && !$hook->ambiguous && $hook->count >= $this->minCount;
    }

    private function directory(Mapper $mapper): string
    {
        $directory = $this->directory . DIRECTORY_SEPARATOR . strtr(get_class($mapper), '\\', '_');

        if ($this->checkFileChecksum) {
            $directory .= DIRECTORY_SEPARATOR . md5_file((new ReflectionClass($mapper))->getFileName());
        }

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        return $directory;
    }

    private function hookMetadataKey(Mapper $mapper): string
    {
        $key = 'jit.hooks.' . strtr(get_class($mapper), '\\', '_');

        if ($this->checkFileChecksum) {
            $key .= '.' . md5_file((new ReflectionClass($mapper))->getFileName());
        }

        return $key;
    }
}
