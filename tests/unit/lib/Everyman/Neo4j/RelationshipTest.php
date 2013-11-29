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

	/**
	 * Test for https://github.com/jadell/neo4jphp/issues/97
	 */
	public function testSerialization()
	{
		$startNode = new Node($this->client);
		$startNode->setId(1);
		$startNode->setProperties(array("prop1" => 1, "prop2" => 2));

		$endNode = new Node($this->client);
		$endNode->setId(2);
		$endNode->setProperties(array("prop3" => 3, "prop3" => 4));

		$relationship = new Relationship($this->client);
		$relationship->setStartNode($startNode);
		$relationship->setEndNode($endNode);
		$relationship->setType("myRelType");
		$relationship->setProperties(array("relProp1" => 1, "relProp2" => 2));
		$relationship->setId(10);
		$relationship->useLazyLoad(false);

		$serializedRel = serialize($relationship);
		$unserializedRel = unserialize($serializedRel);

		$this->assertEquals($relationship->getId(), $unserializedRel->getId());
		$this->assertEquals($relationship->getProperties(), $unserializedRel->getProperties());
		$this->assertEquals($relationship->getType(), $unserializedRel->getType());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $unserializedRel->getStartNode());
		$this->assertInstanceOf('Everyman\Neo4j\Node', $unserializedRel->getEndNode());

	}

	/**
	 * Assert that when the client object of the relationship
	 * is set, the clients of its start and end nodes are
	 * set too
	 */
	public function testSetClient()
	{
		$startNode = new Node($this->client);
		$startNode->setId(1);

		$endNode = new Node($this->client);
		$endNode->setId(2);

		$relationship = new Relationship($this->client);
		$relationship->setStartNode($startNode);
		$relationship->setEndNode($endNode);
		$relationship->setType("myRelType");
		$relationship->setId(1);

		$newClient = $this->getMock('Everyman\Neo4j\Client', array(), array(), '', false);
		$relationship->setClient($newClient);

		$this->assertSame($newClient, $relationship->getStartNode()->getClient());
		$this->assertSame($newClient, $relationship->getEndNode()->getClient());
	}
}
