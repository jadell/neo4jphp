<?php
namespace Everyman\Neo4j;

class Client_IndexTest extends \PHPUnit_Framework_TestCase
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

	public function testSaveIndex_UnknownIndexType_ThrowsException()
	{
		$index = new Index($this->client, 'FOO', 'indexname');
		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->saveIndex($index);
	}

	public function testSaveIndex_NoName_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, null);
		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->saveIndex($index);
	}

	/**
	 * @dataProvider dataProvider_SaveIndexScenarios
	 */
	public function testSaveIndex_ReturnsCorrectSuccessOrFailure($type, $name, $result, $success, $error)
	{
		$index = new Index($this->client, $type, $name);

		$this->transport->expects($this->once())
			->method('post')
			->with('/index/'.$type, array(
				'name' => $name,
			))
			->will($this->returnValue($result));

		$this->assertEquals($success, $this->client->saveIndex($index));
		$this->assertEquals($error, $this->client->getLastError());
	}

	public function dataProvider_SaveIndexScenarios()
	{
		return array(// type, name, result, success, error
			array(Index::TypeNode, 'somekey', array('code'=>201), true, null),
			array(Index::TypeRelationship, 'somekey', array('code'=>201), true, null),
			array(Index::TypeNode, 'somekey', array('code'=>400), false, Client::ErrorBadRequest),
		);
	}

	public function testDeleteIndex_UnknownIndexType_ThrowsException()
	{
		$index = new Index($this->client, 'FOO', 'indexname');
		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->deleteIndex($index);
	}

	public function testDeleteIndex_NoName_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, null);
		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->deleteIndex($index);
	}

	/**
	 * @dataProvider dataProvider_SaveIndexScenarios
	 */
	public function testDeleteIndex_ReturnsCorrectSuccessOrFailure($type, $name, $result, $success, $error)
	{
		$index = new Index($this->client, $type, $name);

		$this->transport->expects($this->once())
			->method('delete')
			->with('/index/'.$type.'/'.$name)
			->will($this->returnValue($result));

		$this->assertEquals($success, $this->client->deleteIndex($index));
		$this->assertEquals($error, $this->client->getLastError());
	}

	public function testDeleteIndex_UrlEntities_ReturnsCorrectSuccess()
	{
		$index = new Index($this->client, Index::TypeNode, 'ind@ex na$me');

		$this->transport->expects($this->once())
			->method('delete')
			->with('/index/node/ind%40ex+na%24me')
			->will($this->returnValue(array('code'=>200)));

		$this->assertTrue($this->client->deleteIndex($index));
		$this->assertNull($this->client->getLastError());
	}

	public function testAddToIndex_UnknownIndexType_ThrowsException()
	{
		$index = new Index($this->client, 'FOO', 'indexname');
		$node = new Node($this->client);

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->addToIndex($index, $node, 'somekey', 'somevalue');
	}

	public function dataProvider_AddToIndexScenarios_NoName_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, null);
		$node = new Node($this->client);

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->addToIndex($index, $node, 'somekey', 'somevalue');
	}

	public function dataProvider_AddToIndexScenarios_WrongEntityType_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeRelationship, 'indexname');
		$node = new Node($this->client);

		$this->setExpectedException('\Everyman\Neo4j\Exception');
		$this->client->addToIndex($index, $node, 'somekey', 'somevalue');
	}

	/**
	 * @dataProvider dataProvider_AddToIndexScenarios
	 */
	public function testAddToIndex_ReturnsCorrectSuccessOrFailure($result, $success, $error)
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');
		$node = new Node($this->client);
		$node->setId(123);

		$data = array(
			'key'   => 'somekey',
			'value' => 'somevalue',
			'uri'   => $this->endpoint.'/node/123',
		);

		$this->transport->expects($this->once())
			->method('post')
			->with('/index/node/indexname', $data)
			->will($this->returnValue($result));

		$this->assertEquals($success, $this->client->addToIndex($index, $node, 'somekey', 'somevalue'));
		$this->assertEquals($error, $this->client->getLastError());
	}
	
	public function dataProvider_AddToIndexScenarios()
	{
		return array(// result, success, error
			array(array('code'=>201), true, null),
			array(array('code'=>400), false, Client::ErrorBadRequest),
		);
	}

	public function testAddToIndex_UrlEntities_ReturnsCorrectSuccess()
	{
		$index = new Index($this->client, Index::TypeNode, 'index name');
		$node = new Node($this->client);
		$node->setId(123);

		$data = array(
			'key'   => 'some@key',
			'value' => 'some$value',
			'uri'   => $this->endpoint.'/node/123',
		);

		$this->transport->expects($this->once())
			->method('post')
			->with('/index/node/index+name', $data)
			->will($this->returnValue(array('code'=>200)));

		$this->assertTrue($this->client->addToIndex($index, $node, 'some@key', 'some$value'));
		$this->assertNull($this->client->getLastError());
	}

	public function testAddToIndex_BadIndexName_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, null);
		$node = new Node($this->client);
		$node->setId(123);

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->addToIndex($index, $node, 'somekey', 'somevalue');
	}

	public function testAddToIndex_EntityNotPersisted_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');
		$node = new Node($this->client);

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->addToIndex($index, $node, 'somekey', 'somevalue');
	}

	public function testAddToIndex_BadType_ThrowsException()
	{
		$index = new Index($this->client, 'FOOTYPE', 'indexname');
		$node = new Node($this->client);
		$node->setId(123);

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->addToIndex($index, $node, 'somekey', 'somevalue');
	}

	public function testAddToIndex_BadKey_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');
		$node = new Node($this->client);
		$node->setId(123);

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->addToIndex($index, $node, null, 'somevalue');
	}

	public function testAddToIndex_RelationshipTypeMismatch_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeRelationship, 'indexname');
		$node = new Node($this->client);
		$node->setId(123);

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->addToIndex($index, $node, 'somekey', 'somevalue');
	}

	public function testAddToIndex_NodeTypeMismatch_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');
		$rel = new Relationship($this->client);
		$rel->setId(123);

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->addToIndex($index, $rel, 'somekey', 'somevalue');
	}

	/**
	 * @dataProvider dataProvider_RemoveFromIndexScenarios
	 */
	public function testRemoveFromIndex_ReturnsCorrectSuccessOrFailure($key, $value, $path, $result, $success, $error)
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');
		$node = new Node($this->client);
		$node->setId(123);

		$this->transport->expects($this->once())
			->method('delete')
			->with('/index/node/indexname'.$path.'/123')
			->will($this->returnValue($result));

		$this->assertEquals($success, $this->client->removeFromIndex($index, $node, $key, $value));
		$this->assertEquals($error, $this->client->getLastError());
	}
	
	public function dataProvider_RemoveFromIndexScenarios()
	{
		return array(// key, value, path, result, success, error
			array('somekey', 'somevalue', '/somekey/somevalue', array('code'=>201), true, null),
			array('somekey', 'somevalue', '/somekey/somevalue', array('code'=>404), true, null),
			array('somekey', null, '/somekey', array('code'=>201), true, null),
			array(null, null, '', array('code'=>201), true, null),
			array('somekey', 'somevalue', '/somekey/somevalue', array('code'=>400), false, Client::ErrorBadRequest),
			array('some key@', 'som$e value', '/some+key%40/som%24e+value', array('code'=>201), true, null),
		);
	}

	public function testRemoveFromIndex_BadIndexName_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, null);
		$node = new Node($this->client);
		$node->setId(123);

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->removeFromIndex($index, $node);
	}

	public function testRemoveFromIndex_EntityNotPersisted_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');
		$node = new Node($this->client);

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->removeFromIndex($index, $node);
	}

	public function testRemoveFromIndex_BadType_ThrowsException()
	{
		$index = new Index($this->client, 'FOOTYPE', 'indexname');
		$node = new Node($this->client);
		$node->setId(123);

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->removeFromIndex($index, $node);
	}

	public function testRemoveFromIndex_RelationshipTypeMismatch_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeRelationship, 'indexname');
		$node = new Node($this->client);
		$node->setId(123);

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->removeFromIndex($index, $node);
	}

	public function testRemoveFromIndex_NodeTypeMismatch_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');
		$rel = new Relationship($this->client);
		$rel->setId(123);

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->removeFromIndex($index, $rel);
	}

	public function testSearchIndex_BadType_ThrowsException()
	{
		$index = new Index($this->client, 'badtype', 'indexname');

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->searchIndex($index, 'somekey', 'somevalue');
	}

	public function testSearchIndex_NoIndexName_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, null);

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->searchIndex($index, 'somekey', 'somevalue');
	}

	public function testSearchIndex_NoKeySpecified_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->searchIndex($index, null, 'somevalue');
	}

	public function testSearchIndex_Error_ReturnsFalse()
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');

		$this->transport->expects($this->once())
			->method('get')
			->with('/index/node/indexname/somekey/somevalue')
			->will($this->returnValue(array('code'=>400)));

		$result = $this->client->searchIndex($index, 'somekey', 'somevalue');
		$this->assertFalse($result);
		$this->assertEquals(Client::ErrorBadRequest, $this->client->getLastError());
	}

	public function testSearchIndex_NodesFound_ReturnsArray()
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');

		$return = array(
			array(
				"self" => "http://localhost:7474/db/data/node/123",
				"data" => array("foo"=>"bar"),
			),
			array(
				"self" => "http://localhost:7474/db/data/node/456",
				"data" => array("baz"=>"qux"),
			)
		);

		$this->transport->expects($this->once())
			->method('get')
			->with('/index/node/indexname/somekey/somevalue')
			->will($this->returnValue(array('code'=>200,'data'=>$return)));

		$result = $this->client->searchIndex($index, 'somekey', 'somevalue');
		$this->assertEquals(2, count($result));
		$this->assertNull($this->client->getLastError());

		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[0]);
		$this->assertEquals(123, $result[0]->getId());
		$this->assertEquals(array('foo'=>'bar'), $result[0]->getProperties());

		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[1]);
		$this->assertEquals(456, $result[1]->getId());
		$this->assertEquals(array('baz'=>'qux'), $result[1]->getProperties());
	}

	public function testSearchIndex_RelationshipsFound_ReturnsArray()
	{
		$index = new Index($this->client, Index::TypeRelationship, 'indexname');

		$return = array(
			array(
				"start" => "http://localhost:7474/db/data/node/123",
				"end" => "http://localhost:7474/db/data/node/456",
				"self" => "http://localhost:7474/db/data/relationship/789",
				"type" => "FOOTYPE",
				"data" => array("foo"=>"bar"),
			),
		);

		$this->transport->expects($this->once())
			->method('get')
			->with('/index/relationship/indexname/somekey/somevalue')
			->will($this->returnValue(array('code'=>200,'data'=>$return)));

		$result = $this->client->searchIndex($index, 'somekey', 'somevalue');
		$this->assertEquals(1, count($result));
		$this->assertNull($this->client->getLastError());

		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $result[0]);
		$this->assertEquals(789, $result[0]->getId());
		$this->assertEquals(array('foo'=>'bar'), $result[0]->getProperties());

		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[0]->getStartNode());
		$this->assertEquals(123, $result[0]->getStartNode()->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[0]->getEndNode());
		$this->assertEquals(456, $result[0]->getEndNode()->getId());
	}

	public function testSearchIndex_UrlEntities_ReturnsArray()
	{
		$index = new Index($this->client, Index::TypeRelationship, 'index name');

		$return = array(
			array(
				"start" => "http://localhost:7474/db/data/node/123",
				"end" => "http://localhost:7474/db/data/node/456",
				"self" => "http://localhost:7474/db/data/relationship/789",
				"type" => "FOOTYPE",
				"data" => array("foo"=>"bar"),
			),
		);

		$this->transport->expects($this->once())
			->method('get')
			->with('/index/relationship/index+name/some%40key/some%24value')
			->will($this->returnValue(array('code'=>200,'data'=>$return)));

		$result = $this->client->searchIndex($index, 'some@key', 'some$value');
		$this->assertEquals(1, count($result));
		$this->assertNull($this->client->getLastError());
	}

	public function testQueryIndex_BadType_ThrowsException()
	{
		$index = new Index($this->client, 'badtype', 'indexname');

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->queryIndex($index, 'somekey:somevalue*');
	}


	public function testQueryIndex_NoIndexName_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, null);

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->queryIndex($index, 'somekey:somevalue*');
	}


	public function testQueryIndex_NoQuerySpecified_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->queryIndex($index, null);
	}


	public function testQueryIndex_Error_ReturnsFalse()
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');

		$this->transport->expects($this->once())
			->method('get')
			->with('/index/node/indexname?query='.urlencode('somekey:somevalue*'))
			->will($this->returnValue(array('code'=>400)));

		$result = $this->client->queryIndex($index, 'somekey:somevalue*');
		$this->assertFalse($result);
		$this->assertEquals(Client::ErrorBadRequest, $this->client->getLastError());
	}


	public function testQueryIndex_NodesFound_ReturnsArray()
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');

		$return = array(
			array(
				"self" => "http://localhost:7474/db/data/node/123",
				"data" => array("foo"=>"bar"),
			),
			array(
				"self" => "http://localhost:7474/db/data/node/456",
				"data" => array("baz"=>"qux"),
			)
		);

		$this->transport->expects($this->once())
			->method('get')
			->with('/index/node/indexname?query='.urlencode('somekey:somevalue*'))
			->will($this->returnValue(array('code'=>200,'data'=>$return)));

		$result = $this->client->queryIndex($index, 'somekey:somevalue*');
		$this->assertEquals(2, count($result));
		$this->assertNull($this->client->getLastError());

		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[0]);
		$this->assertEquals(123, $result[0]->getId());
		$this->assertEquals(array('foo'=>'bar'), $result[0]->getProperties());

		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[1]);
		$this->assertEquals(456, $result[1]->getId());
		$this->assertEquals(array('baz'=>'qux'), $result[1]->getProperties());
	}


	public function testQueryIndex_RelationshipsFound_ReturnsArray()
	{
		$index = new Index($this->client, Index::TypeRelationship, 'indexname');

		$return = array(
			array(
				"start" => "http://localhost:7474/db/data/node/123",
				"end" => "http://localhost:7474/db/data/node/456",
				"self" => "http://localhost:7474/db/data/relationship/789",
				"type" => "FOOTYPE",
				"data" => array("foo"=>"bar"),
			),
		);

		$this->transport->expects($this->once())
			->method('get')
			->with('/index/relationship/indexname?query='.urlencode('somekey:somevalue*'))
			->will($this->returnValue(array('code'=>200,'data'=>$return)));

		$result = $this->client->queryIndex($index, 'somekey:somevalue*');
		$this->assertEquals(1, count($result));
		$this->assertNull($this->client->getLastError());

		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $result[0]);
		$this->assertEquals(789, $result[0]->getId());
		$this->assertEquals(array('foo'=>'bar'), $result[0]->getProperties());

		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[0]->getStartNode());
		$this->assertEquals(123, $result[0]->getStartNode()->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[0]->getEndNode());
		$this->assertEquals(456, $result[0]->getEndNode()->getId());
	}
		
	public function testGetIndexes_ServerReturnsErrorCode_ReturnsFalse()
	{
		$this->transport->expects($this->once())
			->method('get')
			->with('/index/node')
			->will($this->returnValue(array('code'=>Client::ErrorBadRequest)));

		$result = $this->client->getIndexes(Index::TypeNode);
		$this->assertFalse($result);
		$this->assertEquals(Client::ErrorBadRequest, $this->client->getLastError());
	}

	public function testGetIndexes_BadType_ThrowsException()
	{
		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->getIndexes('foo');
	}

	public function testGetIndexes_NoIndexes_ReturnsEmptyArray()
	{
		$this->transport->expects($this->once())
			->method('get')
			->with('/index/node')
			->will($this->returnValue(array('code'=>200, 'data'=>'')));

		$results = $this->client->getIndexes(Index::TypeNode);
		$this->assertInternalType('array', $results);
		$this->assertEquals(0, count($results));
	}

	public function testGetIndexes_NodeType_ReturnsArray()
	{
		$this->transport->expects($this->once())
			->method('get')
			->with('/index/node')
			->will($this->returnValue(array('code'=>200, 'data'=>array(
			'favorites' => array('template' =>'http://0.0.0.0:7474/db/data/index/node/favorites/{key}/{value}'),
			'users' => array('template' =>'http://0.0.0.0:7474/db/data/index/node/users/{key}/{value}'),
		))));

		$results = $this->client->getIndexes(Index::TypeNode);
		$this->assertEquals(2, count($results));

		$this->assertInstanceOf('Everyman\Neo4j\Index', $results[0]);
		$this->assertEquals(Index::TypeNode, $results[0]->getType());
		$this->assertEquals('favorites', $results[0]->getName());

		$this->assertInstanceOf('Everyman\Neo4j\Index', $results[1]);
		$this->assertEquals(Index::TypeNode, $results[1]->getType());
		$this->assertEquals('users', $results[1]->getName());
	}

	public function testGetIndexes_RelationshipType_ReturnsArray()
	{
		$this->transport->expects($this->once())
			->method('get')
			->with('/index/relationship')
			->will($this->returnValue(array('code'=>200, 'data'=>array(
			'favorites' => array('template' =>'http://0.0.0.0:7474/db/data/index/relationship/favorites/{key}/{value}'),
			'users' => array('template' =>'http://0.0.0.0:7474/db/data/index/relationship/users/{key}/{value}'),
		))));

		$results = $this->client->getIndexes(Index::TypeRelationship);
		$this->assertEquals(2, count($results));

		$this->assertInstanceOf('Everyman\Neo4j\Index', $results[0]);
		$this->assertEquals(Index::TypeRelationship, $results[0]->getType());
		$this->assertEquals('favorites', $results[0]->getName());

		$this->assertInstanceOf('Everyman\Neo4j\Index', $results[1]);
		$this->assertEquals(Index::TypeRelationship, $results[1]->getType());
		$this->assertEquals('users', $results[1]->getName());
	}
}
