#!/usr/bin/env php
<?php
namespace Everyman\Neo4j;
use Everyman\Neo4j\Client,
	Everyman\Neo4j\Batch,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Node;

require_once 'example_bootstrap.php';

$client = new Client();

$runs = 5;
$series = array(
	10,
	100,
	250,
	500,
	1000,
	2500,
	5000,
);

$trials = array(
	new CreateNode($client),
	new CreateRelationship($client),
	new CreateFullRelationship($client),
);
foreach ($trials as $trial) {
	$trial->benchmark($series, $runs);
}

////////////////////////////////////////////////////////////////////////////////
// Benchmark trials ///////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////

abstract class Benchmark
{
	protected $client = null;
	protected $title = 'unknown';

	abstract protected function batch($size);
	abstract protected function sequential($size);

	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	public function benchmark($series, $runs)
	{
		echo "Benchmark: {$this->title}\n";
		foreach ($series as $size) {
			$batchTotal = 0;
			$seqTotal = 0;
			for ($i=0; $i<$runs; $i++) {
				echo "{$size}\t{$i}\t";

				$batchTotal += $batchTime = $this->batch($size);
				echo "{$batchTime}\t";

				$seqTotal += $seqTime = $this->sequential($size);
				echo "{$seqTime}\n";
			}
			$batchAvg = round($batchTotal/$runs,2);
			$seqAvg = round($seqTotal/$runs,2);
			echo "\t\t$batchAvg\t$seqAvg\n\n";
		}
		
	}
}

class CreateNode extends Benchmark
{
	protected $title = 'Create nodes';

	protected function batch($size)
	{
		$start = time();
		$this->client->startBatch();
		foreach(range(1, $size) as $id) {
			$this->client->makeNode()->setProperty('stop_id', $id)->save();
		}
		$this->client->commitBatch();
		$end = time();
		return $end - $start;
	}

	protected function sequential($size)
	{
		$start = time();
		foreach(range(1, $size) as $id) {
			$this->client->makeNode()->setProperty('stop_id', $id)->save();
		}
		$end = time();
		return $end - $start;
	}
}

class CreateRelationship extends Benchmark
{
	protected $title = 'Create relationships';

	protected function batch($size)
	{
		$nodeA = $this->client->makeNode()->save();
		$nodeB = $this->client->makeNode()->save();

		$start = time();
		$this->client->startBatch();
		foreach(range(1, $size) as $id) {
			$nodeA->relateTo($nodeB, 'TEST')->setProperty('stop_id', $id)->save();
		}
		$this->client->commitBatch();
		$end = time();
		return $end - $start;
	}

	protected function sequential($size)
	{
		$nodeA = $this->client->makeNode()->save();
		$nodeB = $this->client->makeNode()->save();

		$start = time();
		foreach(range(1, $size) as $id) {
			$nodeA->relateTo($nodeB, 'TEST')->setProperty('stop_id', $id)->save();
		}
		$end = time();
		return $end - $start;
	}
}

class CreateFullRelationship extends Benchmark
{
	protected $title = 'Create full relationships (start node, end node, relationship)';

	protected function batch($size)
	{
		$start = time();
		$this->client->startBatch();
		foreach(range(1, $size) as $id) {
			$nodeA = $this->client->makeNode()->save();
			$nodeB = $this->client->makeNode()->save();
			$nodeA->relateTo($nodeB, 'TEST')->setProperty('stop_id', $id)->save();
		}
		$this->client->commitBatch();
		$end = time();
		return $end - $start;
	}

	protected function sequential($size)
	{
		$start = time();
		foreach(range(1, $size) as $id) {
			$nodeA = $this->client->makeNode()->save();
			$nodeB = $this->client->makeNode()->save();
			$nodeA->relateTo($nodeB, 'TEST')->setProperty('stop_id', $id)->save();
		}
		$end = time();
		return $end - $start;
	}
}
