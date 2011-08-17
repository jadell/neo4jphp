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
			->will($this->returnValue(true));

		$this->assertTrue($this->batch->commit());
	}

	public function testCommit_PassesSelfToClient_Failure_ReturnsFalse()
	{
		$this->client->expects($this->once())
			->method('commitBatch')
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

	public function testGetOperations_MixedOperations_ReturnsOperations()
	{
		$nodeA = new Node($this->client);
			
		$this->assertEquals(0, $this->batch->save($nodeA));
		$this->assertEquals(1, $this->batch->delete($nodeA));
		
		$expected = array(
			array('operation'=>'save', 'entity'=>$nodeA),
			array('operation'=>'delete', 'entity'=>$nodeA),
		);
		$this->assertEquals($expected, $this->batch->getOperations());
	}
}

