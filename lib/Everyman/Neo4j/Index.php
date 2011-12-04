<?php
namespace Everyman\Neo4j;

/**
 * Represents an index in the database
 */
class Index
{
	const TypeNode = 'node';
	const TypeRelationship = 'relationship';

	protected $client = null;
	protected $type = self::TypeNode;
	protected $name = null;
	protected $config = array();

	/**
	 * Initialize the index
	 *
	 * @param Client $client
	 * @param string $type
	 * @param string $name
	 * @param array  $config
	 */
	public function __construct(Client $client, $type, $name, $config=array())
	{
		$this->client = $client;
		$this->type = $type;
		$this->name = $name;
		$this->config = $config;
	}

	/**
	 * Add an entity to the index
	 *
	 * @param PropertyContainer $entity
	 * @param string $key
	 * @param string $value
	 * @return boolean
	 */
	public function add($entity, $key, $value)
	{
		return $this->client->addToIndex($this, $entity, $key, $value);
	}

	/**
	 * Delete this index
	 *
	 * @return boolean
	 */
	public function delete()
	{
		return $this->client->deleteIndex($this);
	}

	/**
	 * Find entities
	 *
	 * @param string $key
	 * @param string $value
	 * @return array
	 */
	public function find($key, $value)
	{
		return $this->client->searchIndex($this, $key, $value);
	}

	/**
	 * Find a single entity
	 *
	 * @param string $key
	 * @param string $value
	 * @return PropertyContainer
	 */
	public function findOne($key, $value)
	{
		$entities = $this->client->searchIndex($this, $key, $value);
		return $entities ? $entities[0] : null;
	}

	/**
	 * Get the configuration options for this index
	 *
	 * Configuration options are only used during index creation,
	 * see `save`
	 *
	 * @return array
	 */
	public function getConfig()
	{
		return $this->config;
	}

	/**
	 * Get the index name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Get the index type
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Query index to find entities
	 *
	 * @param string $query
	 * @return array
	 */
	public function query($query)
	{
		return $this->client->queryIndex($this, $query);
	}

	/**
	 * Query index to find a single entity
	 *
	 * @param string $query
	 * @return PropertyContainer
	 */
	public function queryOne($query)
	{
		$entities = $this->client->queryIndex($this, $query);
		return $entities ? $entities[0] : null;
	}

	/**
	 * Remove an entity from the index
	 * If $value is not given, all reference of the entity for the key
	 * are removed.
	 * If $key is not given, all reference of the entity are removed.
	 *
	 * @param PropertyContainer $entity
	 * @param string $key
	 * @param string $value
	 * @return boolean
	 */
	public function remove($entity, $key=null, $value=null)
	{
		return $this->client->removeFromIndex($this, $entity, $key, $value);
	}

	/**
	 * Save this index
	 *
	 * @return boolean
	 */
	public function save()
	{
		return $this->client->saveIndex($this);
	}
}
