<?php
namespace Everyman\Neo4j;

class BatchTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $batch = null;

	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array(), array(), '', false);
		$this->batch = new Batch($this->client);
	}

	public function testGetClient_ClientSetCorrectly_ReturnsClient()
	{
		$this->assertSame($this->client, $this->batch->getClient());
	}

	public function testCommit_PassesSelfToClient_Success_ReturnsTrue()
	{
		$this->client->expects($this->once())
			->method('commitBatch')
			->with($this->batch)
			->will($this->returnValue(true));

		$this->assertTrue($this->batch->commit());
	}

	public function testCommit_PassesSelfToClient_Failure_ReturnsFalse()
	{
		$this->client->expects($this->once())
			->method('commitBatch')
			->with($this->batch)
			->will($this->returnValue(false));

		$this->assertFalse($this->batch->commit());
	}

	public function testCommit_CommitMoreThanOnce_ThrowsException()
	{
		$this->client->expects($this->once())
			->method('commitBatch');

		$this->batch->commit();
		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->batch->commit();
	}

	public function testSave_PropertyContainerEntities_ReturnsIntegerOperationIndex()
	{
		$nodeA = new Node($this->client);
		$nodeA->setId(123);

		$nodeB = new Node($this->client);
		$nodeB->setId(456);

		$nodeC = new Node($this->client);

		$rel = new Relationship($this->client);
		$rel->setId(987)
			->setStartNode($nodeA)
			->setEndNode($nodeB);
			
		$this->assertEquals(0, $this->batch->save($nodeA));
		$this->assertEquals(1, $this->batch->save($nodeB));
		$this->assertEquals(2, $this->batch->save($nodeC));
		$this->assertEquals(3, $this->batch->save($rel));
	}

	public function testSave_SameEntityMoreThanOnce_ReturnsIntegerOperationIndex()
	{
		$nodeA = new Node($this->client);
			
		$this->assertEquals(0, $this->batch->save($nodeA));
		$this->assertEquals(0, $this->batch->save($nodeA));
	}

	public function testDelete_PropertyContainerEntities_ReturnsIntegerOperationIndex()
	{
		$nodeA = new Node($this->client);
		$nodeA->setId(123);

		$nodeB = new Node($this->client);
		$nodeB->setId(456);

		$nodeC = new Node($this->client);

		$rel = new Relationship($this->client);
		$rel->setId(987)
			->setStartNode($nodeA)
			->setEndNode($nodeB);
			
		$this->assertEquals(0, $this->batch->delete($nodeA));
		$this->assertEquals(1, $this->batch->delete($nodeB));
		$this->assertEquals(2, $this->batch->delete($nodeC));
		$this->assertEquals(3, $this->batch->delete($rel));
	}

	public function testDelete_SameEntityMoreThanOnce_ReturnsIntegerOperationIndex()
	{
		$nodeA = new Node($this->client);
			
		$this->assertEquals(0, $this->batch->delete($nodeA));
		$this->assertEquals(0, $this->batch->delete($nodeA));
	}

	public function testAddToIndex_Index_ReturnsIntegerOperationIndex()
	{
		$nodeA = new Node($this->client);
		$nodeA->setId(123);
		$nodeB = new Node($this->client);
		$nodeB->setId(456);

		$index = new Index($this->client, Index::TypeNode, 'indexname');

		$this->assertEquals(0, $this->batch->addToIndex($index, $nodeA, 'somekey', 'somevalue'));
		$this->assertEquals(1, $this->batch->addToIndex($index, $nodeB, 'otherkey', 'othervalue'));
		$this->assertEquals(2, $this->batch->addToIndex($index, $nodeB, 'diffkey', 'diffvalue'));
	}

	public function testAddToIndex_SameEntitySameKeyValueMoreThanOnce_ReturnsIntegerOperationIndex()
	{
		$nodeA = new Node($this->client);
		$index = new Index($this->client, Index::TypeNode, 'indexname');
			
		$this->assertEquals(0, $this->batch->addToIndex($index, $nodeA, 'somekey', 'somevalue'));
		$this->assertEquals(0, $this->batch->addToIndex($index, $nodeA, 'somekey', 'somevalue'));
	}

	public function testRemoveFromIndex_Index_ReturnsIntegerOperationIndex()
	{
		$nodeA = new Node($this->client);
		$nodeA->setId(123);
		$nodeB = new Node($this->client);
		$nodeB->setId(456);

		$index = new Index($this->client, Index::TypeNode, 'indexname');

		$this->assertEquals(0, $this->batch->removeFromIndex($index, $nodeA, 'somekey', 'somevalue'));
		$this->assertEquals(1, $this->batch->removeFromIndex($index, $nodeA, 'otherkey'));
		$this->assertEquals(2, $this->batch->removeFromIndex($index, $nodeA));
		$this->assertEquals(3, $this->batch->removeFromIndex($index, $nodeB, 'diffkey', 'diffvalue'));
	}

	public function testRemoveFromIndex_SameEntitySameKeyValueMoreThanOnce_ReturnsIntegerOperationIndex()
	{
		$nodeA = new Node($this->client);
		$index = new Index($this->client, Index::TypeNode, 'indexname');
			
		$this->assertEquals(0, $this->batch->removeFromIndex($index, $nodeA, 'somekey', 'somevalue'));
		$this->assertEquals(0, $this->batch->removeFromIndex($index, $nodeA, 'somekey', 'somevalue'));
			
		$this->assertEquals(1, $this->batch->removeFromIndex($index, $nodeA, 'otherkey'));
		$this->assertEquals(1, $this->batch->removeFromIndex($index, $nodeA, 'otherkey'));
			
		$this->assertEquals(2, $this->batch->removeFromIndex($index, $nodeA));
		$this->assertEquals(2, $this->batch->removeFromIndex($index, $nodeA));
	}

	public function testGetOperations_MixedOperations_ReturnsOperations()
	{
		$nodeA = new Node($this->client);
			
		$this->assertEquals(0, $this->batch->save($nodeA));
		$this->assertEquals(1, $this->batch->delete($nodeA));

		$operations = $this->batch->getOperations();
		$this->assertInternalType('array', $operations);
		$this->assertEquals(2, count($operations));

		$saveMatch = new Batch\Save($this->batch, $nodeA, 123);
		$deleteMatch = new Batch\Delete($this->batch, $nodeA, 456);
		$this->assertEquals($saveMatch->matchId(), $operations[0]->matchId());
		$this->assertEquals($deleteMatch->matchId(), $operations[1]->matchId());
	}

	public function testReserve_OperationNotReserved_ReturnsOperation()
	{
		$nodeA = new Node($this->client);
		$opId = $this->batch->save($nodeA);

		$reservation = $this->batch->reserve($opId);
		$this->assertInstanceOf('Everyman\Neo4j\Batch\Operation', $reservation);

		$saveMatch = new Batch\Save($this->batch, $nodeA, 123);
		$this->assertEquals($saveMatch->matchId(), $reservation->matchId());
	}

	public function testReserve_OperationAlreadyReserved_ReturnsFalse()
	{
		$nodeA = new Node($this->client);
		$opId = $this->batch->save($nodeA);

		$temp = $this->batch->reserve($opId);
		$reservation = $this->batch->reserve($opId);
		$this->assertFalse($reservation);
	}

	public function testReserve_OperationNotExists_ReturnsFalse()
	{
		$reservation = $this->batch->reserve(0);
		$this->assertFalse($reservation);
	}
}

