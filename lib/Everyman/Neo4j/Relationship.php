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
	 * Delete this relationship
	 *
	 * @return boolean
	 */
	public function delete()
	{
		return $this->client->deleteRelationship($this);
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
	 * @return boolean
	 */
	public function load()
	{
		return $this->client->loadRelationship($this);
	}

	/**
	 * Save this node
	 *
	 * @return boolean
	 */
	public function save()
	{
		return $this->client->saveRelationship($this);
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
}
