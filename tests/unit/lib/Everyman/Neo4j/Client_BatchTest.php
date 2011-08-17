<?php
namespace Everyman\Neo4j;

class Client_BatchTest extends \PHPUnit_Framework_TestCase
{
	protected $transport = null;
	protected $batch = null;
	protected $client = null;

	public function setUp()
	{
		$this->transport = $this->getMock('Everyman\Neo4j\Transport');
		$this->client = new Client($this->transport);

		$this->batch = new Batch($this->client);
	}

	public function testCommitBatch_CreateNode_Success_ReturnsTrue()
	{
		$node = new Node($this->client);
		$node->setProperties(array('foo' => 'bar','baz' => 'qux'));

		$request = array(array('method' => 'POST', 'to' => '/node',
			'body' => array('foo' => 'bar','baz' => 'qux')));
		
		$return = array('code' => 200, 'data' => array(
				array('location' => 'http://foo:1234/db/data/node/123')));

		$this->batch->save($node);
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);
		$this->assertEquals(123, $node->getId());
	}
	
	public function testCommitBatch_UpdateNode_Success_ReturnsTrue()
	{
		$node = new Node($this->client);
		$node->setId(123)
			->setProperties(array('foo' => 'bar','baz' => 'qux'));

		$request = array(array('method' => 'PUT', 'to' => '/node/123/properties',
			'body' => array('foo' => 'bar','baz' => 'qux')));
		
		$return = array('code' => 200, 'data' => array(
				array()));

		$this->batch->save($node);
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);
	}

	protected function setupTransportExpectation($request, $will)
	{
		$this->transport->expects($this->once())
			->method('post')
			->with('/batch', $request)
			->will($will);
	}
}
