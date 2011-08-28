<?php
namespace Everyman\Neo4j\Command\Batch;
use Everyman\Neo4j\Client,
	Everyman\Neo4j\Index,
	Everyman\Neo4j\Batch,
	Everyman\Neo4j\PropertyContainer,
	Everyman\Neo4j\Command\RemoveFromIndex as SingleRemoveFromIndex;

/**
 * Remove the given entity from the index
 */
class RemoveFromIndex extends Command
{
	protected $opId = null;
	protected $base = null;

	/**
	 * Set the operation to drive the command
	 *
	 * @param Client $client
	 * @param Index $index
	 * @param PropertyContainer $entity
	 * @param string $key
	 * @param string $value
	 * @param integer $opId
	 */
	public function __construct(Client $client, Index $index, PropertyContainer $entity, $key=null, $value=null, $opId)
	{
		parent::__construct($client);
		$this->base = new SingleRemoveFromIndex($client, $index, $entity, $key, $value);
		$this->opId = $opId;
	}

	/**
	 * Return the data to pass
	 *
	 * @return array
	 */
	protected function getData()
	{
		$opData = array(array(
			'method' => strtoupper($this->base->getMethod()),
			'to' => $this->base->getPath(),
			'id' => $this->opId,
		));
		return $opData;
	}

	/**
	 * Use the results
	 *
	 * @param array $result
	 */
	protected function handleSingleResult($result){}
}

