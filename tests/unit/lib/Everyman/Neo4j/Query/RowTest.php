<?php
namespace Everyman\Neo4j\Query;

use Everyman\Neo4j\Client,
    Everyman\Neo4j\Node,
    Everyman\Neo4j\Relationship;

class RowTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;

	public function setUp()
	{
		$this->client = new Client($this->getMock('Everyman\Neo4j\Transport', array(), array(), '', false));
	}
	
	public function testCount()
	{
		$columns = array('name','age');
		$data = array('Brenda', 14);

		$row = new Row($this->client, $columns, $data);
		$this->assertEquals(2, count($row));
	}
	
	public function testIterate()
	{
		$columns = array('name','age');
		$data = array('Brenda', 14);

		$row = new Row($this->client, $columns, $data);
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

		$row = new Row($this->client, $columns, $data);
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

	public function testArrayAccess_Set_ThrowsException()
	{
		$columns = array('name','age');
		$data = array('Brenda', 14);
		$row = new Row($this->client, $columns, $data);

		$this->setExpectedException('BadMethodCallException');
		$row['test'] = 'value';
	}

	public function testArrayAccess_Unset_ThrowsException()
	{
		$columns = array('name','age');
		$data = array('Brenda', 14);
		$row = new Row($this->client, $columns, $data);

		$this->setExpectedException('BadMethodCallException');
		unset($row['name']);
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

		
		$row = new Row($this->client, $columns, $data);
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

		
		$row = new Row($this->client, $columns, $data);
		
		$this->assertTrue($row['user'] instanceof Relationship);
	}
}
