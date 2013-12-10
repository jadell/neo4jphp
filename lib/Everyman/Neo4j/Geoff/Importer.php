<?php
namespace Everyman\Neo4j\Geoff;

use Everyman\Neo4j\Client,
	Everyman\Neo4j\Exception,
	Everyman\Neo4j\Node,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Index,
	Everyman\Neo4j\Batch;

/**
 * Import a GEOFF file into a batch
 */
class Importer
{
	protected $client = null;

	/**
	 * Build the importer
	 *
	 * @param Client $client
	 */
	public function __construct(Client $client)
	{
		$this->client = $client;
	}


	/**
	 * Load a GEOFF string from a stream
	 * If a batch is provided, append imported data to it,
	 * else, create and return a new batch
	 *
	 * @param stream $handle
	 * @param Batch $batch
	 * @return Batch
	 */
	public function load($handle, Batch $batch=null)
	{
		if (!is_resource($handle) || get_resource_type($handle) != 'stream') {
			throw new Exception("Not a stream resource");
		}

		if (!$batch) {
			$batch = new Batch($this->client);
		}

		$i = 0;
		$nodes = array();
		$rels = array();
		while (($line = fgets($handle)) !== false) {
			$this->loadLine($line, $batch, $i, $nodes, $rels);
			$i++;
		}

		return $batch;
	}

	/**
	 * Load a single line into the batch
	 *
	 * @param string $line
	 * @param Batch $batch
	 * @param integer $lineNum
	 * @param array $nodes
	 * @param array $rels
	 */
	protected function loadLine($line, Batch $batch, $lineNum, &$nodes, &$rels)
	{
		$descriptorPattern = "/^(
			\((\w+)\)	            # node identifier or relationship start node
			(                       # next two sub expressions signify a relationship line
				-\[(\w*):(\w+)\]    # relationship identifier and type
				->\((\w+)\)         # relationship end node
		)?)(
			\s+(.*)                 # properties
		)?/x";

		$indexPattern = "/^(
			\{(\w+)\}               # index name
			->(\(|\[)				# ( indicates node index, [ indicates relationship index
				(\w+)               # node identifier to index
			(\)|\])                 # must match opening ( or [
		)(
			\s+(.*)                 # keys:values to index
		)?/x";

		$line = trim($line);
		if (!$line || $line[0]  == '#') {
			return;
		}

		$matches = array();
		$descriptorMatch = preg_match($descriptorPattern, $line, $matches);

		if ($descriptorMatch && !empty($matches[3])) {
			$startNodeId = $matches[2];
			$relId = $matches[4];
			$type = $matches[5];
			$endNodeId = $matches[6];
			if (!isset($nodes[$startNodeId]) || !isset($nodes[$endNodeId])) {
				throw new Exception("Invalid node reference on line {$lineNum}: $line");
			} else if (!empty($relId) && isset($rels[$relId])) {
				throw new Exception("Duplicate relationship on line {$lineNum}: $line");
			}
			$properties = !empty($matches[8]) ? json_decode($matches[8]) : false;
			$rel = $this->client->makeRelationship();
			$rel->setProperties($properties ?: array())
				->setType($type)
				->setStartNode($nodes[$startNodeId])
				->setEndNode($nodes[$endNodeId]);
			if (!empty($relId)) {
				$rels[$relId] = $rel;
			}
			$batch->save($rel);
			return;

		} else if ($descriptorMatch) {
			$nodeId = $matches[2];
			if (isset($nodes[$nodeId])) {
				throw new Exception("Duplicate node on line {$lineNum}: $line");
			}
			$properties = !empty($matches[7]) ? json_decode($matches[7]) : false;
			$node = $this->client->makeNode();
			$node->setProperties($properties ?: array());
			$nodes[$nodeId] = $node;
			$batch->save($node);
			return;
		}

		$matches = array();
		$indexMatch = preg_match($indexPattern, $line, $matches);
		if ($indexMatch) {
			$name = $matches[2];
			$openBrace = $matches[3];
			$closeBrace = $matches[5];
			$entityId = $matches[4];
			$properties = !empty($matches[7]) ? json_decode($matches[7]) : false;
			if ($properties) {
				$type = null;
				if ($openBrace == '(' && $closeBrace == ')') {
					if (!isset($nodes[$entityId])) {
						throw new Exception("Invalid node reference on line {$lineNum}: $line");
					}
					$entity = $nodes[$entityId];
					$type = Index::TypeNode;
				} else if ($openBrace == '[' && $closeBrace == ']') {
					if (!isset($rels[$entityId])) {
						throw new Exception("Invalid relationship reference on line {$lineNum}: $line");
					}
					$entity = $rels[$entityId];
					$type = Index::TypeRelationship;
				}

				if ($type) {
					$index = new Index($this->client, $type, $name);
					foreach ($properties as $key => $value) {
						$batch->addToIndex($index, $entity, $key, $value);
					}
					return;
				}
			}
		}

		throw new Exception("Cannot parse line {$lineNum}: $line");
	}
}
