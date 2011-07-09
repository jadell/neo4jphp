<?php
namespace Everyman\Neo4j\Cache;

use Everyman\Neo4j\Cache;

/**
 * Cache that always indicates success but does not store anything
 */
class Null implements Cache
{
	/**
	 * Delete always succeeds
	 *
	 * @param string $key
	 * @return boolean true on success
	 */
	public function delete($key)
	{
		return true;
	}

	/**
	 * Always false, since no value is stored
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get($key)
	{
		return false;
	}

	/**
	 * Always indicates success, but does not actually store value
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param integer $expire
	 * @return boolean true on success
	 */
	public function set($key, $value, $expire=0)
	{
		return true;
	}
}
