<?php
namespace Everyman\Neo4j;

/**
 * Represents a single label
 *
 * Labels cannot be saved standalone; instead, they are
 * saved when attached to a node
 */
class Label
{
	protected $client;
	protected $name;

	/**
	 * Build the label and set its client and name
	 *
	 * @param Client $client
	 * @param string $name
	 */
	public function __construct(Client $client, $name)
	{
		if (empty($name) || !(is_string($name) || is_numeric($name))) {
			throw new \InvalidArgumentException("Label name must be a string or number");
		}

		$this->client = $client;
		$this->name = (string)$name;
	}

	/**
	 * Return the label name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Get the nodes with this label
	 *
	 * If a property and value are given, only return
	 * nodes where the given property equals the value
	 *
	 * @param string $propertyName
	 * @param mixed  $propertyValue
	 * @return Row
	 * @throws Exception on failure
	 */
	public function getNodes($propertyName=null, $propertyValue=null)
	{
		return $this->client->getNodesForLabel($this, $propertyName, $propertyValue);
	}
}
