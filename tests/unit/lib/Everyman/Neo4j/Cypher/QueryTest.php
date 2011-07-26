<?php
namespace Everyman\Neo4j\Cypher;

class QueryTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $query = null;

	protected $template = null;
	protected $vars = null;

	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array(), array(), '', false);
		$this->template = 'START a=(?) RETURN a';
		$this->vars = array(0);

		$this->query = new Query($this->client, $this->template, $this->vars);
	}

	public function testGetQuery_ReturnsString()
	{
		$expected = 'START a=(0) RETURN a';
		$result = $this->query->getQuery();
		$this->assertEquals($result, $expected);
	}

	public function testGetQuery_NoVars_ReturnsString()
	{
		$template = 'START a=(0) RETURN a';
		$query = new Query($this->client, $template);

		$result = $this->query->getQuery();
		$this->assertEquals($result, $template);
	}

	public function testGetResultSet_OnlyExecutesOnce_ReturnsResultSet()
	{
		$return = $this->getMock('Everyman\Neo4j\Query\ResultSet', array(), array(), '', false);

		$this->client->expects($this->once())
			->method('executeCypherQuery')
			->will($this->returnValue($return));

		$this->assertSame($return, $this->query->getResultSet());
		$this->assertSame($return, $this->query->getResultSet());
	}

	public function testGetResultSet_ClientReturnsFalse_ReturnsFalse()
	{
		$return = false;

		$this->client->expects($this->once())
			->method('executeCypherQuery')
			->will($this->returnValue($return));

		$this->assertFalse($this->query->getResultSet());
		$this->assertFalse($this->query->getResultSet());
	}
}
