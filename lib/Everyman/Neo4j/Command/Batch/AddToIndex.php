<?php
namespace Everyman\Neo4j\Command\Batch;

use Everyman\Neo4j\Client,
	Everyman\Neo4j\Index,
	Everyman\Neo4j\Batch,
	Everyman\Neo4j\PropertyContainer,
	Everyman\Neo4j\Command\AddToIndex as SingleAddToIndex;

/**
 * Add the given entity to the index
 * Also creates the entity if necessary
 */
class AddToIndex extends Command
{
	protected $batch = null;
	protected $entity = null;

	/**
	 * Set the operation to drive the command
	 *
	 * @param Client $client
	 * @param Index $index
	 * @param PropertyContainer $entity
	 * @param string $key
	 * @param string $value
	 * @param integer $opId
	 * @param Batch $batch
	 */
	public function __construct(Client $client, Index $index, PropertyContainer $entity, $key, $value, $opId, Batch $batch)
	{
		parent::__construct($client, new SingleAddToIndex($client, $index, $entity, $key, $value), $opId);
		$this->batch = $batch;
		$this->entity = $entity;
	}

	/**
	 * Return the data to pass
	 *
	 * @return array
	 */
	protected function getData()
	{
		$opData = array();

		// Prevent the command from throwing an Exception if an unsaved entity
		if (!$this->entity->hasId()) {
			$entityId = $this->batch->save($this->entity);
			$reserved = $this->batch->reserve($entityId);
			if ($reserved) {
				$opData = array_merge($opData, $reserved->getCommand()->getData());
			}
			$this->entity->setId(-1);
			$body = $this->base->getData();
			$this->entity->setId(null);
			$body['uri'] = "{{$entityId}}";
		} else {
			$body = $this->base->getData();
		}

		$opData[] = array(
			'method' => strtoupper($this->base->getMethod()),
			'to' => $this->base->getPath(),
			'body' => $body,
			'id' => $this->opId,
		);
		return $opData;
	}
}
