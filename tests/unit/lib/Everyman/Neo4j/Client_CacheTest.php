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
		$this->client->setCache($this->cache);
	}

	public function testLoadNode_Found_NodeInCache()
	{
		$nodeId = 123;
		$node = new Node($this->client);
		$node->setId($nodeId);

		$data = array(
			'name' => 'FOO',
		);

		$this->transport->expects($this->once())
			->method('get')
			->with('/node/'.$nodeId.'/properties')
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
			->with('/node/'.$nodeId.'/properties')
			->will($this->returnValue(array('code'=>'404')));

		$this->client->loadNode($node);
		$this->assertFalse($this->cache->get("node-{$nodeId}"));
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

		$this->client->loadRelationship($rel);
		$this->assertFalse($this->cache->get("relationship-{$relId}"));
	}

	public function testGetNode_Found_SubsequentCallsReturnsFromCache()
	{
		$nodeId = 123;
		$this->transport->expects($this->once())
			->method('get')
			->with('/node/'.$nodeId.'/properties')
			->will($this->returnValue(array('code'=>'200','data'=>array())));

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
}
