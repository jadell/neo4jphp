<?php
namespace Everyman\Neo4j;

class RelationshipTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $relationship = null;
	protected $type = 'FOOTYPE';

	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array(), array(), '', false);
		$this->relationship = new Relationship($this->client);
	}

	public function testSave_SavesSelfUsingClient()
	{
		$this->client->expects($this->once())
			->method('saveRelationship')
			->with($this->relationship)
			->will($this->returnValue(true));

		$this->assertSame($this->relationship, $this->relationship->save());
	}

	public function testDelete_DeletesSelfUsingClient()
	{
		$this->client->expects($this->once())
			->method('deleteRelationship')
			->with($this->relationship)
			->will($this->returnValue(true));

		$this->assertSame($this->relationship, $this->relationship->delete());
	}

	public function testLoad_LoadsSelfUsingClient()
	{
		$this->client->expects($this->once())
			->method('loadRelationship')
			->with($this->relationship)
			->will($this->returnValue(true));

		$this->assertSame($this->relationship, $this->relationship->load());
	}
}
