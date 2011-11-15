<?php
namespace Everyman\Neo4j;

class Client_Batch_RelationshipTest extends \PHPUnit_Framework_TestCase
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

		$this->client->getEntityCache()->setCache(new Cache\Variable());
	}

	public function testCommitBatch_CreateRelationship_Success_ReturnsTrue()
	{
		$startNode = new Node($this->client);
		$startNode->setId(123);
		$endNode = new Node($this->client);
		$endNode->setId(456);

		$rel = new Relationship($this->client);
		$rel->setType('TEST')
			->setStartNode($startNode)
			->setEndNode($endNode)
			->setProperties(array('foo' => 'bar','baz' => 'qux'));

		$request = array(array('id' => 0, 'method' => 'POST', 'to' => '/node/123/relationships',
			'body' => array('to' => $this->endpoint.'/node/456', 'type' => 'TEST',
				'data' => array('foo' => 'bar','baz' => 'qux'))));
		
		$return = array('code' => 200, 'data' => array(
				array('id' => 0, 'location' => 'http://foo:1234/db/data/relationship/789')));

		$this->batch->save($rel);
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);
		$this->assertEquals(789, $rel->getId());

		$this->assertSame($rel, $this->client->getEntityCache()->getCachedEntity(789, 'relationship'));
	}

	public function testCommitBatch_CreateRelationship_StartNodeUnidentified_ReturnsTrue()
	{
		$startNode = new Node($this->client);
		$endNode = new Node($this->client);
		$endNode->setId(456);

		$rel = new Relationship($this->client);
		$rel->setType('TEST')
			->setStartNode($startNode)
			->setEndNode($endNode);

		$request = array(
			array('id' => 1, 'method' => 'POST', 'to' => '/node', 'body' => null),
			array('id' => 0, 'method' => 'POST', 'to' => '{1}/relationships',
				'body' => array('to' => $this->endpoint.'/node/456', 'type' => 'TEST')),
		);

		$return = array('code' => 200, 'data' => array(
			array('id' => 1, 'location' => 'http://foo:1234/db/data/node/123'),
			array('id' => 0, 'location' => 'http://foo:1234/db/data/relationship/789'),
		));

		$this->batch->save($rel);
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);

		$this->assertTrue($result);
		$this->assertEquals(789, $rel->getId());
		$this->assertEquals(123, $startNode->getId());

		$this->assertSame($rel, $this->client->getEntityCache()->getCachedEntity(789, 'relationship'));
		$this->assertSame($startNode, $this->client->getEntityCache()->getCachedEntity(123, 'node'));
	}

	public function testCommitBatch_CreateRelationship_EndNodeUnidentified_ReturnsTrue()
	{
		$startNode = new Node($this->client);
		$startNode->setId(456);
		$endNode = new Node($this->client);

		$rel = new Relationship($this->client);
		$rel->setType('TEST')
			->setStartNode($startNode)
			->setEndNode($endNode)
			->setProperties(array('foo' => 'bar','baz' => 'qux'));

		$request = array(
			array('id' => 1, 'method' => 'POST', 'to' => '/node', 'body' => null),
			array('id' => 0, 'method' => 'POST', 'to' => '/node/456/relationships',
				'body' => array('to' => '{1}', 'type' => 'TEST',
					'data' => array('foo' => 'bar','baz' => 'qux'))),
		);

		$return = array('code' => 200, 'data' => array(
			array('id' => 1, 'location' => 'http://foo:1234/db/data/node/123'),
			array('id' => 0, 'location' => 'http://foo:1234/db/data/relationship/789'),
		));

		$this->batch->save($rel);
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);

		$this->assertTrue($result);
		$this->assertEquals(789, $rel->getId());
		$this->assertEquals(123, $endNode->getId());

		$this->assertSame($rel, $this->client->getEntityCache()->getCachedEntity(789, 'relationship'));
		$this->assertSame($endNode, $this->client->getEntityCache()->getCachedEntity(123, 'node'));
	}

	public function testCommitBatch_CreateRelationship_NeitherNodeUnidentified_ReturnsTrue()
	{
		$startNode = new Node($this->client);
		$endNode = new Node($this->client);

		$rel = new Relationship($this->client);
		$rel->setType('TEST')
			->setStartNode($startNode)
			->setEndNode($endNode)
			->setProperties(array('foo' => 'bar','baz' => 'qux'));

		$request = array(
			array('id' => 1, 'method' => 'POST', 'to' => '/node', 'body' => null),
			array('id' => 2, 'method' => 'POST', 'to' => '/node', 'body' => null),
			array('id' => 0, 'method' => 'POST', 'to' => '{1}/relationships',
				'body' => array('to' => '{2}', 'type' => 'TEST',
					'data' => array('foo' => 'bar','baz' => 'qux'))),
		);

		$return = array('code' => 200, 'data' => array(
			array('id' => 1, 'location' => 'http://foo:1234/db/data/node/123'),
			array('id' => 2, 'location' => 'http://foo:1234/db/data/node/456'),
			array('id' => 0, 'location' => 'http://foo:1234/db/data/relationship/789'),
		));

		$this->batch->save($rel);
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);

		$this->assertTrue($result);
		$this->assertEquals(789, $rel->getId());
		$this->assertEquals(123, $startNode->getId());
		$this->assertEquals(456, $endNode->getId());

		$this->assertSame($rel, $this->client->getEntityCache()->getCachedEntity(789, 'relationship'));
		$this->assertSame($startNode, $this->client->getEntityCache()->getCachedEntity(123, 'node'));
		$this->assertSame($endNode, $this->client->getEntityCache()->getCachedEntity(456, 'node'));
	}

	public function testCommitBatch_CreateRelationship_UnidentifiedNodeAlreadySavedInBatch_ReturnsTrue()
	{
		$startNode = new Node($this->client);
		$endNode = new Node($this->client);

		$rel = new Relationship($this->client);
		$rel->setType('TEST')
			->setStartNode($startNode)
			->setEndNode($endNode)
			->setProperties(array('foo' => 'bar','baz' => 'qux'));

		$request = array(
			array('id' => 0, 'method' => 'POST', 'to' => '/node', 'body' => null),
			array('id' => 2, 'method' => 'POST', 'to' => '/node', 'body' => null),
			array('id' => 1, 'method' => 'POST', 'to' => '{0}/relationships',
				'body' => array('to' => '{2}', 'type' => 'TEST',
					'data' => array('foo' => 'bar','baz' => 'qux'))),
		);

		$return = array('code' => 200, 'data' => array(
			array('id' => 0, 'location' => 'http://foo:1234/db/data/node/123'),
			array('id' => 2, 'location' => 'http://foo:1234/db/data/node/456'),
			array('id' => 1, 'location' => 'http://foo:1234/db/data/relationship/789'),
		));

		$this->batch->save($startNode);
		$this->batch->save($rel);
		$this->batch->save($endNode);
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);

		$this->assertTrue($result);
		$this->assertEquals(789, $rel->getId());
		$this->assertEquals(123, $startNode->getId());
		$this->assertEquals(456, $endNode->getId());
	}

	public function testCommitBatch_UpdateRelationship_Success_ReturnsTrue()
	{
		$rel = new Relationship($this->client);
		$rel->useLazyLoad(false)
			->setId(123)
			->setProperties(array('foo' => 'bar','baz' => 'qux'));

		$request = array(array('id' => 0, 'method' => 'PUT', 'to' => '/relationship/123/properties',
			'body' => array('foo' => 'bar','baz' => 'qux')));
		
		$return = array('code' => 200, 'data' => array(
				array('id' => 0)));

		$this->batch->save($rel);
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);

		$this->assertSame($rel, $this->client->getEntityCache()->getCachedEntity(123, 'relationship'));
	}

	public function testCommitBatch_DeleteRelationship_Success_ReturnsTrue()
	{
		$rel = new Relationship($this->client);
		$rel->setId(123);
		$this->client->getEntityCache()->setCachedEntity($rel);

		$request = array(array('id' => 0, 'method' => 'DELETE', 'to' => '/relationship/123'));
		
		$return = array('code' => 200, 'data' => array(
				array('id' => 0)));

		$this->batch->delete($rel);
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);

		$this->assertFalse($this->client->getEntityCache()->getCachedEntity(123, 'relationship'));
	}

	public function testImplicitBatch_StartBatch_CloseBatch_ExpectedBatchRequest()
	{
		$startNode = new Node($this->client);
		$endNode = new Node($this->client);
		$endNode->setId(456)->useLazyLoad(false);
		$rel = new Relationship($this->client);
		$rel->setType('TEST')
			->setStartNode($startNode)
			->setEndNode($endNode);

		$deleteNode = new Node($this->client);
		$deleteNode->setId(987);

		$deleteRel = new Relationship($this->client);
		$deleteRel->setId(321);

		$addIndexNode = new Node($this->client);
		$addIndexNode->setId(654);
		$removeIndexNode = new Node($this->client);
		$removeIndexNode->setId(209);
		$index = new Index($this->client, Index::TypeNode, 'indexname');

		$request = array(
			array('id' => 0, 'method' => 'POST', 'to' => '/node', 'body' => null),
			array('id' => 1, 'method' => 'PUT', 'to' => '/node/456/properties', 'body' => array()),
			array('id' => 2, 'method' => 'POST', 'to' => '{0}/relationships',
				'body' => array(
					'to' => $this->endpoint.'/node/456',
					'type' => 'TEST'
				)
			),
			array('id' => 3, 'method' => 'DELETE', 'to' => '/node/987'),
			array('id' => 4, 'method' => 'DELETE', 'to' => '/relationship/321'),
			array('id' => 5, 'method' => 'POST', 'to' => '/index/node/indexname',
				'body' => array(
					'key'   => 'addkey',
					'value' => 'addvalue',
					'uri'   => $this->endpoint.'/node/654',
				)
			),
			array('id' => 6, 'method' => 'DELETE', 'to' => '/index/node/indexname/removekey/removevalue/209'),
		);

		$return = array('code' => 200, 'data' => array(
			array('id' => 0, 'location' => 'http://foo:1234/db/data/node/123'),
			array('id' => 1),
			array('id' => 2, 'location' => 'http://foo:1234/db/data/relationship/789'),
			array('id' => 3),
			array('id' => 4),
			array('id' => 5),
			array('id' => 6),
		));

		$this->setupTransportExpectation($request, $this->returnValue($return));

		$batch = $this->client->startBatch();
		$this->assertInstanceOf('Everyman\Neo4j\Batch', $batch);

		$startNode->save();
		$endNode->save();
		$rel->save();
		$deleteNode->delete();
		$deleteRel->delete();
		$index->add($addIndexNode, 'addkey', 'addvalue');
		$index->remove($removeIndexNode, 'removekey', 'removevalue');

		$this->assertTrue($this->client->commitBatch());
		$this->assertEquals(789, $rel->getId());
		$this->assertEquals(123, $startNode->getId());
	}

	protected function setupTransportExpectation($request, $will)
	{
		$this->transport->expects($this->once())
			->method('post')
			->with('/batch', $request)
			->will($will);
	}
}
