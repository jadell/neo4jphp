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
	/**
	 * @var Client Our client
	 */
	protected $client;
	/**
	 * @var string Our name
	 */
	protected $name;

	/**
	 * Build the label and set its client and name
	 *
	 * @param Client $client
	 * @param string $name
	 * @throws \InvalidArgumentException
	 */
	public function __construct(Client $client, $name)
	{
		if (empty($name) || !(is_string($name) || is_numeric($name))) {
			throw new \InvalidArgumentException("Label name must be a string or number");
		}

		$this->setClient($client);
		$this->name = (string)$name;
	}

	/**
	 * Set the client to use with this Label object
	 *
	 * @param Client $client
	 * @return Label
	 */
	public function setClient(Client $client)
	{
		$this->client = $client;
		return $this;
	}

	/**
	 * Get our client
	 *
	 * @return Client
	 */
	public function getClient()
	{
		return $this->client;
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
	 * @return Query\Row
	 * @throws Exception on failure
	 */
	public function getNodes($propertyName=null, $propertyValue=null)
	{
		return $this->client->getNodesForLabel($this, $propertyName, $propertyValue);
	}

	/**
	 * Only serialize our name property
	 *
	 * @return array
	 */
	public function __sleep()
	{
		return array('name');
	}
}
