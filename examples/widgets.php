#!/usr/bin/env php
<?php
use Everyman\Neo4j\Client,
	Everyman\Neo4j\Index\NodeIndex,
	Everyman\Neo4j\Path,
	Everyman\Neo4j\PathFinder,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Node,
	Everyman\Neo4j\Cypher,
	Everyman\Neo4j\Traversal;

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

{$argv[0]} parts
	Display the available parts in the system.

{$argv[0]} stores <part> [<language>]
	Find all the stores where the given part was sold.
	Language can be one of:
		traverse: use the javascript traversal. Default.
		cypher: use a cypher query


HELP;
	exit(0);
}

$client = new Client();
$partsIndex = new NodeIndex($client, 'parts3');

$parts = array('widget','gadget','gizmo');
$stores = array("Bob's Old Houseware","Mainstreet Hardware","Nutz N' Boltz", "Doodad Emporium");
// Store, part list
$orders = array(
	array(0, array(0,1)),
	array(0, array(1)),
	array(1, array(1,2)),
	array(1, array(0,2)),
	array(2, array(0,1,2)),
	array(3, array(2)),
	array(3, array(0)),
);

// Initialize the data
if ($cmd == 'init') {
	echo "Initializing data.\n";
	$p = array();
	$s = array();

	foreach ($parts as $part) {
		$node = $client->makeNode()->setProperty('name', $part)->save();
		$partsIndex->add($node, 'name', $node->getProperty('name'));
		$p[] = $node;
	}

	foreach ($stores as $store) {
		$node = $client->makeNode()->setProperty('name', $store)->save();
		$s[] = $node;
	}

	foreach ($orders as $order) {
		$node = $client->makeNode()->save();

		$s[$order[0]]->relateTo($node, 'SOLD')->save();
		foreach ($order[1] as $pi) {
			$node->relateTo($p[$pi], 'CONTAINS')->save();
		}
	}

// List parts
} else if ($cmd == 'parts') {
	$partsList = $partsIndex->query('name:*');
	foreach ($partsList as $part) {
		echo "* {$part->getProperty('name')}\n";
	}

// Find stores where the part was sold
} else if ($cmd == 'stores' && !empty($argv[2])) {
	$partName = $argv[2];

	// Use the Cypher query language
	if (!empty($argv[3]) && $argv[3] == 'cypher') {
		$queryTemplate = "START part=node:parts3('name:{$partName}') ".
			"MATCH (store)-[:SOLD]->()-[:CONTAINS]->(part) ".
			// Use the count(*) to force distinct values until Cypher gets DISTINCT keyword support
			"RETURN store, count(*)";
		$query = new Cypher\Query($client, $queryTemplate);
		$result = $query->getResultSet();
	
		echo "Found ".count($result)." stores:\n";
		foreach($result as $row) {
			echo "* ".$row['store']->getProperty('name')."\n";
		}
	
	// Use javascript traversal
	} else {
		$part = $partsIndex->findOne('name', $partName);
		if (!$part) {
			die("{$partName} not found.\n");
		}

		$traversal = new Traversal($client);
		$traversal->addRelationship('CONTAINS', Relationship::DirectionIn)
			->addRelationship('SOLD', Relationship::DirectionIn)
			->setMaxDepth(4)
			->setReturnFilter('javascript', '(position.length() > 0 && position.lastRelationship().getType() == "SOLD");');

		$stores = $traversal->getResults($part, Traversal::ReturnTypeNode);
		echo "Found ".count($stores)." stores:\n";
		foreach ($stores as $store) {
			echo "* {$store->getProperty('name')}\n";
		}
	}
}



