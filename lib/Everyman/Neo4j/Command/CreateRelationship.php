<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Exception,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Node;

/**
 * Create a relationship
 */
class CreateRelationship extends Command
{
	protected $rel = null;

	/**
	 * Set the relationship to drive the command
	 *
	 * @param Client $client
	 * @param Relationship $rel
	 */
	public function __construct(Client $client, Relationship $rel)
	{
		parent::__construct($client);
		$this->rel = $rel;
	}

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		$end = $this->rel->getEndNode();
		$type = $this->rel->getType();
		if (!$end || !$end->hasId()) {
			throw new Exception('No relationship end node specified');
		} else if (!$type) {
			throw new Exception('No relationship type specified');
		}

		$endUri = $this->getTransport()->getEndpoint().'/node/'.$end->getId();
		$data = array(
			'type' => $type,
			'to'   => $endUri,
		);

		$properties = $this->rel->getProperties();
		if ($properties) {
			$data['data'] = $properties;
		}

		return $data;
	}

	/**
	 * Return the transport method to call
	 *
	 * @return string
	 */
	protected function getMethod()
	{
		return 'post';
	}

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	protected function getPath()
	{
		$start = $this->rel->getStartNode();
		if (!$start || !$start->hasId()) {
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
	 * @return boolean true on success
	 * @throws Exception on failure
	 */
	protected function handleResult($code, $headers, $data)
	{
		if ((int)($code / 100) != 2) {
			$this->throwException('Unable to create relationship', $code, $headers, $data);
		}

		$relId = $this->getEntityMapper()->getIdFromUri($headers['Location']);
		$this->rel->setId($relId);
		$this->getEntityCache()->setCachedEntity($this->rel);
		return true;
	}
}
