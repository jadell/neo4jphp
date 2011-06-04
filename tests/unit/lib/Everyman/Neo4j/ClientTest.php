<?php
namespace Everyman\Neo4j;

class ClientTest extends \PHPUnit_Framework_TestCase
{
	protected $transport = null;
	protected $client = null;

	public function setUp()
	{
		$this->transport = $this->getMock('Everyman\Neo4j\Transport');
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

	public function testGetRelationship_Found_ReturnsNode()
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
}
