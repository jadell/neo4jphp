<?php

namespace Everyman\Neo4j\Test\Command;

use Everyman\Neo4j\Client;
use ReflectionProperty;
use ReflectionMethod;
use Everyman\Neo4j\Index;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Command\AddToIndexUnique;

/**
 * Test AddToIndexUnique Command
 */
class AddToIndexUniqueTest extends \PHPUnit_Framework_TestCase
{
	/** @var AddToIndexUnique|\PHPUnit_Framework_MockObject_MockObject */
	protected $instance;

	/** @var Client|\PHPUnit_Framework_MockObject_MockObject */
	protected $clientMock;

	/** @var Index */
	protected $index;

	/** @var PropertyContainer|\PHPUnit_Framework_MockObject_MockObject */
	protected $entityMock;

	protected $key;
	protected $value;
	protected $type = null;

	public function setUp()
	{
		$this->clientMock = $this->getMock('Everyman\Neo4j\Client', array(), array(), '', false);
		$this->index = new Index($this->clientMock, Index::TypeNode, 'blah');
		$this->entityMock = $this->getMock('Everyman\Neo4j\Node', array(), array(), '', false);
	}

	public function tearDown()
	{
		$this->clientMock = null;
		$this->index = null;
		$this->entityMock = null;
	}

	protected function createInstance($key, $value, $type = null)
	{
		$this->key = $key;
		$this->value = $value;
		$this->type = $type;
		return $this->getMock(
			'Everyman\Neo4j\Command\AddToIndexUnique',
			array('getEntityMapper'),
			array($this->clientMock, $this->index, $this->entityMock, $this->key, $this->value, $this->type)
		);
	}

	/**
	 * test exception
	 *
	 * this works with phpunit <=3.7, in 3.6 there is an error that cathing the overall exception is not allowed
	 *
	 * @expectedException Exception
	 */
	public function testGetDataInvalidKey()
	{
		$instance = $this->createInstance(null, 'nice value');

		$reflection = new ReflectionMethod($instance, 'getData');
		$reflection->setAccessible(true);
		$reflection->invoke($instance);
	}

	public function testGetData()
	{
		$instance = $this->createInstance('awesome key', 'mega-value');
		$properties = array('nice-prop' => 'dude');

		$this->entityMock->expects($this->once())
			->method('getProperties')
			->will($this->returnValue($properties));

		$expectedResult = array(
			'key' => $this->key,
			'value' => $this->value,
			'properties' => $properties
		);

		$reflection = new ReflectionMethod($instance, 'getData');
		$reflection->setAccessible(true);
		$this->assertEquals($expectedResult, $reflection->invoke($instance));
	}

	public function testGetPathNull()
	{
		$instance = $this->createInstance(null, null, null);

		$reflection = new ReflectionMethod($instance, 'getPath');
		$reflection->setAccessible(true);

		$this->assertRegExp('/.+\?unique$/', $reflection->invoke($instance));
	}

	public function testGetPathCreate()
	{
		$instance = $this->createInstance(null, null, Index::GetOrCreate);

		$reflection = new ReflectionMethod($instance, 'getPath');
		$reflection->setAccessible(true);

		$this->assertRegExp('/.+\?uniqueness=get_or_create$/', $reflection->invoke($instance));
	}

	public function testGetPathFail()
	{
		$instance = $this->createInstance(null, null, Index::CreateOrFail);

		$reflection = new ReflectionMethod($instance, 'getPath');
		$reflection->setAccessible(true);

		$this->assertRegExp('/.+\?uniqueness=create_or_fail$/', $reflection->invoke($instance));
	}

	protected function setupHandleResult()
	{
		$instance = $this->createInstance(null, null);

		$entityMapperMock = $this->getMock('Everyman\Neo4j\EntityMapper', array(), array(), '', false);
		$instance->expects($this->any())
			->method('getEntityMapper')
			->will($this->returnValue($entityMapperMock));
		$entityMapperMock->expects($this->any())
			->method('populateNode')
			->will($this->returnValue(true));
		$entityMapperMock->expects($this->any())
			->method('getIdFromUri')
			->will($this->returnValue(666));
		$this->entityMock->expects($this->any())
			->method('setId')
			->will($this->returnValue(666));

		return $instance;
	}

	public function testHandleResult()
	{
		$instance = $this->setupHandleResult();
		$reflection = new ReflectionMethod($instance, 'handleResult');
		$reflection->setAccessible(true);

		$this->assertTrue($reflection->invoke($instance, 200, null, null));
		$this->assertTrue($reflection->invoke($instance, 201, null, null));
	}

	public function testHandleResult409()
	{
		$instance = $this->setupHandleResult();
		$reflection = new ReflectionMethod($instance, 'handleResult');
		$reflection->setAccessible(true);

		$this->setExpectedException('Exception', 'Node already exists!');
		$reflection->invoke($instance, 409, null, null);
	}

	public function testHandleResultException()
	{
		$instance = $this->setupHandleResult();
		$reflection = new ReflectionMethod($instance, 'handleResult');
		$reflection->setAccessible(true);

		$this->setExpectedException('Exception', 'Unable to add entity to index');
		$reflection->invoke($instance, 666, null, null);
	}
}