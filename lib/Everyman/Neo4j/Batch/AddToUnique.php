<?php
namespace Everyman\Neo4j\Batch;

use Everyman\Neo4j\Batch;
use Everyman\Neo4j\PropertyContainer;
use Everyman\Neo4j\Index;
use Everyman\Neo4j\Command\Batch as Command;

/**
 * Add to index unique operation
 */
class AddToUnique extends Operation
{
	protected $command = null;
	protected $index = null;
	protected $key = null;
	protected $value = null;
	protected $type = null;

	public function __construct(Batch $batch, Index $index, PropertyContainer $entity, $key, $value, $type, $opId)
	{
		parent::__construct($batch, 'addToUnique', $entity, $opId);
		$this->index = $index;
		$this->key = $key;
		$this->value = $value;
		$this->type = $type;
	}

	public function getCommand()
	{
		if (!$this->command) {
			$this->command = new Command\AddToIndexUnique($this->batch->getClient(),
				$this->index, $this->entity, $this->key, $this->value, $this->type, $this->opId, $this->batch);
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