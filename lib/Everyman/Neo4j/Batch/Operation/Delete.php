<?php
namespace Everyman\Neo4j\Batch\Operation;

use Everyman\Neo4j\Batch,
	Everyman\Neo4j\Batch\Operation,
	Everyman\Neo4j\PropertyContainer;

/**
 * A delete operation
 */
class Delete extends Batch\Operation
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
		parent::__construct($batch, 'delete', $entity, $opId);
	}

	/**
	 * Handle the results of performing the operation
	 * There are no results to clean up a delete operation
	 *
	 * @param array $result
	 */
	public function handleResult($result){}

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
