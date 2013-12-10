<?php
namespace Everyman\Neo4j\Command\Batch;

use Everyman\Neo4j\Client,
	Everyman\Neo4j\Batch,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Command\CreateRelationship as SingleCreateRelationship;

/**
 * Create a relationship in a batch
 * Also creates the endpoint nodes if necessary
 */
class CreateRelationship extends Command
{
	protected $batch = null;
	protected $rel = null;

	/**
	 * Set the operation to drive the command
	 *
	 * @param Client $client
	 * @param Relationship $rel
	 * @param integer $opId
	 * @param Batch $batch
	 */
	public function __construct(Client $client, Relationship $rel, $opId, Batch $batch)
	{
		parent::__construct($client, new SingleCreateRelationship($client, $rel), $opId);
		$this->batch = $batch;
		$this->rel = $rel;
	}

	/**
	 * Return the data to pass
	 *
	 * @return array
	 */
	protected function getData()
	{
		$opData = array();

		// Prevent the command from throwing an Exception if an unsaved start node
		$startNode = $this->rel->getStartNode();
		if (!$startNode->hasId()) {
			$startId = $this->batch->save($startNode);
			$reserved = $this->batch->reserve($startId);
			if ($reserved) {
				$opData = array_merge($opData, $reserved->getCommand()->getData());
			}
			$start = "{{$startId}}/relationships";
		} else {
			$start = $this->base->getPath();
		}

		// Prevent the command from throwing an Exception if an unsaved end node
		$endNode = $this->rel->getEndNode();
		if (!$endNode->hasId()) {
			$endId = $this->batch->save($endNode);
			$reserved = $this->batch->reserve($endId);
			if ($reserved) {
				$opData = array_merge($opData, $reserved->getCommand()->getData());
			}
			$endNode->setId('temp');
			$data = $this->base->getData();
			$endNode->setId(null);
			$data['to'] = "{{$endId}}";
		} else {
			$data = $this->base->getData();
		}

		$opData[] = array(
			'method' => strtoupper($this->base->getMethod()),
			'to' => $start,
			'body' => $data,
			'id' => $this->opId,
		);
		return $opData;
	}
}
