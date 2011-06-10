<?php
namespace Everyman\Neo4j;

class TransportTest extends \PHPUnit_Framework_TestCase
{
	protected $host = 'foo.com';
	protected $port = 1234;

	protected $transport = null;

	public function setUp()
	{
		$this->transport = $this->getMock('Everyman\Neo4j\Transport', array('makeRequest'), array($this->host, $this->port));
	}

	public function testGetEndpoint_ReturnsCorrectEndpointUrl()
	{
		$this->assertEquals("http://{$this->host}:{$this->port}/db/data", $this->transport->getEndpoint());
	}

	public function testDelete_MakesRequestWithCorrectParams()
	{
		$expected = array('code'=>200,'headers'=>array('Location'=>'somewhere'),'data'=>array('key'=>'val'));

		$this->transport->expects($this->once())
			->method('makeRequest')
			->with(Transport::DELETE, '/path')
			->will($this->returnValue($expected));

		$this->assertEquals($expected, $this->transport->delete('/path'));
	}

	public function testPut_MakesRequestWithCorrectParams()
	{
		$expected = array('code'=>200,'headers'=>array('Location'=>'somewhere'),'data'=>array('key'=>'val'));

		$this->transport->expects($this->once())
			->method('makeRequest')
			->with(Transport::PUT, '/path', 'somedata')
			->will($this->returnValue($expected));

		$this->assertEquals($expected, $this->transport->put('/path','somedata'));
	}

	public function testPost_MakesRequestWithCorrectParams()
	{
		$expected = array('code'=>200,'headers'=>array('Location'=>'somewhere'),'data'=>array('key'=>'val'));

		$this->transport->expects($this->once())
			->method('makeRequest')
			->with(Transport::POST, '/path', 'somedata')
			->will($this->returnValue($expected));

		$this->assertEquals($expected, $this->transport->post('/path','somedata'));
	}

	public function testGet_MakesRequestWithCorrectParams()
	{
		$expected = array('code'=>200,'headers'=>array('Location'=>'somewhere'),'data'=>array('key'=>'val'));

		$this->transport->expects($this->once())
			->method('makeRequest')
			->with(Transport::GET, '/path', 'somedata')
			->will($this->returnValue($expected));

		$this->assertEquals($expected, $this->transport->get('/path','somedata'));
	}
}
