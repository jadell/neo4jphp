<?php
namespace Everyman\Neo4j;

class Client_CypherTest extends \PHPUnit_Framework_TestCase
{
	protected $transport = null;
	protected $client = null;
	protected $endpoint = 'http://foo:1234/db/data';

	public function setUp()
	{
		$this->transport = $this->getMock('Everyman\Neo4j\Transport');
		$this->transport->expects($this->any())
			->method('getEndpoint')
			->will($this->returnValue($this->endpoint));
		$this->client = new Client($this->transport);
	}

	/**
	 * @dataProvider dataProvider_TestCypherQuery
	 */
	public function testCypherQuery($returnValue, $resultCount)
	{
		$props = array(
			'query' => 'START a=({start}) MATCH (a)->(b) WHERE b.name = {name} RETURN b',
			'params' => array('start' => 1, 'name' => 'friend name'),
		);

		$this->transport->expects($this->once())
			->method('get')
			->with('/')
			->will($this->returnValue(array('code'=>200, 'data'=>array(
				'neo4j_version' => '1.5.foo',
				'extensions' => array('CypherPlugin' => array(
					'execute_query' => $this->endpoint.'/ext/CypherPlugin/graphdb/execute_query'
				)),
			))));
		$this->transport->expects($this->once())
			->method('post')
			->with('/ext/CypherPlugin/graphdb/execute_query', $props)
			->will($this->returnValue($returnValue));

		$query = new Cypher\Query($this->client, $props['query'], $props['params']);

		$result = $this->client->executeCypherQuery($query);
		$this->assertInstanceOf('\Everyman\Neo4j\Query\ResultSet', $result);
		$this->assertEquals(count($result), $resultCount);
	}

	/**
	 * @dataProvider dataProvider_TestCypherQuery
	 */
	public function testCypherQuery_NewEndpoint($returnValue, $resultCount)
	{
		$props = array(
			'query' => 'START a=({start}) MATCH (a)->(b) WHERE b.name = {name} RETURN b',
			'params' => array('start' => 1, 'name' => 'friend name'),
		);

		$this->transport->expects($this->once())
			->method('get')
			->with('/')
			->will($this->returnValue(array('code'=>200, 'data'=>array(
				'neo4j_version' => '1.5.foo',
				'cypher' => $this->endpoint.'/cypher',
			))));
		$this->transport->expects($this->once())
			->method('post')
			->with('/cypher', $props)
			->will($this->returnValue($returnValue));

		$query = new Cypher\Query($this->client, $props['query'], $props['params']);

		$result = $this->client->executeCypherQuery($query);
		$this->assertInstanceOf('\Everyman\Neo4j\Query\ResultSet', $result);
		$this->assertEquals(count($result), $resultCount);
	}

	/**
	 * Test for http://github.com/jadell/neo4jphp/issues/63
	 * @dataProvider dataProvider_TestCypherQuery
	 */
	public function testCypherQuery_ProxyHost($returnValue, $resultCount)
	{
		$proxyEndpoint = 'http://proxy.me:1234/db/data';
		$transport = $this->getMock('Everyman\Neo4j\Transport');
		$transport->expects($this->any())
			->method('getEndpoint')
			->will($this->returnValue($proxyEndpoint));
		$client = new Client($transport);

		$props = array(
			'query' => 'START a=({start}) MATCH (a)->(b) WHERE b.name = {name} RETURN b',
			'params' => array('start' => 1, 'name' => 'friend name'),
		);

		$transport->expects($this->once())
			->method('get')
			->with('/')
			->will($this->returnValue(array('code'=>200, 'data'=>array(
				'neo4j_version' => '1.5.foo',
				'cypher' => $this->endpoint.'/cypher',
			))));
		$transport->expects($this->once())
			->method('post')
			->with('/cypher', $props)
			->will($this->returnValue($returnValue));

		$query = new Cypher\Query($client, $props['query'], $props['params']);

		$result = $client->executeCypherQuery($query);
		$this->assertInstanceOf('\Everyman\Neo4j\Query\ResultSet', $result);
		$this->assertEquals(count($result), $resultCount);
	}

	public function dataProvider_TestCypherQuery()
	{
		$return = array(
			'columns' => array('name','age'),
			'data' => array(
				array('Bob', 12),
				array('Lotta', 0),
				array('Brenda', 14)
			)
		);

		return array(
			array(array('code'=>204,'data'=>null), 0),
			array(array('code'=>200,'data'=>$return), 3),
		);
	}

	public function testCypherQuery_ServerReturnsErrorCode_ThrowsException()
	{
		$props = array(
			'query' => 'START a=(0) RETURN a'
		);

		$this->transport->expects($this->once())
			->method('get')
			->with('/')
			->will($this->returnValue(array('code'=>200, 'data'=>array(
				'neo4j_version' => '1.5.foo',
				'extensions' => array('CypherPlugin' => array(
					'execute_query' => $this->endpoint.'/ext/CypherPlugin/graphdb/execute_query'
				)),
			))));
		$this->transport->expects($this->once())
			->method('post')
			->with('/ext/CypherPlugin/graphdb/execute_query', $props)
			->will($this->returnValue(array('code'=>404)));

		$query = new Cypher\Query($this->client, $props['query']);

		$this->setExpectedException('\Everyman\Neo4j\Exception');
		$this->client->executeCypherQuery($query);
	}

	public function testCypherQuery_CypherNotAvailable_ThrowsException()
	{
		$this->transport->expects($this->once())
			->method('get')
			->with('/')
			->will($this->returnValue(array('code'=>200, 'data'=>array(
				'neo4j_version' => '1.5.foo',
			))));
		$this->transport->expects($this->never())
			->method('post');

		$query = new Cypher\Query($this->client, 'query');

		$this->setExpectedException('\Everyman\Neo4j\Exception');
		$this->client->executeCypherQuery($query);
	}
}
