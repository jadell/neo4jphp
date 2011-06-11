<?php
namespace Everyman\Neo4j;

class ClientTest extends \PHPUnit_Framework_TestCase
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
	 * @dataProvider dataProvider_DeleteNodeScenarios
	 */
	public function testDeleteNode_TransportResult_ReturnsCorrectSuccessOrFailure($result, $success, $error)
	{
		$node = new Node($this->client);
		$node->setId(123);
		
		$this->transport->expects($this->once())
			->method('delete')
			->with('/node/123')
			->will($this->returnValue($result));

		$this->assertEquals($success, $this->client->deleteNode($node));
		$this->assertEquals($error, $this->client->getLastError());
	}

	public function dataProvider_DeleteNodeScenarios()
	{
		return array(// result, success, error
			array(array('code'=>204), true, null),
			array(array('code'=>404), false, Client::ErrorNotFound),
			array(array('code'=>409), false, Client::ErrorConflict),
		);
	}

	public function testDeleteNode_NodeHasNoId_ThrowsException()
	{
		$node = new Node($this->client);

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->deleteNode($node);
	}

	public function testSaveNode_Update_NodeHasNoId_ThrowsException()
	{
		$node = new Node($this->client);
		$command = new Command\UpdateNode($this->client, $node);

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$command->execute();
	}

	/**
	 * @dataProvider dataProvider_UpdateNodeScenarios
	 */
	public function testSaveNode_Update_ReturnsCorrectSuccessOrFailure($result, $success, $error)
	{
		$properties = array(
			'foo' => 'bar',
			'baz' => 'qux',
		);

		$node = new Node($this->client);
		$node->setId(123)
			->setProperties($properties);
		
		$this->transport->expects($this->once())
			->method('put')
			->with('/node/123/properties', $properties)
			->will($this->returnValue($result));

		$this->assertEquals($success, $this->client->saveNode($node));
		$this->assertEquals($error, $this->client->getLastError());
		$this->assertEquals(123, $node->getId());
	}

	public function dataProvider_UpdateNodeScenarios()
	{
		return array(// result, success, error
			array(array('code'=>204), true, null),
			array(array('code'=>404), false, Client::ErrorNotFound),
			array(array('code'=>400), false, Client::ErrorBadRequest),
		);
	}

	/**
	 * @dataProvider dataProvider_CreateNodeScenarios
	 */
	public function testSaveNode_Create_ReturnsCorrectSuccessOrFailure($result, $success, $error, $id)
	{
		$properties = array(
			'foo' => 'bar',
			'baz' => 'qux',
		);

		$node = new Node($this->client);
		$node->setProperties($properties);
		
		$this->transport->expects($this->once())
			->method('post')
			->with('/node', $properties)
			->will($this->returnValue($result));

		$this->assertEquals($success, $this->client->saveNode($node));
		$this->assertEquals($error, $this->client->getLastError());
		$this->assertEquals($id, $node->getId());
	}

	public function dataProvider_CreateNodeScenarios()
	{
		return array(// result, success, error, id
			array(array('code'=>201, 'headers'=>array('Location'=>'http://foo.com:1234/db/data/node/123')), true, null, 123),
			array(array('code'=>400), false, Client::ErrorBadRequest, null),
		);
	}

	public function testGetNode_NotFound_ReturnsNull()
	{
		$nodeId = 123;
		
		$this->transport->expects($this->once())
			->method('get')
			->with('/node/'.$nodeId.'/properties')
			->will($this->returnValue(array('code'=>'404')));

		$this->assertNull($this->client->getNode($nodeId));
		$this->assertEquals(Client::ErrorNotFound, $this->client->getLastError());
	}

	public function testGetNode_Force_ReturnsNode()
	{
		$nodeId = 123;
		
		$this->transport->expects($this->never())
			->method('get');

		$node = $this->client->getNode($nodeId, true);
		$this->assertInstanceOf('Everyman\Neo4j\Node', $node);
		$this->assertEquals($nodeId, $node->getId());
		$this->assertNull($this->client->getLastError());
	}

	public function testGetNode_Found_ReturnsNode()
	{
		$nodeId = 123;
		$properties = array(
			'foo' => 'bar',
			'baz' => 'qux',
		);
		
		$this->transport->expects($this->once())
			->method('get')
			->with('/node/'.$nodeId.'/properties')
			->will($this->returnValue(array('code'=>'200','data'=>$properties)));

		$node = $this->client->getNode($nodeId);
		$this->assertNotNull($node);
		$this->assertInstanceOf('Everyman\Neo4j\Node', $node);
		$this->assertEquals($nodeId, $node->getId());
		$this->assertEquals($properties, $node->getProperties());
		$this->assertNull($this->client->getLastError());
	}

	public function testLoadNode_NodeHasNoId_ThrowsException()
	{
		$node = new Node($this->client);

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->loadNode($node);
	}

	public function testGetRelationship_NotFound_ReturnsNull()
	{
		$relId = 123;
		
		$this->transport->expects($this->once())
			->method('get')
			->with('/relationship/'.$relId)
			->will($this->returnValue(array('code'=>'404')));

		$this->assertNull($this->client->getRelationship($relId));
		$this->assertEquals(Client::ErrorNotFound, $this->client->getLastError());
	}

	public function testGetRelationship_Force_ReturnsRelationship()
	{
		$relId = 123;
		
		$this->transport->expects($this->never())
			->method('get');

		$rel = $this->client->getRelationship($relId, true);
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rel);
		$this->assertEquals($relId, $rel->getId());
		$this->assertNull($this->client->getLastError());
	}

	public function testGetRelationship_Found_ReturnsRelationship()
	{
		$relId = 123;
		$data = array(
			'data' => array(
				'foo' => 'bar',
				'baz' => 'qux',
			),
			'start' => 'http://foo:1234/db/data/node/567',
			'end'   => 'http://foo:1234/db/data/node/890',
			'type'  => 'FOOTYPE',
		);
		
		$this->transport->expects($this->once())
			->method('get')
			->with('/relationship/'.$relId)
			->will($this->returnValue(array('code'=>'200','data'=>$data)));

		$rel = $this->client->getRelationship($relId);
		$this->assertNotNull($rel);
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rel);
		$this->assertEquals($relId, $rel->getId());
		$this->assertEquals($data['data'], $rel->getProperties());
		$this->assertEquals($data['type'], $rel->getType());
		$this->assertNull($this->client->getLastError());

		$start = $rel->getStartNode();
		$this->assertNotNull($start);
		$this->assertInstanceOf('Everyman\Neo4j\Node', $start);
		$this->assertEquals(567, $start->getId());

		$end = $rel->getEndNode();
		$this->assertNotNull($end);
		$this->assertInstanceOf('Everyman\Neo4j\Node', $end);
		$this->assertEquals(890, $end->getId());
	}

	public function testLoadRelationship_RelationshipHasNoId_ThrowsException()
	{
		$rel = new Relationship($this->client);

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->loadRelationship($rel);
	}

	/**
	 * @dataProvider dataProvider_DeleteRelationshipScenarios
	 */
	public function testDeleteRelationship_TransportResult_ReturnsCorrectSuccessOrFailure($result, $success, $error)
	{
		$rel = new Relationship($this->client);
		$rel->setId(123);
		
		$this->transport->expects($this->once())
			->method('delete')
			->with('/relationship/123')
			->will($this->returnValue($result));

		$this->assertEquals($success, $this->client->deleteRelationship($rel));
		$this->assertEquals($error, $this->client->getLastError());
	}

	public function dataProvider_DeleteRelationshipScenarios()
	{
		return array(// result, success, error
			array(array('code'=>204), true, null),
			array(array('code'=>404), false, Client::ErrorNotFound),
		);
	}

	public function testDeleteRelationship_RelationshipHasNoId_ThrowsException()
	{
		$rel = new Relationship($this->client);

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->deleteRelationship($rel);
	}

	public function testSaveRelationship_Create_NoStartNode_ThrowsException()
	{
		$rel = new Relationship($this->client);

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->saveRelationship($rel);
	}

	public function testSaveRelationship_Create_NoEndNode_ThrowsException()
	{
		$start = new Node($this->client);
		$start->setId(123);

		$rel = new Relationship($this->client);
		$rel->setStartNode($start);
		
		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->saveRelationship($rel);
	}

	public function testSaveRelationship_Create_NoType_ThrowsException()
	{
		$start = new Node($this->client);
		$start->setId(123);
		$end = new Node($this->client);
		$end->setId(456);

		$rel = new Relationship($this->client);
		$rel->setStartNode($start);
		$rel->setEndNode($end);
		
		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->saveRelationship($rel);
	}

	/**
	 * @dataProvider dataProvider_CreateRelationshipScenarios
	 */
	public function testSaveRelationship_Create_ReturnsCorrectSuccessOrFailure($result, $success, $error, $id)
	{
		$data = array(
			'data' => array(
				'foo' => 'bar',
				'baz' => 'qux',
			),
			'to' => $this->endpoint.'/node/456',
			'type' => 'FOOTYPE',
		);

		$start = new Node($this->client);
		$start->setId(123);
		$end = new Node($this->client);
		$end->setId(456);

		$rel = new Relationship($this->client);
		$rel->setType('FOOTYPE')
			->setStartNode($start)
			->setEndNode($end)
			->setProperties($data['data']);

		$this->transport->expects($this->once())
			->method('post')
			->with('/node/123/relationships', $data)
			->will($this->returnValue($result));

		$this->assertEquals($success, $this->client->saveRelationship($rel));
		$this->assertEquals($error, $this->client->getLastError());
		$this->assertEquals($id, $rel->getId());
	}

	public function dataProvider_CreateRelationshipScenarios()
	{
		return array(// result, success, error, id
			array(array('code'=>201, 'headers'=>array('Location'=>'http://foo.com:1234/db/data/relationship/890')), true, null, 890),
			array(array('code'=>400), false, Client::ErrorBadRequest, null),
			array(array('code'=>404), false, Client::ErrorNotFound, null),
		);
	}

	public function testSaveRelationship_Update_RelationshipHasNoId_ThrowsException()
	{
		$rel = new Relationship($this->client);
		$command = new Command\UpdateRelationship($this->client, $rel);

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$command->execute();
	}

	/**
	 * @dataProvider dataProvider_UpdateRelationshipScenarios
	 */
	public function testSaveRelationship_Update_ReturnsCorrectSuccessOrFailure($result, $success, $error)
	{
		$properties = array(
			'foo' => 'bar',
			'baz' => 'qux',
		);

		$rel = new Relationship($this->client);
		$rel->setId(123)
			->setProperties($properties);
		
		$this->transport->expects($this->once())
			->method('put')
			->with('/relationship/123/properties', $properties)
			->will($this->returnValue($result));

		$this->assertEquals($success, $this->client->saveRelationship($rel));
		$this->assertEquals($error, $this->client->getLastError());
		$this->assertEquals(123, $rel->getId());
	}

	public function dataProvider_UpdateRelationshipScenarios()
	{
		return array(// result, success, error
			array(array('code'=>204), true, null),
			array(array('code'=>404), false, Client::ErrorNotFound),
			array(array('code'=>400), false, Client::ErrorBadRequest),
		);
	}

	public function testGetNodeRelationships_NodeNotPersisted_ThrowsException()
	{
		$node = new Node($this->client);
		$type = 'FOOTYPE';
		$dir = Relationship::DirectionOut;

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->getNodeRelationships($node, $type, $dir);
	}

	public function testGetNodeRelationships_NodeNotFound_ReturnsFalse()
	{
		$node = new Node($this->client);
		$node->setId(123);

		$this->transport->expects($this->once())
			->method('get')
			->with('/node/123/relationships/all')
			->will($this->returnValue(array('code'=>404)));

		$this->assertFalse($this->client->getNodeRelationships($node, array(), null));
		$this->assertEquals(Client::ErrorNotFound, $this->client->getLastError());
	}

	public function testGetNodeRelationships_NoRelationships_ReturnsEmptyArray()
	{
		$node = new Node($this->client);
		$node->setId(123);
		$types = array('FOOTYPE');
		$dir = Relationship::DirectionIn;

		$this->transport->expects($this->once())
			->method('get')
			->with('/node/123/relationships/in/FOOTYPE')
			->will($this->returnValue(array('code'=>200,'data'=>array())));

		$this->assertEquals(array(), $this->client->getNodeRelationships($node, $types, $dir));
		$this->assertNull($this->client->getLastError());
	}

	public function testGetNodeRelationships_Relationships_ReturnsArray()
	{
		$node = new Node($this->client);
		$node->setId(123);
		$types = array('FOOTYPE','BARTYPE');
		$dir = Relationship::DirectionOut;

		$data = array(
			array(
				"self" => "http://localhost:7474/db/data/relationship/56",
				"start" => "http://localhost:7474/db/data/node/123",
				"end" => "http://localhost:7474/db/data/node/93",
				"type" => "KNOWS",
				"data" => array('foo'=>'bar','baz'=>'qux'),
			),
			array(
				"self" => "http://localhost:7474/db/data/relationship/834",
				"start" => "http://localhost:7474/db/data/node/32",
				"end" => "http://localhost:7474/db/data/node/123",
				"type" => "LOVES",
				"data" => array('bar'=>'foo','qux'=>'baz'),
			),
		);

		$this->transport->expects($this->once())
			->method('get')
			->with('/node/123/relationships/out/FOOTYPE&BARTYPE')
			->will($this->returnValue(array('code'=>200,'data'=>$data)));

		$result = $this->client->getNodeRelationships($node, $types, $dir);
		$this->assertEquals(2, count($result));
		$this->assertNull($this->client->getLastError());

		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $result[0]);
		$this->assertEquals(56, $result[0]->getId());
		$this->assertEquals($data[0]['data'], $result[0]->getProperties());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[0]->getStartNode());
		$this->assertEquals(123, $result[0]->getStartNode()->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[0]->getEndNode());
		$this->assertEquals(93, $result[0]->getEndNode()->getId());

		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $result[1]);
		$this->assertEquals(834, $result[1]->getId());
		$this->assertEquals($data[1]['data'], $result[1]->getProperties());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[1]->getStartNode());
		$this->assertEquals(32, $result[1]->getStartNode()->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[1]->getEndNode());
		$this->assertEquals(123, $result[1]->getEndNode()->getId());
	}
	
	public function testGetPaths_PathsExist_ReturnsArray()
	{
		$startNode = new Node($this->client);
		$startNode->setId(123);
		$endNode = new Node($this->client);
		$endNode->setId(456);
		
		$finder = new PathFinder($this->client);
		$finder->setType('FOOTYPE')
			->setDirection(Relationship::DirectionOut)
			->setMaxLength(3)
			->setStartNode($startNode)
			->setEndNode($endNode);
		
		$data = array(
			'to' => $this->endpoint.'/node/456',
			'relationships' => array('type'=>'FOOTYPE', 'direction'=>Relationship::DirectionOut),
			'max_depth' => 3,
			'algorithm' => 'shortestPath'
		);
		
		$returnData = array(
			array(
				"start" => "http://localhost:7474/db/data/node/123",
				"nodes" => array("http://localhost:7474/db/data/node/123", "http://localhost:7474/db/data/node/341", "http://localhost:7474/db/data/node/456"),
				"length" => 2,
				"relationships" => array("http://localhost:7474/db/data/relationship/564", "http://localhost:7474/db/data/relationship/32"),
				"end" => "http://localhost:7474/db/data/node/456"
			),
			array(
				"start" => "http://localhost:7474/db/data/node/123",
				"nodes" => array("http://localhost:7474/db/data/node/123", "http://localhost:7474/db/data/node/41", "http://localhost:7474/db/data/node/456"),
				"length" => 2,
				"relationships" => array("http://localhost:7474/db/data/relationship/437", "http://localhost:7474/db/data/relationship/97"),
				"end" => "http://localhost:7474/db/data/node/456"
			),
		);
		
		$this->transport->expects($this->once())
			->method('post')
			->with('/node/123/paths', $data)
			->will($this->returnValue(array('code'=>200,'data'=>$returnData)));
			
		$paths = $this->client->getPaths($finder);
		$this->assertEquals(2, count($paths));
		$this->assertInstanceOf('Everyman\Neo4j\Path', $paths[0]);
		$this->assertInstanceOf('Everyman\Neo4j\Path', $paths[1]);
		
		$rels = $paths[0]->getRelationships();
		$this->assertEquals(2, count($rels));
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rels[0]);
		$this->assertEquals(564, $rels[0]->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rels[1]);
		$this->assertEquals(32, $rels[1]->getId());
		
		$rels = $paths[1]->getRelationships();
		$this->assertEquals(2, count($rels));
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rels[0]);
		$this->assertEquals(437, $rels[0]->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rels[1]);
		$this->assertEquals(97, $rels[1]->getId());
	}
	
	public function testGetPaths_DirectionGivenButNoType_ThrowsException()
	{
		$startNode = new Node($this->client);
		$startNode->setId(123);
		$endNode = new Node($this->client);
		$endNode->setId(456);
		
		$finder = new PathFinder($this->client);
		$finder->setDirection(Relationship::DirectionOut)
			->setStartNode($startNode)
			->setEndNode($endNode);
		
		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$paths = $this->client->getPaths($finder);
	}
	
	public function testGetPaths_StartNodeNotPersisted_ThrowsException()
	{
		$startNode = new Node($this->client);
		$endNode = new Node($this->client);
		$endNode->setId(456);
		
		$finder = new PathFinder($this->client);
		$finder->setStartNode($startNode)
			->setEndNode($endNode);
		
		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$paths = $this->client->getPaths($finder);
	}
	
	public function testGetPaths_EndNodeNotPersisted_ThrowsException()
	{
		$startNode = new Node($this->client);
		$startNode->setId(123);
		$endNode = new Node($this->client);
		
		$finder = new PathFinder($this->client);
		$finder->setStartNode($startNode)
			->setEndNode($endNode);
		
		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$paths = $this->client->getPaths($finder);
	}
	
	public function testGetPaths_TransportFails_ReturnsFalse()
	{
		$startNode = new Node($this->client);
		$startNode->setId(123);
		$endNode = new Node($this->client);
		$endNode->setId(456);
		
		$finder = new PathFinder($this->client);
		$finder->setType('FOOTYPE')
			->setDirection(Relationship::DirectionOut)
			->setMaxLength(3)
			->setStartNode($startNode)
			->setEndNode($endNode);
		
		$this->transport->expects($this->any())
			->method('post')
			->will($this->returnValue(array('code'=>400)));

		$this->assertFalse($this->client->getPaths($finder));
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

		$this->transport->expects($this->once())
			->method('post')
			->with('/index/node/indexname/somekey/somevalue', $this->endpoint.'/node/123')
			->will($this->returnValue($result));

		$this->assertEquals($success, $this->client->addToIndex($index, $node, 'somekey', 'somevalue'));
		$this->assertEquals($error, $this->client->getLastError());
	}
	
	public function dataProvider_AddToIndexScenarios()
	{
		return array(// type, name, result, success, error
			array(array('code'=>201), true, null),
			array(array('code'=>400), false, Client::ErrorBadRequest),
		);
	}

	public function testAddToIndex_UrlEntities_ReturnsCorrectSuccess()
	{
		$index = new Index($this->client, Index::TypeNode, 'index name');
		$node = new Node($this->client);
		$node->setId(123);

		$this->transport->expects($this->once())
			->method('post')
			->with('/index/node/index+name/some%40key/some%24value', $this->endpoint.'/node/123')
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
}
