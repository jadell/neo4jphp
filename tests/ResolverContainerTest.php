<?php
/**
 * Created by JetBrains PhpStorm.
 * User: witoo
 * Date: 9/2/13
 * Time: 2:35 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Witooh\Validator\Test;

use Mockery as m;
use Way\Tests\Assert;
use Witooh\Validator\ResolverContainer;

class ResolverContainerTest extends \TestCase
{
    public function testNew()
    {
        $resolverContainer = new ResolverContainer();

        Assert::instance('Witooh\Validator\IResolverContainer', $resolverContainer);
    }

    public function testAddAndHas()
    {
        //Act
        $resolverContainer = new ResolverContainer();
        $resolverContainer->add('a');
        $resolverContainer->add('b');

        //Assert
        Assert::assertTrue($resolverContainer->has('a'));
        Assert::assertTrue($resolverContainer->has('b'));
        Assert::assertFalse($resolverContainer->has('c'));
    }

    public function testResolveAndHas()
    {
        //Arrange
        $this->createApplication();
        $a         = m::mock('Illuminate\Validation\Validator');
        $b         = m::mock('Illuminate\Validation\Validator');
        $resolvers = array(get_class($a), get_class($b));

        //Act
        $resolverContainer = new ResolverContainer();
        $resolverContainer->resolve($resolvers);

        //Assert
        Assert::assertTrue($resolverContainer->has(get_class($a)));
        Assert::assertTrue($resolverContainer->has(get_class($b)));
        Assert::assertFalse($resolverContainer->has('noClass'));
    }

    /**
     * @expectedException \Exception
     */
    public function testResolveClassNotExistException()
    {
        //Arrange
        $resolvers = array('x', 'y', 'z');

        //Act
        $resolverContainer = new ResolverContainer();
        $resolverContainer->resolve($resolvers);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testResolveInvalidaArgumentException()
    {
        //Act
        $resolverContainer = new ResolverContainer();
        $resolverContainer->resolve('123');
    }

}