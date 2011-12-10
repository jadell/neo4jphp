<?php
namespace Everyman\Neo4j\Gremlin;

class QueryTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $query = null;

	protected $queryString = null;
	protected $params = null;

	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array(), array(), '', false);
		$this->queryString = 'i = g.v(start);i.outE.inV';
		$this->params = array('start' => 123);

		$this->query = new Query($this->client, $this->queryString, $this->params);
	}

	public function testGetQuery_ReturnsString()
	{
		$result = $this->query->getQuery();
		$this->assertEquals($result, $this->queryString);
	}

	public function testGetParameters_ReturnsArray()
	{
		$result = $this->query->getParameters();
		$this->assertEquals($result, $this->params);
	}

	public function testGetResultSet_OnlyExecutesOnce_ReturnsResultSet()
	{
		$return = $this->getMock('Everyman\Neo4j\Query\ResultSet', array(), array(), '', false);

		$this->client->expects($this->once())
			->method('executeGremlinQuery')
			->will($this->returnValue($return));

		$this->assertSame($return, $this->query->getResultSet());
		$this->assertSame($return, $this->query->getResultSet());
	}

	public function testGetResultSet_ClientReturnsFalse_ReturnsFalse()
	{
		$return = false;

		$this->client->expects($this->once())
			->method('executeGremlinQuery')
			->will($this->returnValue($return));

		$this->assertFalse($this->query->getResultSet());
		$this->assertFalse($this->query->getResultSet());
	}
}
