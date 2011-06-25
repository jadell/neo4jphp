<?php
namespace Everyman\Neo4j\Cypher;

class QueryAssemblerTest extends \PHPUnit_Framework_TestCase
{
	protected $assembler = null;

	public function setUp()
	{
		$this->assembler = new QueryAssembler();
	}
	
	public function testQuery_NoVars()
	{
		$query = "START a=(0) RETURN a";

		$result = $this->assembler->assembleQuery(array($query));
		$this->assertEquals($query, $result);
	}
	
	public function testQuery_PlaceHolders()
	{
		$expectedQuery = "START a=(1) " .
		                 "MATCH a --> b " .
		                 "WHERE b.email='b\"l\'ah?@\'email.com' AND b.name='bob' " .
		                 "RETURN a";

		$result = $this->assembler->assembleQuery(array(
             "START a=(?) " .
             "MATCH a --> b " .
             "WHERE b.email='b\"l\'ah?@\'email.com' AND b.name=? " .
             "RETURN a",
             1, 'bob'));
		
		$this->assertEquals($expectedQuery , $result);
	}
	
}
