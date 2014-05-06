<?php
namespace Everyman\Neo4j;

class Client_LabelTest extends \PHPUnit_Framework_TestCase
{
	protected $transport = null;
	protected $client = null;
	protected $endpoint = 'http://foo:1234/db/data';

	public function setUp()
	{
		$this->transport = $this->getMock('Everyman\Neo4j\Transport');
		$this->transport->expects($this->any())
			->method('getEndpoint')
			->will($this->returnValue($this->endpoint));
		$this->client = $this->getMock('Everyman\Neo4j\Client', array('getServerInfo'), array($this->transport));
		$this->client->expects($this->any())
			->method('getServerInfo')
			->will($this->returnValue(array(
				'cypher' => $this->endpoint.'/cypher',
				'version' => array(
					"full" => "2.0.0-M06",
					"major" => "2",
					"minor" => "0",
				)
			)));
	}

	public function testMakeLabel_ReturnsLabel()
	{
		$labelNameA = 'FOOBAR';
		$labelNameB = 'BAZQUX';

		$labelA = $this->client->makeLabel($labelNameA);
		$labelB = $this->client->makeLabel($labelNameB);

		self::assertInstanceOf('Everyman\Neo4j\Label', $labelA);
		self::assertEquals($labelNameA, $labelA->getName());

		self::assertInstanceOf('Everyman\Neo4j\Label', $labelB);
		self::assertEquals($labelNameB, $labelB->getName());
	}

	public function testMakeLabel_SameName_ReturnsSameLabelInstance()
	{
		$labelName = 'FOOBAR';

		$labelA = $this->client->makeLabel($labelName);
		$labelB = $this->client->makeLabel($labelName);

		self::assertInstanceOf('Everyman\Neo4j\Label', $labelA);
		self::assertInstanceOf('Everyman\Neo4j\Label', $labelB);
		self::assertSame($labelA, $labelB);
	}

	public function testGetNodesForLabel_NodesExistForLabel_ReturnsRow()
	{
		$labelName = 'FOOBAR';
		$label = new Label($this->client, $labelName);

		$returnData = array(
			array(
				"self" => "http://localhost:7474/db/data/relationship/56",
				"data" => array(),
			),
			array(
				"self" => "http://localhost:7474/db/data/relationship/834",
				"data" => array(),
			),
		);

		$this->transport->expects($this->once())
			->method('get')
			->with("/label/{$labelName}/nodes")
			->will($this->returnValue(array('code'=>200,'data'=>$returnData)));

		$nodes = $this->client->getNodesForLabel($label);
		self::assertInstanceOf('Everyman\Neo4j\Query\Row', $nodes);
		self::assertEquals(2, count($nodes));

		self::assertInstanceOf('Everyman\Neo4j\Node', $nodes[0]);
		self::assertInstanceOf('Everyman\Neo4j\Node', $nodes[1]);
		self::assertEquals(56,  $nodes[0]->getId());
		self::assertEquals(834, $nodes[1]->getId());
	}

	public function testGetNodesForLabel_NodesExistForLabelAndProperty_ReturnsRow()
	{
		$labelName = 'FOOBAR';
		$propertyName = 'baz';
		$propertyValue = 'qux';
		$label = new Label($this->client, $labelName);

		$returnData = array(
			array(
				"self" => "http://localhost:7474/db/data/relationship/56",
				"data" => array($propertyName => $propertyValue),
			),
		);

		$this->transport->expects($this->once())
			->method('get')
			->with("/label/{$labelName}/nodes?{$propertyName}=%22{$propertyValue}%22")
			->will($this->returnValue(array('code'=>200,'data'=>$returnData)));

		$nodes = $this->client->getNodesForLabel($label, $propertyName, $propertyValue);
		self::assertInstanceOf('Everyman\Neo4j\Query\Row', $nodes);
		self::assertEquals(1, count($nodes));

		self::assertInstanceOf('Everyman\Neo4j\Node', $nodes[0]);
		self::assertEquals(56,  $nodes[0]->getId());
	}

	public function testGetNodesForLabel_NoNodesExist_ReturnsEmptyRow()
	{
		$labelName = 'FOOBAR';
		$label = new Label($this->client, $labelName);

		$returnData = array();

		$this->transport->expects($this->once())
			->method('get')
			->with("/label/{$labelName}/nodes")
			->will($this->returnValue(array('code'=>200,'data'=>$returnData)));

		$nodes = $this->client->getNodesForLabel($label);
		self::assertInstanceOf('Everyman\Neo4j\Query\Row', $nodes);
		self::assertEquals(0, count($nodes));
	}

	public function testGetNodesForLabel_ProperlyUrlEncodesPath()
	{
		$labelName = 'FOO+Bar /Baz';
		$propertyName = 'ba$! "z qux"';
		$propertyValue = 'f @oo !B"/+%20ar ';
		$label = new Label($this->client, $labelName);

		$expectedLabel = rawurlencode($labelName);
		$expectedName = rawurlencode($propertyName);
		$expectedValue = rawurlencode('"'.$propertyValue.'"');

		$this->transport->expects($this->once())
			->method('get')
			->with("/label/{$expectedLabel}/nodes?{$expectedName}={$expectedValue}")
			->will($this->returnValue(array('code'=>200,'data'=>array())));

		$this->client->getNodesForLabel($label, $propertyName, $propertyValue);
	}

	public function testGetNodesForLabel_PropertyWithIntegerValueGiven_CallsClientMethod()
	{
		$labelName = 'FOOBAR';
		$propertyName = 'baz';
		$propertyValue = 1;
		$label = new Label($this->client, $labelName);

		$returnData = array(
			array(
				"self" => "http://localhost:7474/db/data/relationship/56",
				"data" => array($propertyName => $propertyValue),
			),
		);

		$this->transport->expects($this->once())
			->method('get')
			->with("/label/{$labelName}/nodes?{$propertyName}={$propertyValue}")
			->will($this->returnValue(array('code'=>200,'data'=>$returnData)));

		$nodes = $this->client->getNodesForLabel($label, $propertyName, $propertyValue);
		self::assertInstanceOf('Everyman\Neo4j\Query\Row', $nodes);
		self::assertEquals(1, count($nodes));

		self::assertInstanceOf('Everyman\Neo4j\Node', $nodes[0]);
		self::assertEquals(56,  $nodes[0]->getId());
	}

	public function testGetNodesForLabel_PropertyNameWithoutValue_ThrowsException()
	{
		$labelName = 'FOOBAR';
		$label = new Label($this->client, $labelName);

		$this->transport->expects($this->never())
			->method('get');

		$this->setExpectedException('InvalidArgumentException');
		$this->client->getNodesForLabel($label, 'prop', null);
	}

	public function testGetNodesForLabel_PropertyValueWithoutName_ThrowsException()
	{
		$labelName = 'FOOBAR';
		$label = new Label($this->client, $labelName);

		$this->transport->expects($this->never())
			->method('get');

		$this->setExpectedException('InvalidArgumentException');
		$this->client->getNodesForLabel($label, null, 'val');
	}

	public function testGetNodesForLabel_NoLabelCapability_ThrowsException()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array('getServerInfo'), array($this->transport));
		$this->client->expects($this->any())
			->method('getServerInfo')
			->will($this->returnValue(array(
				'cypher' => $this->endpoint.'/cypher',
				'version' => array(
					"full" => "1.9.0",
					"major" => "1",
					"minor" => "9",
				)
			)));

		$labelName = 'FOOBAR';
		$label = new Label($this->client, $labelName);

		$this->transport->expects($this->never())
			->method('get');

		$this->setExpectedException('RuntimeException', 'label capability');
		$this->client->getNodesForLabel($label);
	}

	public function testGetLabels_NoNode_ReturnsArrayOfLabelsAttachedToNodesOnTheServer()
	{
		$labelAlreadyInstantiated = $this->client->makeLabel('BAZQUX');

		$returnData = array('FOOBAR', $labelAlreadyInstantiated->getName(), 'LOREMIPSUM');

		$this->transport->expects($this->once())
			->method('get')
			->with("/labels")
			->will($this->returnValue(array('code'=>200,'data'=>$returnData)));

		$labels = $this->client->getLabels();
		self::assertEquals(count($returnData), count($labels));
		foreach ($labels as $i => $label) {
			self::assertInstanceOf('Everyman\Neo4j\Label', $label);
			self::assertEquals($returnData[$i], $label->getName());
		}

		self::assertSame($labelAlreadyInstantiated, $labels[1]);
	}

	public function testGetLabels_NodeSpecified_ReturnsArrayOfLabelsAttachedToNode()
	{
		$nodeId = 123;
		$node = new Node($this->client);
		$node->setId($nodeId);

		$returnData = array('FOOBAR', 'BAZQUX');

		$this->transport->expects($this->once())
			->method('get')
			->with("/node/{$nodeId}/labels")
			->will($this->returnValue(array('code'=>200,'data'=>$returnData)));

		$labels = $this->client->getLabels($node);
		self::assertEquals(count($returnData), count($labels));
		foreach ($labels as $i => $label) {
			self::assertInstanceOf('Everyman\Neo4j\Label', $label);
			self::assertEquals($returnData[$i], $label->getName());
		}
	}

	public function testGetLabels_NodeIdZero_ReturnsArrayOfLabelsAttachedToNode()
	{
		$nodeId = 0;
		$node = new Node($this->client);
		$node->setId($nodeId);

		$returnData = array('FOOBAR', 'BAZQUX');

		$this->transport->expects($this->once())
			->method('get')
			->with("/node/{$nodeId}/labels")
			->will($this->returnValue(array('code'=>200,'data'=>$returnData)));

		$labels = $this->client->getLabels($node);
		self::assertEquals(count($returnData), count($labels));
		foreach ($labels as $i => $label) {
			self::assertInstanceOf('Everyman\Neo4j\Label', $label);
			self::assertEquals($returnData[$i], $label->getName());
		}
	}

	public function testGetLabels_NoNodeId_ThrowsException()
	{
		$node = new Node($this->client);

		$this->transport->expects($this->never())
			->method('get');

		$this->setExpectedException('InvalidArgumentException');
		$labels = $this->client->getLabels($node);
	}

	public function testGetLabels_NoLabelCapabiltiy_ThrowsException()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array('getServerInfo'), array($this->transport));
		$this->client->expects($this->any())
			->method('getServerInfo')
			->will($this->returnValue(array(
				'cypher' => $this->endpoint.'/cypher',
				'version' => array(
					"full" => "1.9.0",
					"major" => "1",
					"minor" => "9",
				)
			)));

		$this->transport->expects($this->never())
			->method('get');

		$this->setExpectedException('RuntimeException', 'label capability');
		$this->client->getLabels();
	}

	public function testAddLabels_SendsCorrectCypherQuery()
	{
		$nodeId = 123;
		$labelAName = 'FOOBAR';
		$labelBName = 'BAZ QUX';
		$labelCName = 'HACK`THIS';

		$escapedCName = 'HACK``THIS';

		$node = new Node($this->client);
		$node->setId($nodeId);

		$labelA = $this->client->makeLabel($labelAName);
		$labelB = $this->client->makeLabel($labelBName);
		$labelC = $this->client->makeLabel($labelCName);

		$expectedLabels = array('LOREMIPSUM', $labelAName, $labelBName, $labelCName);

		$expectedQuery = "START n=node({nodeId}) SET n:`{$labelAName}`:`{$labelBName}`:`{$escapedCName}` RETURN labels(n) AS labels";
		$expectedParams = array("nodeId" => $nodeId);

		$this->transport->expects($this->any())
			->method('get')
			->with('/')
			->will($this->returnValue(array('code'=>200, 'data'=>array(
				'neo4j_version' => '2.0.foo',
				'cypher' => $this->endpoint.'/cypher',
			))));
		$this->transport->expects($this->once())
			->method('post')
			->with('/cypher', array(
				'query'  => $expectedQuery,
				'params' => $expectedParams,
			))
			->will($this->returnValue(array('code'=>200,'data'=>array(
				'columns' => array('labels'),
				'data' => array(array($expectedLabels)),
			))));

		$resultLabels = $this->client->addLabels($node, array($labelA, $labelB, $labelC));
		self::assertEquals(count($expectedLabels), count($resultLabels));
		foreach ($resultLabels as $i => $label) {
			self::assertInstanceOf('Everyman\Neo4j\Label', $label);
			self::assertEquals($expectedLabels[$i], $label->getName());
		}
	}

	public function testAddLabels_NoLabelCapability_ThrowsException()
	{
		$nodeId = 123;
		$node = new Node($this->client);
		$node->setId($nodeId);

		$labelAName = 'FOOBAR';
		$labelA = $this->client->makeLabel($labelAName);

		$this->client = $this->getMock('Everyman\Neo4j\Client', array('getServerInfo'), array($this->transport));
		$this->client->expects($this->any())
			->method('getServerInfo')
			->will($this->returnValue(array(
				'cypher' => $this->endpoint.'/cypher',
				'version' => array(
					"full" => "1.9.0",
					"major" => "1",
					"minor" => "9",
				)
			)));

		$this->transport->expects($this->never())
			->method('get');

		$this->setExpectedException('RuntimeException');
		$this->client->addLabels($node, array($labelA));
	}

	public function testAddLabels_NoNodeId_ThrowsException()
	{
		$labelAName = 'FOOBAR';
		$labelA = $this->client->makeLabel($labelAName);

		$node = new Node($this->client);

		$this->transport->expects($this->never())
			->method('post');

		$this->setExpectedException('InvalidArgumentException', 'unsaved node');
		$this->client->addLabels($node, array($labelA));
	}

	public function testAddLabels_NodeIdZero_DoesNotThrowException()
	{
		$nodeId = 0;
		$labelAName = 'FOOBAR';

		$node = new Node($this->client);
		$node->setId($nodeId);

		$labelA = $this->client->makeLabel($labelAName);

		$expectedQuery = "START n=node({nodeId}) SET n:`{$labelAName}` RETURN labels(n) AS labels";
		$expectedParams = array("nodeId" => $nodeId);

		$this->transport->expects($this->once())
			->method('post')
			->with('/cypher', array(
				'query'  => $expectedQuery,
				'params' => $expectedParams,
			))
			->will($this->returnValue(array('code'=>200,'data'=>array(
				'columns' => array('labels'),
				'data' => array(array(array($labelAName))),
			))));

		$resultLabels = $this->client->addLabels($node, array($labelA));
		self::assertEquals(1, count($resultLabels));
		self::assertEquals($labelAName, $resultLabels[0]->getName());
	}

	public function testAddLabels_NonLabelGiven_ThrowsException()
	{
		$labelAName = 'FOOBAR';
		$labelA = $this->client->makeLabel($labelAName);

		$node = new Node($this->client);
		$node->setId(123);

		$this->transport->expects($this->never())
			->method('post');

		$this->setExpectedException('InvalidArgumentException', 'non-label');
		$this->client->addLabels($node, array($labelA, 'not-a-label'));
	}

	public function testAddLabels_NoLabelsGiven_ThrowsException()
	{
		$node = new Node($this->client);
		$node->setId(123);

		$this->transport->expects($this->never())
			->method('post');

		$this->setExpectedException('InvalidArgumentException', 'No labels');
		$this->client->addLabels($node, array());
	}

	public function testRemoveLabels_SendsCorrectCypherQuery()
	{
		$nodeId = 123;
		$labelAName = 'FOOBAR';
		$labelBName = 'BAZ QUX';

		$node = new Node($this->client);
		$node->setId($nodeId);

		$labelA = $this->client->makeLabel($labelAName);
		$labelB = $this->client->makeLabel($labelBName);

		$expectedLabels = array('LOREMIPSUM', $labelAName, $labelBName);

		$expectedQuery = "START n=node({nodeId}) REMOVE n:`{$labelAName}`:`{$labelBName}` RETURN labels(n) AS labels";
		$expectedParams = array("nodeId" => $nodeId);

		$this->transport->expects($this->any())
			->method('get')
			->with('/')
			->will($this->returnValue(array('code'=>200, 'data'=>array(
				'neo4j_version' => '2.0.foo',
				'cypher' => $this->endpoint.'/cypher',
			))));
		$this->transport->expects($this->once())
			->method('post')
			->with('/cypher', array(
				'query'  => $expectedQuery,
				'params' => $expectedParams,
			))
			->will($this->returnValue(array('code'=>200,'data'=>array(
				'columns' => array('labels'),
				'data' => array(array($expectedLabels)),
			))));

		$resultLabels = $this->client->removeLabels($node, array($labelA, $labelB));
		self::assertEquals(count($expectedLabels), count($resultLabels));
		foreach ($resultLabels as $i => $label) {
			self::assertInstanceOf('Everyman\Neo4j\Label', $label);
			self::assertEquals($expectedLabels[$i], $label->getName());
		}
	}

	public function testRemoveLabels_NoLabelCapability_ThrowsException()
	{
		$nodeId = 123;
		$node = new Node($this->client);
		$node->setId($nodeId);

		$labelAName = 'FOOBAR';
		$labelA = $this->client->makeLabel($labelAName);

		$this->client = $this->getMock('Everyman\Neo4j\Client', array('getServerInfo'), array($this->transport));
		$this->client->expects($this->any())
			->method('getServerInfo')
			->will($this->returnValue(array(
				'cypher' => $this->endpoint.'/cypher',
				'version' => array(
					"full" => "1.9.0",
					"major" => "1",
					"minor" => "9",
				)
			)));

		$this->transport->expects($this->never())
			->method('get');

		$this->setExpectedException('RuntimeException');
		$this->client->removeLabels($node, array($labelA));
	}

	public function testRemoveLabels_NoNodeId_ThrowsException()
	{
		$labelAName = 'FOOBAR';
		$labelA = $this->client->makeLabel($labelAName);

		$node = new Node($this->client);

		$this->transport->expects($this->never())
			->method('post');

		$this->setExpectedException('InvalidArgumentException', 'unsaved node');
		$this->client->removeLabels($node, array($labelA));
	}

	public function testRemoveLabels_NonLabelGiven_ThrowsException()
	{
		$labelAName = 'FOOBAR';
		$labelA = $this->client->makeLabel($labelAName);

		$node = new Node($this->client);
		$node->setId(123);

		$this->transport->expects($this->never())
			->method('post');

		$this->setExpectedException('InvalidArgumentException', 'non-label');
		$this->client->removeLabels($node, array($labelA, 'not-a-label'));
	}

	public function testRemoveLabels_NoLabelsGiven_ThrowsException()
	{
		$node = new Node($this->client);
		$node->setId(123);

		$this->transport->expects($this->never())
			->method('post');

		$this->setExpectedException('InvalidArgumentException', 'No labels');
		$this->client->removeLabels($node, array());
	}
}
