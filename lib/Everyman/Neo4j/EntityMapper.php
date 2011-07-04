<?php
namespace Everyman\Neo4j;

use Everyman\Neo4j\Client,
    Everyman\Neo4j\Node,
    Everyman\Neo4j\Relationship;

/**
 * Used to convert arbitrary arrays into Nodes and Relationships
 * where appropriate. 
 */
class EntityMapper
{
	protected $client = null;

	/**
	 * Set the client for retrieving related entities
	 */
	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	/**
	 * Given any object, see if it fulfills the contract
	 * for being node or relationship data returned by the
	 * server. If so, return a full Node or Relationship instance.
	 * Else, return the value untainted.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function getEntityFor($value)
	{
		if (is_array($value) && array_key_exists('self', $value)) {
			$entityId = $this->getIdFromUri($value['self']);
			if (array_key_exists('type', $value)) {
				$item = $this->client->getRelationship($entityId, true);
				$this->populateRelationship($item, $value);
			} else {
				$item = $this->client->getNode($entityId, true);
				$this->populateNode($item, $value);
			}
			return $item;
		}
		return $value;
	}

	/**
	 * Fill a node with data
	 *
	 * @param Node $node
	 * @param array $data
	 * @return Node
	 */
	public function populateNode(Node $node, $data)
	{
		$node->useLazyLoad(false);
		$node->setProperties($data['data']);
		return $node;
	}

	/**
	 * Fill a path with data
	 *
	 * @param Path $path
	 * @param array $data
	 * @param boolean $full
	 * @return Path
	 */
	public function populatePath(Path $path, $data, $full=false)
	{
		foreach ($data['relationships'] as $relData) {
			$relUri = $full ? $relData['self'] : $relData;
			$relId = $this->getIdFromUri($relUri);
			$rel = $this->client->getRelationship($relId, true);
			if ($full) {
				$rel = $this->populateRelationship($rel, $relData);
			}
			$path->appendRelationship($rel);
		}

		foreach ($data['nodes'] as $nodeData) {
			$nodeUri = $full ? $nodeData['self'] : $nodeData;
			$nodeId = $this->getIdFromUri($nodeUri);
			$node = $this->client->getNode($nodeId, true);
			if ($full) {
				$node = $this->populateNode($node, $nodeData);
			}
			$path->appendNode($node);
		}

		return $path;
	}

	/**
	 * Fill a relationship with data
	 *
	 * @param Relationship $rel
	 * @param array $data
	 * @return Relationship
	 */
	public function populateRelationship(Relationship $rel, $data)
	{
		$rel->useLazyLoad(false);
		$rel->setProperties($data['data']);
		$rel->setType($data['type']);

		$startId = $this->getIdFromUri($data['start']);
		$endId = $this->getIdFromUri($data['end']);
		$rel->setStartNode($this->client->getNode($startId, true));
		$rel->setEndNode($this->client->getNode($endId, true));

		return $rel;
	}

	/**
	 * Get an id from a URI
	 * TODO: Duplicate method from Command class, refactor.
	 *
	 * @param string $uri
	 * @return integer
	 */
	protected function getIdFromUri($uri)
	{
		$uriParts = explode('/', $uri);
		return array_pop($uriParts);
	}
}
