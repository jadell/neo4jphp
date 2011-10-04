<?php
namespace Everyman\Neo4j;

class Client_Batch_IndexTest extends \PHPUnit_Framework_TestCase
{
	protected $transport = null;
	protected $batch = null;
	protected $client = null;
	protected $endpoint = 'http://foo:1234/db/data';

	public function setUp()
	{
		$this->transport = $this->getMock('Everyman\Neo4j\Transport');
		$this->transport->expects($this->any())
			->method('getEndpoint')
			->will($this->returnValue($this->endpoint));
		$this->client = new Client($this->transport);

		$this->batch = new Batch($this->client);
	}

	public function testCommitBatch_AddToIndex_NodeExists_Success_ReturnsTrue()
	{
		$node = new Node($this->client);
		$node->setId(123);

		$index = new Index($this->client, Index::TypeNode, 'indexname');

		$request = array(array('id' => 0, 'method' => 'POST',
			'to' => '/index/node/indexname',
			'body' => array(
				'key'   => 'somekey',
				'value' => 'somevalue',
				'uri'   => $this->endpoint.'/node/123',
			)
		));
		
		$return = array('code' => 200, 'data' => array(array('id' => 0)));

		$this->batch->addToIndex($index, $node, 'somekey', 'somevalue');
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);
	}

	public function testCommitBatch_AddToIndex_NodeDoesNotExist_Success_ReturnsTrue()
	{
		$node = new Node($this->client);

		$index = new Index($this->client, Index::TypeNode, 'indexname');

		$request = array(
			array('id' => 1, 'method' => 'POST', 'to' => '/node', 'body' => null),
			array('id' => 0, 'method' => 'POST',
				'to' => '/index/node/indexname',
				'body' => array(
					'key'   => 'somekey',
					'value' => 'somevalue',
					'uri'   => '{1}',
				)
			));
		
		$return = array('code' => 200, 'data' => array(
			array('id' => 1, 'location' => 'http://foo:1234/db/data/node/123'),
			array('id' => 0)
		));

		$this->batch->addToIndex($index, $node, 'somekey', 'somevalue');
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);
		$this->assertEquals(123, $node->getId());
	}

	public function testCommitBatch_AddToIndex_RelationshipExists_Success_ReturnsTrue()
	{
		$rel = new Relationship($this->client);
		$rel->setId(123);

		$index = new Index($this->client, Index::TypeRelationship, 'indexname');

		$request = array(array('id' => 0, 'method' => 'POST',
			'to' => '/index/relationship/indexname',
			'body' => array(
				'key'   => 'somekey',
				'value' => 'somevalue',
				'uri'   => $this->endpoint.'/relationship/123',
			)
		));
		
		$return = array('code' => 200, 'data' => array(array('id' => 0)));

		$this->batch->addToIndex($index, $rel, 'somekey', 'somevalue');
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);
	}

	public function testCommitBatch_AddToIndex_NoEntitiesExist_Success_ReturnsTrue()
	{
		$nodeA = new Node($this->client);
		$nodeB = new Node($this->client);
		$rel = new Relationship($this->client);
		$rel->setType('TEST')
			->setStartNode($nodeA)
			->setEndNode($nodeB);

		$index = new Index($this->client, Index::TypeRelationship, 'indexname');

		$request = array(
			array('id' => 2, 'method' => 'POST', 'to' => '/node', 'body' => null),
			array('id' => 3, 'method' => 'POST', 'to' => '/node', 'body' => null),
			array('id' => 1, 'method' => 'POST', 'to' => '{2}/relationships',
				'body' => array('to' => '{3}', 'type' => 'TEST')
			),
			array('id' => 0, 'method' => 'POST',
				'to' => '/index/relationship/indexname',
				'body' => array(
					'key'   => 'somekey',
					'value' => 'somevalue',
					'uri'   => '{1}',
				)
			)
		);
		
		$return = array('code' => 200, 'data' => array(
			array('id' => 2, 'location' => 'http://foo:1234/db/data/node/123'),
			array('id' => 3, 'location' => 'http://foo:1234/db/data/node/456'),
			array('id' => 1, 'location' => 'http://foo:1234/db/data/relationship/789'),
			array('id' => 0)
		));

		$this->batch->addToIndex($index, $rel, 'somekey', 'somevalue');
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);
		$this->assertEquals(123, $nodeA->getId());
		$this->assertEquals(456, $nodeB->getId());
		$this->assertEquals(789, $rel->getId());
	}

	public function testCommitBatch_RemoveFromIndex_Entity_Success_ReturnsTrue()
	{
		$node = new Node($this->client);
		$node->setId(123);

		$index = new Index($this->client, Index::TypeNode, 'indexname');

		$request = array(array('id' => 0, 'method' => 'DELETE',
			'to' => '/index/node/indexname/123'));
		
		$return = array('code' => 200, 'data' => array(array('id' => 0)));

		$this->batch->removeFromIndex($index, $node);
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);
	}

	public function testCommitBatch_RemoveFromIndex_EntityKey_Success_ReturnsTrue()
	{
		$node = new Node($this->client);
		$node->setId(123);

		$index = new Index($this->client, Index::TypeNode, 'indexname');

		$request = array(array('id' => 0, 'method' => 'DELETE',
			'to' => '/index/node/indexname/somekey/123'));
		
		$return = array('code' => 200, 'data' => array(array('id' => 0)));

		$this->batch->removeFromIndex($index, $node, 'somekey');
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);
	}

	public function testCommitBatch_RemoveFromIndex_EntityKeyValue_Success_ReturnsTrue()
	{
		$node = new Node($this->client);
		$node->setId(123);

		$index = new Index($this->client, Index::TypeNode, 'indexname');

		$request = array(array('id' => 0, 'method' => 'DELETE',
			'to' => '/index/node/indexname/somekey/somevalue/123'));
		
		$return = array('code' => 200, 'data' => array(array('id' => 0)));

		$this->batch->removeFromIndex($index, $node, 'somekey', 'somevalue');
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);
	}

	protected function setupTransportExpectation($request, $will)
	{
		$this->transport->expects($this->once())
			->method('post')
			->with('/batch', $request)
			->will($will);
	}
}
