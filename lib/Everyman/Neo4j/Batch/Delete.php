<?php
namespace Everyman\Neo4j\Batch;

use Everyman\Neo4j\Batch,
	Everyman\Neo4j\Command\Batch as Command,
	Everyman\Neo4j\Node,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\PropertyContainer;

/**
 * A delete operation
 */
class Delete extends Operation
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
	 * Get the command that represents this operation
	 *
	 * @return Batch\Command
	 */
	public function getCommand()
	{
		$entity = $this->entity;
		$command = null;
		if ($entity instanceof Node) {
			$command = new Command\DeleteNode($this->batch->getClient(), $entity, $this->opId);
		} else if ($entity instanceof Relationship) {
			$command = new Command\DeleteRelationship($this->batch->getClient(), $entity, $this->opId);
		}
		return $command;
	}

	/**
	 * Is the given operation identical to this operation?
	 *
	 * @param Operation $op
	 * @return boolean
	 */
	public function match(Operation $op)
	{
		return ($this->operation == $op->operation && $this->entity === $op->entity);
	}
}
