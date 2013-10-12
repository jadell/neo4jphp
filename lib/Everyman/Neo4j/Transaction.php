<?php
namespace Everyman\Neo4j;

use Everyman\Neo4j\Cypher\Query,
    Everyman\Neo4j\Query\ResultSet;

/**
 * A transaction context for multiple Cypher statements across multiple requests
 */
class Transaction
{
	protected $client;
	protected $id;
	protected $isClosed = false;

	/**
	 * Build the transaction and set its client
	 *
	 * @param Client $client
	 */
	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	/**
	 * Add statements to this transaction
	 *
	 * @param array   $statements a list of Cypher Query objects to add to the transaction
	 * @param boolean $commit should this transaction be committed with these statements?
	 * @return ResultSet
	 */
	public function addStatements($statements, $commit=false)
	{
		$result = $this->performClientAction(function ($client, $transaction) use ($statements, $commit) {
			return $client->addStatementsToTransaction($transaction, $statements, $commit);
		}, $commit);
		return $result;
	}

	/**
	 * Commit this transaction immediately, without adding any new statements
	 *
	 * @return Transaction
	 */
	public function commit()
	{
		$this->performClientAction(function ($client, $transaction) {
			$client->addStatementsToTransaction($transaction, array(), true);
		}, true);
		return $this;
	}

	/**
	 * Return the transaction id
	 *
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Has this transaction been closed?
	 *
	 * @return boolean
	 */
	public function isClosed()
	{
		return $this->isClosed;
	}

	/**
	 * Ask for more time to keep this transaction open
	 *
	 * @return Transaction
	 */
	public function keepAlive()
	{
		$this->performClientAction(function ($client, $transaction) {
			$client->addStatementsToTransaction($transaction);
		}, false);
		return $this;
	}

	/**
	 * Rollback the transaction
	 *
	 * @return Transaction
	 */
	public function rollback()
	{
		$this->performClientAction(function ($client, $transaction) {
			$client->rollbackTransaction($transaction);
		}, true);
		return $this;
	}

	/**
	 * Set the transaction id
	 *
	 * Once an id has been set, the same id can be set again.
	 * Attempting to set a different id will throw an InvalidArgumentException.
	 *
	 * @param integer $id
	 * @return Transaction
	 * @throws InvalidArgumentException if an id is given that is different from the existing id
	 */
	public function setId($id)
	{
		if ($this->id && $this->id != $id) {
			throw new \InvalidArgumentException("Cannot set a new id on a transaction once an id has been set");
		}

		$this->id = $id;
		return $this;
	}

	/**
	 * Perform an action against the client
	 *
	 * @param callable $action
	 * @param boolean  $shouldClose
	 */
	protected function performClientAction($action, $shouldClose)
	{
		if ($this->isClosed()) {
			throw new Exception('Transaction is already closed');
		}

		$result = null;
		if ($this->getId()) {
			$result = $action($this->client, $this);
		}

		$this->isClosed = $shouldClose;

		return $result;
	}
}
