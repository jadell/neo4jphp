<?php
namespace Everyman\Neo4j\Cache;

use Everyman\Neo4j\Cache;

/**
 * Cache everything locally to the process
 * Values cached this way are not persisted
 * when the process or request ends.
 */
class Variable implements Cache
{
	protected $items = array();

	/**
	 * Delete a value from the cache
	 *
	 * @param string $key
	 * @return boolean true on success
	 */
	public function delete($key)
	{
		unset($this->items[$key]);
		return true;
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
		$value = false;
		if (isset($this->items[$key])) {
			if ($this->items[$key]['expire'] >= time()) {
				$value = $this->items[$key]['value'];
			} else {
				$this->delete($key);
			}
		}

		return $value;
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
		$expire = $this->calculateExpiration($expire);

		$this->items[$key] = array(
			'value' => $value,
			'expire' => $expire,
		);
		return true;
	}

	/**
	 * Determine the expiration timestamp
	 *
	 * @param integer $expire
	 * @return integer
	 */
	protected function calculateExpiration($expire)
	{
		if (!$expire) {
			$expire = PHP_INT_MAX;
		} else if ($expire <= 2592000) {
			$expire = time() + $expire;
		}
		return $expire;
	}
}
