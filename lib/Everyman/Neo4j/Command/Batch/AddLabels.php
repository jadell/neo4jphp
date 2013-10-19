<?php
namespace Everyman\Neo4j\Command\Batch;
use Everyman\Neo4j\Client,
	Everyman\Neo4j\Batch,
	Everyman\Neo4j\Node,
	Everyman\Neo4j\Label,
	Everyman\Neo4j\Command\AddLabels as SingleAddLabels;

/**
 * Add a set of labels to a node in a batch
 */
class AddLabels extends Command
{
	protected $batch = null;
	protected $node;
	protected $labels;

	/**
	 * Set the operation to drive the command
	 *
	 * @param Client $client
	 * @param Relationship $rel
	 * @param integer $opId
	 * @param Batch $batch
	 */
	public function __construct(Client $client, Node $node, Array $labels, $opId, Batch $batch)
	{
		parent::__construct($client, new SingleAddLabels($client, $node, $labels), $opId);
		$this->batch = $batch;
		$this->node = $node;
		$this->labels = $labels;
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
		if (!$this->node->hasId()) {
			$nodeId = $this->batch->save($this->node);
			$reserved = $this->batch->reserve($nodeId);
			if ($reserved) {
				$opData = array_merge($opData, $reserved->getCommand()->getData());
			}
			$path = '/node/'.$nodeId.'/labels';
		} else {
			$path = $this->base->getPath();
		}

		$opData[] = array(
			'method' => strtoupper($this->base->getMethod()),
			'to' => $path,
			'body' => $this->base->getData(),
			'id' => $this->opId,
		);
		return $opData;
	}
}

