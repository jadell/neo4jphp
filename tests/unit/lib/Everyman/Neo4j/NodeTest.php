<?php
namespace Everyman\Neo4j;

class NodeTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $node = null;

	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array(
			'saveNode',
			'deleteNode',
			'addLabels',
			'removeLabels',
			'getLabels',
			'loadNode',
			'getNodeRelationships',
			'runCommand',
		));
		$this->node = new Node($this->client);
	}

	public function testSave_SavesSelfUsingClient()
	{
		$expected = $this->node;
		$matched = false;

		$this->client->expects($this->once())
			->method('saveNode')
			// Have to do it this way because PHPUnit clones object parameters
			->will($this->returnCallback(function (Node $actual) use ($expected, &$matched) {
				$matched = $expected->getId() == $actual->getId();
				return true;
			}));

		$this->node->setId(123);
		$this->assertSame($this->node, $this->node->save());
		$this->assertTrue($matched);
	}

	public function testGetLabels_DelegatesToClient()
	{
		$expected = $this->node;
		$matched = false;

		$label = new Label($this->client, 'FOOBAR');

		$this->client->expects($this->once())
			->method('getLabels')
			// Have to do it this way because PHPUnit clones object parameters
			->will($this->returnCallback(function (Node $actual) use ($expected, $label, &$matched) {
				$matched = $expected->getId() == $actual->getId();
				return array($label);
			}));

		$labels = $this->node->getLabels();
		$this->assertEquals(1, count($labels));
		$this->assertSame($label, $labels[0]);
	}

	public function testAddLabels_DelegatesToClient()
	{
		$expected = $this->node;
		$expected->setId(123);
		$matched = false;

		$label = new Label($this->client, 'FOOBAR');

		$this->client->expects($this->once())
			->method('addLabels')
			// Have to do it this way because PHPUnit clones object parameters
			->will($this->returnCallback(function (Node $actual, $labels) use ($expected, $label, &$matched) {
				$matched = $expected->getId() == $actual->getId();
				$matched = $matched && $label->getName() == $labels[0]->getName();
				return array($label);
			}));

		$labels = $this->node->addLabels(array($label));
		$this->assertEquals(1, count($labels));
		$this->assertSame($label, $labels[0]);
	}

	public function testRemoveLabels_DelegatesToClient()
	{
		$expected = $this->node;
		$expected->setId(123);
		$matched = false;

		$label = new Label($this->client, 'FOOBAR');

		$this->client->expects($this->once())
			->method('removeLabels')
			// Have to do it this way because PHPUnit clones object parameters
			->will($this->returnCallback(function (Node $actual, $labels) use ($expected, $label, &$matched) {
				$matched = $expected->getId() == $actual->getId();
				$matched = $matched && $label->getName() == $labels[0]->getName();
				return array($label);
			}));

		$labels = $this->node->removeLabels(array($label));
		$this->assertEquals(1, count($labels));
		$this->assertSame($label, $labels[0]);
	}

	/**
	 * Test for https://github.com/jadell/neo4jphp/issues/58
	 */
	public function testSave_FollowedByPropertyGet_DoesNotLazyLoad()
	{
		$this->client->expects($this->once())
			->method('saveNode')
			->will($this->returnValue(true));

		$this->client->expects($this->never())
			->method('loadNode');

		$this->node->setId(123);
		$this->node->save();
		$this->node->getProperty('foo');
	}

	public function testDelete_DeletesSelfUsingClient()
	{
		$this->client->expects($this->once())
			->method('deleteNode')
			->with($this->node)
			->will($this->returnValue(true));

		$this->assertSame($this->node, $this->node->delete());
	}

	public function testLoad_LoadsSelfUsingClient()
	{
		$this->client->expects($this->once())
			->method('loadNode')
			->with($this->node)
			->will($this->returnValue(true));

		$this->assertSame($this->node, $this->node->load());
	}

	public function testGetRelationships_ReturnsArrayOfRelationships()
	{
		$dir = Relationship::DirectionOut;
		$types = array('FOOTYPE','BARTYPE');

		$returnRels = array(
			new Relationship($this->client),
			new Relationship($this->client),
		);

		$this->client->expects($this->once())
			->method('getNodeRelationships')
			->with($this->node, $types, $dir)
			->will($this->returnValue($returnRels));

		$rels = $this->node->getRelationships($types, $dir);
		$this->assertEquals($returnRels, $rels);
	}

	public function testGetFirstRelationship_ReturnsRelationship()
	{
		$dir = Relationship::DirectionOut;
		$types = array('FOOTYPE','BARTYPE');

		$returnRels = array(
			new Relationship($this->client),
			new Relationship($this->client),
		);

		$this->client->expects($this->once())
			->method('getNodeRelationships')
			->with($this->node, $types, $dir)
			->will($this->returnValue($returnRels));

		$rel = $this->node->getFirstRelationship($types, $dir);
		$this->assertSame($returnRels[0], $rel);
	}

	public function testGetFirstRelationship_NoneFound_ReturnsNull()
	{
		$dir = Relationship::DirectionOut;
		$types = array('FOOTYPE','BARTYPE');

		$returnRels = array();

		$this->client->expects($this->once())
			->method('getNodeRelationships')
			->with($this->node, $types, $dir)
			->will($this->returnValue($returnRels));

		$rel = $this->node->getFirstRelationship($types, $dir);
		$this->assertNull($rel);
	}

	public function testRelateTo_ReturnsRelationship()
	{
		$toNode = new Node($this->client);
		$type = 'FOOTYPE';

		$rel = $this->node->relateTo($toNode, $type);
		$this->assertInstanceOf('Everyman\Neo4j\Relationship', $rel);
		$this->assertSame($this->client, $rel->getClient());
		$this->assertSame($this->node, $rel->getStartNode());
		$this->assertSame($toNode, $rel->getEndNode());
		$this->assertEquals($type, $rel->getType());
	}

	public function testFindPathsTo_ReturnsPathFinder()
	{
		$toNode = new Node($this->client);
		$type = 'FOOTYPE';
		$dir = Relationship::DirectionOut;

		$finder = $this->node->findPathsTo($toNode, $type, $dir);
		$this->assertInstanceOf('Everyman\Neo4j\PathFinder', $finder);
		$this->assertSame($this->node, $finder->getStartNode());
		$this->assertSame($toNode, $finder->getEndNode());
		$this->assertEquals($dir, $finder->getDirection());
		$this->assertEquals($type, $finder->getType());
	}

	/**
	 * Test for https://github.com/jadell/neo4jphp/issues/97
	 */
	public function testSerialization()
	{
		$node = new Node($this->client);
		$node->setId(1);
		$node->setProperties(array("prop1" => 1, "prop2" => 2));
		$node->useLazyLoad(false);
		$serializedNode = serialize($node);
		$unserializedNode = unserialize($serializedNode);


		$this->assertEquals($node->getProperties(), $unserializedNode->getProperties());
	}
}
