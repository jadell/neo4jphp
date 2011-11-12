<?php
namespace Everyman\Neo4j;

class Client_Batch_NodeTest extends \PHPUnit_Framework_TestCase
{
	protected $transport = null;
	protected $batch = null;
	protected $client = null;

	public function setUp()
	{
		$this->transport = $this->getMock('Everyman\Neo4j\Transport');
		$this->client = new Client($this->transport);

		$this->batch = new Batch($this->client);

		$this->client->getEntityCache()->setCache(new Cache\Variable());
	}

	public function testCommitBatch_TransportError_ThrowsException()
	{
		$node = new Node($this->client);
		$request = array(array('id' => 0, 'method' => 'POST', 'to' => '/node', 'body' => null));
		
		$this->batch->save($node);
		$this->setupTransportExpectation($request, $this->returnValue(array('code' => 400)));

		$this->setExpectedException('\Everyman\Neo4j\Exception');
		$this->client->commitBatch($this->batch);
	}

	public function testCommitBatch_CreateNode_Success_ReturnsTrue()
	{
		$node = new Node($this->client);
		$node->setProperties(array('foo' => 'bar','baz' => 'qux'));

		$request = array(array('id' => 0, 'method' => 'POST', 'to' => '/node',
			'body' => array('foo' => 'bar','baz' => 'qux')));
		
		$return = array('code' => 200, 'data' => array(
				array('id' => 0, 'location' => 'http://foo:1234/db/data/node/123')));

		$this->batch->save($node);
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);
		$this->assertEquals(123, $node->getId());

		$this->assertSame($node, $this->client->getEntityCache()->getCachedEntity(123, 'node'));
	}
	
	public function testCommitBatch_UpdateNode_Success_ReturnsTrue()
	{
		$node = new Node($this->client);
		$node->useLazyLoad(false)
			->setId(123)
			->setProperties(array('foo' => 'bar','baz' => 'qux'));

		$request = array(array('id' => 0, 'method' => 'PUT', 'to' => '/node/123/properties',
			'body' => array('foo' => 'bar','baz' => 'qux')));
		
		$return = array('code' => 200, 'data' => array(
				array('id' => 0)));

		$this->batch->save($node);
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);

		$this->assertSame($node, $this->client->getEntityCache()->getCachedEntity(123, 'node'));
	}
	
	public function testCommitBatch_DeleteNode_Success_ReturnsTrue()
	{
		$node = new Node($this->client);
		$node->setId(123);
		$this->client->getEntityCache()->setCachedEntity($node);

		$request = array(array('id' => 0, 'method' => 'DELETE', 'to' => '/node/123'));
		
		$return = array('code' => 200, 'data' => array(
				array('id' => 0)));

		$this->batch->delete($node);
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);

		$this->assertFalse($this->client->getEntityCache()->getCachedEntity(123, 'node'));
	}

	protected function setupTransportExpectation($request, $will)
	{
		$this->transport->expects($this->once())
			->method('post')
			->with('/batch', $request)
			->will($will);
	}
}
