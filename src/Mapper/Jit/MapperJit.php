<?php

namespace Bdf\Prime\Mapper\Jit;

use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Query\Compiled\CompiledSqlQuery;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Repository\RepositoryInterface;
use Closure;
use ReflectionException;
use ReflectionFunction;
use Throwable;

use function count;
use function get_class;
use function is_array;
use function is_object;
use function str_contains;
use function strrchr;
use function strrpos;
use function substr;
use function ucfirst;

/**
 * JIT system for a mapper
 * This class allows to compile some "repository methods" with inlined SQL queries, using {@see CompiledSqlQuery}
 *
 * This is how it works:
 * 1. First the actual method is wrapped into a closure, allowing to intercept the call
 * 2. When called, the method code is analysed to extract the query building parts, and a hook is generated {@see JitQueryHook}
 * 3. The hook instance is called instead of the actual method to extract the SQL query and its arguments
 * 4. After some calls, and if their all consistent (i.e. same SQL query and same arguments), a closure code is generated, replacing all query building code to a simple inlined SQL query
 * 5. The closure is then saved and used for the next calls
 *
 * To allow collect the SQL query and its arguments on method called only few times per request, the extracted information are stored in a persistent storage,
 * and reloaded on the next request.
 */
final class MapperJit
{
    private JitManager $manager;
    private CodeGenerator $generator;
    private Mapper $mapper;

    /**
     * @var array<string, JitQueryHook>|null
     */
    private ?array $hooks = null;

    /**
     * @var array<string, callable>|null
     */
    private ?array $compiledQueries = null;

    /**
     * @var array<string, callable>
     */
    private array $originalMethods = [];

    public function __construct(JitManager $manager, Mapper $mapper, ?CodeGenerator $generator = null)
    {
        $this->mapper = $mapper;
        $this->generator = $generator ?? new CodeGenerator();
        $this->manager = $manager;
    }

    /**
     * Wrap the method to handle the JIT compilation
     *
     * @param string $name The method identifier (can be different from the actual method name)
     * @param callable $function The original method
     *
     * @return callable The wrapped method
     */
    public function handle(string $name, callable $function): callable
    {
        $this->originalMethods[$name] = $function;

        return function (RepositoryInterface $repository, ...$args) use ($name) {
            $original = $this->originalMethods[$name];

            // Disabling constraints may change the query, so ignore the JIT here
            if ($repository instanceof EntityRepository && $repository->isWithoutConstraints()) {
                return ($original)($repository, ...$args);
            }

            $this->compiledQueries ??= $this->manager->loadCompiled($this->mapper);
            $compiled = $this->compiledQueries[$name] ?? null;

            // The query is already inlined, so execute it (step 5)
            if ($compiled) {
                return $compiled($repository, ...$args);
            }

            $this->hooks ??= $this->manager->loadHooks($this->mapper);
            $hook = $this->hooks[$name] ?? null;

            // Generate the hook (step 2)
            if ($hook === null) {
                if ($hook = $this->createHook($original, $name)) {
                    $this->hooks[$name] = $hook;
                }
            }

            if ($hook !== null && !$hook->invalid) {
                // Call the hook (step 3)
                /** @psalm-suppress InvalidFunctionCall - psalm ignore __invoke method */
                $result = $hook($repository, ...$args);
            } else {
                return ($original)($repository, ...$args);
            }

            if ($this->manager->ready($hook)) {
                // Inline the query (step 4)
                $inlined = $this->inlineQuery($hook, $name);

                if ($inlined) {
                    $this->compiledQueries[$name] = $inlined;
                    unset($this->hooks[$name]);
                }
            }

            return $result;
        };
    }

    /**
     * Try to inline the query, using extracted SQL and arguments from the hook
     *  This method should not raise any exception
     *
     * @param JitQueryHook $hook
     * @param string $name The method identifier
     *
     * @return Closure|null The new function using inlined query, or null if failed
     */
    private function inlineQuery(JitQueryHook $hook, string $name): ?Closure
    {
        try {
            $code = $this->generator->generateInlinedQuery($hook);

            if (!$code) {
                return null;
            }

            $this->manager->saveCompiled($this->mapper, $name, $code);

            /** @var Closure $closure */
            $closure = eval('return ' . $code . ';');

            return $closure->bindTo($this->mapper);
        } catch (Throwable $e) {
            if (!$hook->invalid) {
                $hook->invalidate('The query cannot be inlined due to an exception ' . get_class($e) . ': ' . $e->getMessage());
            }

            if ($e instanceof JitException) {
                if ($this->manager->ignoreErrors()) {
                    return null;
                }

                throw $e;
            }

            return $this->manager->raiseError($this->mapper, $name, $e->getMessage(), $e);
        }
    }

    /**
     * Create a hook for the given method, allowing to extract the SQL query and its arguments
     * This method should not raise any exception
     *
     * @param callable $function Function to hook
     *
     * @return JitQueryHook|null The hooked function, or null if failed
     */
    private function createHook(callable $function, string $name): ?JitQueryHook
    {
        $objectAndMethod = $this->parseObjectAndMethod($function);

        if (!$objectAndMethod) {
            return $this->manager->raiseError($this->mapper, $name, 'Cannot parse the method');
        }

        $mapperClassName = get_class($objectAndMethod[0]);

        if (str_contains($mapperClassName, '\\')) {
            $namespace = substr($mapperClassName, 0, strrpos($mapperClassName, '\\'));
            $hookBaseClassName = substr(strrchr($mapperClassName, '\\'), 1) . 'Query' . ucfirst($objectAndMethod[1]);
            $hookClassName = $namespace . '\\' . $hookBaseClassName;
        } else {
            $hookBaseClassName = $hookClassName = $mapperClassName . 'Query' . ucfirst($objectAndMethod[1]);
        }

        try {
            $code = $this->generator->generateHook($objectAndMethod[0], $objectAndMethod[1], $hookBaseClassName, $this->manager->ignoreErrors());

            if (!$code) {
                return null;
            }

            $this->manager->saveAndCompileHook(
                $this->mapper,
                $hookClassName,
                $code
            );

            /** @var JitQueryHook $hook */
            return new $hookClassName($this->mapper, $this->manager->ignoreErrors());
        } catch (Throwable $e) {
            return $this->manager->raiseError($this->mapper, $name, $e->getMessage(), $e);
        }
    }

    /**
     * @return array{0: object, 1: string}|null
     */
    public function parseObjectAndMethod(callable $function): ?array
    {
        // Parse callable in form of [$object, 'method']
        if (is_array($function)) {
            if (count($function) !== 2) {
                return null;
            }

            if (is_object($function[0])) {
                return [$function[0], $function[1]];
            }

            return null;
        }

        if (!$function instanceof Closure) {
            return null;
        }

        // Parse closure created using Closure::fromCallable(xxx), or first class callable syntax $obj->method(...)
        try {
            $r = new ReflectionFunction($function);
        } catch (ReflectionException $e) {
            return null;
        }

        $object = $r->getClosureThis();
        $name = $r->getName();

        // There is no $this, or this is an anonymous function
        if (!$object || str_contains($name, '{closure}')) {
            return null;
        }

        return [$object, $name];
    }

    public function __destruct()
    {
        if ($this->hooks !== null) {
            $this->manager->saveHooks($this->mapper, $this->hooks);
        }
    }
}
