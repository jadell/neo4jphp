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
		$this->client = new Client($this->transport);
	}

	public function testGetLabel_ReturnsLabel()
	{
		$labelNameA = 'FOOBAR';
		$labelNameB = 'BAZQUX';

		$labelA = $this->client->getLabel($labelNameA);
		$labelB = $this->client->getLabel($labelNameB);

		self::assertInstanceOf('Everyman\Neo4j\Label', $labelA);
		self::assertEquals($labelNameA, $labelA->getName());

		self::assertInstanceOf('Everyman\Neo4j\Label', $labelB);
		self::assertEquals($labelNameB, $labelB->getName());
	}

	public function testGetLabel_SameName_ReturnsSameLabelInstance()
	{
		$labelName = 'FOOBAR';

		$labelA = $this->client->getLabel($labelName);
		$labelB = $this->client->getLabel($labelName);

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

	public function testListLabels_ReturnsArrayOfLabelsAttachedToNodesOnTheServer()
	{
		$labelAlreadyInstantiated = $this->client->getLabel('BAZQUX');

		$returnData = array('FOOBAR', $labelAlreadyInstantiated->getName(), 'LOREMIPSUM');

		$this->transport->expects($this->once())
			->method('get')
			->with("/labels")
			->will($this->returnValue(array('code'=>200,'data'=>$returnData)));

		$labels = $this->client->listlabels();
		self::assertEquals(count($returnData), count($labels));
		foreach ($labels as $i => $label) {
			self::assertInstanceOf('Everyman\Neo4j\Label', $label);
			self::assertEquals($returnData[$i], $label->getName());
		}

		self::assertSame($labelAlreadyInstantiated, $labels[1]);
	}
}
