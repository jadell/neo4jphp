<?php
namespace Everyman\Neo4j\Command;
use Everyman\Neo4j\Command,
	Everyman\Neo4j\Exception,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Node;

/**
 * Get and populate a relationship
 */
class GetRelationship implements Command
{
	protected $rel = null;

	/**
	 * Set the relationship to drive the command
	 *
	 * @param Relationship $rel
	 */
	public function __construct(Relationship $rel)
	{
		$this->rel = $rel;
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
		if (!$this->rel->getId()) {
			throw new Exception('No relationship id specified');
		}
		return '/relationship/'.$this->rel->getId();
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
			$this->rel->useLazyLoad(false);
			$this->rel->setProperties($data['data']);
			$this->rel->setType($data['type']);

			$this->rel->setStartNode($this->makeNode($data['start']));
			$this->rel->setEndNode($this->makeNode($data['end']));

			return null;
		}
		return $code;
	}

	/**
	 * Parse a node URI into a node
	 *
	 * @param string $uri
	 * @return Node
	 */
	protected function makeNode($uri)
	{
		$uriParts = explode('/', $uri);
		$nodeId = array_pop($uriParts);
		$node = new Node($this->rel->getClient());
		$node->setId($nodeId);
		return $node;
	}
}

