<?php
/**
 * Container.php
 * User: Max
 * Date: 08.06.13
 * Time: 21:02
 */

namespace Solid\IoC;

class Container
{

    /**
     * @var array
     */
    private $_bound = [];
    /**
     * @var array
     */
    private $_aliases = [];

    /**
     * Resolves specified class or alias.
     * First, when applicable, the alias is resolved to an abstract type.
     * Then, all bindings are searched for this abstract class
     * and an instance of bound class is returned based on the found binding's scope.
     *
     * @param string $className class or alias that will be resolved
     * @return mixed instance of a resolved class or alias
     */
    public function resolve($className)
    {
        if ($this->isAlias($className)) {
            $className = $this->resolveAlias($className);
        }

        if (!$this->isBound($className)) {
            $this->bind($className, $className);
        }

        $factory = $this->getFactory($className);
        return $factory($this);
    }

    /**
     * Returns true if a class is bound.
     *
     * @param string $className a binding for this class will be checked
     * @return bool true, if a binding for $className exists, else false
     */
    public function isBound($className)
    {
        return isset($this->_bound[$className]);
    }

    /**
     * Creates a new binding with a specific scope.
     * When the abstract type will be resolved, an instance of concrete type will be returned.
     * If the concrete type is also abstract, it will be resolved recursively. Te default scope is Scope::TEMPORAL.
     *
     * Can be called in one of the following forms:
     * - bind('ConcreteType') - will bind 'ConcreteType' to itself with TEMPORAL scope
     * - bind('ConcreteType', Scope::SINGLETON) - will bind 'ConcreteType' to itself with SINGLETON scope
     * - bind('AbstractType', 'ConcreteType') - will bind 'AbstractType' to 'ConcreteType' with TEMPORAL scope
     * - bind('AbstractType', 'ConcreteType', Scope::SINGLETON) - will bind 'AbstractType' to 'ConcreteType' with SINGLETON scope
     *
     * @see Scope
     *
     * @param string $abstractType abstract type that will be bound or concrete one if $concreteType is null
     * @param string|null $concreteType concrete type that the abstract type will be bound to
     * @param int $scope scope of the bound item; can be Scope::TEMPORAL or Scope::SINGLETON
     * @throws \InvalidArgumentException when abstract or concrete type are neither a class nor an interface
     */
    public function bind($abstractType, $concreteType = null, $scope = Scope::TEMPORAL)
    {
        // Caller with concrete type only.
        if ($concreteType === null) {
            $concreteType = $abstractType;
        }

        // Called with concrete type and scope only
        if (is_int($concreteType) && func_num_args() == 2) {
            $scope = $concreteType;
            $concreteType = $abstractType;
        }

        if (!interface_exists($abstractType) && !class_exists($abstractType)) {
            throw new \InvalidArgumentException("Neither class nor interface `$abstractType` were found and cannot be bound.");
        }

        if(is_callable($concreteType)) {
            $this->bindFactory($abstractType, $concreteType, $scope);
        } else {
            if (!interface_exists($concreteType) && !class_exists($concreteType)) {
                throw new \InvalidArgumentException("Neither class nor interface `$concreteType` were found and cannot be bound to.");
            }

            $reflectedAbstract = new \ReflectionClass($abstractType);

            if ($reflectedAbstract->isInstantiable()) {
                $this->bindConcreteType($abstractType, $concreteType, $scope);
            } else {
                $this->bindAbstractType($abstractType, $concreteType, $scope);
            }
        }
    }

    /**
     * Shortcut for bind($concreteType, $abstractType, Scope::SINGLETON).
     * Creates a new binding with TEMPORAL scope. Each time an object is resolved
     * the same instance will be returned.
     *
     * @see Solid\IoC\Container::bind()
     * @see Solid\IoC\Scope
     *
     * @param string $abstractType abstract type that will be bound or concrete one if $concreteType is null
     * @param string|null $concreteType concrete type that the abstract type will be bound to
     */
    public function singleton($abstractType, $concreteType = null)
    {
        $this->bind($abstractType, $concreteType, Scope::SINGLETON);
    }

    /**
     * Shortcut for bind($concreteType, $abstractType, Scope::TEMPORAL).
     * Creates a new binding with TEMPORAL scope. Each time an object is resolved
     * a new instance will be created.
     *
     * @see Solid\IoC\Container::bind()
     * @see Solid\IoC\Scope
     *
     * @param string $abstractType abstract type that will be bound or concrete one if $concreteType is null
     * @param string|null $concreteType concrete type that the abstract type will be bound to
     */
    public function temporal($abstractType, $concreteType = null)
    {
        $this->bind($abstractType, $concreteType, Scope::TEMPORAL);
    }

    /**
     * Creates a new alias or updates an old one.
     * From now on, the target can be resolved using this alias.
     *
     * @param string $alias This alias will be used to refer to the target during resolving
     * @param string $target Target referred by created alias
     * @throws \InvalidArgumentException when alias or target are not strings
     */
    public function alias($alias, $target)
    {
        if(!is_string($alias))
            throw new \InvalidArgumentException('Alias must be a string');

        if(!is_string($alias))
            throw new \InvalidArgumentException('Alias must point to a bound class (possibly in future) so it must be a string');

        $this->_aliases[$alias] = $target;
    }

    /**
     * bindConcreteType
     *
     * @param string $abstractClass
     * @param string $concreteClass
     * @param int $scope
     * @throws \InvalidArgumentException
     */
    private function bindConcreteType($abstractClass, $concreteClass, $scope)
    {
        if ($abstractClass !== $concreteClass) {
            throw new \InvalidArgumentException("Class `$abstractClass` is not abstract and cannot be bound.");
        }

        // When resolving a concrete class, we can just return a new instance
        $factory = function () use ($concreteClass) {
            return new $concreteClass;
        };

        $this->bindFactory($abstractClass, $factory, $scope);
    }

    /**
     * bindFactory
     *
     * @param string $abstractClass
     * @param callable $factory
     * @param int $scope
     */
    private function bindFactory($abstractClass, $factory, $scope)
    {
        if ($scope == Scope::SINGLETON) {
            $factory = $this->makeSingletonFactory($factory, $abstractClass);
        }

        $this->_bound[$abstractClass] = compact('factory');
    }

    /**
     * makeSingletonFactory
     *
     * @param callable $factory
     * @param string $abstractClass
     * @return callable
     */
    private function makeSingletonFactory($factory, $abstractClass)
    {
        return function ($c) use ($factory, $abstractClass) {
            if (!isset($c->_bound[$abstractClass]['instance'])) {
                $c->_bound[$abstractClass]['instance'] = $factory($c);
            }
            return $c->_bound[$abstractClass]['instance'];
        };
    }

    /**
     * bindAbstractType
     *
     * @param string $abstractClass
     * @param string $concreteClass
     * @param int $scope
     * @throws \InvalidArgumentException
     */
    private function bindAbstractType($abstractClass, $concreteClass, $scope)
    {
        $reflected = new \ReflectionClass($concreteClass);

        if (!$reflected->isSubclassOf($abstractClass)) {
            throw new \InvalidArgumentException("Class `$concreteClass` is not a subclass of `$abstractClass` and cannot be bound to it.");
        }

        // When resolving an abstract class or an interface, try to resolve the class or interface bound to it
        $factory = function () use ($concreteClass) {
            return $this->resolve($concreteClass);
        };

        $this->bindFactory($abstractClass, $factory, $scope);
    }

    /**
     * getFactory
     *
     * @param string $className
     * @return callable
     */
    private function getFactory($className)
    {
        $bound = $this->_bound[$className];
        return $bound['factory'];
    }

    /**
     * isAlias
     *
     * @param string $alias
     * @return bool
     */
    private function isAlias($alias)
    {
        return isset($this->_aliases[$alias]);
    }

    /**
     * resolveAlias
     *
     * @param string $alias
     * @return string
     */
    private function resolveAlias($alias)
    {
        while ($this->isAlias($alias)) {
            $alias = $this->_aliases[$alias];
        }
        return $alias;
    }
}