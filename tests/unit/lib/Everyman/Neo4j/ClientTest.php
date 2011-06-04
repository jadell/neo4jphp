<?php
namespace Everyman\Neo4j;

class ClientTest extends \PHPUnit_Framework_TestCase
{
	protected $transport = null;
	protected $client = null;

	public function setUp()
	{
		$this->transport = $this->getMock('Everyman\Neo4j\Transport');
		$this->client = new Client($this->transport);
	}

	/**
	 * @dataProvider dataProvider_DeleteScenarios
	 */
	public function testDelete_TransportReturnsSuccess_ReturnsTrue($result, $success, $error)
	{
		$node = new Node($this->client);
		$node->setId(123);
		
		$this->transport->expects($this->once())
			->method('delete')
			->with('/node/123')
			->will($this->returnValue($result));

		$this->assertEquals($success, $this->client->deleteNode($node));
		$this->assertEquals($error, $this->client->getLastError());
	}

	public function dataProvider_DeleteScenarios()
	{
		return array(// result, success, error
			array(array('code'=>204), true, null),
			array(array('code'=>404), false, Client::ErrorNotFound),
			array(array('code'=>409), false, Client::ErrorConflict),
		);
	}

	public function testDelete_NodeHasNoId_ThrowsException()
	{
		$node = new Node($this->client);

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$this->client->deleteNode($node);
	}
}
