<?php
namespace Everyman\Neo4j;

/**
 * Represents a single node in the database
 */
class Node extends PropertyContainer
{
	/**
	 * Delete this node
	 *
	 * @return boolean
	 */
	public function delete()
	{
		return $this->client->deleteNode($this);
	}

	/**
	 * Find paths from this node to the given node
	 *
	 * @param Node $to
	 * @param string $type
	 * @param string $dir
	 * @return PathFinder
	 */
	public function findPathsTo(Node $to, $type=null, $dir=null)
	{
		$finder = new PathFinder($this->client);
		$finder->setStartNode($this);
		$finder->setEndNode($to);
		if ($dir) {
			$finder->setDirection($dir);
		}

		if ($type) {
			$finder->setType($type);
		}

		return $finder;
	}

	/**
	 * Get relationships of this node
	 *
	 * @param mixed  $types string or array of strings
	 * @param string $dir
	 * @return array of Relationship
	 */
	public function getRelationships($types=array(), $dir=null)
	{
		return $this->client->getNodeRelationships($this, $types, $dir);
	}

	/**
	 * Load this node
	 *
	 * @return boolean
	 */
	public function load()
	{
		return $this->client->loadNode($this);
	}

	/**
	 * Make a new relationship
	 *
	 * @param Node $to
	 * @param string $type
	 * @return Relationship
	 */
	public function relateTo(Node $to, $type)
	{
		$rel = new Relationship($this->client);
		$rel->setStartNode($this);
		$rel->setEndNode($to);
		$rel->setType($type);

		return $rel;
	}

	/**
	 * Save this node
	 *
	 * @return boolean
	 */
	public function save()
	{
		return $this->client->saveNode($this);
	}
}
