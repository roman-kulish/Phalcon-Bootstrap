<?php

namespace Phalcon\Bootstrap;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testContainer()
    {
        $container = new Container(array(
            'a' => 1,
            'b' => true
        ));

        $container->merge(array(
            'c' => null
        ));

        $this->assertEquals($container->a, 1);
        $this->assertTrue($container->b);
        $this->assertNull($container->c);
        $this->assertNull($container->d);

        $container->c = 2;

        $this->assertTrue( isset($container->a) );
        $this->assertTrue( isset($container->b) );
        $this->assertTrue( isset($container->c) );
        $this->assertFalse( isset($container->d) );

        unset($container->c);
        $this->assertFalse( isset($container->c) );

        return $container;
    }

    /**
     * @depends testContainer
     */
    public function testContainerDi(Container $container)
    {
        try {
            $container->setDI('foo');
            $this->fail('Container::setDi() invalid argument test failed');
        } catch(\InvalidArgumentException $e) {}

        $container->di = new \Phalcon\DI\FactoryDefault();

        $this->assertInstanceOf('\\Phalcon\\DiInterface', $container->di);
        $this->assertInstanceOf('\\Phalcon\\DiInterface', $container->getDI());
    }
}
 