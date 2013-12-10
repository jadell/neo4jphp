<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Exception,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Node,
	Everyman\Neo4j\Index;

/**
 * Search for entities in an index
 */
class SearchIndex extends Command
{
	protected $index = null;
	protected $key = null;
	protected $value = null;

	/**
	 * Set the index to drive the command
	 *
	 * @param Client $client
	 * @param Index $index
	 * @param string $key
	 * @param string $value
	 */
	public function __construct(Client $client, Index $index, $key, $value)
	{
		parent::__construct($client);
		$this->index = $index;
		$this->key = $key;
		$this->value = $value;
	}

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		return null;
	}

	/**
	 * Return the transport method to call
	 *
	 * @return string
	 */
	protected function getMethod()
	{
		return 'get';
	}

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	protected function getPath()
	{
		$type = trim((string)$this->index->getType());
		if ($type != Index::TypeNode && $type != Index::TypeRelationship) {
			throw new Exception('No type specified for index');
		}

		$name = trim((string)$this->index->getName());
		if (!$name) {
			throw new Exception('No name specified for index');
		}

		$key = trim((string)$this->key);
		if (!$key) {
			throw new Exception('No key specified to search index');
		}

		$name = rawurlencode($name);
		$key = rawurlencode($key);
		$value = rawurlencode($this->value);

		return '/index/'.$type.'/'.$name.'/'.$key.'/'.$value;
	}

	/**
	 * Use the results
	 *
	 * @param integer $code
	 * @param array   $headers
	 * @param array   $data
	 * @return integer on failure
	 */
	protected function handleResult($code, $headers, $data)
	{
		if ((int)($code / 100) != 2) {
			$this->throwException('Unable to search index', $code, $headers, $data);
		}

		$buildMethod = $this->index->getType() == Index::TypeNode ? 'makeNode' : 'makeRelationship';
		$results = array();
		foreach ($data as $entityData) {
			$results[] = $this->getEntityMapper()->$buildMethod($entityData);
		}
		return $results;
	}
}
