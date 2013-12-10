<?php
namespace Everyman\Neo4j\Cache;

use Everyman\Neo4j\Client,
	Everyman\Neo4j\Exception,
	Everyman\Neo4j\PropertyContainer,
	Everyman\Neo4j\Node,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Cache;

/**
 * Store and retrieve cached entities without hitting the server
 */
class EntityCache
{
	protected $client = null;
	protected $cache = null;
	protected $cacheTimeout = null;

	/**
	 * Set the client and caching plugin to use
	 *
	 * @param Client $client
	 * @param Cache $cache
	 * @param integer $cacheTimeout
	 */
	public function __construct(Client $client, Cache $cache=null, $cacheTimeout=null)
	{
		$this->client = $client;
		$this->setCache($cache, $cacheTimeout);
	}

	/**
	 * Delete an entity from the cache
	 *
	 * @param PropertyContainer $entity
	 */
	public function deleteCachedEntity(PropertyContainer $entity)
	{
		$this->getCache()->delete($this->getEntityCacheKey($entity));
	}

	/**
	 * Get an entity from the cache
	 *
	 * @param integer $id
	 * @param string $type
	 */
	public function getCachedEntity($id, $type)
	{
		if ($type != 'node' && $type != 'relationship') {
			throw new Exception('Unknown entity type: '.$type);
		}

		$entity = $this->getCache()->get("{$type}-{$id}");
		if ($entity) {
			$entity->setClient($this->client);
		}
		return $entity;
	}

	/**
	 * Set the cache to use
	 *
	 * @param Cache $cache
	 * @param integer $cacheTimeout
	 */
	public function setCache(Cache $cache=null, $cacheTimeout=null)
	{
		$this->cache = $cache;
		$this->cacheTimeout = (int)$cacheTimeout;
	}

	/**
	 * Set an entity in the cache
	 *
	 * @param PropertyContainer $entity
	 */
	public function setCachedEntity(PropertyContainer $entity)
	{
		$this->getCache()->set($this->getEntityCacheKey($entity), $entity, $this->cacheTimeout);
	}

	/**
	 * Get the cache plugin
	 *
	 * @return Cache
	 */
	protected function getCache()
	{
		if ($this->cache === null) {
			$this->setCache(new Cache\Null(), $this->cacheTimeout);
		}
		return $this->cache;
	}

	/**
	 * Determine the cache key used to retrieve the given entity from the cache
	 *
	 * @param PropertyContainer $entity
	 * @return string 
	 */
	protected function getEntityCacheKey(PropertyContainer $entity)
	{
		if ($entity instanceof Node) {
			return 'node-'.$entity->getId();
		} else if ($entity instanceof Relationship) {
			return 'relationship-'.$entity->getId();
		}
		throw new Exception('Unknown entity type: '.get_class($entity));
	}
}
