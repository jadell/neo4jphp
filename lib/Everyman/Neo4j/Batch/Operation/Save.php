<?php
namespace Everyman\Neo4j\Batch\Operation;

use Everyman\Neo4j\Batch,
	Everyman\Neo4j\Batch\Operation,
	Everyman\Neo4j\Batch\Command,
	Everyman\Neo4j\Node,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\PropertyContainer;

/**
 * A save operation
 */
class Save extends Batch\Operation
{
	/**
	 * Build the operation
	 *
	 * @param Batch $batch
	 * @param PropertyContainer $entity
	 * @param integer $opId
	 */
	public function __construct(Batch $batch, PropertyContainer $entity, $opId)
	{
		parent::__construct($batch, 'save', $entity, $opId);
	}

	/**
	 * Handle the results of performing the operation
	 *
	 * @param array $result
	 */
	public function handleResult($result)
	{
		$entity = $this->entity;
		$command = null;
		if (!$entity->hasId()) {
			if ($entity instanceof Node) {
				$command = new Command\CreateNode($this->batch->getClient(), $entity);
			} else if ($entity instanceof Relationship) {
				$command = new Command\CreateRelationship($this->batch->getClient(), $entity);
			}
		}

		if ($command) {
			$command->handleResult(200, array('Location'=>$result['location']), array());
		}
	}

	/**
	 * Is the given operation identical to this operation?
	 *
	 * @param Operation $op
	 * @return boolean
	 */
	public function match(Operation $op)
	{
		$otherOperation = $op->getOperation();
		$otherEntity = $op->getEntity();
		return ($this->operation == $otherOperation && $this->entity === $otherEntity);
	}
}
