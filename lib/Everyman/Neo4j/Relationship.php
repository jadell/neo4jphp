<?php
namespace Everyman\Neo4j;

/**
 * Represents a relationship between two nodes
 */
class Relationship extends PropertyContainer
{
	const DirectionAll       = 'all';
	const DirectionIn        = 'in';
	const DirectionOut       = 'out';

	protected $start = null;
	protected $end = null;
	protected $type = null;

	/**
	 * Defines the properties that will be serialized
	 */
	public function __sleep()
	{
		return array('id', 'properties', 'lazyLoad', 'loaded', 'start', 'end', 'type');
	}

	/**
	 * Delete this relationship
	 *
	 * @return PropertyContainer
	 * @throws Exception on failure
	 */
	public function delete()
	{
		$this->client->deleteRelationship($this);
		return $this;
	}

	/**
	 * Get the end node
	 *
	 * @return Node
	 */
	public function getEndNode()
	{
		$this->loadProperties();
		return $this->end;
	}

	/**
	 * Get the start node
	 *
	 * @return Node
	 */
	public function getStartNode()
	{
		$this->loadProperties();
		return $this->start;
	}

	/**
	 * Get the relationship type
	 *
	 * @return string
	 */
	public function getType()
	{
		$this->loadProperties();
		return $this->type;
	}

	/**
	 * Load this relationship
	 *
	 * @return PropertyContainer
	 * @throws Exception on failure
	 */
	public function load()
	{
		$this->client->loadRelationship($this);
		return $this;
	}

	/**
	 * Save this node
	 *
	 * @return PropertyContainer
	 * @throws Exception on failure
	 */
	public function save()
	{
		$this->client->saveRelationship($this);
		$this->useLazyLoad(false);
		return $this;
	}

	/**
	 * Set the end node
	 *
	 * @param Node $end
	 * @return Relationship
	 */
	public function setEndNode(Node $end)
	{
		$this->end = $end;
		return $this;
	}

	/**
	 * Set the start node
	 *
	 * @param Node $start
	 * @return Relationship
	 */
	public function setStartNode(Node $start)
	{
		$this->start = $start;
		return $this;
	}

	/**
	 * Set the type
	 *
	 * @param string $type
	 * @return Relationship
	 */
	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * Set the client of the relationship and the clients of
	 * the start and end nodes
	 * @param Client $client
	 * @return Relationship
	 */
	public function setClient(Client $client)
	{
		$this->client = $client;

		if ($this->getStartNode() !== null) {
			$this->getStartNode()->setClient($client);
		}

		if ($this->getEndNode() !== null) {
			$this->getEndNode()->setClient($client);
		}
		return $this;
	}
}
