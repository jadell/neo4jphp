<?php
namespace Everyman\Neo4j;

class EntityMapperTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $mapper = null;
	
	public function setUp()
	{
		$transport = $this->getMock('Everyman\Neo4j\Transport');
		$this->client = new Client($transport);
		$this->mapper = new EntityMapper($this->client);
	}
	
	public function testGetIdFromUri_UriGiven_ReturnsInteger()
	{
		$uri = 'http://localhost:7474/db/data/node/1';
		$this->assertEquals(1, $this->mapper->getIdFromUri($uri));
	}

	public function testMakeNode_NodeDataGiven_ReturnsNode()
	{
		$data = array(
			'data' => array(
				'name' => 'Bob'
			),
			'self' => 'http://localhost:7474/db/data/node/1',
		);

		$node = $this->mapper->makeNode($data);
		
		$this->assertInstanceOf('Everyman\Neo4j\Node', $node);
		$this->assertEquals(1, $node->getId());
		$this->assertEquals('Bob', $node->getProperty('name'));
	}
	
	public function testMakeRelationship_RelationshipDataGiven_ReturnsRelationship()
	{
		$data = array(
			'data' => array(
				'name' => 'Bob'
			),
			'type' => 'KNOWS',
			'start' => 'http://localhost/db/data/node/1', 
			'end' => 'http://localhost/db/data/node/2', 
			'self' => 'http://localhost:7474/db/data/relationship/3',
		);

		$rel = $this->mapper->makeRelationship($data);
		
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rel);
		$this->assertEquals(3, $rel->getId());
		$this->assertEquals('KNOWS', $rel->getType());
		$this->assertEquals('Bob', $rel->getProperty('name'));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $rel->getStartNode());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $rel->getEndNode());
		$this->assertEquals(1, $rel->getStartNode()->getId());
		$this->assertEquals(2, $rel->getEndNode()->getId());
	}

	public function testPopulateNode_NodeGiven_ReturnsNode()
	{
		$data = array(
			'data' => array(
				'name' => 'Bob'
			),
		);

		$node = new Node($this->client);
		$this->mapper->populateNode($node, $data);
		
		$this->assertEquals('Bob', $node->getProperty('name'));
	}
	
	public function testPopulateRelationship_RelationshipGiven_ReturnsRelationship()
	{
		$data = array(
			'data' => array(
				'name' => 'Bob'
			),
			'type' => 'KNOWS',
			'start' => 'http://localhost/db/data/node/1', 
			'end' => 'http://localhost/db/data/node/2', 
		);

		$rel = new Relationship($this->client);
		$this->mapper->populateRelationship($rel, $data);
		
		$this->assertEquals('KNOWS', $rel->getType());
		$this->assertEquals('Bob', $rel->getProperty('name'));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $rel->getStartNode());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $rel->getEndNode());
		$this->assertEquals(1, $rel->getStartNode()->getId());
		$this->assertEquals(2, $rel->getEndNode()->getId());
	}

	public function testPopulatePath_PathGiven_ReturnsPath()
	{
		$data = array(
			"nodes" => array("http://localhost:7474/db/data/node/123", "http://localhost:7474/db/data/node/341", "http://localhost:7474/db/data/node/456"),
			"relationships" => array("http://localhost:7474/db/data/relationship/564", "http://localhost:7474/db/data/relationship/32"),
		);
		
		$path = new Path($this->client);
		$this->mapper->populatePath($path, $data);
		
		$rels = $path->getRelationships();
		$this->assertEquals(2, count($rels));
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rels[0]);
		$this->assertEquals(564, $rels[0]->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rels[1]);
		$this->assertEquals(32, $rels[1]->getId());

		$nodes = $path->getNodes();
		$this->assertEquals(3, count($nodes));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $nodes[0]);
		$this->assertEquals(123, $nodes[0]->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $nodes[1]);
		$this->assertEquals(341, $nodes[1]->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $nodes[2]);
		$this->assertEquals(456, $nodes[2]->getId());
	}

	public function testPopulatePath_FullPath_ReturnsPath()
	{
		$data = array(
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
		);

		$path = new Path($this->client);
		$this->mapper->populatePath($path, $data, true);
		
		$rels = $path->getRelationships();
		$this->assertEquals(1, count($rels));
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rels[0]);
		$this->assertEquals(2, $rels[0]->getId());
		$this->assertEquals('FOOTYPE', $rels[0]->getType());
		$this->assertEquals('baz', $rels[0]->getProperty('name'));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $rels[0]->getStartNode());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $rels[0]->getEndNode());
		$this->assertEquals(1, $rels[0]->getStartNode()->getId());
		$this->assertEquals(3, $rels[0]->getEndNode()->getId());

		$nodes = $path->getNodes();
		$this->assertEquals(2, count($nodes));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $nodes[0]);
		$this->assertEquals(1, $nodes[0]->getId());
		$this->assertEquals('foo', $nodes[0]->getProperty('name'));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $nodes[1]);
		$this->assertEquals(3, $nodes[1]->getId());
		$this->assertEquals('bar', $nodes[1]->getProperty('name'));
	}
	
	public function testGetEntityFor_RelationshipData_ReturnsRelationship()
	{
		$data = array(
			'data' => array(
				'name' => 'Bob'
			),
			'type' => 'KNOWS',
			'start' => 'http://localhost/db/data/node/1', 
			'end' => 'http://localhost/db/data/node/2', 
			'self' => 'http://localhost/db/data/relationship/0'
		);

		$rel = $this->mapper->getEntityFor($data);
		
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rel);
		$this->assertEquals(0, $rel->getId());
		$this->assertEquals('KNOWS', $rel->getType());
		$this->assertEquals('Bob', $rel->getProperty('name'));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $rel->getStartNode());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $rel->getEndNode());
		$this->assertEquals(1, $rel->getStartNode()->getId());
		$this->assertEquals(2, $rel->getEndNode()->getId());
	}

	public function testGetEntityFor_NodeData_ReturnsNode()
	{
		$data = array(
			'data' => array(
				'name' => 'Bob'
			),
			'self' => 'http://localhost/db/data/node/0'
		);

		$node = $this->mapper->getEntityFor($data);
		
		$this->assertInstanceOf('Everyman\Neo4j\Node', $node);
		$this->assertEquals(0, $node->getId());
		$this->assertEquals('Bob', $node->getProperty('name'));
	}

	public function testGetEntityFor_PathData_ReturnsPath()
	{
		$data = array(
			"relationships" => array(
				"http://localhost:7474/db/data/relationship/2",
			),
			"nodes" => array(
				"http://localhost:7474/db/data/node/1",
				"http://localhost:7474/db/data/node/3",
			),
		);

		$path = $this->mapper->getEntityFor($data);
		$this->assertInstanceOf('Everyman\Neo4j\Path', $path);
		$this->assertEquals(1, $path->getStartNode()->getId());
		$this->assertEquals(3, $path->getEndNode()->getId());

		$rels = $path->getRelationships();
		$this->assertEquals(1, count($rels));
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rels[0]);
		$this->assertEquals(2, $rels[0]->getId());

		$nodes = $path->getNodes();
		$this->assertEquals(2, count($nodes));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $nodes[0]);
		$this->assertEquals(1, $nodes[0]->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $nodes[1]);
		$this->assertEquals(3, $nodes[1]->getId());
	}
}
