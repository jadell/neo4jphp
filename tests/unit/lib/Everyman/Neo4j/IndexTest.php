<?php
namespace Everyman\Neo4j;

class IndexTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $index = null;

	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array(), array(), '', false);
		$this->index = new Index($this->client, Index::TypeNode, 'indexname');
	}

	public function testSave_SavesSelfUsingClient()
	{
		$this->client->expects($this->once())
			->method('saveIndex')
			->with($this->index)
			->will($this->returnValue(true));

		$this->assertTrue($this->index->save());
	}

	public function testDelete_DeletesSelfUsingClient()
	{
		$this->client->expects($this->once())
			->method('deleteIndex')
			->with($this->index)
			->will($this->returnValue(true));

		$this->assertTrue($this->index->delete());
	}

	public function testAdd_AddsEntityUsingClient()
	{
		$node = new Node($this->client);

		$this->client->expects($this->once())
			->method('addToIndex')
			->with($this->index, $node, 'somekey', 'somevalue')
			->will($this->returnValue(true));

		$this->assertTrue($this->index->add($node, 'somekey', 'somevalue'));
	}

	public function testRemove_RemovesEntityUsingClient()
	{
		$node = new Node($this->client);

		$this->client->expects($this->once())
			->method('removeFromIndex')
			->with($this->index, $node, 'somekey', 'somevalue')
			->will($this->returnValue(true));

		$this->assertTrue($this->index->remove($node, 'somekey', 'somevalue'));
	}

	public function testFind_FindsNodesUsingClient()
	{
		$node = new Node($this->client);

		$this->client->expects($this->once())
			->method('searchIndex')
			->with($this->index, 'somekey', 'somevalue')
			->will($this->returnValue(array($node)));

		$result = $this->index->find('somekey', 'somevalue');
		$this->assertEquals(1, count($result));
		$this->assertSame($node, $result[0]);
	}
}
