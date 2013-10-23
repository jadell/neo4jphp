<?php
namespace Everyman\Neo4j;

/**
 * A transaction context for multiple Cypher statements across multiple requests
 */
class Transaction
{
	protected $client;
	protected $id;
	protected $isClosed = false;
	protected $isError = false;

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
	 * @param mixed   $statements a single or list of Cypher\Query objects to add to the transaction
	 * @param boolean $commit should this transaction be committed with these statements?
	 * @return Query\ResultSet
	 */
	public function addStatements($statements, $commit=false)
	{
		$unwrap = false;
		if (!is_array($statements)) {
			$statements = array($statements);
			$unwrap = true;
		}

		$result = $this->performClientAction(function ($client, $transaction) use ($statements, $commit) {
			return $client->addStatementsToTransaction($transaction, $statements, $commit);
		}, $commit, false);

		if ($unwrap) {
			$result = reset($result);
		}

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
	 * Has this transaction experienced an error?
	 *
	 * @return boolean
	 */
	public function isError()
	{
		return $this->isError;
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
	 * @param boolean  $requireId
	 */
	protected function performClientAction($action, $shouldClose, $requireId=true)
	{
		if ($this->isClosed()) {
			throw new Exception('Transaction is already closed');
		}

		$result = null;
		if (!$requireId || $this->getId()) {
			try {
				$result = $action($this->client, $this);
			} catch (\Exception $e) {
				$this->isClosed = true;
				$this->isError = true;
				throw $e;
			}
		}

		$this->isClosed = $shouldClose;

		return $result;
	}
}
