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

	public function testIterateWithNull()
	{
		$columns = array('name', 'undefined content', 'age');
		$data = array('Brenda', NULL, 14);

		$row = new Row($this->client, $columns, $data);
		$i = 0;
		foreach($row as $columnName => $fieldValue) {
			$this->assertEquals($columns[$i], $columnName);
			$this->assertEquals($data[$i], $fieldValue);
			$i++;
		}
		$this->assertEquals(count($columns), $i, 'did not iterate over all data');
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

	public function testValueArray_ReturnsAsANewRowObject()
	{
		$columns = array('a', 'b');
		$data = array(
			// 'a' is a node value
			array(
				'data' => array('name' => 'Alice'),
				'self' => 'http://localhost/db/data/node/0'
			),

			// 'b' is a collection of node values
			array(
				array(
					'data' => array('name' => 'Bob'),
					'self' => 'http://localhost/db/data/node/1'
				),
				array(
					'data' => array('name' => 'Cathy'),
					'self' => 'http://localhost/db/data/node/2'
				),
				array(
					'data' => array('name' => 'David'),
					'self' => 'http://localhost/db/data/node/3'
				),
			),
		);

		$row = new Row($this->client, $columns, $data);
		$this->assertInstanceOf('Everyman\Neo4j\Node', $row['a']);

		$this->assertInstanceOf('Everyman\Neo4j\Query\Row', $row['b']);
		foreach ($row['b'] as $innerValue) {
			$this->assertInstanceOf('Everyman\Neo4j\Node', $innerValue);
		}

		$this->assertEquals($data[1][1]['data']['name'], $row['b'][1]->getProperty('name'));
	}
}
