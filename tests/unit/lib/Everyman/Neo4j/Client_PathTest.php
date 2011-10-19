<?php
namespace Everyman\Neo4j;

class Client_PathTest extends \PHPUnit_Framework_TestCase
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

	public function testGetPaths_PathsExist_ReturnsArray()
	{
		$startNode = new Node($this->client);
		$startNode->setId(123);
		$endNode = new Node($this->client);
		$endNode->setId(456);
		
		$finder = new PathFinder($this->client);
		$finder->setType('FOOTYPE')
			->setDirection(Relationship::DirectionOut)
			->setMaxDepth(3)
			->setStartNode($startNode)
			->setEndNode($endNode);
		
		$data = array(
			'to' => $this->endpoint.'/node/456',
			'relationships' => array('type'=>'FOOTYPE', 'direction'=>Relationship::DirectionOut),
			'max_depth' => 3,
			'max depth' => 3,
			'algorithm' => 'shortestPath'
		);
		
		$returnData = array(
			array(
				"start" => "http://localhost:7474/db/data/node/123",
				"nodes" => array("http://localhost:7474/db/data/node/123", "http://localhost:7474/db/data/node/341", "http://localhost:7474/db/data/node/456"),
				"length" => 2,
				"relationships" => array("http://localhost:7474/db/data/relationship/564", "http://localhost:7474/db/data/relationship/32"),
				"end" => "http://localhost:7474/db/data/node/456"
			),
			array(
				"start" => "http://localhost:7474/db/data/node/123",
				"nodes" => array("http://localhost:7474/db/data/node/123", "http://localhost:7474/db/data/node/41", "http://localhost:7474/db/data/node/456"),
				"length" => 2,
				"relationships" => array("http://localhost:7474/db/data/relationship/437", "http://localhost:7474/db/data/relationship/97"),
				"end" => "http://localhost:7474/db/data/node/456"
			),
		);
		
		$this->transport->expects($this->once())
			->method('post')
			->with('/node/123/paths', $data)
			->will($this->returnValue(array('code'=>200,'data'=>$returnData)));
			
		$paths = $this->client->getPaths($finder);
		$this->assertEquals(2, count($paths));
		$this->assertInstanceOf('Everyman\Neo4j\Path', $paths[0]);
		$this->assertInstanceOf('Everyman\Neo4j\Path', $paths[1]);
		
		$rels = $paths[0]->getRelationships();
		$this->assertEquals(2, count($rels));
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rels[0]);
		$this->assertEquals(564, $rels[0]->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rels[1]);
		$this->assertEquals(32, $rels[1]->getId());

		$nodes = $paths[0]->getNodes();
		$this->assertEquals(3, count($nodes));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $nodes[0]);
		$this->assertEquals(123, $nodes[0]->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $nodes[1]);
		$this->assertEquals(341, $nodes[1]->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $nodes[2]);
		$this->assertEquals(456, $nodes[2]->getId());
		
		$rels = $paths[1]->getRelationships();
		$this->assertEquals(2, count($rels));
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rels[0]);
		$this->assertEquals(437, $rels[0]->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rels[1]);
		$this->assertEquals(97, $rels[1]->getId());

		$nodes = $paths[1]->getNodes();
		$this->assertEquals(3, count($nodes));
		$this->assertInstanceOf('Everyman\Neo4j\Node', $nodes[0]);
		$this->assertEquals(123, $nodes[0]->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $nodes[1]);
		$this->assertEquals(41, $nodes[1]->getId());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $nodes[2]);
		$this->assertEquals(456, $nodes[2]->getId());
	}
	
	public function testGetPaths_NoMaxDepth_MaxDepthDefaultsToOne_ReturnsArray()
	{
		$startNode = new Node($this->client);
		$startNode->setId(123);
		$endNode = new Node($this->client);
		$endNode->setId(456);
		
		$finder = new PathFinder($this->client);
		$finder->setType('FOOTYPE')
			->setDirection(Relationship::DirectionOut)
			->setStartNode($startNode)
			->setEndNode($endNode);
		
		$data = array(
			'to' => $this->endpoint.'/node/456',
			'relationships' => array('type'=>'FOOTYPE', 'direction'=>Relationship::DirectionOut),
			'max_depth' => 1,
			'max depth' => 1,
			'algorithm' => 'shortestPath'
		);
		
		$returnData = array();
		
		$this->transport->expects($this->once())
			->method('post')
			->with('/node/123/paths', $data)
			->will($this->returnValue(array('code'=>200,'data'=>$returnData)));
			
		$paths = $this->client->getPaths($finder);
		$this->assertEquals(0, count($paths));
	}

	public function testGetPaths_DirectionGivenButNoType_ThrowsException()
	{
		$startNode = new Node($this->client);
		$startNode->setId(123);
		$endNode = new Node($this->client);
		$endNode->setId(456);
		
		$finder = new PathFinder($this->client);
		$finder->setDirection(Relationship::DirectionOut)
			->setStartNode($startNode)
			->setEndNode($endNode);
		
		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$paths = $this->client->getPaths($finder);
	}
	
	public function testGetPaths_StartNodeNotPersisted_ThrowsException()
	{
		$startNode = new Node($this->client);
		$endNode = new Node($this->client);
		$endNode->setId(456);
		
		$finder = new PathFinder($this->client);
		$finder->setStartNode($startNode)
			->setEndNode($endNode);
		
		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$paths = $this->client->getPaths($finder);
	}
	
	public function testGetPaths_EndNodeNotPersisted_ThrowsException()
	{
		$startNode = new Node($this->client);
		$startNode->setId(123);
		$endNode = new Node($this->client);
		
		$finder = new PathFinder($this->client);
		$finder->setStartNode($startNode)
			->setEndNode($endNode);
		
		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$paths = $this->client->getPaths($finder);
	}
	
	public function testGetPaths_DijkstraSearchNoCostProperty_ThrowsException()
	{
		$startNode = new Node($this->client);
		$startNode->setId(123);
		$endNode = new Node($this->client);
		$endNode->setId(456);
		
		$finder = new PathFinder($this->client);
		$finder->setStartNode($startNode)
			->setEndNode($endNode)
			->setAlgorithm(PathFinder::AlgoDijkstra);
		
		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$paths = $this->client->getPaths($finder);
	}
	
	public function testGetPaths_DijkstraSearch_ReturnsResult()
	{
		$startNode = new Node($this->client);
		$startNode->setId(123);
		$endNode = new Node($this->client);
		$endNode->setId(456);
		
		$finder = new PathFinder($this->client);
		$finder->setStartNode($startNode)
			->setEndNode($endNode)
			->setAlgorithm(PathFinder::AlgoDijkstra)
			->setCostProperty('distance')
			->setDefaultCost(2);
		
		$data = array(
			'to' => $this->endpoint.'/node/456',
			'max_depth' => 1,
			'max depth' => 1,
			'algorithm' => 'dijkstra',
			'cost_property' => 'distance',
			'cost property' => 'distance',
			'default_cost' => 2,
			'default cost' => 2,
		);
		
		$returnData = array();
		
		$this->transport->expects($this->once())
			->method('post')
			->with('/node/123/paths', $data)
			->will($this->returnValue(array('code'=>200,'data'=>$returnData)));
			
		$paths = $this->client->getPaths($finder);
		$this->assertEquals(0, count($paths));
	}
	
	public function testGetPaths_TransportFails_ThrowsException()
	{
		$startNode = new Node($this->client);
		$startNode->setId(123);
		$endNode = new Node($this->client);
		$endNode->setId(456);
		
		$finder = new PathFinder($this->client);
		$finder->setType('FOOTYPE')
			->setDirection(Relationship::DirectionOut)
			->setMaxDepth(3)
			->setStartNode($startNode)
			->setEndNode($endNode);
		
		$this->transport->expects($this->any())
			->method('post')
			->will($this->returnValue(array('code'=>400)));

		$this->setExpectedException('\Everyman\Neo4j\Exception');			
		$this->client->getPaths($finder);
	}
}
