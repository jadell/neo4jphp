<?php
namespace Everyman\Neo4j\Command\Batch;

use Everyman\Neo4j\Client,
	Everyman\Neo4j\Batch;

/**
 * Commit a batch operation
 * @todo: Handle the case of empty body or body\data needing to be objects not arrays
 * @todo: Is this really a batch command in itself, or something different?
 */
class Commit extends Command
{
	protected $batch = null;

	/**
	 * Set the batch to drive the command
	 *
	 * @param Client $client
	 * @param Batch $batch
	 */
	public function __construct(Client $client, Batch $batch)
	{
		parent::__construct($client, $this, null);
		$this->batch = $batch;
	}

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		$operations = $this->batch->getOperations();
		$data = array();
		foreach ($operations as $op) {
			if ($op->reserve()) {
				$opData = $op->getCommand()->getData();
				foreach ($opData as $datum) {
					$data[] = $datum;
				}
			}
		}
		return $data;
	}

	/**
	 * Use the results
	 *
	 * @param array $result
	 */
	protected function handleSingleResult($result)
	{
		$operations = $this->batch->getOperations();
		$command = $operations[$result['id']]->getCommand();
		return $command->handleSingleResult($result);
	}
}
