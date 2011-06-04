<?php
namespace Everyman\Neo4j;

class NodeTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $node = null;

	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array(), array(), '', false);
		$this->node = new Node($this->client);
	}

	public function testProperties_PropertyNotSet_ReturnsNull()
	{
		$this->assertNull($this->node->getProperty('notset'));
	}

	public function testProperties_PropertySet_ReturnsValue()
	{
		$this->node->setProperty('somekey','someval');
		$this->assertEquals('someval', $this->node->getProperty('somekey'));
	}

	public function testProperties_PropertyRemoved_ReturnsNull()
	{
		$this->node->setProperty('somekey','someval');
		$this->node->removeProperty('somekey');
		$this->assertNull($this->node->getProperty('somekey'));
	}

	public function testProperties_BatchSet_ReturnsValues()
	{
		$this->node->setProperties(array(
			'somekey' => 'someval',
			'yakey' => 'yaval',
		));
		$this->assertEquals('someval', $this->node->getProperty('somekey'));
		$this->assertEquals('yaval', $this->node->getProperty('yakey'));
	}

	public function testProperties_GetAllProperties_ReturnsValues()
	{
		$this->node->setProperties(array(
			'somekey' => 'someval',
			'yakey' => 'yaval',
		));
		$this->assertEquals(array(
			'somekey' => 'someval',
			'yakey' => 'yaval',
		), $this->node->getProperties());
	}

	public function testSave_SavesSelfUsingClient()
	{
		$this->client->expects($this->once())
			->method('saveNode')
			->with($this->node)
			->will($this->returnValue(true));

		$this->assertTrue($this->node->save());
	}

	public function testDelete_DeletesSelfUsingClient()
	{
		$this->client->expects($this->once())
			->method('deleteNode')
			->with($this->node)
			->will($this->returnValue(true));

		$this->assertTrue($this->node->delete());
	}
}
