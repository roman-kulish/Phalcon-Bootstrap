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

/**
 * Class Bootstrap is a lightweight and minimalistic application bootstrap chain implementation.
 *
 * Bootstrap chain consists of anonymous functions - a small code that initializes and executes the bigger application.
 * Each such a function is called a "module".
 *
 * Bootstrap supports environment via {@see Environment} object and supports passing variables between modules using {@see Container}.
 *
 * Out of the box {@see Bootstrap} runs under {@see Environment::PRODUCTION} environment. Bootstrap implements chaining
 * and designed to be used like this:
 *
 * Bootstrap::init()
 *     ->addModule(...)
 *     ->addModule(...)
 *     ->execute();
 *
 * @author      Roman Kulish <roman.kulish@gmail.com>
 */
class Bootstrap
{
    /**
     * Variables container
     *
     * @var Container
     */
    protected $container = null;

    /**
     * Bootstrap environment
     *
     * @var Environment
     */
    protected $environment = null;

    /**
     * Bootstrap chain
     *
     * @var Module[]
     */
    protected $chain = array();

    /**
     * Constructor
     *
     * @param null|Container   $container   Variables container
     * @param null|Environment $environment Bootstrap environment
     */
    protected function __construct(Container $container = null, Environment $environment = null)
    {
        $this->setContainer($container === null ? new Container() : $container);
        $this->setEnvironment($environment === null ? new Environment() : $environment);
    }

    /**
     * Add new module to the bootstrap chain
     *
     * If $module is an instance of \Closure, then function arguments will be resolved using \ReflectionFunction.
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
     * The following way to specify a module is a bit more complex, but the fastest.
     *
     * If $module is an array, then the last array element must be a module function. Elements following it are
     * names of variables inside the container or names of services inside the Di. A number of them must match the
     * number of function arguments. Names can be different though.
     *
     * If parameter name inside the $module starts with '@' symbol, it indicates Di service, otherwise it is a
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
     * If $environment is not NULL then module will be executed only if specified and current environments match.
     *
     * @param \Closure|array|Module   $module      Bootstrap module or  callable or a module specification
     * @param null|string|Environment $environment Environment
     * @return Bootstrap
     */
    public function addModule($module, $environment = null)
    {
        if (! $module instanceof Module ) {
            $module = new Module($module);
        }

        if ($environment !== null) {
            $module->setEnvironment($environment);
        }

        $this->chain[] = $module;
        return $this;
    }

    /**
     * Run bootstrap chain
     *
     * @return Bootstrap
     */
    public function execute()
    {
        for($i = 0, $n = sizeof($this->chain); $i < $n; $i++) {
            $this->chain[$i]->execute( $this->getContainer(), $this->getEnvironment() );
        }

        $this->chain = array();
        return $this;
    }

    /**
     * Set new bootstrap environment
     *
     * @param string|\Closure|Environment $environment Environment as a string or a detector function
     * @return Bootstrap
     */
    public function setEnvironment($environment)
    {
        $this->environment = ( !( $environment instanceof Environment ) ? new Environment($environment) : $environment );
        return $this;
    }

    /**
     * Get bootstrap environment
     *
     * @return Environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Set new container
     *
     * @param array|Container $container Container or container data
     * @return Bootstrap
     */
    public function setContainer($container)
    {
        $this->container = ( !( $container instanceof Container ) ? new Container($container) : $container );
        return $this;
    }

    /**
     * Get variables container
     *
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Start bootstrap chain and return Bootstrap object instance
     *
     * @param null|array|Container             $container   Container or container data
     * @param null|string|\Closure|Environment $environment Environment as a string or a detector function
     * @return Bootstrap
     * @throws \InvalidArgumentException
     */
    public static function init($container = null, $environment = null)
    {
        if ($container !== null) {
            if ( is_array($container) ) {
                $container = new Container($container);
            } else if (! $container instanceof Container ) {
                throw new \InvalidArgumentException(sprintf(
                    'Argument $container must be an array or an instance of %s\\Container',
                    __NAMESPACE__
                ));
            }
        }

        if ($environment !== null) {
            if ( is_string($environment) || ($environment instanceof \Closure) ) {
                $environment = new Environment($environment);
            } else if (! $environment instanceof Environment ) {
                throw new \InvalidArgumentException(sprintf(
                    'Argument $environment must be a string, an instance of \\Closure or an instance of %s\\Environment',
                    __NAMESPACE__
                ));
            }
        }

        /** @noinspection PhpParamsInspection */
        return new static($container, $environment);
    }
}