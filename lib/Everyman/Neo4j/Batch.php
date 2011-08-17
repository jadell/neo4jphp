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
		return $this->addOperation('delete', $entity);
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
		return $this->operations;
	}

	/**
	 * Add an entity to the batch to save
	 *
	 * @param PropertyContainer $entity
	 * @return integer
	 */
	public function save(PropertyContainer $entity)
	{
		return $this->addOperation('save', $entity);
	}
	
	/**
	 * Add an operation to the batch
	 *
	 * @param string $operation
	 * @param PropertyContainer $entity
	 * @return integer operation index
	 */
	protected function addOperation($operation, PropertyContainer $entity)
	{
		$opId = $this->checkOperation($operation, $entity);
		if ($opId === null) {
			$opId = count($this->operations);
			$this->operations[] = array(
				'operation' => $operation,
				'entity' => $entity,
			);
		}
	
		return $opId;
	}
	
	/**
	 * Check to see if the given operation is already being performed on the given entity
	 *
	 * @param string $operation
	 * @param PropertyContainer $entity
	 * @return integer operation index if operation is found
	 */
	protected function checkOperation($operation, PropertyContainer $entity)
	{
		foreach ($this->operations as $i => $op) {
			if ($op['operation'] == $operation && $op['entity'] === $entity) {
				return $i;
			}
		}
		return null;
	}
}
