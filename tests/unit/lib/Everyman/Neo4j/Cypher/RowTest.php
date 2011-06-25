<?php
namespace Everyman\Neo4j\Cypher;

use Everyman\Neo4j\EntityMapper,
    Everyman\Neo4j\Node,
    Everyman\Neo4j\Relationship;

class RowTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $mapper = null;

	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array('cypherQuery'), array(), '', false);
		$this->mapper = new EntityMapper($this->client);
	}
	
	public function testCount()
	{
		$columns = array('name','age');
		$data = array('Brenda', 14);

		$row = new Row($this->client, $this->mapper, $columns, $data);
		$this->assertEquals(2, count($row));
	}
	
	public function testIterate()
	{
		$columns = array('name','age');
		$data = array('Brenda', 14);

		$row = new Row($this->client, $this->mapper, $columns, $data);
		$i = 0;
		foreach($row as $columnName => $fieldValue) {
			$this->assertEquals($columns[$i], $columnName);
			$this->assertEquals($data[$i], $fieldValue);
			$i++;
		}
	}
	
	public function testArrayAccess()
	{
		$columns = array('name','age');
		$data = array('Brenda', 14);

		$row = new Row($this->client, $this->mapper, $columns, $data);
		$i = 0;
		foreach($columns as $column) {
			$this->assertEquals(true, isset($row[$column]));
			$this->assertEquals(true, isset($row[$i]));
			$this->assertEquals($row[$column], $data[$i]);
			$this->assertEquals($row[$i], $data[$i]);
			$i++;
		}
		
		$this->assertEquals(false, isset($row['blah']));
		$this->assertEquals(false, isset($row[3]));
	}
	
	public function testNodeCasting()
	{
		$columns = array('user');
		$data = array(
			array(
				'data' => array(
					'name' => 'Bob'
				),
				'self' => 'http://localhost/db/data/node/0'
			));

		
		$row = new Row($this->client, $this->mapper, $columns, $data);
		$i = 0;
		
		$this->assertTrue($row['user'] instanceof Node);
	}
	
	public function testRelationshipCasting()
	{
		$columns = array('user');
		$data = array(
			array(
				'data' => array(
					'name' => 'Bob'
				),
				'type' => 'KNOWS',
				'start' => 'http://localhost/db/data/node/0', 
				'end' => 'http://localhost/db/data/node/1', 
				'self' => 'http://localhost/db/data/relationship/0'
			));

		
		$row = new Row($this->client, $this->mapper, $columns, $data);
		
		$this->assertTrue($row['user'] instanceof Relationship);
	}
}
