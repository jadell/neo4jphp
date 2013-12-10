<?php
namespace Everyman\Neo4j\Batch;

use Everyman\Neo4j\Batch,
	Everyman\Neo4j\Command\Batch as Command,
	Everyman\Neo4j\Index,
	Everyman\Neo4j\PropertyContainer;

/**
 * An add-to-index operation
 */
class AddTo extends Operation
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
		parent::__construct($batch, 'addto', $entity, $opId);
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
			$this->command = new Command\AddToIndex(
				$this->batch->getClient(),
				$this->index,
				$this->entity,
				$this->key,
				$this->value,
				$this->opId,
				$this->batch
			);
		}
		return $this->command;
	}

	/**
	 * Get the index
	 *
	 * @return Index
	 */
	public function getIndex()
	{
		return $this->index;
	}

	/**
	 * Get the key being indexed
	 *
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * Get the value being indexed
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
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
