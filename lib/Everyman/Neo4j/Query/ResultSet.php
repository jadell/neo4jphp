<?php
namespace Everyman\Neo4j\Query;

use Everyman\Neo4j\Client;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;

/**
 * This is what you get when you execute a query. Looping
 * over this will give you {@link Row} instances.
 */
class ResultSet implements \Iterator, \Countable, \ArrayAccess
{
	protected $client = null;

	protected $rows = array();
	protected $data = array();
	protected $columns = array();
	protected $position = 0;

	/**
	 * Set the array of results to represent
	 *
	 * @param Client $client
	 * @param array $result
	 */
	public function __construct(Client $client, $result)
	{
		$this->client = $client;
		if (is_array($result) && array_key_exists('data', $result)) {
			$this->data = $result['data'];
			$this->columns = $result['columns'];
		}
	}

	/**
	 * Return the list of column names
	 *
	 * @return array
	 */
	public function getColumns()
	{
		return $this->columns;
	}

	// ArrayAccess API

	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}

	public function offsetGet($offset)
	{
		if (!isset($this->rows[$offset])) {
			$this->rows[$offset] = new Row($this->client, $this->columns, $this->data[$offset]);
		}
		return $this->rows[$offset];
	}

	public function offsetSet($offset, $value)
	{
		throw new \BadMethodCallException("You cannot modify a query result.");
	}

	public function offsetUnset($offset)
	{
		throw new \BadMethodCallException("You cannot modify a query result.");
	}


	// Countable API

	public function count()
	{
		return count($this->data);
	}


	// Iterator API

	public function rewind()
	{
		$this->position = 0;
	}

	public function current()
	{
		return $this[$this->position];
	}

	public function key()
	{
		return $this->position;
	}

	public function next()
	{
		++$this->position;
	}

	public function valid()
	{
		return isset($this->data[$this->position]);
	}
    
    /**
     *  toArray implementation
     */
    public function toArray()
    {
        return array_map(
            function($data) {
                return array_combine(
                    $this->getColumns(),
                    $data
                );
            },
            $this->data
        );
    }
    
    /**
     *  toJson implementation
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
    
    /**
     *  String type casting implementation
     */
    public function __toString()
    {
        return $this->toJson();
    }
}
