<?php

namespace Phalcon\Bootstrap;

class ModuleMock extends Module
{
    public function getEnvironment()
    {
        return $this->environment;
    }
}

class BootstrapTest extends \PHPUnit_Framework_TestCase
{
    public function testFactoryInstance()
    {
        try {
            Bootstrap::init(1);
            $this->fail('Injecting invalid value for $container test failed');
        } catch(\InvalidArgumentException $e) {}

        try {
            Bootstrap::init(null, array());
            $this->fail('Injecting invalid value for $environment test failed');
        } catch(\InvalidArgumentException $e) {}

        $bootstrap = Bootstrap::init(array('a' => 1), 'test');

        $this->assertInstanceOf( '\\Phalcon\\Bootstrap\\Container', $bootstrap->getContainer() );
        $this->assertInstanceOf( '\\Phalcon\\Bootstrap\\Environment', $bootstrap->getEnvironment() );

        $this->assertEquals( $bootstrap->getContainer()->a, 1 );
        $this->assertEquals( $bootstrap->getEnvironment(), 'test' );

        return $bootstrap;
    }

    /**
     * @depends testFactoryInstance
     */
    public function testSetContainer(Bootstrap $bootstrap)
    {
        $bootstrap->setContainer(array('b' => 5));

        $this->assertTrue( isset($bootstrap->getContainer()->b) && $bootstrap->getContainer()->b == 5 );
        $this->assertFalse( isset($bootstrap->getContainer()->a) );

        return $bootstrap;
    }

    /**
     * @depends testSetContainer
     */
    public function testSetEnvironment(Bootstrap $bootstrap)
    {
        $bootstrap->setEnvironment('foo');

        $this->assertEquals( $bootstrap->getEnvironment(), 'foo' );

        try {
            $bootstrap->setEnvironment(null);
            $this->fail('Setting invalid environment value test failed');
        } catch(\InvalidArgumentException $e) {}
    }

    public function testAddModule()
    {
        $bootstrap = Bootstrap::init();
        $module = new ModuleMock(function() { echo '*'; });

        try {
            $bootstrap->addModule([]);
            $this->fail('Adding invalid module test failed');
        } catch(\InvalidArgumentException $e) {}

        $bootstrap->addModule(function() { echo '*'; });
        $bootstrap->addModule(function() { echo '*'; });
        $bootstrap->addModule($module, Environment::STAGING);

        $this->assertEquals($module->getEnvironment(), Environment::STAGING);
        $bootstrap->addModule($module);

        return $bootstrap;
    }

    /**
     * @depends testAddModule
     */
    public function testExecute(Bootstrap $bootstrap)
    {
        $this->expectOutputString('**');
        $bootstrap->execute();
    }
}
 