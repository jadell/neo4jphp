#!/usr/bin/env php
<?php
namespace Everyman\Neo4j;
use Everyman\Neo4j\Transport,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Batch,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Node;
error_reporting(-1);
ini_set('display_errors', 1);
spl_autoload_register(function ($sClass) {
	$sLibPath = __DIR__.'/../lib/';
	$sClassFile = str_replace('\\',DIRECTORY_SEPARATOR,$sClass).'.php';
	$sClassPath = $sLibPath.$sClassFile;
	if (file_exists($sClassPath)) {
		require($sClassPath);
	}
});


$transport = new Transport();
$client = new Client($transport);

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
		$batch = new Batch($this->client);
		foreach(range(1, $size) as $id) {
			$node = new Node($this->client);
			$node->setProperty('stop_id', $id);
			$batch->save($node);
		}
		$batch->commit();
		$end = time();
		return $end - $start;
	}

	protected function sequential($size)
	{
		$start = time();
		foreach(range(1, $size) as $id) {
			$node = new Node($this->client);
			$node->setProperty('stop_id', $id);
			$node->save();
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
		$nodeA = new Node($this->client);
		$nodeA->save();
		$nodeB = new Node($this->client);
		$nodeB->save();

		$start = time();
		$batch = new Batch($this->client);
		foreach(range(1, $size) as $id) {
			$rel = $nodeA->relateTo($nodeB, 'TEST');
			$rel->setProperty('stop_id', $id);
			$batch->save($rel);
		}
		$batch->commit();
		$end = time();
		return $end - $start;
	}

	protected function sequential($size)
	{
		$nodeA = new Node($this->client);
		$nodeA->save();
		$nodeB = new Node($this->client);
		$nodeB->save();

		$start = time();
		foreach(range(1, $size) as $id) {
			$rel = $nodeA->relateTo($nodeB, 'TEST');
			$rel->setProperty('stop_id', $id);
			$rel->save();
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
		$batch = new Batch($this->client);
		foreach(range(1, $size) as $id) {
			$nodeA = new Node($this->client);
			$nodeB = new Node($this->client);
			$rel = $nodeA->relateTo($nodeB, 'TEST');
			$rel->setProperty('stop_id', $id);
			$batch->save($rel);
		}
		$batch->commit();
		$end = time();
		return $end - $start;
	}

	protected function sequential($size)
	{
		$start = time();
		foreach(range(1, $size) as $id) {
			$nodeA = new Node($this->client);
			$nodeA->save();
			$nodeB = new Node($this->client);
			$nodeB->save();
			$rel = $nodeA->relateTo($nodeB, 'TEST');
			$rel->setProperty('stop_id', $id);
			$rel->save();
		}
		$end = time();
		return $end - $start;
	}
}
