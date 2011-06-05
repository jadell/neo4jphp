<?php
namespace Everyman\Neo4j\Command;
use Everyman\Neo4j\Command,
	Everyman\Neo4j\Exception,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Node;

/**
 * Create a relationship
 */
class CreateRelationship implements Command
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
		$end = $this->rel->getEndNode();
		$type = $this->rel->getType();
		if (!$end || !$end->getId()) {
			throw new Exception('No relationship end node specified');
		} else if (!$type) {
			throw new Exception('No relationship type specified');
		}

		$endUri = $end->getClient()->getEndpoint().'/node/'.$end->getId();
		$data = array(
			'data' => $this->rel->getProperties(),
			'type' => $type,
			'to'   => $endUri,
		);

		return $data;
	}

	/**
	 * Return the transport method to call
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return 'post';
	}

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	public function getPath()
	{
		$start = $this->rel->getStartNode();
		if (!$start || !$start->getId()) {
			throw new Exception('No relationship start node specified');
		}
		return '/node/'.$start->getId().'/relationships';
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
			$locationParts = explode('/', $headers['Location']);
			$relId = array_pop($locationParts);
			$this->rel->setId($relId);
			return null;
		}
		return $code;
	}
}

