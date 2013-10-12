<?php
namespace Everyman\Neo4j;

use Everyman\Neo4j\Cypher\Query,
    Everyman\Neo4j\Query\ResultSet;

class TransactionTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $transaction = null;

	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client');
		$this->transaction = new Transaction($this->client);
	}

	public function testSetId_CorrectlySetsId()
	{
		$id = 123;
		$result = $this->transaction->setId($id);
		self::assertEquals($id, $this->transaction->getId());
		self::assertSame($this->transaction, $result);
	}

	public function testSetId_SameIdSetAgain_CorrectlySetsId()
	{
		$id = 123;
		$this->transaction->setId($id);
		$this->transaction->setId($id);
		self::assertEquals($id, $this->transaction->getId());
	}

	public function testSetId_DifferentIdSet_ThrowsException()
	{
		$id = 123;
		$this->transaction->setId($id);

		$idDifferent = 321;
		$this->setExpectedException('InvalidArgumentException', 'new id');
		$this->transaction->setId($idDifferent);
	}

	public function testCommit_DelegatesToClient()
	{
		$this->client->expects($this->once())
			->method('addStatementsToTransaction')
			->with($this->transaction, array(), true);

		$result = $this->transaction->commit();
		self::assertSame($this->transaction, $result);
	}

	public function testRollback_DelegatesToClient()
	{
		$this->client->expects($this->once())
			->method('rollbackTransaction')
			->with($this->transaction);

		$result = $this->transaction->rollback();
		self::assertSame($this->transaction, $result);
	}

	public function testKeepAlive_DelegatesToClient()
	{
		$this->client->expects($this->once())
			->method('addStatementsToTransaction')
			->with($this->transaction, array(), false);

		$result = $this->transaction->keepAlive();
		self::assertSame($this->transaction, $result);
	}

	public function testAddStatements_DelegatesToClient()
	{
		$statements = array(
			new Query($this->client, 'foo'),
			new Query($this->client, 'bar'),
		);
		$commit = true;

		$expected = new ResultSet($this->client, array());

		$this->client->expects($this->once())
			->method('addStatementsToTransaction')
			->with($this->transaction, $statements, $commit)
			->will($this->returnValue($expected));

		$result = $this->transaction->addStatements($statements, $commit);
		self::assertSame($expected, $result);
	}
}
