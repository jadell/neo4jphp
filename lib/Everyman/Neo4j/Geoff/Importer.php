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
		if (get_resource_type($handle) != 'stream') {
			throw new Exception("Not a stream resource");
		}

		if (!$batch) {
			$batch = new Batch($this->client);
		}

		$i = 0;
		$nodes = array();
		while (($line = fgets($handle)) !== false) {
			$this->loadLine($line, $batch, $i, $nodes);
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
	 */
	protected function loadLine($line, Batch $batch, $lineNum, &$nodes)
	{
		$descriptorPattern = "/^(\((\w+)\)(-\[:(\w+)\]->\((\w+)\))?)(\s+(.*))?/";
		$indexPattern = "/^(\{(\w+)\}->\((\w+)\))(\s+(.*))?/";

		$line = trim($line);
		if (!$line || $line[0]  == '#') {
			return;
		}

		$matches = array();
		$descriptorMatch = preg_match($descriptorPattern, $line, $matches);

		if ($descriptorMatch && !empty($matches[3])) {
			$startNodeId = $matches[2];
			$type = $matches[4];
			$endNodeId = $matches[5];
			if (!isset($nodes[$startNodeId]) || !isset($nodes[$endNodeId])) {
				throw new Exception("Invalid node reference on line {$lineNum}: $line");
			}
			$properties = !empty($matches[7]) ? json_decode($matches[7]) : false;
			$rel = new Relationship($this->client);
			$rel->setProperties($properties ?: array())
				->setType($type)
				->setStartNode($nodes[$startNodeId])
				->setEndNode($nodes[$endNodeId]);
			$batch->save($rel);
			return;

		} else if ($descriptorMatch) {
			$nodeId = $matches[2];
			if (isset($nodes[$nodeId])) {
				throw new Exception("Duplicate node on line {$lineNum}: $line");
			}
			$properties = !empty($matches[7]) ? json_decode($matches[7]) : false;
			$node = new Node($this->client);
			$node->setProperties($properties ?: array());
			$nodes[$nodeId] = $node;
			$batch->save($node);
			return;
		}

		$matches = array();
		$indexMatch = preg_match($indexPattern, $line, $matches);
		if ($indexMatch) {
			$name = $matches[2];
			$nodeId = $matches[3];
			if (!isset($nodes[$nodeId])) {
				throw new Exception("Invalid node reference on line {$lineNum}: $line");
			}
			$properties = !empty($matches[5]) ? json_decode($matches[5]) : false;
			if ($properties) {
				$index = new Index($this->client, Index::TypeNode, $name);
				foreach ($properties as $key => $value) {
					$batch->addToIndex($index, $nodes[$nodeId], $key, $value);
				}
			}
			return;
		}

		throw new Exception("Cannot parse line {$lineNum}: $line");
	}
}