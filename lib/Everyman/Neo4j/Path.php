<?php
namespace Everyman\Neo4j;

/**
 * Represents collection of relationships joining two nodes
 */
class Path implements \Countable, \IteratorAggregate
{
	const ContextNode = 'node';
	const ContextRelationship = 'relationship';

	protected $relationships = array();
	protected $nodes = array();
	protected $context = self::ContextNode;

	/**
	 * Add another node to the end of this path
	 *
	 * @param Node $node
	 * @return Path
	 */
	public function appendNode(Node $node)
	{
		$this->nodes[] = $node;
	}

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
		return $this->context == self::ContextNode ? count($this->nodes) : count($this->relationships);
	}

	/**
	 * Get the current context for iteration
	 *
	 * @return string
	 */
	public function getContext()
	{
		return $this->context;
	}

	/**
	 * Get the end node
	 *
	 * @return Node
	 */
	public function getEndNode()
	{
		$length = count($this->nodes);
		if ($length) {
			return $this->nodes[$length-1];
		}
		return null;
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
		return $this->context == self::ContextNode
			? new \ArrayIterator($this->nodes)
				: new \ArrayIterator($this->relationships);
	}

	/**
	 * Get the list of nodes that make up this path
	 *
	 * @return array
	 */
	public function getNodes()
	{
		return $this->nodes;
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
		$length = count($this->nodes);
		if ($length) {
			return $this->nodes[0];
		}
		return null;
	}

	/**
	 * Set the context for iteration
	 *
	 * @param string $context
	 * @return Path
	 */
	public function setContext($context)
	{
		if ($context != self::ContextNode && $context != self::ContextRelationship) {
			$context = self::ContextNode;
		}
		$this->context = $context;
		return $this;
	}
}
