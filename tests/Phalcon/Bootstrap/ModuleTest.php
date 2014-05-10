<?php

namespace Phalcon\Bootstrap;

class ModuleTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        try {
            new Module('foo');
            $this->fail('Constructor invalid argument test failed');
        } catch(\InvalidArgumentException $e) {}

        try {
            new Module([]);
            $this->fail('Constructor invalid argument test failed');
        } catch(\InvalidArgumentException $e) {}

        new Module(function() {});
    }

    public function testEnvironment()
    {
        $module = new Module(function() {});
        $module->setEnvironment('test');
        $module->setEnvironment( new Environment() );

        try {
            $module->setEnvironment(null);
            $this->fail('Environment invalid argument test failed');
        } catch(\InvalidArgumentException $e) {}
    }

    /**
     * @dataProvider provider
     */
    public function testExecute(Container $container)
    {
        $module = new Module(function() {});
        $module->setEnvironment('test');
        $module->execute($container, new Environment('foo'));
    }

    /**
     * @dataProvider provider
     */
    public function testExecuteReflection(Container $container, Environment $environment)
    {
        $test = $this;

        $module = new Module(function(Container $container,
                                      Environment $environment,
                                      \Phalcon\DiInterface $di,
                                      \Phalcon\Mvc\Router $router,
                                      $a = 1,
                                      array $b = null) use($test) {

            $test->assertInstanceOf('\\Phalcon\\Bootstrap\\Container', $container);
            $test->assertInstanceOf('\\Phalcon\\Bootstrap\\Environment', $environment);
            $test->assertInstanceOf('\\Phalcon\\DiInterface', $di);
            $test->assertInstanceOf('\\Phalcon\\Mvc\\Router', $router);
            $test->assertEquals($a, 123);
            $test->assertNull($b);
        });

        $module->execute($container, $environment);

        $container->router = true;

        try {
            $module->execute($container, $environment);
            $this->fail('Ambiguous parameter name test failed');
        } catch(\LogicException $e) {}

        $module = new Module(function($blah) {});

        try {
            $module->execute($container, $environment);
            $this->fail('Invalid parameter test failed');
        } catch(\LogicException $e) {}
    }

    /**
     * @dataProvider provider
     */
    public function testExecuteSpecification(Container $container, Environment $environment)
    {
        $test = $this;

        $module = new Module(['$container', '$environment', '$di', '@router', 'a', 'b' => null,
                              function(Container $container,
                                      Environment $environment,
                                      \Phalcon\DiInterface $di,
                                      \Phalcon\Mvc\Router $router,
                                      $a = 1,
                                      array $b = null) use($test) {

            $test->assertInstanceOf('\\Phalcon\\Bootstrap\\Container', $container);
            $test->assertInstanceOf('\\Phalcon\\Bootstrap\\Environment', $environment);
            $test->assertInstanceOf('\\Phalcon\\DiInterface', $di);
            $test->assertInstanceOf('\\Phalcon\\Mvc\\Router', $router);
            $test->assertEquals($a, 123);
            $test->assertNull($b);
        }]);

        $module->execute($container, $environment);

        unset($container->di);

        try {
            $module->execute($container, $environment);
            $this->fail('Getting a service when DI is not set test failed');
        } catch(\LogicException $e) {};

        $module = new Module([null, function($blah) {}]);

        try {
            $module->execute($container, $environment);
            $this->fail('Invalid parameter test failed');
        } catch(\InvalidArgumentException $e) {}

        $module = new Module(['$unknown', function($blah) {}]);

        try {
            $module->execute($container, $environment);
            $this->fail('Unknown reserved parameter test failed');
        } catch(\InvalidArgumentException $e) {}

        $module = new Module(['unknown', function($blah) {}]);

        try {
            $module->execute($container, $environment);
            $this->fail('Invalid parameter test failed');
        } catch(\LogicException $e) {}
    }

    public function provider()
    {
        return array(array(
            new Container(array(
                'a'  => 123,
                'di' => new \Phalcon\DI\FactoryDefault()
            )),
            new Environment('test')
        ));
    }
}
