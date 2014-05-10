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
 * Class Environment is used to detect and / or access environment the application is running under.
 *
 * Environment object behaves like a string during comparison:
 *
 * if ($environment == 'test') {
 *     // ... do something ...
 * }
 *
 * Environment class offers some pre-defined environments with {@see Environment::PRODUCTION} being the default.
 * An instance of Environment is immutable.
 *
 * If detecting environment is a complex operation, an anonymous function can be passed to the constructor
 * instead of an environment string. This function is executed once the environment string is requested for the first
 * time. The detector function is called without parameters and must returns a non-empty string indicating current
 * environment. If anything other than string is returned, a \RuntimeException exception will be thrown.
 *
 * @author      Roman Kulish <roman.kulish@gmail.com>
 */
final class Environment
{
    /**
     * Development environment, e.g., not stable or local developer machine
     */
    const DEVELOPMENT = 'development';

    /**
     * Test environment, e.g., not stable or used by both QA & developers
     */
    const TEST = 'test';

    /**
     * Staging, pre-release environment
     */
    const STAGING = 'staging';

    /**
     * Production environment
     */
    const PRODUCTION = 'production';

    /**
     * Current environment
     *
     * @var string
     */
    protected $environment = null;

    /**
     * Environment detector
     *
     * @var \Closure
     */
    protected $detector = null;

    /**
     * Constructor
     *
     * @param string|\Closure $environment Environment
     * @throws \InvalidArgumentException
     */
    public function __construct($environment = self::DEVELOPMENT)
    {
        if ( is_string($environment) && ( $environment = trim($environment) ) != '' ) {
            $this->environment = $environment;
        } else if ($environment instanceof \Closure) {
            $this->detector = $environment; // environment will be detected once requested
        } else {
            throw new \InvalidArgumentException('Argument $environment must be a string or an instance of \\Closure');
        }
    }

    /**
     * Tests which environment the application is running under
     *
     * @param string|Environment $environment Environment
     * @return bool
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function is($environment)
    {
        if ((!($environment instanceof Environment) && !is_string($environment)) ||
            ( is_string($environment) && ( $environment = trim($environment) ) == '' )) {

            throw new \InvalidArgumentException(sprintf(
                'Argument $environment must be a string or an instance of %s\\Environment',
                __NAMESPACE__
            ));
        }

        try {
            $this->detect();
        } catch(\Exception $exception) {
            throw new \RuntimeException('Unable to detected current environment', 0, $exception);
        }

        return ( strcasecmp($this->environment, trim($environment)) == 0 );
    }

    /**
     * Returns current environment
     *
     * @return string
     */
    public function __toString()
    {
        try {
            $this->detect();
        } catch(\Exception $exception) {
            trigger_error($exception->getMessage(), E_USER_ERROR); // must not throw an exception
        }

        return $this->environment;
    }

    /**
     * Executes environment detector and sets current environment
     *
     * @throws \RuntimeException
     */
    protected function detect()
    {
        if ($this->environment !== null) {
            return ;
        }

        try {
            $detectedEnvironment = call_user_func($this->detector);
        } catch(\Exception $exception) {
            throw new \RuntimeException('Environment detector function failed to execute', 0, $exception);
        }

        if ( !is_string($detectedEnvironment) || ( $detectedEnvironment = trim($detectedEnvironment) ) == '' ) {
            throw new \RuntimeException('Environment detector function returned invalid value');
        }

        $this->environment = $detectedEnvironment;
    }
}