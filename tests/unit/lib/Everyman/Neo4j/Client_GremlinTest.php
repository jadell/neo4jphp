<?php
namespace Everyman\Neo4j;

class Client_GremlinTest extends \PHPUnit_Framework_TestCase
{
	protected $transport = null;
	protected $client = null;
	protected $endpoint = 'http://foo:1234/db/data';

	public function setUp()
	{
		$this->transport = $this->getMock('Everyman\Neo4j\Transport');
		$this->client = $this->getMock('Everyman\Neo4j\Client', array('getServerInfo'), array($this->transport));
		$this->client->expects($this->any())
			->method('getServerInfo')
			->will($this->returnValue(array(
				'extensions' => array(
					'GremlinPlugin' => array(
						'execute_script' => $this->endpoint.'/ext/GremlinPlugin/graphdb/execute_script',
					)
				)
			)));
	}

	public function testGremlinQuery_ServerReturnsErrorCode_ReturnsFalse()
	{
		$props = array(
			'script' => 'i=g.foo(start);',
			'params' => array('start' => 123),
		);
		$query = new Gremlin\Query($this->client, $props['script'], $props['params']);

		$this->transport->expects($this->once())
			->method('post')
			->with('/ext/GremlinPlugin/graphdb/execute_script', $props)
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('\Everyman\Neo4j\Exception');
		$this->client->executeGremlinQuery($query);
	}

	public function testGremlinQuery_DataAndColumnsReturned_ReturnsResultSet()
	{
		$props = array(
			'script' => 'i=g.foo(start);',
			'params' => array('start' => 123),
		);
		$query = new Gremlin\Query($this->client, $props['script'], $props['params']);

		$this->transport->expects($this->once())
			->method('post')
			->with('/ext/GremlinPlugin/graphdb/execute_script', $props)
			->will($this->returnValue(array('code'=>200,'data'=>array(
				'columns' => array('name','age'),
				'data' => array(
					array('Bob', 12),
					array('Lotta', 0),
					array('Brenda', 14)
				)
			))));

		$result = $this->client->executeGremlinQuery($query);
		$this->assertInstanceOf('Everyman\Neo4j\Query\ResultSet', $result);
		$this->assertEquals('Brenda', $result[2]['name']);
	}

	public function testGremlinQuery_ListOfEntitiesReturned_ReturnsResultSet()
	{
		$props = array('script' => 'i=g.foo();');
		$query = new Gremlin\Query($this->client, $props['script']);

		$this->transport->expects($this->once())
			->method('post')
			->with('/ext/GremlinPlugin/graphdb/execute_script', $props)
			->will($this->returnValue(array('code'=>200,'data'=>array(
				array('self' => 'http://foo:1234/db/data/node/1','data'=>array()),
				array('self' => 'http://foo:1234/db/data/node/2','data'=>array()),
				array('self' => 'http://foo:1234/db/data/node/3','data'=>array()),
			))));

		$result = $this->client->executeGremlinQuery($query);
		$this->assertInstanceOf('Everyman\Neo4j\Query\ResultSet', $result);
		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[1][0]);
		$this->assertEquals(2, $result[1][0]->getId());
	}

	public function testGremlinQuery_SingleEntityReturned_ReturnsResultSet()
	{
		$props = array('script' => 'i=g.foo();');
		$query = new Gremlin\Query($this->client, $props['script']);

		$this->transport->expects($this->once())
			->method('post')
			->with('/ext/GremlinPlugin/graphdb/execute_script', $props)
			->will($this->returnValue(array('code'=>200,'data'=>array(
				'self' => 'http://foo:1234/db/data/node/2',
				'data' => array()
			))));

		$result = $this->client->executeGremlinQuery($query);
		$this->assertInstanceOf('Everyman\Neo4j\Query\ResultSet', $result);
		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[0][0]);
		$this->assertEquals(2, $result[0][0]->getId());
	}

	public function testGremlinQuery_ScalarValueReturned_ReturnsResultSet()
	{
		$props = array('script' => 'i=g.foo();');
		$query = new Gremlin\Query($this->client, $props['script']);

		$this->transport->expects($this->once())
			->method('post')
			->with('/ext/GremlinPlugin/graphdb/execute_script', $props)
			->will($this->returnValue(array('code'=>200,'data'=>"this is some scalar value")));

		$result = $this->client->executeGremlinQuery($query);
		$this->assertInstanceOf('Everyman\Neo4j\Query\ResultSet', $result);
		$this->assertEquals("this is some scalar value", $result[0][0]);
	}

	public function testGremlinQuery_GremlinNotAvailable_ThrowsException()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array('getServerInfo'), array($this->transport));
		$this->client->expects($this->any())
			->method('getServerInfo')
			->will($this->returnValue(array('extensions' => array())));

		$this->transport->expects($this->never())
			->method('post');

		$props = array('script' => 'i=g.foo();');
		$query = new Gremlin\Query($this->client, $props['script']);

		$this->setExpectedException('\Everyman\Neo4j\Exception');
		$this->client->executeGremlinQuery($query);
	}
}
