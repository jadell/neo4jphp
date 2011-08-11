<?php
namespace Everyman\Neo4j;

/**
 * A set of operations expected to succeed (or fail) atomically
 */
class Batch
{
    protected $client = null;

	protected $operands = array();

	/**
	 * Build the batch and set its client
	 *
	 * @param Client $client
	 */
	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	/**
	 * Get the batch's client
	 *
	 * @return Client
	 */
	public function getClient()
	{
		return $this->client;
	}

	/**
	 * Get the entity referenced by the given position
	 *
	 * @param integer $index
	 * @return PropertyContainer
	 */
	public function getOperand($index)
	{
		return isset($this->operands[$index]) ? $this->operands[$index] : null;
	}

	/**
	 * Add an entity to the batch to save
	 *
	 * @param PropertyContainer $entity
	 * @return integer
	 */
	public function save(PropertyContainer $entity)
	{
		if ($entity instanceof Relationship) {
			$this->checkAndSaveRelationshipEndpoints($entity);
		}
	
		$opId = count($this->operands);
		$this->operands[] = $entity;
		
		return $opId;
	}
	
	/**
	 * Check that a relationship's start and end don't need saving
	 * Save them if they do
	 *
	 * @param Relationship $rel
	 */
	protected function checkAndSaveRelationshipEndpoints(Relationship $rel)
	{
		$start = $rel->getStartNode();
		if ($start && !$start->hasId()) {
			$this->save($start);
		}
		
		$end = $rel->getEndNode();
		if ($end && !$end->hasId()) {
			$this->save($end);
		}
	}
}
