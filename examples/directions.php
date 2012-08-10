#!/usr/bin/env php
<?php
use Everyman\Neo4j\Client,
	Everyman\Neo4j\Index\NodeIndex,
	Everyman\Neo4j\Path,
	Everyman\Neo4j\PathFinder,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Node;

require_once 'example_bootstrap.php';

$cmd = !empty($argv[1]) ? $argv[1] : null;
$from = '';
$to = '';
$paths = array();

if (!$cmd) {
	echo <<<HELP
Usage:
{$argv[0]}
	Display usage instructions

{$argv[0]} init
	Initialize the data.  This only needs to be done once.

{$argv[0]} map
	Display the available points and the connections and distances between them.

{$argv[0]} path <start> <end> [<algorithm>]
	Find a path from one node to another.
	algorithm is optional, and can be one of:
		optimal: Find the shortest paths by distance.  Default.
		simple:  Find the shortest paths by number of nodes.
		all:     Find all paths, regardless of length.


HELP;
	exit(0);
}

$client = new Client();
$intersections = new NodeIndex($client, 'intersections1');

$inters = array(
	'A'=>null,
	'B'=>null,
	'C'=>null,
	'D'=>null,
	'E'=>null,
	'F'=>null,
);

$streets = array(
	// name, start, end, direction, travel time
	array('A', 'B', array('direction'=>'east', 'distance'=>2.1, 'name'=>'AB')),
	array('A', 'D', array('direction'=>'south', 'distance'=>2.1, 'name'=>'AD')),
	array('A', 'E', array('direction'=>'south', 'distance'=>3.1, 'name'=>'AE')),

	array('B', 'A', array('direction'=>'west', 'distance'=>2.1, 'name'=>'AB')),
	array('B', 'C', array('direction'=>'east', 'distance'=>2.1, 'name'=>'BC')),
	array('B', 'E', array('direction'=>'south', 'distance'=>2.1, 'name'=>'BE')),

	array('C', 'B', array('direction'=>'west', 'distance'=>2.1, 'name'=>'BC')),
	array('C', 'F', array('direction'=>'south', 'distance'=>1.1, 'name'=>'CF')),

	array('D', 'A', array('direction'=>'north', 'distance'=>2.1, 'name'=>'AD')),
	array('D', 'E', array('direction'=>'east', 'distance'=>2.1, 'name'=>'DE')),

	array('E', 'D', array('direction'=>'west', 'distance'=>2.1, 'name'=>'DE')),
	array('E', 'B', array('direction'=>'north', 'distance'=>2.1, 'name'=>'BE')),

	array('F', 'C', array('direction'=>'north', 'distance'=>1.1, 'name'=>'CF')),
	array('F', 'E', array('direction'=>'west', 'distance'=>2.1, 'name'=>'FE')),
);

$turns = array(
	'east' => array(
		'north' => 'left',
		'south' => 'right',
		'west' => 'u-turn',
	),
	'west' => array(
		'north' => 'right',
		'south' => 'left',
		'east' => 'u-turn',
	),
	'north' => array(
		'east' => 'right',
		'west' => 'left',
		'south' => 'u-turn',
	),
	'south' => array(
		'east' => 'left',
		'west' => 'right',
		'north' => 'u-turn',
	),
);

// Initialize the data
if ($cmd == 'init') {
	echo "Initializing data.\n";
	foreach ($inters as $inter => $temp) {
		$node = $client->makeNode()->setProperty('name', $inter)->save();
		$intersections->add($node, 'name', $node->getProperty('name'));
		$inters[$inter] = $node;
	}

	foreach ($streets as $info) {
		$start = $inters[$info[0]];
		$end = $inters[$info[1]];
		$properties = $info[2];
		$street = $start->relateTo($end, 'CONNECTS')->setProperties($properties);
		$street->save();
	}

// Find a path
} else if ($cmd == 'path' && !empty($argv[2]) && !empty($argv[3])) {
	$from = $argv[2];
	$to = $argv[3];

	$algorithm = null;
	$all = !empty($argv[4]) && $argv[4] == 'all';

	$fromNode = $intersections->findOne('name', $from);
	$toNode = $intersections->findOne('name', $to);

	$finder = $fromNode->findPathsTo($toNode, 'CONNECTS', Relationship::DirectionOut)
		->setMaxDepth(5);

	$algorithm = !empty($argv[4]) ? $argv[4] : null;

	// Find all paths regardless of complexity or distance
	if ($algorithm == 'all') {
		$finder->setAlgorithm(PathFinder::AlgoAllSimple);

	// Find paths with the smallest number of instructions
	} else if ($algorithm == 'simple') {
		$finder->setAlgorithm(PathFinder::AlgoShortest);

	// Find the most optimal paths
	} else {
		$finder->setAlgorithm(PathFinder::AlgoDijkstra)
			->setCostProperty('distance');
	}

	$paths = $finder->getPaths();
}

echo <<<MAP
Map:
	A <-2-> B <-2-> C
	^\      ^       ^
	| \     |       |
	|  \    |       |
	|   \   |       |
	2   3\  2       1
	|     \ |       |
	|      \|       |
	V       V       V
	D <-2-> E <-2-- F 
MAP;
echo "\n\n";

foreach ($paths as $i => $path) {
	$path->setContext(Path::ContextRelationship);
	$prevDirection = null;
	$totalDistance = 0;

	echo "Path " . ($i+1) .":\n";
	foreach ($path as $j => $rel) {
		$direction = $rel->getProperty('direction');
		$distance = $rel->getProperty('distance');
		$name = $rel->getProperty('name');

		if (!$prevDirection) {
			$action = 'Head';
		} else if ($prevDirection == $direction) {
			$action = 'Continue';
		} else {
			$turn = $turns[$prevDirection][$direction];
			$action = "Turn $turn, and continue";
		}
		$prevDirection = $direction;
		$step = $j+1;
		$totalDistance += $distance;

		echo "\t{$step}: {$action} {$direction} on {$name} for {$distance} miles.\n";
	}
	echo "\tTravel distance: {$totalDistance}\n\n";
}



