<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command\ExecuteCypherQuery,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Node,
	Everyman\Neo4j\Cypher\Query,
	Everyman\Neo4j\Label;

/**
 * Add or remove a set of labels on a node
 *
 * @todo: Don't extend ExecuteCypherQuery, extract and use a more generic Command interface
 *        that proxies to an ExecuteCypherQuery command
 */
class SetLabels extends ExecuteCypherQuery
{
	/**
	 * Proxy creation to a cypher query that does what we want
	 *
	 * @param Client   $client
	 * @param Node     $node
	 * @param array    $labels
	 * @param boolean  $remove
	 */
	public function __construct(Client $client, Node $node, $labels, $remove=false)
	{
		if (!$client->hasCapability(Client::CapabilityLabel)) {
			throw new \RuntimeException('The connected Neo4j version does not have label capability');
		}

		$nodeId = $node->getId();
		if (!is_numeric($nodeId)) {
			throw new \InvalidArgumentException("Cannot set labels on an unsaved node");
		}

		if (!$labels) {
			throw new \InvalidArgumentException("No labels given to set on node");
		}

		$labelSet = implode(':', array_map(function ($label) {
			if (!($label instanceof Label)) {
				throw new \InvalidArgumentException("Cannot set a non-label");
			}
			$name = str_replace('`', '``', $label->getName());
			return "`{$name}`";
		}, $labels));

		$setCommand = $remove ? 'REMOVE' : 'SET';

		$query = "START n=node({nodeId}) {$setCommand} n:{$labelSet} RETURN labels(n) AS labels";
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
