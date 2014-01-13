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

	/**
	 * @var Node Our start node
	 */
	protected $start = null;
	/**
	 * @var Node Our end node
	 */
	protected $end = null;
	/**
	 * @var string Our type
	 */
	protected $type = null;


	/**
	 * @inheritdoc
	 * @param Client $client
	 * @return Relationship
	 */
	public function setClient(Client $client)
	{
		parent::setClient($client);
		// set the client of our start and end nodes if they exists and doesn't have client yet
		if ($this->start && !$this->start->getClient()) {
			$this->start->setClient($client);
		}
		if ($this->end && !$this->end->getClient()) {
			$this->end->setClient($client);
		}
		return $this;
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
		if (null === $this->end) {
			$this->loadProperties();
		}
		return $this->end;
	}

	/**
	 * Get the start node
	 *
	 * @return Node
	 */
	public function getStartNode()
	{
		if (null === $this->start) {
			$this->loadProperties();
		}
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
	 * Be sure to add our properties to the things to serialize
	 *
	 * @return array
	 */
	public function __sleep()
	{
		return array_merge(parent::__sleep(), array('start', 'end', 'type'));
	}
}
