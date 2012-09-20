<?php
namespace Everyman\Neo4j;

class DITest extends \PHPUnit_Framework_TestCase
{

    public function testRegisterScalar()
    {

        DI::register("testScalar", 42);
        $this->assertEquals(DI::resolve("testScalar"), 42);
        DI::unregister("testScalar");
    }

    public function testRegisterClosure()
    {
        DI::register("testClosure", function() {
            return 42;
        });

        $this->assertEquals(DI::resolve("testClosure"), 42);
        DI::unregister("testClosure");

        DI::register("testClosureWithParams", function ($a, $b) {
            return $a+$b;
        });

        $this->assertEquals(DI::resolve("testClosureWithParams", array(2,40)), 42);
        DI::unregister("testClosureWithParams");
    }

    public function testSingleton()
    {
        DI::register("testSingleton", function() {return new \StdClass();}, true);

        $singleton1 = DI::resolve("testSingleton");
        $singleton1->test = 42;

        $singleton2 = DI::resolve("testSingleton");
        $this->assertEquals($singleton2->test, 42);

        $singleton2->test++;
        $this->assertEquals($singleton1->test, 43);

        DI::unregister("testSingleton");
    }

    public function testUnregister()
    {
        DI::register("testScalar", 42);
        $this->assertTrue(DI::isRegistered("testScalar"));

        DI::unregister("testScalar");
        $this->assertFalse(DI::isRegistered("testScalar"));

    }

    public function testExceptionForUnexistingEntry()
    {
        $this->setExpectedException('Everyman\Neo4j\Exception');
        DI::resolve("unexistingEntry");
    }

}