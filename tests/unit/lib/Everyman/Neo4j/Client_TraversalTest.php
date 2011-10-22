<?php
namespace Everyman\Neo4j;

class Client_TraversalTest extends \PHPUnit_Framework_TestCase
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
	
	public function testTraversal_NoNodeId_ThrowsException()
	{
		$traversal = new Traversal($this->client);
		$node = new Node($this->client);

		$this->setExpectedException('\Everyman\Neo4j\Exception');
		$this->client->executeTraversal($traversal, $node, Traversal::ReturnTypeNode);
	}

	public function testTraversal_BadReturnType_ThrowsException()
	{
		$traversal = new Traversal($this->client);
		$node = new Node($this->client);
		$node->setId(1);

		$this->setExpectedException('\Everyman\Neo4j\Exception');
		$this->client->executeTraversal($traversal, $node, 'FOOTYPE');
	}

	/**
	 * @dataProvider dataProvider_TestTraversal
	 */
	public function testTraversal_TraversalOptions_PassesThroughCorrectDataToTransport($traversal, $expectedData)
	{
		$node = new Node($this->client);
		$node->setId(1);

		$this->transport->expects($this->once())
			->method('post')
			->with('/node/1/traverse/node', $expectedData)
			->will($this->returnValue(array("code"=>200,"data"=>array())));

		$result = $this->client->executeTraversal($traversal, $node, Traversal::ReturnTypeNode);
		$this->assertEquals(array(), $result);
	}
	
	public function dataProvider_TestTraversal()
	{
		$this->transport = $this->getMock('Everyman\Neo4j\Transport');
		$this->transport->expects($this->any())
			->method('getEndpoint')
			->will($this->returnValue($this->endpoint));
		$this->client = new Client($this->transport);

		$scenarios = array();

		$traversal = new Traversal($this->client);
		$scenarios[] = array($traversal, array());

		$traversal = new Traversal($this->client);
		$traversal->setOrder(Traversal::OrderDepthFirst);
		$scenarios[] = array($traversal, array(
			"order" => "depth_first",
		));

		$traversal = new Traversal($this->client);
		$traversal->setUniqueness(Traversal::UniquenessNodePath);
		$scenarios[] = array($traversal, array(
			"uniqueness" => "node_path",
		));

		$traversal = new Traversal($this->client);
		$traversal->setMaxDepth(2);
		$scenarios[] = array($traversal, array(
			"max_depth" => 2,
		));

		$traversal = new Traversal($this->client);
		$traversal->addRelationship('FOOTYPE')
			->addRelationship('BARTYPE', Relationship::DirectionIn);
		$scenarios[] = array($traversal, array(
			"relationships" => array(
				array('type'=>'FOOTYPE'),
				array('type'=>'BARTYPE', 'direction' => 'in'),
			),
		));

		$traversal = new Traversal($this->client);
		$traversal->setPruneEvaluator('javascript', "position.endNode().getProperty('date')>1234567;");
		$scenarios[] = array($traversal, array(
			"prune_evaluator" => array(
				"language" => "javascript",
				"body" => "position.endNode().getProperty('date')>1234567;",
			),
		));

		$traversal = new Traversal($this->client);
		$traversal->setPruneEvaluator(Traversal::PruneNone);
		$scenarios[] = array($traversal, array(
			"prune_evaluator" => array(
				"language" => "builtin",
				"name" => "none",
			),
		));

		$traversal = new Traversal($this->client);
		$traversal->setReturnFilter('javascript', "position.endNode().getProperty('date')>1234567;");
		$scenarios[] = array($traversal, array(
			"return_filter" => array(
				"language" => "javascript",
				"body" => "position.endNode().getProperty('date')>1234567;",
			),
		));

		$traversal = new Traversal($this->client);
		$traversal->setReturnFilter(Traversal::ReturnAll);
		$scenarios[] = array($traversal, array(
			"return_filter" => array(
				"language" => "builtin",
				"name" => "all",
			),
		));

		return $scenarios;
	}

	public function testTraversal_ReturnTypeNode_ReturnsArrayOfNodes()
	{
		$traversal = new Traversal($this->client);
		$node = new Node($this->client);
		$node->setId(1);

		$data = array(
			array(
				"self" => "http://localhost:7474/db/data/node/2",
				"data" => array(
					"name" => "foo",
				),
			),
		);

		$this->transport->expects($this->once())
			->method('post')
			->with('/node/1/traverse/node', array())
			->will($this->returnValue(array("code"=>200,"data"=>$data)));

		$result = $this->client->executeTraversal($traversal, $node, Traversal::ReturnTypeNode);
		$this->assertEquals(1, count($result));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[0]);
		$this->assertEquals(2, $result[0]->getId());
		$this->assertEquals('foo', $result[0]->getProperty('name'));
	}

	public function testTraversal_ReturnTypeRelationship_ReturnsArrayOfRelationships()
	{
		$traversal = new Traversal($this->client);
		$node = new Node($this->client);
		$node->setId(1);

		$data = array(
			array(
				"self" => "http://localhost:7474/db/data/relationship/2",
				"start" => "http://localhost:7474/db/data/node/1",
				"end" => "http://localhost:7474/db/data/node/3",
				"type" => "FOOTYPE",
				"data" => array(
					"name" => "foo",
				),
			),
		);

		$this->transport->expects($this->once())
			->method('post')
			->with('/node/1/traverse/relationship', array())
			->will($this->returnValue(array("code"=>200,"data"=>$data)));

		$result = $this->client->executeTraversal($traversal, $node, Traversal::ReturnTypeRelationship);
		$this->assertEquals(1, count($result));
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $result[0]);
		$this->assertEquals(2, $result[0]->getId());
		$this->assertEquals('foo', $result[0]->getProperty('name'));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[0]->getStartNode());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[0]->getEndNode());
		$this->assertEquals(1, $result[0]->getStartNode()->getId());
		$this->assertEquals(3, $result[0]->getEndNode()->getId());
	}

	public function testTraversal_ReturnTypePath_ReturnsArrayOfPaths()
	{
		$traversal = new Traversal($this->client);
		$node = new Node($this->client);
		$node->setId(1);

		$data = array(
			array(
				"relationships" => array("http://localhost:7474/db/data/relationship/2"),
				"nodes" => array("http://localhost:7474/db/data/node/1","http://localhost:7474/db/data/node/3"),
			),
		);

		$this->transport->expects($this->once())
			->method('post')
			->with('/node/1/traverse/path', array())
			->will($this->returnValue(array("code"=>200,"data"=>$data)));

		$result = $this->client->executeTraversal($traversal, $node, Traversal::ReturnTypePath);
		$this->assertEquals(1, count($result));
		$this->assertInstanceOf('Everyman\Neo4j\Path', $result[0]);
		
		$rels = $result[0]->getRelationships();
		$this->assertEquals(1, count($rels));
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rels[0]);
		$this->assertEquals(2, $rels[0]->getId());

		$nodes = $result[0]->getNodes();
		$this->assertEquals(2, count($nodes));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $nodes[0]);
		$this->assertEquals(1, $nodes[0]->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $nodes[1]);
		$this->assertEquals(3, $nodes[1]->getId());
	}

	public function testTraversal_ReturnTypeFullPath_ReturnsArrayOfPaths()
	{
		$traversal = new Traversal($this->client);
		$node = new Node($this->client);
		$node->setId(1);

		$data = array(
			array(
				"relationships" => array(
					array(
						"self" => "http://localhost:7474/db/data/relationship/2",
						"start" => "http://localhost:7474/db/data/node/1",
						"end" => "http://localhost:7474/db/data/node/3",
						"type" => "FOOTYPE",
						"data" => array(
							"name" => "baz",
						),
					),
				),
				"nodes" => array(
					array(
						"self" => "http://localhost:7474/db/data/node/1",
						"data" => array(
							"name" => "foo",
						),
					),
					array(
						"self" => "http://localhost:7474/db/data/node/3",
						"data" => array(
							"name" => "bar",
						),
					),
				),
			),
		);

		$this->transport->expects($this->once())
			->method('post')
			->with('/node/1/traverse/fullpath', array())
			->will($this->returnValue(array("code"=>200,"data"=>$data)));

		$result = $this->client->executeTraversal($traversal, $node, Traversal::ReturnTypeFullPath);
		$this->assertEquals(1, count($result));
		$this->assertInstanceOf('Everyman\Neo4j\Path', $result[0]);
		
		$rels = $result[0]->getRelationships();
		$this->assertEquals(1, count($rels));
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rels[0]);
		$this->assertEquals(2, $rels[0]->getId());
		$this->assertEquals('FOOTYPE', $rels[0]->getType());
		$this->assertEquals('baz', $rels[0]->getProperty('name'));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $rels[0]->getStartNode());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $rels[0]->getEndNode());
		$this->assertEquals(1, $rels[0]->getStartNode()->getId());
		$this->assertEquals(3, $rels[0]->getEndNode()->getId());

		$nodes = $result[0]->getNodes();
		$this->assertEquals(2, count($nodes));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $nodes[0]);
		$this->assertEquals(1, $nodes[0]->getId());
		$this->assertEquals('foo', $nodes[0]->getProperty('name'));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $nodes[1]);
		$this->assertEquals(3, $nodes[1]->getId());
		$this->assertEquals('bar', $nodes[1]->getProperty('name'));
	}

	public function testTraversal_ServerReturnsErrorCode_ThrowsException()
	{
		$traversal = new Traversal($this->client);
		$node = new Node($this->client);
		$node->setId(1);

		$this->transport->expects($this->once())
			->method('post')
			->with('/node/1/traverse/node', array())
			->will($this->returnValue(array("code"=>400)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$result = $this->client->executeTraversal($traversal, $node, Traversal::ReturnTypeNode);
	}

	public function testPagedTraversal_TraversalGiven_ReturnsResultSets()
	{
		$traversal = new Traversal($this->client);
		$traversal->setOrder(Traversal::OrderDepthFirst);

		$node = new Node($this->client);
		$node->setId(1);

		$pager = new Pager($traversal, $node, Traversal::ReturnTypeNode);
		$pager->setPageSize(1)
			->setLeaseTime(30);

		$data = array(
			// First results page
			array(
				array(
					"self" => "http://localhost:7474/db/data/node/2",
					"data" => array(
						"name" => "foo",
					),
				),
			),

			// Second results page
			array(
				array(
					"self" => "http://localhost:7474/db/data/node/3",
					"data" => array(
						"name" => "bar",
					),
				),
			),
		);
		
		$this->transport->expects($this->at(0))
			->method('post')
			->with('/node/1/paged/traverse/node?pageSize=1&leaseTime=30',array("order" => "depth_first"))
			->will($this->returnValue(array("code"=>200,"data"=>$data[0],"headers"=>array('Location' => "http://localhost:7474/db/data/node/1/paged/traverse/node/a1b2c3"))));

		$this->transport->expects($this->at(1))
			->method('get')
			->with('/node/1/paged/traverse/node/a1b2c3', null)
			->will($this->returnValue(array("code"=>200,"data"=>$data[1])));

		$this->transport->expects($this->at(2))
			->method('get')
			->with('/node/1/paged/traverse/node/a1b2c3', null)
			->will($this->returnValue(array("code"=>404)));

		$result = $this->client->executePagedTraversal($pager);
		$this->assertEquals(1, count($result));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[0]);
		$this->assertEquals(2, $result[0]->getId());
		$this->assertEquals('foo', $result[0]->getProperty('name'));

		$result = $this->client->executePagedTraversal($pager);
		$this->assertEquals(1, count($result));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $result[0]);
		$this->assertEquals(3, $result[0]->getId());
		$this->assertEquals('bar', $result[0]->getProperty('name'));

		$result = $this->client->executePagedTraversal($pager);
		$this->assertNull($result);
	}

	public function testPagedTraversal_ServerReturnsError_ThrowsException()
	{
		$traversal = new Traversal($this->client);
		$traversal->setOrder(Traversal::OrderDepthFirst);

		$node = new Node($this->client);
		$node->setId(1);

		$pager = new Pager($traversal, $node, Traversal::ReturnTypeNode);
		$pager->setPageSize(1)
			->setLeaseTime(30);

		$this->transport->expects($this->once())
			->method('post')
			->with('/node/1/paged/traverse/node?pageSize=1&leaseTime=30',array("order" => "depth_first"))
			->will($this->returnValue(array("code"=>400)));

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$result = $this->client->executePagedTraversal($pager);
	}
}
