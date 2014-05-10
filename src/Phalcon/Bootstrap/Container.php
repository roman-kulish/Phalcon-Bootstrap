<?php

/**
 * This file is part of the Phalcon\Bootstrap component
 *
 * For the full copyright and license information, please view the LICENSE.txt file,
 * that is distributed with this source code.
 *
 * @license MIT
 */

namespace Phalcon\Bootstrap;

use Phalcon\DI\InjectionAwareInterface;
use Phalcon\DiInterface;

/**
 * Container implements a user-space for storing and retrieving variables. Setting or retrieving a variable is the same
 * as operating with object properties:
 *
 * $container->var1 = 'some value'; // sets 'some value' to 'var1' variable of the $container
 * $var1 = $container->var1; // returns value of 'var1' container variable
 * unset($container->var1); // removes 'var1' container variable
 * isset($container->var1); // returns TRUE if 'var1' container variable exists and is not NULL
 *
 * @author      Roman Kulish <roman.kulish@gmail.com>
 */
final class Container implements InjectionAwareInterface
{
    /**
     * Phalcon\DI container variable name
     */
    const DI = 'di';

    /**
     * Container variables
     *
     * @var array
     */
    protected $variables = array();

    /**
     * Constructor
     *
     * @param array $variables Initial variables
     */
    public function __construct(array $variables = null)
    {
        if ($variables !== null) {
            $this->merge($variables);
        }
    }

    /**
     * Merge $variables array with the variables inside the container
     *
     * @param array $variables Variables to merge
     * @return Container
     */
    public function merge(array $variables)
    {
        foreach($variables as $name => $value) {
            $this->__set($name, $value);
        }

        return $this;
    }

    /**
     * __get() overloading
     *
     * Magic method that implements $container->{$name}
     *
     * @param string $name Property name
     * @return mixed|null
     * @throws \InvalidArgumentException
     */
    public function __get($name)
    {
        return ( isset($this->variables[$name]) ? $this->variables[$name] : null );
    }

    /**
     * __set() overloading
     *
     * Magic method that implements $container->{$name} = $value
     *
     * @param string $name  Property name
     * @param mixed  $value Property value
     * @throws \InvalidArgumentException
     */
    public function __set($name, $value)
    {
        if ($name == self::DI) {
            $this->setDI($value); // make sure it complies the protocol
        } else {
            $this->variables[$name] = $value;
        }
    }

    /**
     * __isset() overloading
     *
     * Magic method that implements isset($container->{$name})
     *
     * @param string $name Property name
     * @return boolean
     * @throws \InvalidArgumentException
     */
    public function __isset($name)
    {
        return isset($this->variables[$name]);
    }

    /**
     * __unset() overloading
     *
     * Magic method that implements unset($container->{$name})
     *
     * @param string $name Property name
     * @throws \InvalidArgumentException
     */
    public function __unset($name)
    {
        unset($this->variables[$name]);
    }

    /**
     * Sets the dependency injector
     *
     * @param DiInterface $dependencyInjector
     * @return Container
     * @throws \InvalidArgumentException
     */
    public function setDI($dependencyInjector)
    {
        if (! $dependencyInjector instanceof DiInterface ) {
            throw new \InvalidArgumentException('Argument $dependencyInjector must be an instance of \\Phalcon\\DiInterface');
        }

        $this->variables[self::DI] = $dependencyInjector;
        return $this;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return DiInterface
     */
    public function getDI()
    {
        return $this->__get(self::DI);
    }
}