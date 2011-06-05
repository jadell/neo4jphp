<?php
namespace Everyman\Neo4j\Command;
use Everyman\Neo4j\Command,
	Everyman\Neo4j\Exception,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Node;

/**
 * Find relationships on a node
 */
class GetNodeRelationships implements Command
{
	protected $node  = null;
	protected $types = null;
	protected $dir   = null;
	protected $rels  = array();

	/**
	 * Set the parameters to search
	 *
	 * @param Node   $node
	 * @param string $dir
	 * @param mixed  $types a string or array of strings
	 */
	public function __construct(Node $node, $dir=null, $types=array())
	{
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
	public function getData()
	{
		return null;
	}

	/**
	 * Return the transport method to call
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return 'get';
	}

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	public function getPath()
	{
		$nodeId = $this->node->getId();
		if (!$nodeId) {
			throw new Exception('No node id specified');
		}

		$path = "/node/{$nodeId}/relationships/{$this->dir}";
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
	public function handleResult($code, $headers, $data)
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
		$node = new Node($this->node->getClient());
		$node->setId($this->getIdFromUri($uri));
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

