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

	public function testConstants_MakeSureNothingSillyHappensLikeMisnamingTheConstants_ReturnsCorrectString()
	{
		$this->assertEquals('GET', Transport::GET);
		$this->assertEquals('POST', Transport::POST);
		$this->assertEquals('PUT', Transport::PUT);
		$this->assertEquals('DELETE', Transport::DELETE);
	}

	public function testGetEndpoint_ReturnsCorrectEndpointUrl()
	{
		$this->assertEquals("http://{$this->host}:{$this->port}/db/data", $this->transport->getEndpoint());
	}

	public function testGetEndpoint_UseHttps_ReturnsCorrectEndpointUrl()
	{
		$this->transport->useHttps();
		$this->assertEquals("https://{$this->host}:{$this->port}/db/data", $this->transport->getEndpoint());
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
	
	public function testEncodeData_StringGiven_ReturnsString()
	{
		$data = 'http://localhost:7474/db/data/node/19';
		$expected = '"http:\/\/localhost:7474\/db\/data\/node\/19"';
		$result = $this->transport->encodeData($data);
		$this->assertEquals($expected, $result);
	}
	
	public function testEncodeData_ArrayWithNonNumericKeys_ReturnsString()
	{
		$obj = new \stdClass();
		$obj->s = "hi";
		$obj->i = 9;
		$obj->a = array(7,8);
	
		$data = array(
			'string' => 'http://localhost:7474/db/data/node/19',
			'int' => 123,
			'array' => array(4,5,6),
			'object' => $obj,
		);
		$expected = '{"string":"http:\/\/localhost:7474\/db\/data\/node\/19","int":123,"array":[4,5,6],"object":{"s":"hi","i":9,"a":[7,8]}}';
		$result = $this->transport->encodeData($data);
		$this->assertEquals($expected, $result);
	}
	
	public function testEncodeData_ArrayWithNumericKeys_ReturnsString()
	{
		$obj = new \stdClass();
		$obj->s = "hi";
		$obj->i = 9;
		$obj->a = array(7,8);

		$data = array(
			'http://localhost:7474/db/data/node/19',
			123,
			array(4,5,6),
			$obj,
		);
		$expected = '["http:\/\/localhost:7474\/db\/data\/node\/19",123,[4,5,6],{"s":"hi","i":9,"a":[7,8]}]';
		$result = $this->transport->encodeData($data);
		$this->assertEquals($expected, $result);
	}
	
	public function testEncodeData_EmptyArray_ReturnsString()
	{
		$data = array();
		$expected = '{}';
		$result = $this->transport->encodeData($data);
		$this->assertEquals($expected, $result);
	}
}
