<?php
namespace Everyman\Neo4j;

/**
 * A set of operations expected to succeed (or fail) atomically
 */
class Batch
{
    protected $client = null;

	protected $committed = false;
	protected $operations = array();
	protected $reservations = array();

	/**
	 * Build the batch and set its client
	 *
	 * @param Client $client
	 */
	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	/**
	 * Commit the batch to the server
	 *
	 * @return boolean
	 */
	public function commit()
	{
		if ($this->committed) {
			throw new Exception('Cannot commit the same batch more than once.');
		}
		$this->committed = true;
	
		return $this->client->commitBatch($this);
	}

	/**
	 * Add an entity to the batch to delete
	 *
	 * @param PropertyContainer $entity
	 * @return integer
	 */
	public function delete(PropertyContainer $entity)
	{
		return $this->addOperation(new Batch\Operation\Delete($this, $entity, $this->nextId()));
	}

	/**
	 * Get the batch's client
	 *
	 * @return Client
	 */
	public function getClient()
	{
		return $this->client;
	}
	
	/**
	 * Return the list of operations in this batch
	 *
	 * @return array
	 */
	public function getOperations()
	{
		$operations = array();
		foreach ($this->operations as $op) {
			$operations[] = array(
				'operation' => $op->getOperation(),
				'entity' => $op->getEntity(),
			);			
		}
		return $operations;
	}

	/**
	 * Reserve an operation to prevent it from being double-committed
	 * Once an operation has been reserved, future reserve calls will
	 * return false, indicating it has already been reserved.
	 * This is mostly useful during commit to prevent an operation being
	 * sent twice
	 *
	 * @param integer $opId
	 * @return mixed array operation if not yet reserved, false otherwise
	 */
	public function reserve($opId)
	{
		if (isset($this->operations[$opId]) && $this->operations[$opId]->reserve()) {
			return array(
				'operation' => $this->operations[$opId]->getOperation(),
				'entity' => $this->operations[$opId]->getEntity(),
			);
		}
		return false;
	}

	/**
	 * Add an entity to the batch to save
	 *
	 * @param PropertyContainer $entity
	 * @return integer
	 */
	public function save(PropertyContainer $entity)
	{
		return $this->addOperation(new Batch\Operation\Save($this, $entity, $this->nextId()));
	}
	
	/**
	 * Add an operation to the batch
	 *
	 * @param Batch\Operation $operation
	 * @return integer operation index
	 */
	protected function addOperation(Batch\Operation $operation)
	{
		$foundOp = $this->checkOperation($operation);
		$this->operations[$foundOp->getId()] = $foundOp;
		return $foundOp->getId();
	}
	
	/**
	 * Check to see if the given operation is already being performed on the given entity
	 *
	 * @param Batch\Operation $operation
	 * @return Batch\Operation
	 */
	protected function checkOperation(Batch\Operation $operation)
	{
		foreach ($this->operations as $testOp) {
			if ($testOp->match($operation)) {
				return $testOp;
			}
		}
		return $operation;
	}

	/**
	 * Get the next unused id
	 *
	 * @return integer
	 */
	protected function nextId()
	{
		return count($this->operations);
	}
}
