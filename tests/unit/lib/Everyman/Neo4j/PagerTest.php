<?php
namespace Everyman\Neo4j;

class PagerTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $traversal = null;
	protected $node = null;
	protected $returnType = null;
	protected $pager = null;
	
	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array(), array(), '', false);
		$this->traversal = new Traversal($this->client);

		$this->node = new Node($this->client);
		$this->node->setId(123);

		$this->returnType = Traversal::ReturnTypeNode;

		$this->pager = new Pager($this->traversal, $this->node, $this->returnType);
	}

	public function testConstruct_SetsParametersCorrectly_ReturnsCorrectValues()
	{
		$this->assertSame($this->traversal, $this->pager->getTraversal());
		$this->assertSame($this->node, $this->pager->getStartNode());
		$this->assertEquals($this->returnType, $this->pager->getReturnType());
	}

	public function testPageSize_NoneGiven_ReturnsNull()
	{
		$this->assertNull($this->pager->getPageSize());
	}

	public function testPageSize_PageSizeGiven_ReturnsInteger()
	{
		$this->pager->setPageSize(10);
		$this->assertEquals(10, $this->pager->getPageSize());
	}

	public function testLeaseTime_NoneGiven_ReturnsNull()
	{
		$this->assertNull($this->pager->getLeaseTime());
	}

	public function testLeaseTime_LeaseTimeGiven_ReturnsInteger()
	{
		$this->pager->setLeaseTime(30);
		$this->assertEquals(30, $this->pager->getLeaseTime());
	}

	public function testId_NoneGiven_ReturnsNull()
	{
		$this->assertNull($this->pager->getId());
	}

	public function testId_IdGiven_ReturnsString()
	{
		$this->pager->setId('thisistheid');
		$this->assertEquals('thisistheid', $this->pager->getId());
	}

	public function testGetNextResults_PassesThroughToClient()
	{
		$expectedNodes = array(new Node($this->client), new Node($this->client));

		$this->client->expects($this->once())
			->method('executePagedTraversal')
			->with($this->pager)
			->will($this->returnValue($expectedNodes));

		$nodes = $this->pager->getNextResults();
		$this->assertEquals($expectedNodes, $nodes);
	}
}

