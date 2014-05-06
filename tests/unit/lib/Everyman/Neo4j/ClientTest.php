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

	public function testConstruct_TransportGiven_SetsTransport()
	{
		$this->assertSame($this->transport, $this->client->getTransport());
	}

	public function testConstruct_NoTransportGiven_SetsCreateTransport()
	{
		$client = new Client();
		$transport = $client->getTransport();
		$this->assertInstanceOf('Everyman\Neo4j\Transport', $transport);
		$this->assertEquals('http://localhost:7474/db/data', $transport->getEndpoint());
	}

	public function testConstruct_HostAndPortGiven_SetsCreateTransport()
	{
		$client = new Client('somehost', 7575);
		$transport = $client->getTransport();
		$this->assertInstanceOf('Everyman\Neo4j\Transport', $transport);
		$this->assertEquals('http://somehost:7575/db/data', $transport->getEndpoint());
	}

	public function testDeleteNode_NodeDeleted_ReturnsTrue()
	{
		$node = new Node($this->client);
		$node->setId(123);

		$this->transport->expects($this->once())
			->method('delete')
			->with('/node/123')
			->will($this->returnValue(array('code'=>204)));

		$this->assertTrue($this->client->deleteNode($node));
	}

	public function testDeleteNode_NodeNotFound_ThrowsException()
	{
		$node = new Node($this->client);
		$node->setId(123);

		$this->transport->expects($this->once())
			->method('delete')
			->with('/node/123')
			->will($this->returnValue(array('code'=>404)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->deleteNode($node);
	}

	public function testDeleteNode_TransportError_ThrowsException()
	{
		$node = new Node($this->client);
		$node->setId(123);

		$this->transport->expects($this->once())
			->method('delete')
			->with('/node/123')
			->will($this->returnValue(array('code'=>409)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->deleteNode($node);
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

	public function testSaveNode_UpdateNodeFound_ReturnsTrue()
	{
		$properties = array(
			'foo' => 'bar',
			'baz' => 'qux',
		);

		$node = new Node($this->client);
		$node->useLazyLoad(false)
			->setId(123)
			->setProperties($properties);

		$this->transport->expects($this->once())
			->method('put')
			->with('/node/123/properties', $properties)
			->will($this->returnValue(array('code'=>204)));

		$this->assertTrue($this->client->saveNode($node));
		$this->assertEquals(123, $node->getId());
	}

	public function testSaveNode_UpdateNodeNotFound_ThrowsException()
	{
		$properties = array(
			'foo' => 'bar',
			'baz' => 'qux',
		);

		$node = new Node($this->client);
		$node->useLazyLoad(false)
			->setId(123)
			->setProperties($properties);

		$this->transport->expects($this->once())
			->method('put')
			->with('/node/123/properties', $properties)
			->will($this->returnValue(array('code'=>404)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->saveNode($node);
	}

	public function testSaveNode_Update_TransportError_ThrowsException()
	{
		$properties = array(
			'foo' => 'bar',
			'baz' => 'qux',
		);

		$node = new Node($this->client);
		$node->useLazyLoad(false)
			->setId(123)
			->setProperties($properties);

		$this->transport->expects($this->once())
			->method('put')
			->with('/node/123/properties', $properties)
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->saveNode($node);
	}

	public function testSaveNode_Create_ReturnsTrue()
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
			->will($this->returnValue(array(
				'code'=>201,
				'headers'=>array('Location'=>'http://foo.com:1234/db/data/node/123')
			)));

		$this->assertTrue($this->client->saveNode($node));
		$this->assertEquals(123, $node->getId());
	}

	public function testSaveNode_Create_TransportError_ThrowsException()
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
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->saveNode($node);
	}

	public function testSaveNode_CreateNoProperties_ReturnsSuccess()
	{
		$node = new Node($this->client);

		$this->transport->expects($this->once())
			->method('post')
			->with('/node',null)
			->will($this->returnValue(array('code'=>201, 'headers'=>array('Location'=>'http://foo.com:1234/db/data/node/123'))));

		$this->assertTrue($this->client->saveNode($node));
		$this->assertEquals(123, $node->getId());
	}

	public function testGetNode_TransportError_ThrowsException()
	{
		$nodeId = 123;

		$this->transport->expects($this->once())
			->method('get')
			->with('/node/'.$nodeId)
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->getNode($nodeId);
	}

	public function testGetNode_NotFound_ReturnsNull()
	{
		$nodeId = 123;

		$this->transport->expects($this->once())
			->method('get')
			->with('/node/'.$nodeId)
			->will($this->returnValue(array('code'=>404)));

		$this->assertNull($this->client->getNode($nodeId));
	}

	public function testGetNode_Force_ReturnsNode()
	{
		$nodeId = 123;

		$this->transport->expects($this->never())
			->method('get');

		$node = $this->client->getNode($nodeId, true);
		$this->assertInstanceOf('Everyman\Neo4j\Node', $node);
		$this->assertEquals($nodeId, $node->getId());
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
			->with('/node/'.$nodeId)
			->will($this->returnValue(array('code'=>200,'data'=>array('data'=>$properties))));

		$node = $this->client->getNode($nodeId);
		$this->assertNotNull($node);
		$this->assertInstanceOf('Everyman\Neo4j\Node', $node);
		$this->assertEquals($nodeId, $node->getId());
		$this->assertEquals($properties, $node->getProperties());
	}

	public function testLoadNode_NodeNotFound_ThrowsException()
	{
		$nodeId = 123;
		$node = new Node($this->client);
		$node->setId($nodeId);

		$this->transport->expects($this->once())
			->method('get')
			->with('/node/'.$nodeId)
			->will($this->returnValue(array('code'=>404)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->loadNode($node);
	}

	public function testLoadNode_NodeHasNoId_ThrowsException()
	{
		$node = new Node($this->client);

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->loadNode($node);
	}

	public function testGetRelationship_TransportError_ThrowsException()
	{
		$relId = 123;

		$this->transport->expects($this->once())
			->method('get')
			->with('/relationship/'.$relId)
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->getRelationship($relId);
	}

	public function testGetRelationship_NotFound_ReturnsNull()
	{
		$relId = 123;

		$this->transport->expects($this->once())
			->method('get')
			->with('/relationship/'.$relId)
			->will($this->returnValue(array('code'=>'404')));

		$this->assertNull($this->client->getRelationship($relId));
	}

	public function testGetRelationship_Force_ReturnsRelationship()
	{
		$relId = 123;

		$this->transport->expects($this->never())
			->method('get');

		$rel = $this->client->getRelationship($relId, true);
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rel);
		$this->assertEquals($relId, $rel->getId());
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

		$start = $rel->getStartNode();
		$this->assertNotNull($start);
		$this->assertInstanceOf('Everyman\Neo4j\Node', $start);
		$this->assertEquals(567, $start->getId());

		$end = $rel->getEndNode();
		$this->assertNotNull($end);
		$this->assertInstanceOf('Everyman\Neo4j\Node', $end);
		$this->assertEquals(890, $end->getId());
	}

	/**
	 * Regression test for http://github.com/jadell/neo4jphp/issues/52
	 */
	public function testGetRelationship_Found_LazyLoadNodes()
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

		$this->transport->expects($this->at(0))
			->method('get')
			->with('/relationship/'.$relId)
			->will($this->returnValue(array('code'=>'200','data'=>$data)));
		$this->transport->expects($this->at(1))
			->method('get')
			->with('/node/567')
			->will($this->returnValue(array('code'=>'200','data'=>array('data' => array()))));
		$this->transport->expects($this->at(2))
			->method('get')
			->with('/node/890')
			->will($this->returnValue(array('code'=>'200','data'=>array('data' => array()))));

		$rel = $this->client->getRelationship($relId);
		$rel->getStartNode()->getProperties();
		$rel->getEndNode()->getProperties();
	}

	public function testLoadRelationship_RelationshipNotFound_ThrowsException()
	{
		$relId = 123;
		$rel = new Relationship($this->client);
		$rel->setId($relId);

		$this->transport->expects($this->once())
			->method('get')
			->with('/relationship/'.$relId)
			->will($this->returnValue(array('code'=>404)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->loadRelationship($rel);
	}

	public function testLoadRelationship_RelationshipHasNoId_ThrowsException()
	{
		$rel = new Relationship($this->client);

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->loadRelationship($rel);
	}

	public function testDeleteRelationship_Found_ReturnsTrue()
	{
		$rel = new Relationship($this->client);
		$rel->setId(123);

		$this->transport->expects($this->once())
			->method('delete')
			->with('/relationship/123')
			->will($this->returnValue(array('code'=>204)));

		$this->assertTrue($this->client->deleteRelationship($rel));
	}

	public function testDeleteRelationship_NotFound_ThrowsException()
	{
		$rel = new Relationship($this->client);
		$rel->setId(123);

		$this->transport->expects($this->once())
			->method('delete')
			->with('/relationship/123')
			->will($this->returnValue(array('code'=>404)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->deleteRelationship($rel);
	}

	public function testDeleteRelationship_TransportError_ThrowsException()
	{
		$rel = new Relationship($this->client);
		$rel->setId(123);

		$this->transport->expects($this->once())
			->method('delete')
			->with('/relationship/123')
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->deleteRelationship($rel);
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

	public function testSaveRelationship_Create_ReturnsTrue()
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
			->will($this->returnValue(array('code'=>201, 'headers'=>array('Location'=>'http://foo.com:1234/db/data/relationship/890'))));

		$this->assertTrue($this->client->saveRelationship($rel));
		$this->assertEquals(890, $rel->getId());
	}

	public function testSaveRelationship_CreateNoData_ReturnsTrue()
	{
		$data = array(
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
			->setProperties(array());

		$this->transport->expects($this->once())
			->method('post')
			->with('/node/123/relationships', $data)
			->will($this->returnValue(array('code'=>201, 'headers'=>array('Location'=>'http://foo.com:1234/db/data/relationship/890'))));

		$this->assertTrue($this->client->saveRelationship($rel));
		$this->assertEquals(890, $rel->getId());
	}

	public function testSaveRelationship_CreateTransportError_ThrowsException()
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
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->saveRelationship($rel);
	}

	public function testSaveRelationship_Update_RelationshipHasNoId_ThrowsException()
	{
		$rel = new Relationship($this->client);
		$command = new Command\UpdateRelationship($this->client, $rel);

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$command->execute();
	}

	public function testSaveRelationship_UpdateFound_ReturnsTrue()
	{
		$properties = array(
			'foo' => 'bar',
			'baz' => 'qux',
		);

		$rel = new Relationship($this->client);
		$rel->useLazyLoad(false)
			->setId(123)
			->setProperties($properties);

		$this->transport->expects($this->once())
			->method('put')
			->with('/relationship/123/properties', $properties)
			->will($this->returnValue(array('code'=>204)));

		$this->assertTrue($this->client->saveRelationship($rel));
	}

	public function testSaveRelationship_UpdateNotFound_ReturnsFalse()
	{
		$properties = array(
			'foo' => 'bar',
			'baz' => 'qux',
		);

		$rel = new Relationship($this->client);
		$rel->useLazyLoad(false)
			->setId(123)
			->setProperties($properties);

		$this->transport->expects($this->once())
			->method('put')
			->with('/relationship/123/properties', $properties)
			->will($this->returnValue(array('code'=>404)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->saveRelationship($rel);
	}

	public function testSaveRelationship_UpdateTransportError_ThrowsException()
	{
		$properties = array(
			'foo' => 'bar',
			'baz' => 'qux',
		);

		$rel = new Relationship($this->client);
		$rel->useLazyLoad(false)
			->setId(123)
			->setProperties($properties);

		$this->transport->expects($this->once())
			->method('put')
			->with('/relationship/123/properties', $properties)
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->saveRelationship($rel);
	}

	public function testGetNodeRelationships_NodeNotPersisted_ThrowsException()
	{
		$node = new Node($this->client);
		$type = 'FOOTYPE';
		$dir = Relationship::DirectionOut;

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->getNodeRelationships($node, $type, $dir);
	}

	public function testGetNodeRelationships_NodeNotFound_ThrowsException()
	{
		$node = new Node($this->client);
		$node->setId(123);

		$this->transport->expects($this->once())
			->method('get')
			->with('/node/123/relationships/all')
			->will($this->returnValue(array('code'=>404)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->getNodeRelationships($node, array(), null);
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

	public function testGetNodeRelationships_UrlCharactersInTypeName_EncodesCorrectly()
	{
		$node = new Node($this->client);
		$node->setId(123);
		$types = array('FOO\TYPE','BAR?TYPE','BAZ/TYPE');

		$this->transport->expects($this->once())
			->method('get')
			->with('/node/123/relationships/all/FOO%5CTYPE&BAR%3FTYPE&BAZ%2FTYPE')
			->will($this->returnValue(array('code'=>200,'data'=>array())));

		$result = $this->client->getNodeRelationships($node, $types);
	}

	public function testGetRelationshipTypes_ServerReturnsErrorCode_ThrowsException()
	{
		$this->transport->expects($this->once())
			->method('get')
			->with('/relationship/types')
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$result = $this->client->getRelationshipTypes();
	}

	public function testGetRelationshipTypes_ServerReturnsArray_ReturnsArray()
	{
		$this->transport->expects($this->once())
			->method('get')
			->with('/relationship/types')
			->will($this->returnValue(array('code'=>200, 'data'=>array("foo","bar"))));

		$result = $this->client->getRelationshipTypes();
		$this->assertEquals(array("foo","bar"), $result);
	}

	public function testGetServerInfo_ServerReturnsArray_ReturnsArray()
	{
		$returnData = array(
			"relationship_index" => "http://localhost:7474/db/data/index/relationship",
			"node" => "http://localhost:7474/db/data/node",
			"relationship_types" => "http://localhost:7474/db/data/relationship/types",
			"batch" => "http://localhost:7474/db/data/batch",
			"extensions_info" => "http://localhost:7474/db/data/ext",
			"node_index" => "http://localhost:7474/db/data/index/node",
			"reference_node" => "http://localhost:7474/db/data/node/2",
			"extensions" => array(),
			"neo4j_version" => "1.5.M01-793-gc100417-dirty",
		);

		$expectedData = $returnData;
		$expectedData['version'] = array(
			"full" => "1.5.M01-793-gc100417-dirty",
			"major" => "1",
			"minor" => "5",
			"release" => "M01",
		);

		$this->transport->expects($this->once())
			->method('get')
			->with('/')
			->will($this->returnValue(array('code'=>200, 'data'=>$returnData)));

		$result = $this->client->getServerInfo();
		$this->assertEquals($expectedData, $result);
	}

	public function testGetServerInfo_GeneralAvailabilityRelease_ReturnsArray()
	{
		$returnData = array(
			"relationship_index" => "http://localhost:7474/db/data/index/relationship",
			"node" => "http://localhost:7474/db/data/node",
			"neo4j_version" => "1.5",
		);

		$expectedData = $returnData;
		$expectedData['version'] = array(
			"full" => "1.5",
			"major" => "1",
			"minor" => "5",
			"release" => "GA",
		);

		$this->transport->expects($this->once())
			->method('get')
			->with('/')
			->will($this->returnValue(array('code'=>200, 'data'=>$returnData)));

		$result = $this->client->getServerInfo();
		$this->assertEquals($expectedData, $result);
	}

	public function testGetServerInfo_UnsuccessfulResponse_ThrowsException()
	{
		$this->transport->expects($this->once())
			->method('get')
			->with('/')
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->getServerInfo();
	}

	public function testStartBatch_MultipleCallsWithoutCommit_ReturnsSameBatch()
	{
		$batch = $this->client->startBatch();
		$this->assertInstanceOf('Everyman\Neo4j\Batch', $batch);

		$batchAgain = $this->client->startBatch();
		$this->assertSame($batch, $batchAgain);
	}

	public function testStartBatch_CommitAndStartAnother_ReturnsNewBatch()
	{
		$this->transport->expects($this->once())
			->method('post')
			->with('/batch')
			->will($this->returnValue(array('code'=>200)));


		$batch = $this->client->startBatch();
		$batch->save(new Node($this->client));
		$this->assertInstanceOf('Everyman\Neo4j\Batch', $batch);
		$this->client->commitBatch();

		$batchAgain = $this->client->startBatch();
		$this->assertInstanceOf('Everyman\Neo4j\Batch', $batchAgain);
		$this->assertNotSame($batch, $batchAgain);
	}

	public function testStartBatch_CommitOpenedBatch_ReturnsNewBatch()
	{
		$this->transport->expects($this->once())
			->method('post')
			->with('/batch')
			->will($this->returnValue(array('code'=>200)));


		$batch = $this->client->startBatch();
		$batch->save(new Node($this->client));
		$this->assertInstanceOf('Everyman\Neo4j\Batch', $batch);
		$batch->commit();

		$batchAgain = $this->client->startBatch();
		$this->assertInstanceOf('Everyman\Neo4j\Batch', $batchAgain);
		$this->assertNotSame($batch, $batchAgain);
	}

	public function testStartBatch_CommitOtherBatch_ReturnsSameBatch()
	{
		$this->transport->expects($this->once())
			->method('post')
			->with('/batch')
			->will($this->returnValue(array('code'=>200)));

		$openBatch = $this->client->startBatch();
		$batch = new Batch($this->client);
		$batch->save(new Node($this->client));
		$batch->commit();

		$batchAgain = $this->client->startBatch();
		$this->assertSame($openBatch, $batchAgain);
	}

	public function testStartBatch_EndBatch_ReturnsNewBatch()
	{
		$batch = $this->client->startBatch();
		$this->client->endBatch();

		$batchAgain = $this->client->startBatch();
		$this->assertNotSame($batch, $batchAgain);
	}

	public function testCommitBatch_NoBatchGivenNoOpenBatch_ThrowsException()
	{
		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->commitBatch();
	}

	public function testCommitBatch_NoOperationsInBatch_ReturnsTrue()
	{
		$this->transport->expects($this->never())
			->method('post');

		$batch = new Batch($this->client);
		$this->assertTrue($this->client->commitBatch($batch));
	}

	public function testMakeNode_ReturnsNode()
	{
		$data = array(
			'foo' => 'bar',
			'baz' => 'qux',
		);

		$node = $this->client->makeNode($data);
		$this->assertInstanceOf('Everyman\Neo4j\Node', $node);
		$this->assertSame($this->client, $node->getClient());
		$this->assertEquals($data, $node->getProperties());
	}

	public function testMakeRelationship_ReturnsRelationship()
	{
		$data = array(
			'foo' => 'bar',
			'baz' => 'qux',
		);

		$rel = $this->client->makeRelationship($data);
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rel);
		$this->assertSame($this->client, $rel->getClient());
		$this->assertEquals($data, $rel->getProperties());
	}

	public function testGetReferenceNode_Found_ReturnsNode()
	{
		$this->transport->expects($this->once())
			->method('get')
			->with('/node/0')
			->will($this->returnValue(array('code'=>200,'data'=>array('data'=>array()))));

		$node = $this->client->getReferenceNode();
		$this->assertNotNull($node);
		$this->assertInstanceOf('Everyman\Neo4j\Node', $node);
		$this->assertEquals(0, $node->getId());
	}

	public function testNodeFactory_SetNodeFactory_ReturnsNodeFromFactory()
	{
		$this->client->setNodeFactory(function (Client $client, $properties=array()) {
			return new NodeFactoryTestClass_ClientTest($client);
		});

		$node = $this->client->makeNode();
		$this->assertInstanceOf('Everyman\Neo4j\NodeFactoryTestClass_ClientTest', $node);
	}

	public function testNodeFactory_SetNodeFactory_NotCallable_ThrowsException()
	{
		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->setNodeFactory('bar');
	}

	public function testNodeFactory_NodeFactoryReturnsNotNode_ThrowsException()
	{
		$this->client->setNodeFactory(function (Client $client, $properties=array()) {
			return new \stdClass();
		});

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$node = $this->client->makeNode();
	}

	public function testRelationshipFactory_SetRelationshipFactory_ReturnsRelationshipFromFactory()
	{
		$this->client->setRelationshipFactory(function (Client $client, $properties=array()) {
			return new RelFactoryTestClass_ClientTest($client);
		});

		$rel = $this->client->makeRelationship();
		$this->assertInstanceOf('Everyman\Neo4j\RelFactoryTestClass_ClientTest', $rel);
	}

	public function testRelationshipFactory_SetRelationshipFactory_NotCallable_ThrowsException()
	{
		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->setRelationshipFactory('bar');
	}

	public function testRelationshipFactory_RelationshipFactoryReturnsNotRelationship_ThrowsException()
	{
		$this->client->setRelationshipFactory(function (Client $client, $properties=array()) {
			return new \stdClass();
		});

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$rel = $this->client->makeRelationship();
	}
}

class NodeFactoryTestClass_ClientTest extends Node {}
class RelFactoryTestClass_ClientTest extends Relationship {}
