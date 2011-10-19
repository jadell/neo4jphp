<?php
namespace Everyman\Neo4j\Command\Batch;
use Everyman\Neo4j\Command as SingleCommand,
	Everyman\Neo4j\Client;

/**
 * A single command executed in a batch
 */
abstract class Command extends SingleCommand
{
	/**
	 * Set the operation to drive the command
	 *
	 * @param Client $client
	 */
	public function __construct(Client $client)
	{
		parent::__construct($client);
	}

	/**
	 * Handle a single result from the batch of results
	 *
	 * @param array $result
	 */
	abstract protected function handleSingleResult($result);
	
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
		return '/batch';
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
			$this->throwException('Unable to commit batch', $code, $headers, $data);
		}

		foreach ($data as $result) {
			$this->handleSingleResult($result);
		}
		return true;
	}
}

