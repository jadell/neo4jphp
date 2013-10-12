<?php
namespace Everyman\Neo4j;

use Everyman\Neo4j\Cypher\Query,
    Everyman\Neo4j\Query\ResultSet;

class TransactionTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $transaction = null;
	protected $transactionId = 123;

	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client');
		$this->transaction = new Transaction($this->client);
		$this->transaction->setId($this->transactionId);
	}

	public function testSetId_CorrectlySetsId()
	{
		self::assertEquals($this->transactionId, $this->transaction->getId());
	}

	public function testSetId_SameIdSetAgain_CorrectlySetsId()
	{
		$result = $this->transaction->setId($this->transactionId);
		self::assertEquals($this->transactionId, $this->transaction->getId());
		self::assertSame($this->transaction, $result);
	}

	public function testSetId_DifferentIdSet_ThrowsException()
	{
		$idDifferent = $this->transactionId+1000;
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
		self::assertTrue($this->transaction->isClosed());
	}

	public function testCommit_NoId_NoopAndMarksTransactionClosed()
	{
		$transaction = new Transaction($this->client);

		$this->client->expects($this->never())
			->method('addStatementsToTransaction');

		$result = $transaction->commit();
		self::assertSame($transaction, $result);
		self::assertTrue($transaction->isClosed());
	}

	public function testCommit_ClosedTransaction_ThrowsException()
	{
		$result = $this->transaction->commit();

		$this->setExpectedException('\Everyman\Neo4j\Exception', 'already closed');
		$this->transaction->commit();
	}

	public function testRollback_DelegatesToClient()
	{
		$this->client->expects($this->once())
			->method('rollbackTransaction')
			->with($this->transaction);

		$result = $this->transaction->rollback();
		self::assertSame($this->transaction, $result);
		self::assertTrue($this->transaction->isClosed());
	}

	public function testRollback_NoTransactionId_NoopAndMarksTransactionClosed()
	{
		$transaction = new Transaction($this->client);

		$this->client->expects($this->never())
			->method('rollbackTransaction');

		$result = $transaction->rollback();
		self::assertSame($transaction, $result);
		self::assertTrue($transaction->isClosed());
	}

	public function testRollback_ClosedTransaction_ThrowsException()
	{
		$result = $this->transaction->commit();

		$this->setExpectedException('\Everyman\Neo4j\Exception', 'already closed');
		$this->transaction->rollback();
	}

	public function testKeepAlive_DelegatesToClient()
	{
		$this->client->expects($this->once())
			->method('addStatementsToTransaction')
			->with($this->transaction, array(), false);

		$result = $this->transaction->keepAlive();
		self::assertSame($this->transaction, $result);
		self::assertFalse($this->transaction->isClosed());
	}

	public function testKeepAlive_NoTransactionId_Noop()
	{
		$transaction = new Transaction($this->client);

		$this->client->expects($this->never())
			->method('addStatementsToTransaction');

		$result = $transaction->keepAlive();
		self::assertSame($transaction, $result);
		self::assertFalse($transaction->isClosed());
	}

	public function testKeepAlive_ClosedTransaction_ThrowsException()
	{
		$result = $this->transaction->commit();

		$this->setExpectedException('\Everyman\Neo4j\Exception', 'already closed');
		$this->transaction->keepAlive();
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
		self::assertTrue($this->transaction->isClosed());
	}

	public function testAddStatements_NoCommit_TransactionOpen()
	{
		$statements = array(new Query($this->client, 'foo'));

		$this->client->expects($this->once())
			->method('addStatementsToTransaction')
			->with($this->transaction, $statements, false);

		$this->transaction->addStatements($statements);
		self::assertFalse($this->transaction->isClosed());
	}

	public function testAddStatements_ClosedTransaction_ThrowsException()
	{
		$result = $this->transaction->commit();

		$this->setExpectedException('\Everyman\Neo4j\Exception', 'already closed');
		$this->transaction->addStatements(array(
			new Query($this->client, 'foo'),
			new Query($this->client, 'bar'),
		));
	}
}
