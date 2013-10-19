<?php
namespace Everyman\Neo4j\Batch;

use Everyman\Neo4j\Batch,
	Everyman\Neo4j\Command\Batch as Command,
	Everyman\Neo4j\Node;

/**
 * An addLebels operation
 */
class AddLabels extends Operation
{
	protected $command = null;
	protected $labels = null;

	/**
	 * Build the operation
	 *
	 * @param Batch $batch
	 * @param PropertyContainer $entity
	 * @param integer $opId
	 */
	public function __construct(Batch $batch, Node $entity, Array $labels, $opId)
	{
		parent::__construct($batch, 'addLebels', $entity, $opId);
		$this->labels = $labels;
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
			$command = new Command\AddLabels($this->batch->getClient(), $entity, $this->labels, $this->opId, $this->batch);

			$this->command = $command;
		}
		return $this->command;
	}
}
