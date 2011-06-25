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

	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	/**
	 * Given any object, see if it fulfills the contract
	 * for being node or relationship data returned by the
	 * server. If so, return a full Node or Relationship instance.
	 * Else, return the value untainted.
	 */
	public function getEntityFor($value) {
		if(is_array($value) && array_key_exists('self', $value)) {
			if(array_key_exists('type', $value)) {
				$item = new Relationship($this->client);

				$startId = $this->getIdFromUri($value['start']);
				$endId = $this->getIdFromUri($value['end']);
				
				$item->setType($value['type']);
				$item->setStartNode($this->client->getNode($startId, true));
				$item->setEndNode($this->client->getNode($endId, true));
			} else {
				$item = new Node($this->client);
			}
			$item->useLazyLoad(false);
			$item->setId($this->getIdFromUri($value['self']));
			$item->setProperties($value['data']);
			return $item;
		}
		return $value;
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
