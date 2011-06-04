<?php
namespace Everyman\Neo4j;

/**
 * Represents an entity that is a collection of properties
 */
abstract class PropertyContainer
{
	protected $id = null;
	protected $client = null;
	protected $properties = array();

	/**
	 * Build the container and set its client
	 *
	 * @param Client $client
	 */
	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	/**
	 * Get the entity's client
	 *
	 * @return Client
	 */
	public function getClient()
	{
		return $this->client;
	}

	/**
	 * Get the entity's id
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
	 * Remove a property set on the entity
	 *
	 * @param string $property
	 * @return PropertyContainer
	 */
	public function removeProperty($property)
	{
		unset($this->properties[$property]);
		return $this;
	}

	/**
	 * Set the entity's id
	 *
	 * @param integer $id
	 * @return PropertyContainer
	 */
	public function setId($id)
	{
		$this->id = (int)$id;
		return $this;
	}

	/**
	 * Set multiple properties on the entity
	 *
	 * @param array $properties
	 * @return PropertyContainer
	 */
	public function setProperties($properties)
	{
		foreach ($properties as $property => $value) {
			$this->setProperty($property, $value);
		}
		return $this;
	}

	/**
	 * Set a property on the entity
	 *
	 * @param string $property
	 * @param mixed $value
	 * @return PropertyContainer
	 */
	public function setProperty($property, $value)
	{
		$this->properties[$property] = $value;
		return $this;
	}
}
