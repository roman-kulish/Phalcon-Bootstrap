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

use Phalcon\DiInterface;

/**
 * Class Module is a wrapper on a bootstrap callable (anonymous function)
 *
 * @author      Roman Kulish <roman.kulish@gmail.com>
 */
class Module
{
    /**
     * Bootstrap module callable
     *
     * @var \Closure
     */
    protected $callable = null;

    /**
     * Bootstrap module callable function arguments
     *
     * @var null|array
     */
    protected $specification = null;

    /**
     * Environment
     *
     * @var null|Environment
     */
    protected $environment = null;

    /**
     * Constructor
     *
     * If $specification is an instance of \Closure, then function arguments will be resolved using \ReflectionFunction.
     * This is the easiest, but the slowest way to specify module function and it has some limitations. One of them is that
     * variables insider the container and Di must not have the same names, otherwise it is not possible to decide which
     * one should be passed to the function.
     *
     * It's important to use type hints with function arguments. {@see Container} and {@see Environment} are resolved
     * by the class in front of the argument. In all other cases, arguments are resolved by names in the following order:
     * first they are searched inside the container and then inside the Di, if it is initialized and set on the container.
     * The argument's name that receives Di is $di and matches {@see Container::DI}.
     *
     * Default values can be specified in a normal way.
     *
     * Example:
     *
     * function(Container $container, Environment $environment, Phalcon\Di $di, Phalcon\Mvc\Router $router = null, $a)
     * {
     *     // $container and $environment will be instances of Container and Environment respectively
     *
     *     // $router's value will be searched inside the container and then inside the Di if it's set to the container.
     *     // If it's not found then default value of NULL will be passed to the $router.
     *
     *     // The logic is the same for $a, but $a does not have a default value and if it cannot be resolved, then
     *     // an exception will be thrown.
     * }
     *
     * The following way to specify a module function argument is a bit more complex, but the fastest.
     *
     * If $specification is an array, then the last array element must be a module function. Elements following it are
     * names of variables inside the container or names of services inside the Di. A number of them must match the
     * number of function arguments. Names can be different though.
     *
     * If parameter name inside the $specification starts with '@' symbol, it indicates Di service, otherwise it is a
     * name of the variable inside the container.
     *
     * $container, $environment and $di are pre-defined parameters that are resolved to instances of Container, Environment
     * and Phalcon\Di respectively. Di must be set to the container.
     *
     * To specify a default value, set a parameter as an array key and its value as an array value.
     *
     * Example:
     *
     * ['$container', '$environment', '$di' => new Phalcon\Di\FactoryDefault(), '@router', 'a', 'bool' => true,
     * function(Container $c, Environment $e, Phalcon\Di $di, Phalcon\CLI\Router $router, $var1, $var2) {
     *     // ...
     * }]
     *
     * @param array|\Closure $specification Bootstrap module specification or a callable
     * @throws \InvalidArgumentException
     */
    public function __construct($specification)
    {
        if ( is_array($specification) ) {
            if (! ( $callable = array_pop($specification) ) instanceof \Closure) {
                throw new \InvalidArgumentException('The last element of $specification array must be an instance of \\Closure');
            }
        } else if ($specification instanceof \Closure) {
            $callable = $specification;
            $specification = null;
        } else {
            throw new \InvalidArgumentException('Argument $specification must be an array or an instance or \\Closure');
        }

        $this->callable = $callable;
        $this->specification = $specification;
    }

    /**
     * Set environment to execute this stage under
     *
     * @param string|Environment $environment The environment to execute this bootstrap stage under
     */
    public function setEnvironment($environment)
    {
        $this->environment = ( !( $environment instanceof Environment ) ? new Environment($environment) : $environment );
    }

    /**
     * Execute the bootstrap module. Module return result is ignored.
     *
     * @param Container   $container   Variables container
     * @param Environment $environment Bootstrap environment
     */
    public function execute(Container $container, Environment $environment)
    {
        if ( !is_null($this->environment) && !$environment->is($this->environment) ) {
            return ;
        }

        if ($this->specification === null) {
            $this->invokeUsingReflection($container, $environment);
        } else {
            $this->invokeUsingSpecification($container, $environment);
        }
    }

    /**
     * Resolve function arguments and invoke it using Reflection API
     *
     * @param Container   $container   Variables container
     * @param Environment $environment Bootstrap environment
     * @throws \LogicException
     */
    protected function invokeUsingReflection(Container $container, Environment $environment)
    {
        $reflectionFunction = new \ReflectionFunction($this->callable);

        $di = $container->getDI();
        $args = array();

        foreach($reflectionFunction->getParameters() as $parameter) {
            if (( $reflectionClass = $parameter->getClass() ) !== null) {
                if ( $reflectionClass->isInstance($container) ) {
                    $args[] = $container;
                    continue;
                } else if ( $reflectionClass->isInstance($environment) ) {
                    $args[] = $environment;
                    continue;
                }
            }

            $parameterName = $parameter->getName();
            $isService = ( ($di instanceof DiInterface) && $di->has($parameterName) );

            if ( isset($container->{$parameterName}) ) {
                if ($isService) {
                    throw new \LogicException(sprintf('Parameter name "%s" is ambiguous', $parameterName));
                }

                $args[] = $container->{$parameterName};
            } else if ($isService) {
                $args[] = $di->get($parameterName);
            } else if ( $parameter->isDefaultValueAvailable() ) {
                $args[] = $parameter->getDefaultValue();
            } else {
                throw new \LogicException(sprintf('Unable to resolve a value for the "%s" parameter', $parameterName));
            }
        }

        $reflectionFunction->invokeArgs($args);
    }

    /**
     * Resolve function arguments using specification and invoke it
     *
     * @param Container   $container   Variables container
     * @param Environment $environment Bootstrap environment
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    protected function invokeUsingSpecification(Container $container, Environment $environment)
    {
        $args = array();

        $di = $container->getDI();
        $position = 0;
        $marker = null;

        foreach($this->specification as $parameter => $default) {
            if ( is_int($parameter) ) {
                $parameter = $default;
                $default = null;
                $defaultValueAvailable = false;
            } else {
                $defaultValueAvailable = true;
            }

            if ( !is_string($parameter) || ( $parameter = trim($parameter) ) == '' ) {
                throw new \InvalidArgumentException(sprintf('Invalid parameter name in position [%d], must be a string', $position));
            }

            if (( $marker = substr($parameter, 0, 1) ) == '$') {
                switch( strtolower($parameter) ) {
                    case '$container':
                        $args[] = $container;
                        break;

                    case '$environment':
                        $args[] = $environment;
                        break;

                    case '$di':
                        $args[] = $di;
                        break;

                    default:
                        throw new \InvalidArgumentException(sprintf('Unsupported "%s" parameter', $parameter));
                }
            } else if ($marker == '@') {
                if (! $di instanceof DiInterface ) {
                    throw new \LogicException('No dependency injector instance exists in the container');
                }

                $args[] = $di->get( substr($parameter, 1) );
            } else if ( isset($container->{$parameter}) ) {
                $args[] = $container->{$parameter};
            } else if ($defaultValueAvailable) {
                $args[] = $default;
            } else {
                throw new \LogicException(sprintf('Unable to resolve a value for the "%s" parameter', $parameter));
            }

            $position++;
        }

        call_user_func_array($this->callable, $args);
    }
}