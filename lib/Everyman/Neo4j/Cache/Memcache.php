<?php
namespace Everyman\Neo4j\Cache;

use Everyman\Neo4j\Cache;

/**
 * Use the Memcache extension
 */
class Memcache implements Cache
{
	protected $memcache = null;

	/**
	 * Initialize the Memcache connection
	 *
	 * @param Memcache $memcache
	 */
	public function __construct(\Memcache $memcache)
	{
		$this->memcache = $memcache;
	}

	/**
	 * Delete a value from the cache
	 *
	 * @param string $key
	 * @return boolean true on success
	 */
	public function delete($key)
	{
		return $this->memcache->delete($key);
	}

	/**
	 * Retrieve a value
	 * Returns false if the key does not
	 * exist, or the value is false
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get($key)
	{
		return $this->memcache->get($key);
	}

	/**
	 * Store a value in the cache
	 * $expire is specified as an integer:
	 *   - less than or equal to 2592000 (the number of seconds in 30 days)
	 *     will be considered an expire time of that many seconds from the
	 *     current timestamp
	 *   - Greater than that amount will be considered as literal Unix
	 *     timestamp values
	 *   - 0 means "never expire."
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param integer $expire
	 * @return boolean true on success
	 */
	public function set($key, $value, $expire=0)
	{
		return $this->memcache->set($key, $value, 0, $expire);
	}
}
