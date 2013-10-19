<?php
namespace Everyman\Neo4j;

class Client_Batch_LabelTest extends \PHPUnit_Framework_TestCase
{
	protected $transport = null;
	protected $batch = null;
	protected $client = null;
	protected $endpoint = 'http://foo:1234/db/data';

	public function setUp()
	{
		$this->transport = $this->getMock('Everyman\Neo4j\Transport');
		$this->transport->expects($this->any())
			->method('getEndpoint')
			->will($this->returnValue($this->endpoint));
		$this->client = new Client($this->transport);

		$this->batch = new Batch($this->client);

		$this->client->getEntityCache()->setCache(new Cache\Variable());
	}

	public function testCommitBatch_CreateRelationship_Success_ReturnsTrue()
	{
		$node = new Node($this->client);
		$node->setId(123);

		$labelName = 'BAZQUX';
		$label = $this->client->makeLabel($labelName);

		$request = array(
			array(
				'id' => 0, 
				'method' => 'POST', 
				'to' => '/node/123/labels',
				'body' => array($labelName)
			)
		);
		
		$return = array('code' => 204, 'data' => null);

		$this->batch->addLabels($node, array($label));
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);
	}

	public function testCommitBatch_CreateRelationship_StartNodeUnidentified_ReturnsTrue()
	{
		$node = new Node($this->client);

		$labelName = 'BAZQUX';
		$label = $this->client->makeLabel($labelName);

		$request = array(
			array(
				'id' => 1, 
				'method' => 'POST', 
				'to' => '/node', 
				'body' => null
			),
			array(
				'id' => 0, 
				'method' => 'POST', 
				'to' => '/node/1/labels',
				'body' => array($labelName)
			)
		);

		$return = array('code' => 200, 'data' => array(
			array('id' => 1, 'location' => 'http://foo:1234/db/data/node/1'),
			array('id' => 0),
		));

		$this->batch->addLabels($node, array($label));
		$this->setupTransportExpectation($request, $this->returnValue($return));
		$result = $this->client->commitBatch($this->batch);
		
		$this->assertTrue($result);
		$this->assertEquals(1, $node->getId());
		$this->assertSame($node, $this->client->getEntityCache()->getCachedEntity(1, 'node'));

		return;
	}

	protected function setupTransportExpectation($request, $will)
	{
		$this->transport->expects($this->once())
			->method('post')
			->with('/batch', $request)
			->will($will);
	}
}
