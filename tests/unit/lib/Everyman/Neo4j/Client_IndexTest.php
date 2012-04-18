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
	public function testSaveIndex_ReturnsSuccess($type, $name, $config, $result)
	{
		$index = new Index($this->client, $type, $name, $config);
		$data = array('name' => $name);
		if ($config) {
			$data['config'] = $config;
		}

		$this->transport->expects($this->once())
			->method('post')
			->with('/index/'.$type, $data)
			->will($this->returnValue($result));

		$this->assertTrue($this->client->saveIndex($index));
	}

	public function dataProvider_SaveIndexScenarios()
	{
		return array(// type, name, config, result
			array(Index::TypeNode, 'somekey', array(), array('code'=>201)),
			array(Index::TypeRelationship, 'somekey', array(), array('code'=>201)),
			array(Index::TypeNode, 'somekey', array('type' => 'fulltext'), array('code'=>201)),
		);
	}

	public function testSaveIndex_ServerError_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, 'somekey');

		$this->transport->expects($this->once())
			->method('post')
			->with('/index/node', array(
				'name' => 'somekey',
			))
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->saveIndex($index);
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
	public function testDeleteIndex_ReturnsSuccess($type, $name, $config, $result)
	{
		$index = new Index($this->client, $type, $name);

		$this->transport->expects($this->once())
			->method('delete')
			->with('/index/'.$type.'/'.$name)
			->will($this->returnValue($result));

		$this->assertTrue($this->client->deleteIndex($index));
	}

	public function testDeleteIndex_ServerError_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, 'somekey');

		$this->transport->expects($this->once())
			->method('delete')
			->with('/index/node/somekey')
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->deleteIndex($index);
	}

	public function testDeleteIndex_UrlEntities_ReturnsCorrectSuccess()
	{
		$index = new Index($this->client, Index::TypeNode, 'ind@ex na$me');

		$this->transport->expects($this->once())
			->method('delete')
			->with('/index/node/ind%40ex%20na%24me')
			->will($this->returnValue(array('code'=>200)));

		$this->assertTrue($this->client->deleteIndex($index));
	}

	public function testDeleteIndex_NotFound_ReturnsSuccess()
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');

		$this->transport->expects($this->once())
			->method('delete')
			->with('/index/node/indexname')
			->will($this->returnValue(array('code'=>404)));

		$this->assertTrue($this->client->deleteIndex($index));
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

	public function testAddToIndex_EntityAdded_ReturnsSuccess()
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
			->will($this->returnValue(array('code'=>201)));

		$this->assertTrue($this->client->addToIndex($index, $node, 'somekey', 'somevalue'));
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
			->with('/index/node/index%20name', $data)
			->will($this->returnValue(array('code'=>200)));

		$this->assertTrue($this->client->addToIndex($index, $node, 'some@key', 'some$value'));
	}

	public function testAddToIndex_ServerError_ThrowsException()
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
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->addToIndex($index, $node, 'somekey', 'somevalue');
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
	public function testRemoveFromIndex_ReturnsSuccess($key, $value, $path, $result)
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');
		$node = new Node($this->client);
		$node->setId(123);

		$this->transport->expects($this->once())
			->method('delete')
			->with('/index/node/indexname'.$path.'/123')
			->will($this->returnValue($result));

		$this->assertTrue($this->client->removeFromIndex($index, $node, $key, $value));
	}
	
	public function dataProvider_RemoveFromIndexScenarios()
	{
		return array(// key, value, path, result
			array('somekey', 'somevalue', '/somekey/somevalue', array('code'=>201)),
			array('somekey', null, '/somekey', array('code'=>201)),
			array(null, null, '', array('code'=>201)),
			array('some key@', 'som$e value', '/some%20key%40/som%24e%20value', array('code'=>201)),
		);
	}

	public function testRemoveFromIndex_NotFound_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');
		$node = new Node($this->client);
		$node->setId(123);

		$this->transport->expects($this->once())
			->method('delete')
			->with('/index/node/indexname/somekey/somevalue/123')
			->will($this->returnValue(array('code'=>404)));

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->removeFromIndex($index, $node, 'somekey', 'somevalue');
	}

	public function testRemoveFromIndex_ServerError_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');
		$node = new Node($this->client);
		$node->setId(123);

		$this->transport->expects($this->once())
			->method('delete')
			->with('/index/node/indexname/somekey/somevalue/123')
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->removeFromIndex($index, $node, 'somekey', 'somevalue');
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

	public function testSearchIndex_Error_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');

		$this->transport->expects($this->once())
			->method('get')
			->with('/index/node/indexname/somekey/somevalue')
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->searchIndex($index, 'somekey', 'somevalue');
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
			->with('/index/relationship/index%20name/some%40key/some%24value')
			->will($this->returnValue(array('code'=>200,'data'=>$return)));

		$result = $this->client->searchIndex($index, 'some@key', 'some$value');
		$this->assertEquals(1, count($result));
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


	public function testQueryIndex_Error_ThrowsException()
	{
		$index = new Index($this->client, Index::TypeNode, 'indexname');

		$this->transport->expects($this->once())
			->method('get')
			->with('/index/node/indexname?query='.rawurlencode('somekey:somevalue*'))
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('\Everyman\Neo4j\Exception');
		$this->client->queryIndex($index, 'somekey:somevalue*');
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
			->with('/index/node/indexname?query='.rawurlencode('somekey:somevalue*'))
			->will($this->returnValue(array('code'=>200,'data'=>$return)));

		$result = $this->client->queryIndex($index, 'somekey:somevalue*');
		$this->assertEquals(2, count($result));

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
			->with('/index/relationship/indexname?query='.rawurlencode('somekey:somevalue*'))
			->will($this->returnValue(array('code'=>200,'data'=>$return)));

		$result = $this->client->queryIndex($index, 'somekey:somevalue*');
		$this->assertEquals(1, count($result));

		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $result[0]);
		$this->assertEquals(789, $result[0]->getId());
		$this->assertEquals(array('foo'=>'bar'), $result[0]->getProperties());

		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[0]->getStartNode());
		$this->assertEquals(123, $result[0]->getStartNode()->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[0]->getEndNode());
		$this->assertEquals(456, $result[0]->getEndNode()->getId());
	}
		
	public function testGetIndexes_ServerError_ThrowsException()
	{
		$this->transport->expects($this->once())
			->method('get')
			->with('/index/node')
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$results = $this->client->getIndexes(Index::TypeNode);
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
		$favoritesConfig = array(
			'template' =>'http://0.0.0.0:7474/db/data/index/node/favorites/{key}/{value}',
			'provider' =>'lucene',
			'type' =>'exact',
		);

		$usersConfig = array(
			'template' =>'http://0.0.0.0:7474/db/data/index/node/users/{key}/{value}',
			'provider' =>'lucene',
			'type' =>'fulltext',
		);

		$this->transport->expects($this->once())
			->method('get')
			->with('/index/node')
			->will($this->returnValue(array('code'=>200, 'data'=>array(
			'favorites' => $favoritesConfig,
			'users' => $usersConfig,
		))));

		$results = $this->client->getIndexes(Index::TypeNode);
		$this->assertEquals(2, count($results));

		$this->assertInstanceOf('Everyman\Neo4j\Index', $results[0]);
		$this->assertEquals(Index::TypeNode, $results[0]->getType());
		$this->assertEquals('favorites', $results[0]->getName());
		$this->assertEquals($favoritesConfig, $results[0]->getConfig());

		$this->assertInstanceOf('Everyman\Neo4j\Index', $results[1]);
		$this->assertEquals(Index::TypeNode, $results[1]->getType());
		$this->assertEquals('users', $results[1]->getName());
		$this->assertEquals($usersConfig, $results[1]->getConfig());
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
