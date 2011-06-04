<?php
namespace Everyman\Neo4j;

/**
 * Represents a single node in the database
 *
 * @todo: Relationships
 * @todo: Paths
 */
class Node
{
	protected $id = null;
	protected $client = null;
	protected $properties = array();

	/**
	 * Build the node and set its client
	 *
	 * @param Client $client
	 */
	public function __construct(Client $client)
	{
		$this->client = $client;
	}

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
	 * Get the node's id
	 *
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Return all properties
	 *
	 * @return array
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * Return the named property
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function getProperty($property)
	{
		return (isset($this->properties[$property])) ? $this->properties[$property] : null;
	}

	/**
	 * Remove a property set on the node
	 *
	 * @param string $property
	 * @return Node
	 */
	public function removeProperty($property)
	{
		unset($this->properties[$property]);
		return $this;
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

	/**
	 * Set the node's id
	 *
	 * @param integer $id
	 * @return Node
	 */
	public function setId($id)
	{
		$this->id = (int)$id;
		return $this;
	}

	/**
	 * Set multiple properties on the node
	 *
	 * @param array $properties
	 * @return Node
	 */
	public function setProperties($properties)
	{
		foreach ($properties as $property => $value) {
			$this->setProperty($property, $value);
		}
		return $this;
	}

	/**
	 * Set a property on the node
	 *
	 * @param string $property
	 * @param mixed $value
	 * @return Node
	 */
	public function setProperty($property, $value)
	{
		$this->properties[$property] = $value;
		return $this;
	}
}
