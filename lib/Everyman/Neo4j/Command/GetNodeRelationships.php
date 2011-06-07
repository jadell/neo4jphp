<?php
namespace Everyman\Neo4j\Command;
use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Exception,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Node;

/**
 * Find relationships on a node
 */
class GetNodeRelationships extends Command
{
	protected $node  = null;
	protected $types = null;
	protected $dir   = null;
	protected $rels  = array();

	/**
	 * Set the parameters to search
	 *
	 * @param Client $client
	 * @param Node   $node
	 * @param string $dir
	 * @param mixed  $types a string or array of strings
	 */
	public function __construct(Client $client, Node $node, $dir=null, $types=array())
	{
		parent::__construct($client);

		if (empty($dir)) {
			$dir = Relationship::DirectionAll;
		}
		if (empty($types)) {
			$types = array();
		} else if (!is_array($types)) {
			$types = array($types);
		}

		$this->node = $node;
		$this->dir = $dir;
		$this->types = $types;
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
		if (!$this->node->hasId()) {
			throw new Exception('No node id specified');
		}

		$path = '/node/'.$this->node->getId().'/relationships/'.$this->dir;
		if (!empty($this->types)) {
			$path .= '/'.join('&', $this->types);
		}

		return $path;
	}

	/**
	 * Get the result array of relationships
	 *
	 * @return array
	 */
	public function getResult()
	{
		return $this->rels;
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
			foreach ($data as $relData) {
				$this->rels[] = $this->makeRelationship($relData);
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
		$rel = new Relationship($this->node->getClient());
		$rel->useLazyLoad(false);
		$rel->setId($this->getIdFromUri($data['self']));
		$rel->setProperties($data['data']);
		$rel->setType($data['type']);

		$rel->setStartNode($this->makeNode($data['start']));
		$rel->setEndNode($this->makeNode($data['end']));

		return $rel;
	}

	/**
	 * Parse a node URI into a node
	 *
	 * @param string $uri
	 * @return Node
	 */
	protected function makeNode($uri)
	{
		$nodeId = $this->getIdFromUri($uri);
		$node = $this->client->getNode($nodeId, true);
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

