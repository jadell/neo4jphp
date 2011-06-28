<?php
namespace Everyman\Neo4j;

use Everyman\Neo4j\Node,
    Everyman\Neo4j\Relationship;

class EntityMapperTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $mapepr = null;
	
	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array('cypherQuery'), array(), '', false);
		$this->d = new EntityMapper($this->client);
	}
	
	public function testRelationship()
	{
		$data = array(
			'data' => array(
				'name' => 'Bob'
			),
			'type' => 'KNOWS',
			'start' => 'http://localhost/db/data/node/0', 
			'end' => 'http://localhost/db/data/node/1', 
			'self' => 'http://localhost/db/data/relationship/0'
		);

		$rel = $this->d->getEntityFor($data);
		
		$this->assertTrue($rel instanceof Relationship);
		$this->assertEquals($rel->getId(), 0);
	}
	
	public function testNode()
	{
		$data = array(
			'data' => array(
				'name' => 'Bob'
			),
			'self' => 'http://localhost/db/data/node/0'
		);

		$node = $this->d->getEntityFor($data);
		
		$this->assertTrue($node instanceof Node);
		$this->assertEquals($node->getId(), 0);
	}
}
