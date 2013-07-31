<?php
namespace Everyman\Neo4j\Query;

use Everyman\Neo4j\Client;

class ResultSetTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;

	public function setUp()
	{
		$this->client = new Client($this->getMock('Everyman\Neo4j\Transport'));
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

		$result = new ResultSet($this->client, $data);
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

		$result = new ResultSet($this->client, $data);
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
				array('Brenda', 14),
				array('Jimmy', null)
			)
		);

		$result = new ResultSet($this->client, $data);
		for($i=0,$l=4; $i<$l; $i++) {
			$this->assertEquals(true, isset($result[$i]));
			$this->assertEquals($data['data'][$i][0], $result[$i][0]);
		}

		//issue #83
		$this->assertFalse(isset($result[3]['age']));
		$this->assertTrue(is_null($result[3]['age']));
		$this->assertEquals(null, $result[3]['age']);

		$this->assertEquals(false, isset($result[4]));
	}

	public function testArrayAccess_CacheResultRows()
	{
		$data = array(
			'columns' => array('name','age'),
			'data' => array(
				array('Bob', 12),
				array('Lotta', 0),
				array('Brenda', 14)
			)
		);

		$result = new ResultSet($this->client, $data);
		$row = $result[0];
		$again = $result[0];
		$this->assertSame($row, $again);
	}

	public function testArrayAccess_Set_ThrowsException()
	{
		$data = array(
			'columns' => array('name','age'),
			'data' => array(
				array('Bob', 12),
				array('Lotta', 0),
				array('Brenda', 14)
			)
		);

		$result = new ResultSet($this->client, $data);

		$this->setExpectedException('BadMethodCallException');
		$result[3] = 'value';
	}

	public function testArrayAccess_Unset_ThrowsException()
	{
		$data = array(
			'columns' => array('name','age'),
			'data' => array(
				array('Bob', 12),
				array('Lotta', 0),
				array('Brenda', 14)
			)
		);

		$result = new ResultSet($this->client, $data);

		$this->setExpectedException('BadMethodCallException');
		unset($result[0]);
	}
}
