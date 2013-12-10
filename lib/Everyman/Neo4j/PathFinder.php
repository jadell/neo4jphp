<?php
namespace Everyman\Neo4j;

/**
 * Holds the parameters for finding a path between two nodes
 */
class PathFinder
{
	const AlgoShortest  = 'shortestPath';
	const AlgoAll       = 'allPaths';
	const AlgoAllSimple = 'allSimplePaths';
	const AlgoDijkstra  = 'dijkstra';

	protected $client = null;

	protected $start = null;
	protected $end = null;
	protected $type = null;
	protected $maxDepth = null;
	protected $dir = null;
	protected $algo = self::AlgoShortest;
	protected $costProperty = null;
	protected $defaultCost = null;

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
	 * Get the current path finding algorithm
	 *
	 * @return string
	 */
	public function getAlgorithm()
	{
		return $this->algo;
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
	 * Get the cost property name for the Dijkstra search
	 *
	 * @return string
	 */
	public function getCostProperty()
	{
		return $this->costProperty;
	}

	/**
	 * Get the default relationship cost for the Dijkstra search
	 *
	 * @return numeric
	 */
	public function getDefaultCost()
	{
		return $this->defaultCost;
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
	public function getMaxDepth()
	{
		return $this->maxDepth;
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
	 * Get a single path
	 *
	 * @return Path
	 */
	public function getSinglePath()
	{
		$paths = $this->getPaths();
		return $paths ? $paths[0] : null;
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
	 * Set the algorithm to use
	 *
	 * @param string $algo
	 * @return PathFinder
	 */
	public function setAlgorithm($algo)
	{
		$this->algo = $algo;
		return $this;
	}

	/**
	 * Set the cost property name for the Dijkstra search
	 *
	 * @param string $property
	 * @return PathFinder
	 */
	public function setCostProperty($property)
	{
		$this->costProperty = $property;
		return $this;
	}

	/**
	 * Set the default relationship cost for the Dijkstra search
	 *
	 * @param numeric $cost
	 * @return PathFinder
	 */
	public function setDefaultCost($cost)
	{
		$this->defaultCost = $cost;
		return $this;
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
	public function setMaxDepth($max)
	{
		$this->maxDepth = $max;
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
