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

	public function testGetStartNode_NodeNotSet_LazyLoad()
	{
		$this->relationship->setId(123);
		$this->client->expects($this->once())
			->method('loadRelationship');

		$this->relationship->getStartNode();
	}

	public function testGetEndNode_NodeNotSet_LazyLoad()
	{
		$this->relationship->setId(123);
		$this->client->expects($this->once())
			->method('loadRelationship');

		$this->relationship->getEndNode();
	}

	public function testGetStartAndEndNode_NodesSet_DoesNotLazyLoad()
	{
		$startNode = new Node($this->client);
		$endNode = new Node($this->client);

		$this->relationship->setId(123);
		$this->relationship->setStartNode($startNode);
		$this->relationship->setEndNode($endNode);

		$this->client->expects($this->never())
			->method('loadRelationship');

		$this->assertSame($startNode, $this->relationship->getStartNode());
		$this->assertSame($endNode, $this->relationship->getEndNode());
	}

	public function testSerialize_KeepsStartEndAndType()
	{
		$expectedStart = new Node($this->client);
		$expectedStart->setId(123);
		$expectedEnd = new Node($this->client);
		$expectedEnd->setId(456);
		$this->relationship
			->setType($this->type)
			->setStartNode($expectedStart)
			->setEndNode($expectedEnd);

		// serialize and unserialize
		$data = serialize($this->relationship);
		$rel = unserialize($data);
		// we must reset the client
		$rel->setClient($this->client);
		$start = $rel->getStartNode();
		$end = $rel->getEndNode();

		$this->assertEquals($this->relationship, $rel, 'The relationship is restored by unserialize');
		$this->assertEquals($expectedStart, $start, 'The start node should be restored by unserialize');
		$this->assertEquals($expectedEnd, $end, 'The end node should be restored by unserialize');
		$this->assertEquals($this->type, $rel->getType(), 'The type should be restored by unserialize');
		$this->assertEquals($this->client, $start->getClient(), 'The client should be restored in the start node by setClient on the relation');
		$this->assertEquals($this->client, $end->getClient(), 'The client should be restored in the end node by setClient on the relation');
	}
}
