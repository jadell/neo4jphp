<?php
namespace Everyman\Neo4j\Batch;

use Everyman\Neo4j\Batch,
	Everyman\Neo4j\Command\Batch as Command,
	Everyman\Neo4j\Index,
	Everyman\Neo4j\PropertyContainer;

/**
 * A remove-from-index operation
 */
class RemoveFrom extends Operation
{
	protected $command = null;
	protected $index = null;
	protected $key = null;
	protected $value = null;

	/**
	 * Build the operation
	 *
	 * @param Batch $batch
	 * @param Index $index
	 * @param PropertyContainer $entity
	 * @param string $key
	 * @param string $value
	 * @param integer $opId
	 */
	public function __construct(Batch $batch, Index $index, PropertyContainer $entity, $key, $value, $opId)
	{
		parent::__construct($batch, 'removefrom', $entity, $opId);
		$this->index = $index;
		$this->key = $key;
		$this->value = $value;
	}

	/**
	 * Get the command that represents this operation
	 *
	 * @return Batch\Command
	 */
	public function getCommand()
	{
		if (!$this->command) {
			$this->command = new Command\RemoveFromIndex(
				$this->batch->getClient(),
				$this->index,
				$this->entity,
				$this->key,
				$this->value,
				$this->opId
			);
		}
		return $this->command;
	}

	/**
	 * Based on this operations parameters, generate a consistent id
	 *
	 * @return mixed
	 */
	public function matchId()
	{
		return parent::matchId() . spl_object_hash($this->index) . $this->key . $this->value;
	}
}
