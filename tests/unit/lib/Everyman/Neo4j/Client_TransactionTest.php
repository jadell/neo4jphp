<?php
namespace Everyman\Neo4j;

class Client_TransactionTest extends \PHPUnit_Framework_TestCase
{
	protected $transport = null;
	protected $client = null;
	protected $endpoint = 'http://foo:1234/db/data';

	public function setUp()
	{
		$this->transport = $this->getMock('Everyman\Neo4j\Transport');
		$this->transport->expects($this->any())
			->method('getEndpoint')
			->will($this->returnValue($this->endpoint));

		$this->client = $this->getMock('Everyman\Neo4j\Client', array('hasCapability'), array($this->transport));
		$this->client->expects($this->any())
			->method('hasCapability')
			->will($this->returnValue(true));
	}

	public function testBeginTransaction_ReturnsNewTransactionWithNoId()
	{
		$result = $this->client->beginTransaction();
		self::assertInstanceOf('\Everyman\Neo4j\Transaction', $result);
		self::assertNull($result->getId());
	}

	public function testAddStatements_NewTransaction_ReturnsResultSetAndSetsTransactionId()
	{
		$queryTemplateA = "This is the query template";
		$queryParamsA = array('foo' => 'bar', 'baz' => 123);
		$queryA = new Cypher\Query($this->client, $queryTemplateA, $queryParamsA);

		$queryTemplateB = "This is the query template B";
		$queryParamsB = array('foo' => 'barB', 'bazB' => 456);
		$queryB = new Cypher\Query($this->client, $queryTemplateB, $queryParamsB);

		$transaction = new Transaction($this->client);

		$expectedRequest = array(
			'statements' => array(
				array(
					'statement'  => $queryTemplateA,
					'parameters' => $queryParamsA,
					'resultDataContents' => array('rest'),
				),
				array(
					'statement'  => $queryTemplateB,
					'parameters' => $queryParamsB,
					'resultDataContents' => array('rest'),
				),
			),
		);

		$expectedResponse = array(
			"commit" => $this->endpoint . '/transaction/987/commit',
			"transaction" => array("expires" => "Wed, 16 Oct 2013 23:07:12 +0000"),
			"errors" => array(),
			"results" => array(
				// Result of queryA
				array(
					'columns' => array('name','age'),
					'data' => array(
						array("rest" => array('Bob', 12)),
						array("rest" => array('Lotta', 0)),
						array("rest" => array('Brenda', 14)),
					)
				),
				// Result of queryB
				array(
					'columns' => array('count','somenode'),
					'data' => array(
						array("rest" => array(
							5,
							array(
								"self" => $this->endpoint.'/node/34',
								"data" => array("baz" => "qux"),
							)
						)),
						array("rest" => array(
							2,
							array(
								"self" => $this->endpoint.'/node/21',
								"data" => array("lorem" => "ipsum"),
							)
						)),
					)
				),
			),
		);

		$this->transport->expects($this->once())
			->method('post')
			->with('/transaction', $expectedRequest)
			->will($this->returnValue(array("code" => 201, "data" => $expectedResponse)));

		$result = $this->client->addStatementsToTransaction($transaction, array($queryA, $queryB));
		self::assertInternalType('array', $result);
		self::assertEquals(2, count($result));

		$resultA = $result[0];
		self::assertInstanceOf('\Everyman\Neo4j\Query\ResultSet', $resultA);
		self::assertEquals(3, count($resultA));
		self::assertEquals('Bob', $resultA[0]['name']);
		self::assertEquals(12, $resultA[0]['age']);

		$resultB = $result[1];
		self::assertInstanceOf('\Everyman\Neo4j\Query\ResultSet', $resultB);
		self::assertEquals(2, count($resultB));
		self::assertEquals(2, $resultB[1]['count']);
		self::assertInstanceOf('\Everyman\Neo4j\Node', $resultB[1]['somenode']);
		self::assertEquals(21, $resultB[1]['somenode']->getId());
		self::assertEquals('ipsum', $resultB[1]['somenode']->getProperty('lorem'));

		self::assertEquals(987, $transaction->getId());
		self::assertFalse($transaction->isClosed());
		self::assertFalse($transaction->isError());
	}

	public function testAddStatements_ExistingTransactionId_ReturnsResultSet()
	{
		$queryTemplateA = "This is the query template";
		$queryParamsA = array('foo' => 'bar', 'baz' => 123);
		$queryA = new Cypher\Query($this->client, $queryTemplateA, $queryParamsA);

		$transaction = new Transaction($this->client);
		$transaction->setId(321);

		$expectedResponse = array(
			"commit" => $this->endpoint . '/transaction/321/commit',
			"transaction" => array("expires" => "Wed, 16 Oct 2013 23:07:12 +0000"),
			"errors" => array(),
			"results" => array(),
		);

		$this->transport->expects($this->once())
			->method('post')
			->with('/transaction/'.$transaction->getId())
			->will($this->returnValue(array("code" => 201, "data" => $expectedResponse)));

		$this->client->addStatementsToTransaction($transaction, array($queryA));
		self::assertFalse($transaction->isClosed());
		self::assertFalse($transaction->isError());
	}

	public function testAddStatements_TransactionFailed_ThrowsException()
	{
		$this->markTestIncomplete();
	}

	public function testAddStatements_NewTransactionWithCommit_ReturnsResulSetAndMarksTransactionClosed()
	{
		$this->markTestIncomplete();
	}

	public function testAddStatements_ExistingTransactionWithCommit_ReturnsResulSetAndMarksTransactionClosed()
	{
		$this->markTestIncomplete();
	}

	public function testAddStatements_KeepAlive_HasTransactionId_SendsToTransportWithoutStatements()
	{
		$this->markTestIncomplete();
	}

	public function testAddStatements_KeepAlive_NoTransactionId_ThrowsException()
	{
		$this->markTestIncomplete();
	}

	public function testAddStatements_NoTransactionCapability_ThrowsException()
	{
		$this->markTestIncomplete();
	}

	public function testRollback_HasTransactionId_SendsDelete()
	{
		$this->markTestIncomplete();
	}

	public function testRollback_NoTransactionId_ThrowsException()
	{
		$this->markTestIncomplete();
	}

	public function testRollback_NoTransactionCapability_ThrowsException()
	{
		$this->markTestIncomplete();
	}
}
