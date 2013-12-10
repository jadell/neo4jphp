<?php
namespace Everyman\Neo4j\Batch;

use Everyman\Neo4j\Batch,
	Everyman\Neo4j\PropertyContainer;

/**
 * A single operation in a batch
 */
abstract class Operation
{
	protected $batch = null;
	protected $operation = null;
	protected $entity = null;
	protected $opId = null;

	protected $reserved = false;

	/**
	 * Build the operation
	 *
	 * @param Batch $batch
	 * @param string $operation
	 * @param PropertyContainer $entity
	 * @param integer $opId
	 */
	public function __construct(Batch $batch, $operation, PropertyContainer $entity, $opId)
	{
		$this->batch = $batch;
		$this->operation = $operation;
		$this->entity = $entity;
		$this->opId = $opId;
	}

	/**
	 * Get the underlying batch command for this operation
	 *
	 * @return Batch\Command
	 */
	abstract public function getCommand();

	/**
	 * Return the associated entity
	 *
	 * @return PropertyContainer
	 */
	public function getEntity()
	{
		return $this->entity;
	}

	/**
	 * Get the operation id
	 *
	 * @return integer
	 */
	public function getId()
	{
		return $this->opId;
	}

	/**
	 * Based on this operations parameters, generate a consistent id
	 *
	 * @return mixed
	 */
	public function matchId()
	{
		return $this->operation . spl_object_hash($this->entity);
	}

	/**
	 * Reserve this operation to prevent it from being double-committed
	 * Once an operation has been reserved, future reserve calls will
	 * return false, indicating it has already been reserved.
	 * This is mostly useful during commit to prevent an operation being
	 * sent twice
	 *
	 * @return boolean true if reservation succeeded
	 */
	public function reserve()
	{
		if (!$this->reserved) {
			$this->reserved = true;
			return true;
		}
		return false;
	}
}
