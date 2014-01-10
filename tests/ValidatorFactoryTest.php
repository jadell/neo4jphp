<?php

namespace Witooh\Validator\Test;

use Mockery as m;
use Witooh\Validator\Validator;
use Witooh\Validator\ResolverContainer;
use Way\Tests\Assert;
use Witooh\Validator\ValidatorFactory;

class ValidatorFactoryTest extends \TestCase
{
    /**
     * @var \Witooh\Validator\IResolverContainer
     */
    protected $resolverContainer;

    public function setUp()
    {
        parent::setUp();
        $this->resolverContainer = m::mock('Witooh\Validator\IResolverContainer');
        $this->resolverContainer->shouldReceive('resolve')->andReturnNull();
    }

    public function testNew(){
        //Act
        $validatorFactory = new ValidatorFactory($this->resolverContainer);

        Assert::instance('Witooh\Validator\ValidatorFactory', $validatorFactory);
    }

    public function testMakeWithExistClass()
    {
        //Arrange
        $mock = m::mock('Witooh\Validator\IBaseValidator');

        //Act
        $validatorFactory = new ValidatorFactory($this->resolverContainer);
        $validatorClass   = $validatorFactory->make(get_class($mock), array());

        //Assert
        Assert::instance('Witooh\Validator\IBaseValidator', $validatorClass);
    }

    /**
     * @expectedException \ReflectionException
     */
    public function testMakeWithOutExistClass()
    {
        //Act
        $validatorFactory = new ValidatorFactory($this->resolverContainer);
        $validatorFactory->make('TestValidator', array());
    }
}