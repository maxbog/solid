<?php
/**
 * Scope.php
 * User: Max
 * Date: 11.06.13
 * Time: 22:18
 */

namespace Solid\IoC;



class Scope {
    /**
     * Temporal scope for objects bound using Solid\IoC\Container
     *
     * @see Solid\IoC\Container::bind()
     */
    const TEMPORAL = 1;

    /**
     * Singleton scope for objects bound using Solid\IoC\Container
     *
     * @see Solid\IoC\Container::bind()
     */
    const SINGLETON = 2;
}