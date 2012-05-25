<?php
namespace Everyman\Neo4j;

class PropertyContainerTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $entity = null;

	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array(), array(), '', false);
		$this->entity = $this->getMock('Everyman\Neo4j\PropertyContainer',
			array('delete','save','load'), array($this->client));
	}
	
	public function testProperties_MagicNotSet_ReturnsNull()
	{
		$this->assertNull($this->entity->notset);
	}

	public function testProperties_MagicSet_ReturnsValue()
	{
		$this->entity->somekey = 'someval';
		$this->assertEquals('someval', $this->entity->getProperty('somekey'));
	}

	public function testProperties_MagicRemoved_ReturnsNull()
	{
		$this->entity->setProperty('somekey', 'someval');
		unset($this->entity->somekey);
		$this->assertNull($this->entity->getProperty('somekey'));
	}

	public function testProperties_PropertyNotSet_ReturnsNull()
	{
		$this->assertNull($this->entity->getProperty('notset'));
	}

	public function testProperties_PropertySet_ReturnsValue()
	{
		$this->entity->setProperty('somekey','someval');
		$this->assertEquals('someval', $this->entity->getProperty('somekey'));
	}

	public function testProperties_PropertyRemoved_ReturnsNull()
	{
		$this->entity->setProperty('somekey','someval');
		$this->entity->removeProperty('somekey');
		$this->assertNull($this->entity->getProperty('somekey'));
	}

	public function testProperties_BatchSet_ReturnsValues()
	{
		$this->entity->setProperties(array(
			'somekey' => 'someval',
			'yakey' => 'yaval',
		));
		$this->assertEquals('someval', $this->entity->getProperty('somekey'));
		$this->assertEquals('yaval', $this->entity->getProperty('yakey'));
	}

	public function testProperties_GetAllProperties_ReturnsValues()
	{
		$this->entity->setProperties(array(
			'somekey' => 'someval',
			'yakey' => 'yaval',
		));
		$this->assertEquals(array(
			'somekey' => 'someval',
			'yakey' => 'yaval',
		), $this->entity->getProperties());
	}

	public function testProperties_SetPropertyNullValue_ReturnsNullAndPropertyRemoved()
	{
		$this->entity->setProperties(array(
			'somekey' => 'someval',
			'yakey' => 'yaval',
		));

		$this->entity->setProperty('somekey', null);
		$this->assertNull($this->entity->getProperty('somekey'));

		$this->assertEquals(array(
			'yakey' => 'yaval',
		), $this->entity->getProperties());
	}

	public function testProperties_LazyLoad_OnlyLoadsTheFirstTime()
	{
		$this->entity->expects($this->once())
			->method('load');
		
		$this->entity->setId(123);
		$this->entity->getProperties();
		$this->entity->getProperties();
	}

	public function testSetGetId_IntegerId_ReturnsInteger()
	{
		$this->entity->setId(123);
		$this->assertTrue($this->entity->hasId());
		$this->assertEquals(123, $this->entity->getId());
	}

	public function testSetGetId_ZeroIdIsValid_ReturnsInteger()
	{
		$this->entity->setId(0);
		$this->assertTrue($this->entity->hasId());
		$this->assertEquals(0, $this->entity->getId());
	}

	public function testSetGetId_NullValid_ReturnsNull()
	{
		$this->entity->setId(null);
		$this->assertFalse($this->entity->hasId());
		$this->assertNull($this->entity->getId());
	}

	public function testSetGetId_NonIntegerCastToInteger_ReturnsInteger()
	{
		$this->entity->setId('temp');
		$this->assertTrue($this->entity->hasId());
		$this->assertEquals(0, $this->entity->getId());
	}
}
