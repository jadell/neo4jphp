<?php
namespace Everyman\Neo4j;

/**
 * Used to convert arbitrary arrays into Nodes and Relationships
 * where appropriate. 
 */
class EntityMapper
{
	protected $client = null;

	/**
	 * Set the client for retrieving related entities
	 *
	 * @param Client $client
	 */
	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	/**
	 * Given any object, see if it fulfills the contract
	 * for being a path, node or relationship data returned by the
	 * server. If so, return a full Path, Node or Relationship instance.
	 * Else, return the value untainted.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function getEntityFor($value)
	{
		if (is_array($value)) {
			if (array_key_exists('self', $value)) {
				if (array_key_exists('type', $value)) {
					$value = $this->makeRelationship($value);
				} else if(array_key_exists('data', $value) && is_array($value['data']) && array_key_exists('geomencoder', $value['data'])) {
                    $value = $this->makeSpatialLayer($value);
                } else {
					$value = $this->makeNode($value);
				}
			} else if (array_key_exists('nodes', $value) && array_key_exists('relationships', $value)) {
				$value = $this->populatePath(new Path($this->client), $value);
			}
		}
		return $value;
	}

	/**
	 * Get an id from a URI
	 *
	 * @param string $uri
	 * @return mixed
	 */
	public function getIdFromUri($uri)
	{
		$uriParts = explode('/', $uri);
		return array_pop($uriParts);
	}

	/**
	 * Generate and populate a node from the given data
	 *
	 * @param array $data
	 * @return Node
	 */
	public function makeNode($data)
	{
		$node = $this->getNodeFromUri($data['self']);
		return $this->populateNode($node, $data);
	}

	/**
	 * Generate and populate a relationship from the given data
	 *
	 * @param array $data
	 * @return Relationship
	 */
	public function makeRelationship($data)
	{
		$rel = $this->getRelationshipFromUri($data['self']);
		return $this->populateRelationship($rel, $data);
	}
    
    /**
	 * Generate and populate a spatial layer from the given data
	 *
	 * @param array $data
	 * @return SpatialLayer
	 */
	public function makeSpatialLayer($data)
	{
		$rel = $this->getSpatialLayerFromUri($data['self']);
		return $this->populateSpatialLayer($rel, $data);
	}
    
    /**
	 * Fill a spatial layer with data
	 *
	 * @param SpatialLayer $spatialLayer
	 * @param array $data
	 * @return SpatialLayer
	 */
	public function populateSpatialLayer(SpatialLayer $spatialLayer, $data)
	{
		
		return $node;
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
			$rel = $this->getRelationshipFromUri($relUri);
			if ($full) {
				$rel = $this->populateRelationship($rel, $relData);
			}
			$path->appendRelationship($rel);
		}

		foreach ($data['nodes'] as $nodeData) {
			$nodeUri = $full ? $nodeData['self'] : $nodeData;
			$node = $this->getNodeFromUri($nodeUri);
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

		$rel->setStartNode($this->getNodeFromUri($data['start']));
		$rel->setEndNode($this->getNodeFromUri($data['end']));

		return $rel;
	}

    /**
	 * Retrieve a spaltial layer by it's 'self' uri
	 *
	 * @param string $uri
	 * @return SpatialLayer
	 */
	protected function getSpatialLayerFromUri($uri)
	{
		$relId = $this->getIdFromUri($uri);
		return $this->client->getSpatialLayer($relId, true);
	}
    
	/**
	 * Retrieve a node by it's 'self' uri
	 *
	 * @param string $uri
	 * @return Node
	 */
	protected function getNodeFromUri($uri)
	{
		$nodeId = $this->getIdFromUri($uri);
		return $this->client->getNode($nodeId, true);
	}

	/**
	 * Retrieve a relationship by it's 'self' uri
	 *
	 * @param string $uri
	 * @return Relationship
	 */
	protected function getRelationshipFromUri($uri)
	{
		$relId = $this->getIdFromUri($uri);
		return $this->client->getRelationship($relId, true);
	}
}
