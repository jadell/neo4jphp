<?php
namespace Everyman\Neo4j;

/**
 * Holds the parameters for finding a path between two nodes
 */
class PathFinder
{
	protected $client = null;

	protected $start = null;
	protected $end = null;
	protected $type = null;
	protected $maxLength = null;
	protected $dir = Relationship::DirectionAll;

	/**
	 * Build the finder and set its client
	 *
	 * @param Client $client
	 */
	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	/**
	 * Get the finder's client
	 *
	 * @return Client
	 */
	public function getClient()
	{
		return $this->client;
	}
	/**
	 * Get the path direction type
	 *
	 * @return string
	 */
	public function getDirection()
	{
		return $this->dir;
	}

	/**
	 * Get the end node
	 *
	 * @return Node
	 */
	public function getEndNode()
	{
		return $this->end;
	}

	/**
	 * Get the maximum allowed path length
	 *
	 * @return integer
	 */
	public function getMaxLength()
	{
		return $this->maxLength;
	}

	/**
	 * Find paths
	 *
	 * @return array of Path
	 */
	public function getPaths()
	{
		return $this->client->getPaths($this);
	}

	/**
	 * Get the start node
	 *
	 * @return Node
	 */
	public function getStartNode()
	{
		return $this->start;
	}

	/**
	 * Get the relationship type
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Set the direction of the path
	 *
	 * @param string $dir
	 * @return PathFinder
	 */
	public function setDirection($dir)
	{
		$this->dir = $dir;
		return $this;
	}

	/**
	 * Set the end node
	 *
	 * @param Node $end
	 * @return PathFinder
	 */
	public function setEndNode(Node $end)
	{
		$this->end = $end;
		return $this;
	}

	/**
	 * Set the maximum allowed path length
	 *
	 * @param integer $max
	 * @return PathFinder
	 */
	public function setMaxLength($max)
	{
		$this->maxLength = $max;
		return $this;
	}

	/**
	 * Set the start node
	 *
	 * @param Node $start
	 * @return PathFinder
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
	 * @return PathFinder
	 */
	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}
}
