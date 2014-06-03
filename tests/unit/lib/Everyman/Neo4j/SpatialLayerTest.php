<?php
namespace Everyman\Neo4j;

class SpacialLayerTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $layer = null;

	public function setUp()
	{
		$this->client   = $this->getMock('Everyman\Neo4j\Client', array(), array(), '', false);
		$this->layer    = new SimplPointLayer($this->client, SpatialLayer::TypeSimplePoint, 'layername');
	}

	public function testSave_SavesSelfUsingClient()
	{
		$this->client->expects($this->once())
			->method('saveLayer')
			->with($this->layer)
			->will($this->returnValue(true));

		$this->assertTrue($this->layer->save());
	}

	public function testAdd_AddsEntityUsingClient()
	{
		$node = new Node($this->client);

		$this->client->expects($this->once())
			->method('addToLayer')
			->with($this->layer, $node)
			->will($this->returnValue(true));

		$this->assertTrue($this->layer->add($node));
	}

	public function testFind_FindsNodesWithinDistanceUsingClient()
	{
		$node = new Node($this->client);
        
		$this->client->expects($this->once())
			->method('findNodesWithinDistance')
			->with($this->layer,  -25, 50, 100)
			->will($this->returnValue(array($node)));

		$result = $this->layer->findNodesWithinDistance(-25, 50, 100);
		$this->assertEquals(1, count($result));
		$this->assertSame($node, $result[0]);
	}
    
    public function testFind_FindsNodesInBBoxUsingClient()
	{
		$node = new Node($this->client);
        
		$this->client->expects($this->once())
			->method('findNodesInBBox')
			->with($this->layer, -25, 25, -50, 40)
			->will($this->returnValue(array($node)));

		$result = $this->layer->findNodesInBBox(-25, 25, -50, 40);
		$this->assertEquals(1, count($result));
		$this->assertSame($node, $result[0]);
	}
}
