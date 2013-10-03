<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command\ExecuteCypherQuery,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Node,
	Everyman\Neo4j\Cypher\Query,
	Everyman\Neo4j\Label;

/**
 * Add a set of labels to a node
 *
 * @todo: Detect availability of labels functionality
 * @todo: Don't extend ExecuteCypherQuery, extract and use a more generic Command interface
 *        that proxies to an ExecuteCypherQuery command
 */
class AddLabels extends ExecuteCypherQuery
{
	/**
	 * Proxy creation to a cypher query that does what we want
	 *
	 * @param Client $client
	 * @param Node   $node
	 * @param array  $labels
	 */
	public function __construct(Client $client, Node $node, $labels)
	{
		$nodeId = $node->getId();
		if (!$nodeId) {
			throw new \InvalidArgumentException("Cannot add labels to an unsaved node");
		}

		if (!$labels) {
			throw new \InvalidArgumentException("No labels given to add to node");
		}

		$labelSet = implode(':', array_map(function ($label) {
			if (!($label instanceof Label)) {
				throw new \InvalidArgumentException("Cannot add a non-label");
			}
			return $label->getName();
		}, $labels));

		$query = "START n=node({nodeId}) SET n:{$labelSet} RETURN labels(n) AS labels";
		$params = array('nodeId' => $nodeId);

		$cypher = new Query($client, $query, $params);
		parent::__construct($client, $cypher);
	}

	/**
	 * Use the results
	 *
	 * @param integer $code
	 * @param array   $headers
	 * @param array   $data
	 * @return array of Label
	 */
	protected function handleResult($code, $headers, $data)
	{
		$results = parent::handleResult($code, $headers, $data);

		$nodeLabels = array();
		foreach ($results[0]['labels'] as $labelName) {
			$nodeLabels[] = $this->client->makeLabel($labelName);
		}
		return $nodeLabels;
	}
}

