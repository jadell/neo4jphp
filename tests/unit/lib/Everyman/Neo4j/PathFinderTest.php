<?php
namespace Everyman\Neo4j;

class PathFinderTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $finder = null;
	
	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array(), array(), '', false);
		$this->finder = new PathFinder($this->client);
	}

	public function testGetClient_ClientSetCorrectly_ReturnsClient()
	{
		$this->assertSame($this->client, $this->finder->getClient());
	}

	public function testGetPaths_PassesThroughToClient()
	{
		$expectedPaths = array(new Path($this->client), new Path($this->client));
	
		$this->client->expects($this->once())
			->method('getPaths')
			->with($this->finder)
			->will($this->returnValue($expectedPaths));

		$paths = $this->finder->getPaths();
		$this->assertEquals($expectedPaths, $paths);
	}

	public function testGetSinglePath_PassesThroughToClient()
	{
		$firstPath = new Path($this->client);
		$expectedPaths = array($firstPath, new Path($this->client));
	
		$this->client->expects($this->once())
			->method('getPaths')
			->with($this->finder)
			->will($this->returnValue($expectedPaths));

		$path = $this->finder->getSinglePath();
		$this->assertEquals($firstPath, $path);
	}

	public function testGetSinglePath_NoPaths_ReturnsNull()
	{
		$this->client->expects($this->once())
			->method('getPaths')
			->with($this->finder)
			->will($this->returnValue(array()));

		$path = $this->finder->getSinglePath();
		$this->assertNull($path);
	}
}
