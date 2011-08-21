<?php
namespace Everyman\Neo4j\Batch\Command;
use Everyman\Neo4j\Command,
	Everyman\Neo4j\Batch,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Relationship;

/**
 * Create a relationship
 * Exposes methods for batches to use the command
 */
class CreateRelationship extends Command\CreateRelationship
{
	protected $batch = null;

	/**
	 * Set the relationship to drive the command
	 *
	 * @param Client $client
	 * @param Relationship $rel
	 * @param Batch $batch
	 */
	public function __construct(Client $client, Relationship $rel, Batch $batch)
	{
		parent::__construct($client, $rel);
		$this->batch = $batch;
	}

	/**
	 * Return the data to pass
	 *
	 * @param integer $opId
	 * @return array
	 */
	public function getData($opId=null)
	{
		$opData = array();

		// Prevent the command from throwing an Exception if an unsaved start node
		$startNode = $this->rel->getStartNode();
		if (!$startNode->hasId()) {
			$startId = $this->batch->save($startNode);
			$reserved = $this->batch->reserve($startId);
			if ($reserved) {
				$opData = array_merge($opData, $reserved->getData());
			}
			$start = "{{$startId}}/relationships";
		} else {
			$start = parent::getPath();
		}

		// Prevent the command from throwing an Exception if an unsaved end node
		$endNode = $this->rel->getEndNode();
		if (!$endNode->hasId()) {
			$endId = $this->batch->save($endNode);
			$reserved = $this->batch->reserve($endId);
			if ($reserved) {
				$opData = array_merge($opData, $reserved->getData());
			}
			$endNode->setId('temp');
			$data = parent::getData();
			$endNode->setId(null);
			$data['to'] = "{{$endId}}";
		} else {
			$data = parent::getData();
		}

		$opData[] = array(
			'method' => strtoupper(parent::getMethod()),
			'to' => $start,
			'body' => $data,
			'id' => $opId,
		);
		return $opData;
	}

	/**
	 * Use the results
	 *
	 * @param integer $code
	 * @param array   $headers
	 * @param array   $data
	 * @return integer on failure
	 */
	public function handleResult($code, $headers, $data)
	{
		$headers['Location'] = $data['location'];
		return parent::handleResult($code, $headers, $data);
	}
}

