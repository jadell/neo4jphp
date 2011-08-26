<?php
namespace Everyman\Neo4j\Batch;

use Everyman\Neo4j\Batch,
	Everyman\Neo4j\Command\Batch as Command,
	Everyman\Neo4j\Node,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\PropertyContainer;

/**
 * A save operation
 */
class Save extends Operation
{
	protected $command = null;

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
	 * Get the command that represents this operation
	 *
	 * @return Batch\Command
	 */
	public function getCommand()
	{
		if (!$this->command) {
			$entity = $this->entity;
			$command = null;
			if (!$entity->hasId()) {
				if ($entity instanceof Node) {
					$command = new Command\CreateNode($this->batch->getClient(), $entity, $this->opId);
				} else if ($entity instanceof Relationship) {
					$command = new Command\CreateRelationship($this->batch->getClient(), $entity, $this->opId, $this->batch);
				}
			} else {
				if ($entity instanceof Node) {
					$command = new Command\UpdateNode($this->batch->getClient(), $entity, $this->opId);
				} else if ($entity instanceof Relationship) {
					$command = new Command\UpdateRelationship($this->batch->getClient(), $entity, $this->opId);
				}
			}

			$this->command = $command;
		}
		return $this->command;
	}
}
