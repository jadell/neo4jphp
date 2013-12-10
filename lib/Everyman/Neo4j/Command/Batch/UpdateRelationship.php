<?php
namespace Everyman\Neo4j\Command\Batch;

use Everyman\Neo4j\Client,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Command\UpdateRelationship as SingleUpdateRelationship;

/**
 * Update a relationship in a batch
 */
class UpdateRelationship extends Command
{
	/**
	 * Set the operation to drive the command
	 *
	 * @param Client $client
	 * @param Relationship $rel
	 * @param integer $opId
	 */
	public function __construct(Client $client, Relationship $rel, $opId)
	{
		parent::__construct($client, new SingleUpdateRelationship($client, $rel), $opId);
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
			'body' => $this->base->getData(),
			'id' => $this->opId,
		));
		return $opData;
	}
}
