<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Exception,
	Everyman\Neo4j\Transaction;

/**
 * Rollback an open Cypher transaction
 */
class RollbackTransaction extends Command
{
	protected $transaction = null;

	/**
	 * Set the transaction to rollback
	 *
	 * @param Client $client
	 * @param Transaction $transaction
	 */
	public function __construct(Client $client, Transaction $transaction)
	{
		parent::__construct($client);
		$this->transaction = $transaction;
	}

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		return null;
	}

	/**
	 * Return the transport method to call
	 *
	 * @return string
	 */
	protected function getMethod()
	{
		return 'delete';
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

		$id = $this->transaction->getId();
		if (!$id) {
			throw new Exception('Cannot rollback a transaction without a transaction id');
		}

		$path = '/transaction/'.$id;

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
		if ((int)($code / 100) != 2) {
			$this->throwException('Error during transaction rollback', $code, $headers, $data);
		}

		return true;
	}
}
