<?php namespace Marshall\IoC;

class Container {

    /**
     * @var array
     */
    protected $bindings = [];

    /**
     * @var array
     */
    protected $singletons = [];

    /**
     * @param string $target
     * @param string $implementation
     */
    public function bind($target, $implementation)
    {
        $this->bindings[$target] = $implementation;
    }

    /**
     * @param string $target
     * @param mixed|callable $singleton
     */
    public function singleton($target, $singleton)
    {
        $this->singletons[$target] = $singleton;
    }

    /**
     * @param string $target
     * @return mixed
     */
    public function create($target)
    {
        $binding = $this->getTargetImplementation($target);

        if ($this->hasSingleton($binding)) return $this->getSingleton($binding);

        $reflect = new \ReflectionClass($binding);

        if ( ! $reflect->isInstantiable())
        {
            throw new \RuntimeException('Class is not instantiable.');
        }

        $constructor = $reflect->getConstructor();

        if (is_null($constructor))
        {
            return new $binding;
        }

        $parameters = $constructor->getParameters();

        $dependencies = $this->getDependencies($parameters);

        return $reflect->newInstanceArgs($dependencies);
    }

    /**
     * @param mixed $obj
     * @param string $target
     * @return mixed
     */
    public function invoke($obj, $target)
    {
        $method = new \ReflectionMethod($obj, $target);

        $parameters = $method->getParameters();

        $dependencies = $this->getDependencies($parameters);

        return $method->invokeArgs($obj, $dependencies);
    }

    /**
     * @param \Closure $closure
     * @return mixed
     */
    public function call($closure)
    {
        $reflect = new \ReflectionFunction($closure);

        $parameters = $reflect->getParameters();

        $dependencies = $this->getDependencies($parameters);

        return $reflect->invokeArgs($dependencies);
    }

    //

    /**
     * Overrides a specific target's implementation.
     *
     * @param string $target
     * @return string
     */
    public function getTargetImplementation($target)
    {
        if (array_key_exists($target, $this->bindings))
        {
            return $this->bindings[$target];
        }

        return $target;
    }

    /**
     * Resolves a list of dependencies.
     *
     * @param \ReflectionParameter[] $parameters
     * @return mixed[]
     */
    public function getDependencies($parameters)
    {
        $dependencies = [];

        foreach ($parameters as $parameter)
        {
            $dependencies[] = $this->getDependency($parameter);
        }

        return $dependencies;
    }

    /**
     * Resolves a dependency on a parameter.
     *
     * @param \ReflectionParameter $parameter
     * @return mixed
     */
    public function getDependency($parameter)
    {
        $class = $parameter->getClass();

        if ( ! is_null($class))
        {
            return $this->create($class->getName());
        }

        if ($parameter->isDefaultValueAvailable())
        {
            return $parameter->getDefaultValue();
        }

        throw new \RuntimeException('Method parameter is not invokable.');
    }

    /**
     * @param string $target
     * @return bool
     */
    public function hasSingleton($target)
    {
        return array_key_exists($target, $this->singletons);
    }

    /**
     * @param string $target
     * @return mixed|null
     */
    public function getSingleton($target)
    {
        $singleton = $this->singletons[$target];

        if ($singleton instanceof \Closure)
        {
            $singleton = $this->call($singleton);

            $this->singletons[$target] = $singleton;
        }

        return $singleton;
    }

}
