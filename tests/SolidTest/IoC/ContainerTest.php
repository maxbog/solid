<?php

namespace SolidTest\IoC;


use Solid\IoC\Container;
use Solid\IoC\Scope;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldResolveConcreteClass() {

        $container = new Container;

        $resolved = $container->resolve('SolidTest\IoC\ConcreteClass');

        $this->assertInstanceOf('SolidTest\IoC\ConcreteClass', $resolved);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testShouldNotResolveNonExistentConcreteClass() {

        $container = new Container;

        $container->resolve('SolidTest\IoC\ThisClassDoesNotExist');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testShouldNotResolveUnboundAbstractClass() {

        $container = new Container;

        $container->resolve('SolidTest\IoC\AbstractClass');
    }

    public function testShouldResolveBoundAbstractClass() {

        $container = new Container;

        $container->bind('SolidTest\IoC\AbstractClass', 'SolidTest\IoC\ConcreteClass');
        $resolved = $container->resolve('SolidTest\IoC\AbstractClass');

        $this->assertInstanceOf('SolidTest\IoC\ConcreteClass', $resolved);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testShouldNotBindWhenConcreteClassDoesntExist() {

        $container = new Container;

        $container->bind('SolidTest\IoC\AbstractClass', 'SolidTest\IoC\ConcreteClassThatDoesntExist');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testShouldNotBindWhenAbstractClassDoesntExist() {

        $container = new Container;

        $container->bind('SolidTest\IoC\AbstractClassThatDoesntExist', 'SolidTest\IoC\ConcreteClass');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testShouldNotBindWhenConcreteClassDoesntExendAbstractClass() {

        $container = new Container;

        $container->bind('SolidTest\IoC\AbstractClass', 'SolidTest\IoC\OtherConcreteClass');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testShouldNotBindToNonAbstractClass() {

        $container = new Container;

        $container->bind('SolidTest\IoC\AbstractClass', 'SolidTest\IoC\OtherConcreteClass');
    }

    public function testShouldBindThroughOtherClass() {

        $container = new Container;

        $container->bind('SolidTest\IoC\NestedAbstractClass', 'SolidTest\IoC\NestedConcreteClass');
        $container->bind('SolidTest\IoC\AbstractClass', 'SolidTest\IoC\NestedAbstractClass');

        $resolved = $container->resolve('SolidTest\IoC\AbstractClass');

        $this->assertInstanceOf('SolidTest\IoC\NestedConcreteClass', $resolved);
    }

    public function testShouldResolveInterface() {
        $container = new Container;

        $container->bind('SolidTest\IoC\NestedTestInterface', 'SolidTest\IoC\ConcreteClass');

        $resolved = $container->resolve('SolidTest\IoC\NestedTestInterface');

        $this->assertInstanceOf('SolidTest\IoC\ConcreteClass', $resolved);
    }

    public function testShouldBindThroughOtherInterface() {
        $container = new Container;

        $container->bind('SolidTest\IoC\NestedTestInterface', 'SolidTest\IoC\ConcreteClass');
        $container->bind('SolidTest\IoC\TestInterface', 'SolidTest\IoC\NestedTestInterface');

        $resolved = $container->resolve('SolidTest\IoC\TestInterface');


        $this->assertInstanceOf('SolidTest\IoC\ConcreteClass', $resolved);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAbstractTypeShouldNotBindToItself() {
        $container = new Container;

        $container->bind('SolidTest\IoC\AbstractClass', 'SolidTest\IoC\AbstractClass');

        $resolved = $container->resolve('SolidTest\IoC\AbstractClass');

        $this->assertInstanceOf('SolidTest\IoC\AbstractClass', $resolved);
    }

    public function testConcreteTypeShouldBindToItself() {
        $container = new Container;

        $container->bind('SolidTest\IoC\ConcreteClass', 'SolidTest\IoC\ConcreteClass');

        $resolved = $container->resolve('SolidTest\IoC\ConcreteClass');

        $this->assertInstanceOf('SolidTest\IoC\ConcreteClass', $resolved);
    }

    public function testConcreteTypeShouldBindWithoutSecondArgument() {
        $container = new Container;

        $container->bind('SolidTest\IoC\ConcreteClass');

        $resolved = $container->resolve('SolidTest\IoC\ConcreteClass');

        $this->assertInstanceOf('SolidTest\IoC\ConcreteClass', $resolved);
    }

    public function testConcreteSingletonShouldResolveToTheSameObject() {
        $container = new Container;

        $container->singleton('SolidTest\IoC\ConcreteClass');

        $container->bind('SolidTest\IoC\OtherConcreteClass', Scope::SINGLETON);

        $resolved1 = $container->resolve('SolidTest\IoC\ConcreteClass');
        $resolved2 = $container->resolve('SolidTest\IoC\ConcreteClass');

        $resolved3 = $container->resolve('SolidTest\IoC\OtherConcreteClass');
        $resolved4 = $container->resolve('SolidTest\IoC\OtherConcreteClass');

        $this->assertInstanceOf('SolidTest\IoC\ConcreteClass', $resolved1);
        $this->assertSame($resolved1, $resolved2);

        $this->assertInstanceOf('SolidTest\IoC\OtherConcreteClass', $resolved3);
        $this->assertSame($resolved3, $resolved4);
    }

    public function testAbstractSingletonShouldResolveToTheSameObject() {
        $container = new Container;

        $container->singleton('SolidTest\IoC\AbstractClass', 'SolidTest\IoC\ConcreteClass');

        $resolved1 = $container->resolve('SolidTest\IoC\AbstractClass');
        $resolved2 = $container->resolve('SolidTest\IoC\AbstractClass');

        $this->assertInstanceOf('SolidTest\IoC\ConcreteClass', $resolved1);
        $this->assertSame($resolved1, $resolved2);
    }

    public function testTemporalShouldResolveToDifferentObjects() {
        $container = new Container;

        $container->temporal('SolidTest\IoC\ConcreteClass');

        $container->bind('SolidTest\IoC\OtherConcreteClass', Scope::TEMPORAL);

        $resolved1 = $container->resolve('SolidTest\IoC\ConcreteClass');
        $resolved2 = $container->resolve('SolidTest\IoC\ConcreteClass');

        $resolved3 = $container->resolve('SolidTest\IoC\OtherConcreteClass');
        $resolved4 = $container->resolve('SolidTest\IoC\OtherConcreteClass');

        $this->assertInstanceOf('SolidTest\IoC\ConcreteClass', $resolved1);
        $this->assertInstanceOf('SolidTest\IoC\ConcreteClass', $resolved2);
        $this->assertNotSame($resolved1, $resolved2);

        $this->assertInstanceOf('SolidTest\IoC\OtherConcreteClass', $resolved3);
        $this->assertInstanceOf('SolidTest\IoC\OtherConcreteClass', $resolved4);
        $this->assertNotSame($resolved3, $resolved4);
    }

    public function testAliasShouldResolveToBoundConcreteObject() {
        $container = new Container;

        $container->bind('SolidTest\IoC\ConcreteClass');
        $container->alias('cc', 'SolidTest\IoC\ConcreteClass');

        $resolved = $container->resolve('cc');

        $this->assertInstanceOf('SolidTest\IoC\ConcreteClass', $resolved);
    }


    public function testAliasShouldResolveToUnboundConcreteObject() {
        $container = new Container;

        $container->alias('cc', 'SolidTest\IoC\ConcreteClass');

        $resolved = $container->resolve('cc');

        $this->assertInstanceOf('SolidTest\IoC\ConcreteClass', $resolved);
    }

    public function testAliasShouldResolveToThroughAnotherAlias() {
        $container = new Container;

        $container->alias('a', 'SolidTest\IoC\ConcreteClass');
        $container->alias('b', 'a');

        $resolved = $container->resolve('b');

        $this->assertInstanceOf('SolidTest\IoC\ConcreteClass', $resolved);
    }

    public function testCustomFactoryShouldResolveToObject() {
        $container = new Container;

        $container->bind('SolidTest\IoC\ConcreteClass', function() { return new ConcreteClass; });
        $resolved = $container->resolve('SolidTest\IoC\ConcreteClass');

        $this->assertInstanceOf('SolidTest\IoC\ConcreteClass', $resolved);
    }

    public function testContainerPassedToCustomFactory() {
        $container = new Container;

        $container->bind('Solid\IoC\Container', function($c) { return $c; });
        $resolved = $container->resolve('Solid\IoC\Container');

        $this->assertInstanceOf('Solid\IoC\Container', $resolved);
        $this->assertSame($container, $resolved);
    }

    public function testSingletonCallsCustomFactoryOnce() {
        $container = new Container;

        $calls = 0;

        $container->singleton('SolidTest\IoC\ConcreteClass', function() use(&$calls) { $calls++; return new ConcreteClass; });
        $resolved = $container->resolve('SolidTest\IoC\ConcreteClass');
        $container->resolve('SolidTest\IoC\ConcreteClass');
        $container->resolve('SolidTest\IoC\ConcreteClass');

        $this->assertInstanceOf('SolidTest\IoC\ConcreteClass', $resolved);
        $this->assertEquals(1, $calls);
    }

}

interface TestInterface {

}

interface NestedTestInterface extends TestInterface {

}

abstract class AbstractClass implements NestedTestInterface {
}

class ConcreteClass extends AbstractClass {
}

abstract class NestedAbstractClass extends AbstractClass {
}

class NestedConcreteClass extends NestedAbstractClass {
}


class OtherConcreteClass {
}


/*
bind($concreteClassName);
bind($abstractName, $concreteClassName);
bind($abstractName, $closure);
bind($alias, $abstractName);
bind($alias, $concreteName);
bind($alias, $closure);
bind($alias, $alias);
*/