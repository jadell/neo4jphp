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

	public function testAdd_AllEntitiesIdentified_ReturnsIntegerOperationIndex()
	{
		$nodeA = new Node($this->client);
		$nodeA->setId(123);

		$nodeB = new Node($this->client);
		$nodeB->setId(456);

		$nodeC = new Node($this->client);
		$nodeC->setId(789);

		$rel = new Relationship($this->client);
		$rel->setId(987)
			->setStartNode($nodeA)
			->setEndNode($nodeB);
			
		$this->assertEquals(0, $this->batch->add($nodeA));
		$this->assertEquals(1, $this->batch->add($nodeB));
		$this->assertEquals(2, $this->batch->add($nodeC));
		$this->assertEquals(3, $this->batch->add($rel));
	}
}

