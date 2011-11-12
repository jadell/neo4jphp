<?php
namespace Everyman\Neo4j;

class Client_CacheTest extends \PHPUnit_Framework_TestCase
{
	protected $transport = null;
	protected $cache = null;
	protected $client = null;

	public function setUp()
	{
		$this->transport = $this->getMock('Everyman\Neo4j\Transport');
		$this->cache = new Cache\Variable();

		$this->client = new Client($this->transport);
		$this->client->getEntityCache()->setCache($this->cache);
	}

	public function testLoadNode_Found_NodeInCache()
	{
		$nodeId = 123;
		$node = new Node($this->client);
		$node->setId($nodeId);

		$data = array('data' => array(
				'name' => 'FOO',
		));

		$this->transport->expects($this->once())
			->method('get')
			->with('/node/'.$nodeId)
			->will($this->returnValue(array('code'=>'200','data'=>$data)));

		$this->client->loadNode($node);
		$this->assertSame($node, $this->cache->get("node-{$nodeId}"));
		
		$subseq = new Node($this->client);
		$subseq->setId($nodeId);
		$this->client->loadNode($subseq);
		$this->assertEquals($node->getProperties(), $subseq->getProperties());
	}

	public function testLoadNode_NotFound_NodeNotInCache()
	{
		$nodeId = 123;
		$node = new Node($this->client);
		$node->setId($nodeId);

		$this->transport->expects($this->once())
			->method('get')
			->with('/node/'.$nodeId)
			->will($this->returnValue(array('code'=>'404')));

		try {
			$this->client->loadNode($node);
			$this->fail();
		} catch (Exception $e) {
			$this->assertFalse($this->cache->get("node-{$nodeId}"));
		}
	}

	public function testLoadRelationship_Found_RelationshipInCache()
	{
		$relId = 123;
		$rel = new Relationship($this->client);
		$rel->setId($relId);

		$data = array(
			'data' => array(
				'name' => 'FOO',
			),
			'start' => 'http://foo:1234/db/data/node/567',
			'end'   => 'http://foo:1234/db/data/node/890',
			'type'  => 'FOOTYPE',
		);
		
		$this->transport->expects($this->once())
			->method('get')
			->with('/relationship/'.$relId)
			->will($this->returnValue(array('code'=>'200','data'=>$data)));

		$this->client->loadRelationship($rel);
		$this->assertSame($rel, $this->cache->get("relationship-{$relId}"));

		$subseq = new Relationship($this->client);
		$subseq->setId($relId);
		$this->client->loadRelationship($subseq);
		$this->assertEquals($rel->getProperties(), $subseq->getProperties());
	}

	public function testLoadRelationship_NotFound_RelationshipNotInCache()
	{
		$relId = 123;
		$rel = new Relationship($this->client);
		$rel->setId($relId);
		
		$this->transport->expects($this->once())
			->method('get')
			->with('/relationship/'.$relId)
			->will($this->returnValue(array('code'=>'404','data'=>array())));


		try {
			$this->client->loadRelationship($rel);
			$this->fail();
		} catch (Exception $e) {
			$this->assertFalse($this->cache->get("relationship-{$relId}"));
		}
	}

	public function testGetNode_Found_SubsequentCallsReturnsFromCache()
	{
		$nodeId = 123;
		$this->transport->expects($this->once())
			->method('get')
			->with('/node/'.$nodeId)
			->will($this->returnValue(array('code'=>'200','data'=>array('data'=>array()))));

		$node = $this->client->getNode($nodeId);
		$subseq = $this->client->getNode($nodeId);
		$this->assertSame($node, $subseq);
	}

	public function testGetRelationship_Found_SubsequentCallsReturnsFromCache()
	{
		$relId = 123;
		$data = array(
			'data' => array(),
			'start' => 'http://foo:1234/db/data/node/567',
			'end'   => 'http://foo:1234/db/data/node/890',
			'type'  => 'FOOTYPE',
		);
		
		$this->transport->expects($this->once())
			->method('get')
			->with('/relationship/'.$relId)
			->will($this->returnValue(array('code'=>'200','data'=>$data)));

		$rel = $this->client->getRelationship($relId);
		$subseq = $this->client->getRelationship($relId);
		$this->assertSame($rel, $subseq);
	}

	public function testDeleteNode_Success_NodeNotInCache()
	{
		$nodeId = 123;
		$node = new Node($this->client);
		$node->setId($nodeId);

		$this->transport->expects($this->once())
			->method('delete')
			->with('/node/'.$nodeId)
			->will($this->returnValue(array('code'=>'200')));

		$this->cache->set("node-{$nodeId}", $node);
		$this->client->deleteNode($node);
		$this->assertFalse($this->cache->get("node-{$nodeId}"));
	}

	public function testDeleteNode_Failure_NodeRemainsInCache()
	{
		$nodeId = 123;
		$node = new Node($this->client);
		$node->setId($nodeId);

		$this->transport->expects($this->once())
			->method('delete')
			->with('/node/'.$nodeId)
			->will($this->returnValue(array('code'=>'400')));

		$this->cache->set("node-{$nodeId}", $node);
		try {
			$this->client->deleteNode($node);
			$this->fail();
		} catch (Exception $e) {
			$this->assertSame($node, $this->cache->get("node-{$nodeId}"));
		}
	}

	public function testDeleteRelationship_Success_RelationshipNotInCache()
	{
		$relId = 123;
		$rel = new Relationship($this->client);
		$rel->setId($relId);

		$this->transport->expects($this->once())
			->method('delete')
			->with('/relationship/'.$relId)
			->will($this->returnValue(array('code'=>'200')));

		$this->cache->set("relationship-{$relId}", $rel);
		$this->client->deleteRelationship($rel);
		$this->assertFalse($this->cache->get("relationship-{$relId}"));
	}

	public function testDeleteRelationship_Failure_RelationshipRemainsInCache()
	{
		$relId = 123;
		$rel = new Relationship($this->client);
		$rel->setId($relId);

		$this->transport->expects($this->once())
			->method('delete')
			->with('/relationship/'.$relId)
			->will($this->returnValue(array('code'=>400)));

		$this->cache->set("relationship-{$relId}", $rel);

		try {
			$this->client->deleteRelationship($rel);
			$this->fail();
		} catch (Exception $e) {
			$this->assertSame($rel, $this->cache->get("relationship-{$relId}"));
		}
	}

	public function testSaveNode_Success_NodeInCache()
	{
		$nodeId = 123;
		$node = new Node($this->client);
		$node->useLazyLoad(false)
			->setId($nodeId);

		$this->transport->expects($this->once())
			->method('put')
			->with('/node/123/properties', array())
			->will($this->returnValue(array('code'=>204)));

		$this->client->saveNode($node);
		$this->assertSame($node, $this->cache->get("node-{$nodeId}"));
	}

	public function testSaveNode_Failure_NodeNotInCache()
	{
		$nodeId = 123;
		$node = new Node($this->client);
		$node->useLazyLoad(false)
			->setId($nodeId);

		$this->transport->expects($this->once())
			->method('put')
			->with('/node/123/properties', array())
			->will($this->returnValue(array('code'=>400)));

		try {
			$this->client->saveNode($node);
			$this->fail();
		} catch (Exception $e) {
			$this->assertFalse($this->cache->get("node-{$nodeId}"));
		}
	}

	public function testSaveRelationship_Success_RelationshipInCache()
	{
		$relId = 123;
		$rel = new Relationship($this->client);
		$rel->useLazyLoad(false)
			->setId($relId);
		
		$this->transport->expects($this->once())
			->method('put')
			->with('/relationship/123/properties', array())
			->will($this->returnValue(array('code'=>204)));

		$this->client->saveRelationship($rel);
		$this->assertSame($rel, $this->cache->get("relationship-{$relId}"));
	}

	public function testSaveRelationship_Failure_RelationshipNotInCache()
	{
		$relId = 123;
		$rel = new Relationship($this->client);
		$rel->useLazyLoad(false)
			->setId($relId);
		
		$this->transport->expects($this->once())
			->method('put')
			->with('/relationship/123/properties', array())
			->will($this->returnValue(array('code'=>400)));

		try {
			$this->client->saveRelationship($rel);
			$this->fail();
		} catch (Exception $e) {
			$this->assertFalse($this->cache->get("relationship-{$relId}"));
		}
	}
}
