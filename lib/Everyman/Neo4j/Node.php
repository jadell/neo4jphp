<?php
namespace Everyman\Neo4j;

/**
 * Represents a single node in the database
 *
 * @todo: Relationships
 * @todo: Paths
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
	 * Save this node
	 *
	 * @return boolean
	 */
	public function save()
	{
		return $this->client->saveNode($this);
	}
}
