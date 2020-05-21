<?php

namespace Bdf\Prime\Entity\Hydrator\Generator;

/**
 * Resolve accessor for embedded (or not) attributes / objects
 */
class AccessorResolver
{
    /**
     * @var ClassAccessor
     */
    private $baseAccessor;

    /**
     * @var AttributesResolver
     */
    private $resolver;

    /**
     * @var CodeGenerator
     */
    private $code;

    /**
     * @var ClassAccessor[]
     */
    private $accessors = [];


    /**
     * AccessorResolver constructor.
     *
     * @param ClassAccessor $baseAccessor
     * @param AttributesResolver $resolver
     * @param CodeGenerator $code
     */
    public function __construct(ClassAccessor $baseAccessor, AttributesResolver $resolver, CodeGenerator $code)
    {
        $this->baseAccessor = $baseAccessor;
        $this->resolver = $resolver;
        $this->code = $code;
    }

    /**
     * Get the accessor for the given class name
     *
     * @param string $className The class to resolve
     *
     * @return ClassAccessor
     */
    public function get($className)
    {
        if ($className === $this->baseAccessor->className()) {
            return $this->baseAccessor;
        }

        if (isset($this->accessors[$className])) {
            return $this->accessors[$className];
        }

        return $this->accessors[$className] = new ClassAccessor($className, ClassAccessor::SCOPE_EXTERNAL);
    }

    /**
     * Get the embedded accessor for the given attribute
     *
     * @param EmbeddedInfo $embedded The embedded entity metadata to resolve
     *
     * @return EmbeddedAccessor
     */
    public function embedded(EmbeddedInfo $embedded)
    {
        return new EmbeddedAccessor(
            $this->code,
            $embedded,
            array_values(array_map([$this, 'get'], $embedded->classes())),
            $embedded->isRoot()
                ? $this->baseAccessor
                : $this->embedded($embedded->parent())
        );
    }
}
