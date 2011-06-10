<?php
namespace Everyman\Neo4j;

class PathTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $path = null;

	protected $rels = array();

	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array(), array(), '', false);
		$this->path = new Path($this->client);

		$rel = new Relationship($this->client);
		$rel->setStartNode(new Node($this->client));
		$rel->setEndNode(new Node($this->client));
		$this->path->appendRelationship($rel);
		$this->rels[0] = $rel;

		$rel = new Relationship($this->client);
		$rel->setStartNode(new Node($this->client));
		$rel->setEndNode(new Node($this->client));
		$this->path->appendRelationship($rel);
		$this->rels[1] = $rel;
	}

	public function testGetLength_ReturnsInteger()
	{
		$this->assertEquals(count($this->rels), $this->path->getLength());
		$this->assertEquals(count($this->rels), count($this->path));
	}

	public function testEndpoints_ReturnsCorrectNodes()
	{
		$this->assertSame($this->rels[0]->getStartNode(), $this->path->getStartNode());
		$this->assertSame($this->rels[1]->getEndNode(), $this->path->getEndNode());
	}

	public function testEndpoints_NoRelationship_ReturnsNull()
	{
		$this->path = new Path($this->client);
		$this->assertNull($this->path->getStartNode());
		$this->assertNull($this->path->getEndNode());
	}

	public function testGetRelationships_ReturnsArray()
	{
		$rels = $this->path->getRelationships();
		$this->assertEquals(count($this->rels), count($rels));
		$this->assertSame($this->rels[0], $rels[0]);
		$this->assertSame($this->rels[1], $rels[1]);
	}

	public function testIteration_PathCanBeIteratedOver()
	{
		$this->assertInstanceOf('Traversable', $this->path);
		foreach ($this->path as $i => $rel) {
			$this->assertSame($this->rels[$i], $rel);
		}
	}
}
