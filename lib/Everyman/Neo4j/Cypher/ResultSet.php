<?php
namespace Everyman\Neo4j\Cypher;

use Everyman\Neo4j\EntityMapper,
    Everyman\Neo4j\Client;

/**
 * This is what you get when you execute a Cypher query. Looping
 * over this will give you {@link Row} instances.
 */
class ResultSet implements \Iterator, \Countable, \ArrayAccess
{

	protected $client = null;
	protected $entityMapper = null;
	
	protected $data = null;
	protected $columns = null;
	protected $position = 0;

	public function __construct(Client $client, EntityMapper $entityMapper, $result)
	{
		$this->client = $client;
		$this->entityMapper = $entityMapper;
		if(is_array($result) && array_key_exists('data', $result)){
			$this->data = $result['data'];
			$this->columns = $result['columns'];
		} else {
			$this->data = array();
			$this->columns = array();
		}
	}
	
	/**
	 * @return string An array of column names.
	 */
	public function getColumns() {
		return $this->columns;
	}
	
	// ArrayAccess API
    
    public function offsetExists($offset) {
    	return isset($this->data[$offset]);
    }
    
    public function offsetGet($offset) {
		# TODO: Cache these Row instances
    	return new Row($this->client,
    	               $this->entityMapper,
    	               $this->columns, 
    	               $this->data[$offset]);
    }
	
	public function offsetSet($offset, $value) {
        throw new \BadMethodCallException("You cannot modify a query result.");
    }
    
    public function offsetUnset($offset) {
        throw new \BadMethodCallException("You cannot modify a query result.");
    }
	
	
	// Countable API
	
	function count() {
		return count($this->data);
	}
	
	
	// Iterator API
	
	function rewind() {
		$this->position = 0;
	}

	function current() {
		return $this[$this->position];
	}

	function key() {
		return $this->position;
	}

	function next() {
		++$this->position;
	}

	function valid() {
		return isset($this->data[$this->position]);
	}
}
