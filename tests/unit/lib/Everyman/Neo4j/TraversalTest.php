<?php
namespace Everyman\Neo4j;

class TraversalTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $traversal = null;
	
	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array(), array(), '', false);
		$this->traversal = new Traversal($this->client);
	}

	public function testGetClient_ClientSetCorrectly_ReturnsClient()
	{
		$this->assertSame($this->client, $this->traversal->getClient());
	}

	public function testOrder_NoneGiven_ReturnsNull()
	{
		$this->assertNull($this->traversal->getOrder());
	}

	public function testOrder_OrderGiven_ReturnsString()
	{
		$this->traversal->setOrder(Traversal::OrderDepthFirst);
		$this->assertEquals(Traversal::OrderDepthFirst, $this->traversal->getOrder());
	}

	public function testUniqueness_NoneGiven_ReturnsNull()
	{
		$this->assertNull($this->traversal->getUniqueness());
	}

	public function testUniqueness_UniquenessGiven_ReturnsString()
	{
		$this->traversal->setUniqueness(Traversal::UniquenessNodeGlobal);
		$this->assertEquals(Traversal::UniquenessNodeGlobal, $this->traversal->getUniqueness());
	}

	public function testMaxDepth_NoneGiven_ReturnsNull()
	{
		$this->assertNull($this->traversal->getMaxDepth());
	}

	public function testMaxDepth_MaxDepthGiven_ReturnsInteger()
	{
		$this->traversal->setMaxDepth(3);
		$this->assertEquals(3, $this->traversal->getMaxDepth());
	}

	public function testRelationships_NoneGiven_ReturnsEmptyArray()
	{
		$relationships = $this->traversal->getRelationships();
		$this->assertEquals(array(), $relationships);
	}

	public function testRelationships_TypeGivenAndDirectionGiven_ReturnsArray()
	{
		$this->traversal->addRelationship('FOOTYPE');
		$this->traversal->addRelationship('BARTYPE', Relationship::DirectionOut);

		$relationships = $this->traversal->getRelationships();
		$this->assertEquals(array(
			array('type'=>'FOOTYPE'),
			array('type'=>'BARTYPE', 'direction'=>Relationship::DirectionOut),
		), $relationships);
	}

	public function testPruneEvaluator_NoneGiven_ReturnsNull()
	{
		$this->assertNull($this->traversal->getPruneEvaluator());
	}

	public function testPruneEvaluator_LanguageAndBody_ReturnsArray()
	{
		$this->traversal->setPruneEvaluator('javascript', 'return true;');

		$evaluator = $this->traversal->getPruneEvaluator();
		$this->assertEquals('javascript', $evaluator['language']);
		$this->assertEquals('return true;', $evaluator['body']);
	}

	public function testPruneEvaluator_BuiltIn_ReturnsArray()
	{
		$this->traversal->setPruneEvaluator(Traversal::PruneNone);

		$evaluator = $this->traversal->getPruneEvaluator();
		$this->assertEquals('builtin', $evaluator['language']);
		$this->assertEquals(Traversal::PruneNone, $evaluator['body']);
	}

	public function testPruneEvaluator_Reset_ReturnsNull()
	{
		$this->traversal->setPruneEvaluator('javascript', 'return true;');
		$this->traversal->setPruneEvaluator();

		$this->assertNull($this->traversal->getPruneEvaluator());
	}

	public function testReturnFilter_NoneGiven_ReturnsNull()
	{
		$this->assertNull($this->traversal->getReturnFilter());
	}

	public function testReturnFilter_LanguageAndBody_ReturnsArray()
	{
		$this->traversal->setReturnFilter('javascript', 'return true;');

		$filter = $this->traversal->getReturnFilter();
		$this->assertEquals('javascript', $filter['language']);
		$this->assertEquals('return true;', $filter['body']);
	}

	public function testReturnFilter_BuiltIn_ReturnsArray()
	{
		$this->traversal->setReturnFilter(Traversal::ReturnAllButStart);

		$filter = $this->traversal->getReturnFilter();
		$this->assertEquals('builtin', $filter['language']);
		$this->assertEquals(Traversal::ReturnAllButStart, $filter['body']);
	}

	public function testReturnFilter_Reset_ReturnsNull()
	{
		$this->traversal->setReturnFilter('javascript', 'return true;');
		$this->traversal->setReturnFilter();

		$this->assertNull($this->traversal->getPruneEvaluator());
	}

	public function testGetResults_PassesThroughToClient()
	{
		$startNode = new Node($this->client);

		$expectedNodes = array(new Node($this->client), new Node($this->client));

		$this->client->expects($this->once())
			->method('executeTraversal')
			->with($this->traversal, $startNode, Traversal::ReturnTypeNode)
			->will($this->returnValue($expectedNodes));

		$nodes = $this->traversal->getResults($startNode, Traversal::ReturnTypeNode);
		$this->assertEquals($expectedNodes, $nodes);
	}

	public function testGetSingleResult_PassesThroughToClient()
	{
		$startNode = new Node($this->client);

		$firstResult = new Node($this->client);
		$expectedNodes = array($firstResult, new Node($this->client));

		$this->client->expects($this->once())
			->method('executeTraversal')
			->with($this->traversal, $startNode, Traversal::ReturnTypeNode)
			->will($this->returnValue($expectedNodes));

		$result = $this->traversal->getSingleResult($startNode, Traversal::ReturnTypeNode);
		$this->assertSame($firstResult, $result);
	}

	public function testGetSingleResult_NoResults_ReturnsNull()
	{
		$startNode = new Node($this->client);

		$expectedNodes = array();

		$this->client->expects($this->once())
			->method('executeTraversal')
			->with($this->traversal, $startNode, Traversal::ReturnTypeNode)
			->will($this->returnValue($expectedNodes));

		$result = $this->traversal->getSingleResult($startNode, Traversal::ReturnTypeNode);
		$this->assertNull($result);
	}
}

