<?php
namespace Everyman\Neo4j;

/**
 * Implementation of GEOFF - Graph Export Object File Format
 * From http://py2neo.org/geoff
 */
class Geoff
{
	protected $client = null;
	protected $batch = null;

	protected $nodes = array();

	/**
	 * Build the importer/exported
	 *
	 * @param Client $client
	 */
	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	/**
	 * Load a GEOFF string
	 *
	 * @param string $geoffString
	 * @return Batch
	 */
	public function loadString($geoffString)
	{
		$batch = $this->getBatch();
		$lines = explode("\n", $geoffString);
		foreach ($lines as $i => $line) {
			$this->loadLine($line, $batch, $i);
		}

		return $batch;
	}
	

	/**
	 * Append all future loads to this batch
	 *
	 * @param Batch $batch
	 * @return Geoff
	 */
	public function setBatch(Batch $batch)
	{
		$this->batch = $batch;
	}

	/**
	 * Get the batch to append to
	 *
	 * @return Batch
	 */
	protected function getBatch()
	{
		if ($this->batch) {
			return $this->batch;
		} else {
			$this->nodes = array();
			return new Batch($this->client);
		}
	}

	/**
	 * Load a single line into the batch
	 *
	 * @param string $line
	 * @param Batch $batch
	 * @param integer $lineNum
	 */
	protected function loadLine($line, Batch $batch, $lineNum)
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
			if (isset($this->nodes[$nodeId])) {
				throw new Exception("Invalid node reference on line {$lineNum}: $line");
			}
			$properties = json_decode($matches[7]);
			$node = new Node($this->client);
			$node->setProperties($properties ?: array());
			$this->nodes[$nodeId] = $node;
			$batch->save($node);
		}
	}
}
