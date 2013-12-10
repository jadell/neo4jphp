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
	protected $matches = array();

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
	 * Add the given entity to the given index with the given key/value
	 *
	 * @param Index $index
	 * @param PropertyContainer $entity
	 * @param string $key
	 * @param string $value
	 * @return integer
	 */
	public function addToIndex(Index $index, PropertyContainer $entity, $key, $value)
	{
		return $this->addOperation(new Batch\AddTo($this, $index, $entity, $key, $value, $this->nextId()));
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
		return $this->addOperation(new Batch\Delete($this, $entity, $this->nextId()));
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
	 * Remove the given entity from the given index with the given key/value
	 *
	 * @param Index $index
	 * @param PropertyContainer $entity
	 * @param string $key
	 * @param string $value
	 * @return integer
	 */
	public function removeFromIndex(Index $index, PropertyContainer $entity, $key=null, $value=null)
	{
		return $this->addOperation(new Batch\RemoveFrom($this, $index, $entity, $key, $value, $this->nextId()));
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
			return $this->operations[$opId];
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
		return $this->addOperation(new Batch\Save($this, $entity, $this->nextId()));
	}

	/**
	 * Add an operation to the batch
	 *
	 * @param Batch\Operation $operation
	 * @return integer operation index
	 */
	protected function addOperation(Batch\Operation $operation)
	{
		$opId = $operation->getId();
		$matchId = $operation->matchId();

		if (isset($this->matches[$matchId])) {
			return $this->matches[$matchId]->getId();
		}

		$this->operations[$opId] = $operation;
		$this->matches[$matchId] = $operation;
		return $opId;
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
