<?php
namespace Everyman\Neo4j;

/**
 * A dependency injector
 */
class DI
{
	/**
	 * Stores the registereditems
	 * @var array
	 */
    private static $register = array();

    /**
     * Stores resolved singletons
     * @var array
     */
	private static $singletons = array();

	/**
	 *
	 * Register new entry for resolution
	 * @param string $id - resolution entry identifier
	 * @param mixed $value - resolution entry value
	 * @param bool $singleton=false - flag indicating whether the entry is singleton
	 */
	public static function register ($id, $value, $singleton = false)
	{

		static::$register[$id]['resolver'] = $value;
		static::$register[$id]['singleton'] = (bool)$singleton;
	}

	/**
	 *
	 * Resolves registered entry and returns the resolved result
	 * @param string $id - the identifier of the entity to be resolved
	 * @param array $args - arguments if the entry to be resolved is a closure
	 *  that expects parameters
	 * @throws \Exception
	 * @return resolved entry
	 */
	public static function resolve ($id, array $args = array())
	{
		if (!array_key_exists($id, static::$register)
			&& !array_key_exists($id, static::$singletons)
		) {
			throw new Exception("No registry for key {$id}!");
		}

		//return singleton
		if (array_key_exists($id, static::$singletons)) {
			return static::$singletons[$id];
		}


		if (static::$register[$id]['resolver'] instanceof \Closure) {

			//if the resolver is registered as singleton create an
			//instance and keep it instead of the resolver function
			if (static::$register[$id]['singleton']) {

				static::$singletons[$id] = call_user_func_array(
					static::$register[$id]['resolver'],
					$args
				);
				unset(static::$register[$id]);

				return static::$singletons[$id];
			}

			return call_user_func_array(
				static::$register[$id]['resolver'],
				$args
			);
		}

		return static::$register[$id]['resolver'];
	}

	/**
	 *
	 * Checks if there is entry registered under a given key
	 * @param string $id
	 */
	public static function isRegistered ($id)
	{
	    return array_key_exists($id, static::$register)
	           || array_key_exists($id, static::$singletons);
	}

	/**
	 *
	 * Delete entry from the register
	 * @param string $id
	 */
	public static function unregister ($id)
	{
	    unset(static::$register[$id]);
	    unset(static::$singletons[$id]);
	}
}