<?php
namespace Everyman\Neo4j\Batch\Operation;

use Everyman\Neo4j\Batch,
	Everyman\Neo4j\Batch\Operation,
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
