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
	 * Is the given operation identical to this operation?
	 *
	 * @param Operation $op
	 * @return boolean
	 */
	abstract public function match(Operation $op);

	/**
	 * Return the entity
	 *
	 * @return string
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
	 * Return the operation type
	 *
	 * @return string
	 */
	public function getOperation()
	{
		return $this->operation;
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
