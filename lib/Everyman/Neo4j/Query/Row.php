<?php
namespace Everyman\Neo4j\Query;

use Everyman\Neo4j\Client;

/**
 * Represents a single result row from a query.
 * You can loop over this to get each value, or you can
 * use array access ($myRow['columnName'] or $myRow[0])
 * to get specific fields.
 */
class Row implements \Iterator, \Countable, \ArrayAccess
{
	protected $client = null;
	protected $raw = null;
	protected $data = null;
	protected $columns = null;
	protected $position = 0;

	/**
	 * Set the raw result data of this row
	 *
	 * @param Client $client
	 * @param array $columns
	 * @param array $rowData
	 */
	public function __construct(Client $client, $columns, $rowData)
	{
		$this->client = $client;
		$this->raw = $rowData;
		$this->data = array();
		$this->columns = $columns;
	}

	// ArrayAccess API

	public function offsetExists($offset)
	{
		if (!is_integer($offset)) {
			return in_array($offset, $this->columns);
		}

		return isset($this->raw[$offset]);
	}

	public function offsetGet($offset)
	{
		if (!is_integer($offset)) {
			$offset = array_search($offset, $this->columns);
		}

		if (!isset($this->data[$offset])) {
			$this->data[$offset] = $this->client->getEntityMapper()->getEntityFor($this->raw[$offset]);
		}

		return $this->data[$offset];
	}

	public function offsetSet($offset, $value)
	{
		throw new \BadMethodCallException("You cannot modify a result row.");
	}

	public function offsetUnset($offset)
	{
		throw new \BadMethodCallException("You cannot modify a result row.");
	}


	// Countable API

	public function count()
	{
		return count($this->raw);
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
		return $this->columns[$this->position];
	}

	public function next()
	{
		++$this->position;
	}

	public function valid()
	{
		return isset($this->raw[$this->position]);
	}
}
