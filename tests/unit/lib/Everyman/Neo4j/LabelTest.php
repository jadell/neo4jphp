<?php
namespace Everyman\Neo4j;

class LabelTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;

	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client');
	}

	public function dataProvider_ValidNames()
	{
		return array(
			array('TEST LABEL NAME'),
			array(123),
			array(123.45),
		);
	}

	/**
	 * @dataProvider dataProvider_ValidNames
	 */
	public function testContruct_ValidNameGiven_SetsNameCastAsString($name)
	{
		$label = new Label($this->client, $name);
		self::assertEquals($name, $label->getName());
		self::assertInternalType('string', $label->getName());
	}

	public function dataProvider_InvalidNames()
	{
		return array(
			array(null),
			array(''),
			array(true),
			array(array()),
			array(array('foo')),
			array(new \stdClass()),
		);
	}

	/**
	 * @dataProvider dataProvider_InvalidNames
	 */
	public function testContruct_InvalidNameGiven_ThrowsException($name)
	{
		$this->setExpectedException('InvalidArgumentException');
		$label = new Label($this->client, $name);
	}

	public function testGetNodes_NoPropertyGiven_CallsClientMethod()
	{
		$label = new Label($this->client, 'foobar');

		$this->client->expects($this->once())
			->method('getNodesForLabel')
			->with($label, null, null);

		$label->getNodes();
	}

	public function testGetNodes_PropertyGiven_CallsClientMethod()
	{
		$label = new Label($this->client, 'foobar');
		$property = 'baz';
		$value = 'qux';

		$this->client->expects($this->once())
			->method('getNodesForLabel')
			->with($label, $property, $value);

		$label->getNodes($property, $value);
	}
}
