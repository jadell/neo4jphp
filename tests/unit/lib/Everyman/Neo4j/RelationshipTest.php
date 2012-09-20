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
		$expected = $this->relationship;
		$matched = false;

		$this->client->expects($this->once())
			->method('saveRelationship')
			// Have to do it this way because PHPUnit clones object parameters
			->will($this->returnCallback(function (Relationship $actual) use ($expected, &$matched) {
				$matched = $expected->getId() == $actual->getId();
				return true;
			}));

		$this->assertSame($this->relationship, $this->relationship->save());
		$this->assertTrue($matched);
	}

	/**
	 * Test for https://github.com/jadell/neo4jphp/issues/58
	 */
	public function testSave_FollowedByPropertyGet_DoesNotLazyLoad()
	{
		$this->client->expects($this->once())
			->method('saveRelationship')
			->will($this->returnValue(true));

		$this->client->expects($this->never())
			->method('loadRelationship');

		$this->relationship->setId(123);
		$this->relationship->save();
		$this->relationship->getProperty('foo');
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
