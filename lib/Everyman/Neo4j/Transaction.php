<?php
namespace Everyman\Neo4j;

/**
 * A transaction context for multiple Cypher statements across multiple requests
 */
class Transaction
{
	protected $client;
	protected $id;

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
	 * Commit this transaction immediately, without adding any new statements
	 *
	 * @todo: If the transaction is no longer open, don't pass to client
	 * @return Transaction
	 */
	public function commit()
	{
		$this->client->addStatementsToTransaction($this, array(), true);
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
	 * Ask for more time to keep this transaction open
	 *
	 * @todo: If the transaction is no longer open, don't pass to client
	 * @return Transaction
	 */
	public function keepAlive()
	{
		$this->client->addStatementsToTransaction($this);
		return $this;
	}

	/**
	 * Rollback the transaction
	 *
	 * @todo: If the transaction is no longer open, don't pass to client
	 * @return Transaction
	 */
	public function rollback()
	{
		$this->client->rollbackTransaction($this);
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
}
