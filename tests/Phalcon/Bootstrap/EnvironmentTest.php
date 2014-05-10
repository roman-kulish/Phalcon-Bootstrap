<?php

namespace Phalcon\Bootstrap;

class EnvironmentTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $environment = new Environment();
        $this->assertTrue( $environment->is(Environment::DEVELOPMENT) );

        $environment = new Environment('test');
        $this->assertTrue( $environment->is('test') );

        $environment = new Environment(function() { return 'test'; });
        $this->assertTrue( $environment->is('test') );

        try {
            $environment = new Environment(null);
            $this->fail();
        } catch(\InvalidArgumentException $e) {}
    }

    public function testInvalidDetector()
    {
        $environment = new Environment(function() { throw new \Exception(); });

        try {
            $environment->is('foo');
            $this->fail('Exception in detector test failed');
        } catch(\RuntimeException $e) {}

        $environment = new Environment(function() {});

        try {
            $environment->is('foo');
            $this->fail('Invalid result in detector test failed');
        } catch(\RuntimeException $e) {}
    }

    public function testEnvironmentCompare()
    {
        $environment1 = new Environment('test');
        $environment2 = new Environment(function() { return 'TEST'; });

        try {
            $environment1->is(null);
            $this->fail('Invalid argument in environment compare test failed');
        } catch(\InvalidArgumentException $e) {}

        try {
            $environment1->is('');
            $this->fail('Empty argument in environment compare test failed');
        } catch(\InvalidArgumentException $e) {}

        $this->assertTrue( $environment1->is('test') );
        $this->assertTrue( $environment1->is('TEST') );
        $this->assertTrue( $environment1->is( $environment2 ) );
    }

    /**
     * @expectedException \Exception
     * @runInSeparateProcess
     */
    public function testStringCast()
    {
        $self = $this;

        set_error_handler(function($errno) use($self) {
            if ($errno != E_USER_ERROR) {
                return;
            }

            throw new \Exception(); // exit with expected exception
        });

        $environment = new Environment('test');
        $this->assertEquals($environment, 'test');

        $environment = new Environment(function() {});
        @$environment->__toString(); // let error handler intercept it

        $this->fail('Invalid detector in __toString() failed');
    }
}
 