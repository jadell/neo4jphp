<?php
namespace Everyman\Neo4j\Cypher;

use Everyman\Neo4j\EntityMapper;

class ResultSetTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $mapper = null;

	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array(), array(), '', false);
		$this->mapper = new EntityMapper($this->client);
	}
	
	public function testCount()
	{
		$data = array(
			'columns' => array('name','age'),
			'data' => array(
				array('Bob', 12),
				array('Lotta', 0),
				array('Brenda', 14)
			)
		);

		$result = new ResultSet($this->client, $this->mapper, $data);
		$this->assertEquals(3, count($result));
	}
	
	public function testIterate()
	{
		$data = array(
			'columns' => array('name','age'),
			'data' => array(
				array('Bob', 12),
				array('Lotta', 0),
				array('Brenda', 14)
			)
		);

		$result = new ResultSet($this->client, $this->mapper,$data);
		foreach($result as $index => $row) {
			$this->assertEquals($data['data'][$index][0], $row['name']);
			$this->assertEquals($data['data'][$index][0], $row[0]);
			$this->assertTrue($row instanceof Row);
		}
	}
	
	public function testArrayAccess()
	{
		$data = array(
			'columns' => array('name','age'),
			'data' => array(
				array('Bob', 12),
				array('Lotta', 0),
				array('Brenda', 14)
			)
		);

		$result = new ResultSet($this->client, $this->mapper, $data);
		for($i=0,$l=3; $i<$l; $i++) {
			$this->assertEquals(true, isset($result[$i]));
			$this->assertEquals($data['data'][$i][0], $result[$i][0]);
		}
		
		$this->assertEquals(false, isset($result[4]));
	}
}
