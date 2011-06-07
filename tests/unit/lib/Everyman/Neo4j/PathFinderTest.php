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

	public function testGetPaths_PassesThroughToClient()
	{
		$expectedPaths = array(new Path($this->client));
	
		$this->client->expects($this->once())
			->method('getPaths')
			->with($this->finder)
			->will($this->returnValue($expectedPaths));

		$paths = $this->finder->getPaths();
		$this->assertEquals($expectedPaths, $paths);
	}
}
