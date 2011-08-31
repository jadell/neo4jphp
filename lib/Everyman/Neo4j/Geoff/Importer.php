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

		$line = trim($line);
		if (!$line || $line[0]  == '#') {
			return;
		}

		$matches = array();
		$descriptorMatch = preg_match($descriptorPattern, $line, $matches);
		if ($descriptorMatch) {
			$nodeId = $matches[2];
			if (isset($nodes[$nodeId])) {
				throw new Exception("Invalid node reference on line {$lineNum}: $line");
			}
			$properties = json_decode($matches[7]);
			$node = new Node($this->client);
			$node->setProperties($properties ?: array());
			$nodes[$nodeId] = $node;
			$batch->save($node);
		}
	}
}