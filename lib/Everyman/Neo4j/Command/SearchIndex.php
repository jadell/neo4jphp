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

	protected $results = array();

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

		return '/index/'.$type.'/'.$name.'/'.$key.'/'.$this->value;
	}

	/**
	 * Get the result array of entities
	 *
	 * @return array
	 */
	public function getResult()
	{
		return $this->results;
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
		if ((int)($code / 100) == 2) {
			$buildMethod = $this->index->getType() == Index::TypeNode ? 'makeNode' : 'makeRelationship';
			foreach ($data as $entityData) {
				$this->results[] = $this->$buildMethod($entityData);
			}
			return null;
		}
		return $code;
	}

	/**
	 * Parse data into a relationship object
	 *
	 * @param array $data
	 * @return Relationship
	 */
	protected function makeRelationship($data)
	{
		$rel = new Relationship($this->client);
		$rel->useLazyLoad(false);
		$rel->setId($this->getIdFromUri($data['self']));
		$rel->setProperties($data['data']);
		$rel->setType($data['type']);

		$startId = $this->getIdFromUri($data['start']);
		$endId = $this->getIdFromUri($data['end']);
		$rel->setStartNode($this->client->getNode($startId, true));
		$rel->setEndNode($this->client->getNode($endId, true));

		return $rel;
	}

	/**
	 * Parse data into a node object
	 *
	 * @param array $data
	 * @return Node
	 */
	protected function makeNode($data)
	{
		$node = new Node($this->client);
		$node->useLazyLoad(false)
			->setId($this->getIdFromUri($data['self']))
			->setProperties($data['data']);
		return $node;
	}

	/**
	 * Get an id from a URI
	 *
	 * @param string $uri
	 * @return integer
	 */
	protected function getIdFromUri($uri)
	{
		$uriParts = explode('/', $uri);
		$id = array_pop($uriParts);
		return $id;
	}
}

