<?php
namespace Everyman\Neo4j;

class TransportTest extends \PHPUnit_Framework_TestCase
{
	protected $host = 'foo.com';
	protected $port = 1234;

	protected $transport = null;

	public function setUp()
	{
		$this->transport = new Transport($this->host, $this->port);
	}

	public function testGetEndpoint_ReturnsCorrectEndpointUrl()
	{
		$this->assertEquals("http://{$this->host}:{$this->port}/db/data", $this->transport->getEndpoint());
	}
}
