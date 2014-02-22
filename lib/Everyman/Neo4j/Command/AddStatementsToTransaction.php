<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Exception,
	Everyman\Neo4j\Transaction,
	Everyman\Neo4j\Cypher\Query,
	Everyman\Neo4j\Query\ResultSet;

/**
 * Open, add statements to, and/or commit a Cypher transaction
 */
class AddStatementsToTransaction extends Command
{
	protected $transaction = null;
	protected $statements = array();
	protected $commit = false;

	/**
	 * Set the transaction and statements to use
	 *
	 * @param Client $client
	 * @param Transaction $transaction
	 * @param array $statements
	 * @param boolean $commit
	 */
	public function __construct(Client $client, Transaction $transaction, $statements=array(), $commit=false)
	{
		parent::__construct($client);
		$this->transaction = $transaction;
		$this->statements = $statements;
		$this->commit = $commit;
	}

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		if (!$this->statements && !$this->transaction->getId()) {
			throw new Exception("Cannot keep-alive a transaction without an id");
		}

		$statements = array_map(array($this, 'formatStatement'), $this->statements);
		return array('statements' => $statements);
	}

	/**
	 * Format the given query into a transactional statement
	 *
	 * @param Query $statement
	 * @return array
	 */
	protected function formatStatement(Query $statement)
	{
		return array(
			'statement'          => $statement->getQuery(),
			'parameters'         => (object)$statement->getParameters(),
			'resultDataContents' => array('rest'),
		);
	}

	/**
	 * Return the transport method to call
	 *
	 * @return string
	 */
	protected function getMethod()
	{
		return 'post';
	}

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	protected function getPath()
	{
		if (!$this->client->hasCapability(Client::CapabilityTransactions)) {
			throw new Exception('Transactions unavailable');
		}

		$path = '/transaction';

		$id = $this->transaction->getId();
		if ($id) {
			$path .= '/'.$id;
		}

		if ($this->commit) {
			$path .= '/commit';
		}

		return $path;
	}

	/**
	 * Use the results
	 *
	 * @param integer $code
	 * @param array   $headers
	 * @param array   $data
	 * @return integer on failure
	 */
	protected function handleResult($code, $headers, $data)
	{
		if ((int)($code / 100) != 2 || !empty($data['errors'])) {
			$this->throwException('Error in transaction', $code, $headers, $data);
		}

		if (!$this->transaction->getId() && !$this->commit) {
			$this->setTransactionId($data);
		}

		$results = array_map(array($this, 'mapResult'), $data['results']);
		return $results;
	}

	/**
	 * Map the response into a ResultSet
	 *
	 * @param array $result
	 * @return ResultSet
	 */
	protected function mapResult($result)
	{
		$data = array_map(function ($row) {
			return $row['rest'];
		}, $result['data']);

		return new ResultSet($this->client, array(
			'columns' => $result['columns'],
			'data' => $data,
		));
	}

	/**
	 * Parse the transaction id out of the response and set it on the transaction
	 *
	 * @param array $data
	 */
	protected function setTransactionId($data)
	{
		$commit = $data['commit'];
		$path = parse_url($commit, PHP_URL_PATH);
		$parts = explode('/', $path);
		$id = $parts[count($parts)-2];
		$this->transaction->setId($id);
	}
}
