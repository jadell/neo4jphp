<?php
namespace Everyman\Neo4j;

/**
 * Represents collection of relationships joining two nodes
 */
class Path implements \Countable, \IteratorAggregate
{
	protected $relationships = array();

	/**
	 * Add another relationship to the end of this path
	 *
	 * @param Relationship $rel
	 * @return Path
	 */
	public function appendRelationship(Relationship $rel)
	{
		$this->relationships[] = $rel;
	}

	/**
	 * Get the number of relationships in this path
	 *
	 * @return integer
	 */
	public function count()
	{
		return count($this->relationships);
	}

	/**
	 * Get the end node
	 *
	 * @return Node
	 */
	public function getEndNode()
	{
		$length = $this->getLength();
		if ($length) {
			return $this->relationships[$length-1]->getEndNode();
		}
		return $null;
	}

	/**
	 * Get the number of relationships in this path
	 *
	 * @return integer
	 */
	public function getLength()
	{
		return $this->count();
	}

	/**
	 * Return an iterator for iterating through the path
	 *
	 * @return Iterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->relationships);
	}

	/**
	 * Get the list of relationships that make up this path
	 *
	 * @return array
	 */
	public function getRelationships()
	{
		return $this->relationships;
	}

	/**
	 * Get the start node
	 *
	 * @return Node
	 */
	public function getStartNode()
	{
		$length = $this->getLength();
		if ($length) {
			return $this->relationships[0]->getStartNode();
		}
		return $null;
	}
}
