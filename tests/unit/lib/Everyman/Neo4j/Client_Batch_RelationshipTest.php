<?php
namespace Everyman\Neo4j;

class Client_Batch_RelationshipTest extends \PHPUnit_Framework_TestCase
{
	protected $transport = null;
	protected $batch = null;
	protected $client = null;

	public function setUp()
	{
		$this->transport = $this->getMock('Everyman\Neo4j\Transport');
		$this->client = new Client($this->transport);

		$this->batch = new Batch($this->client);
	}

	public function testCommitBatch_CreateRelationship_Success_ReturnsTrue()
	{
		$this->markTestIncomplete();

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
			'body' => array('to' => '/node/456', 'type' => 'TEST',
				'data' => array('foo' => 'bar','baz' => 'qux'))));
		
		$return = array('code' => 200, 'data' => array(
				array('id' => 0, 'location' => 'http://foo:1234/db/data/relationship/789')));

		$this->batch->save($rel);
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);
		$this->assertEquals(789, $rel->getId());
	}

	public function testCommitBatch_CreateRelationship_StartNodeUnidentified_ReturnsTrue()
	{
		$this->markTestIncomplete();

		$startNode = new Node($this->client);
		$endNode = new Node($this->client);
		$endNode->setId(456);

		$rel = new Relationship($this->client);
		$rel->setType('TEST')
			->setStartNode($startNode)
			->setEndNode($endNode);

		$request = array(
			array('id' => 1, 'method' => 'POST', 'to' => '/node', 'body' => array()),
			array('id' => 0, 'method' => 'POST', 'to' => '{1}/relationships',
				'body' => array('to' => '/node/456', 'type' => 'TEST')),
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
	}

	public function testCommitBatch_CreateRelationship_EndNodeUnidentified_ReturnsTrue()
	{
		$this->markTestIncomplete();

		$startNode = new Node($this->client);
		$startNode->setId(456);
		$endNode = new Node($this->client);

		$rel = new Relationship($this->client);
		$rel->setType('TEST')
			->setStartNode($startNode)
			->setEndNode($endNode)
			->setProperties(array('foo' => 'bar','baz' => 'qux'));

		$request = array(
			array('id' => 1, 'method' => 'POST', 'to' => '/node', 'body' => array()),
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
	}

	public function testCommitBatch_CreateRelationship_NeitherNodeUnidentified_ReturnsTrue()
	{
		$this->markTestIncomplete();

		$startNode = new Node($this->client);
		$endNode = new Node($this->client);

		$rel = new Relationship($this->client);
		$rel->setType('TEST')
			->setStartNode($startNode)
			->setEndNode($endNode)
			->setProperties(array('foo' => 'bar','baz' => 'qux'));

		$request = array(
			array('id' => 1, 'method' => 'POST', 'to' => '/node', 'body' => array()),
			array('id' => 2, 'method' => 'POST', 'to' => '/node', 'body' => array()),
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
	}

	public function testCommitBatch_CreateRelationship_UnidentifiedNodeAlreadySavedInBatch_ReturnsTrue()
	{
		$this->markTestIncomplete();

		$startNode = new Node($this->client);
		$endNode = new Node($this->client);

		$rel = new Relationship($this->client);
		$rel->setType('TEST')
			->setStartNode($startNode)
			->setEndNode($endNode)
			->setProperties(array('foo' => 'bar','baz' => 'qux'));

		$request = array(
			array('id' => 0, 'method' => 'POST', 'to' => '/node', 'body' => array()),
			array('id' => 1, 'method' => 'POST', 'to' => '/node', 'body' => array()),
			array('id' => 2, 'method' => 'POST', 'to' => '{0}/relationships',
				'body' => array('to' => '{1}', 'type' => 'TEST',
					'data' => array('foo' => 'bar','baz' => 'qux'))),
		);

		$return = array('code' => 200, 'data' => array(
			array('id' => 0, 'location' => 'http://foo:1234/db/data/node/123'),
			array('id' => 1, 'location' => 'http://foo:1234/db/data/node/456'),
			array('id' => 2, 'location' => 'http://foo:1234/db/data/relationship/789'),
		));

		$this->batch->save($startNode);
		$this->batch->save($endNode);
		$this->batch->save($rel);
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);

		$this->assertTrue($result);
		$this->assertEquals(789, $rel->getId());
		$this->assertEquals(123, $startNode->getId());
		$this->assertEquals(456, $endNode->getId());
	}

	public function testCommitBatch_UpdateRelationship_Success_ReturnsTrue()
	{
		$this->markTestIncomplete();

		$rel = new Relationship($this->client);
		$rel->setId(123)
			->setProperties(array('foo' => 'bar','baz' => 'qux'));

		$request = array(array('id' => 0, 'method' => 'PUT', 'to' => '/relationship/123/properties',
			'body' => array('foo' => 'bar','baz' => 'qux')));
		
		$return = array('code' => 200, 'data' => array(
				array('id' => 0)));

		$this->batch->save($rel);
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);
	}

	public function testCommitBatch_DeleteRelationship_Success_ReturnsTrue()
	{
		$this->markTestIncomplete();

		$rel = new Relationship($this->client);
		$rel->setId(123);

		$request = array(array('id' => 0, 'method' => 'DELETE', 'to' => '/relationship/123'));
		
		$return = array('code' => 200, 'data' => array(
				array('id' => 0)));

		$this->batch->delete($rel);
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
