#!/usr/bin/env php
<?php
use Everyman\Neo4j\Client,
	Everyman\Neo4j\Index\NodeIndex,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Node,
	Everyman\Neo4j\Cypher;

require_once 'example_bootstrap.php';

$cmd = !empty($argv[1]) ? $argv[1] : null;

if (!$cmd) {
	echo <<<HELP
Usage:
{$argv[0]}
	Display usage instructions

{$argv[0]} init
	Initialize the data.  This only needs to be done once.

{$argv[0]} actors <movie_name>
	Get a list of all actors in the movie.


HELP;
	exit(0);
}

$client = new Client();
$actors = new NodeIndex($client, 'actors');

// Initialize the data
if ($cmd == 'init') {
	$keanu = $client->makeNode()->setProperty('name', 'Keanu Reeves')->save();
	$laurence = $client->makeNode()->setProperty('name', 'Laurence Fishburne')->save();
	$jennifer = $client->makeNode()->setProperty('name', 'Jennifer Connelly')->save();
	$kevin = $client->makeNode()->setProperty('name', 'Kevin Bacon')->save();

	$actors->add($keanu, 'name', $keanu->getProperty('name'));
	$actors->add($laurence, 'name', $laurence->getProperty('name'));
	$actors->add($jennifer, 'name', $jennifer->getProperty('name'));
	$actors->add($kevin, 'name', $kevin->getProperty('name'));

	$matrix = $client->makeNode()->setProperty('title', 'The Matrix')->save();
	$higherLearning = $client->makeNode()->setProperty('title', 'Higher Learning')->save();
	$mysticRiver = $client->makeNode()->setProperty('title', 'Mystic River')->save();

	$keanu->relateTo($matrix, 'IN')->save();
	$laurence->relateTo($matrix, 'IN')->save();

	$laurence->relateTo($higherLearning, 'IN')->save();
	$jennifer->relateTo($higherLearning, 'IN')->save();

	$laurence->relateTo($mysticRiver, 'IN')->save();
	$kevin->relateTo($mysticRiver, 'IN')->save();

// Find all actors in a movie
} else if ($cmd == 'actors') {
	
	if(!empty($argv[2])) {
		$movie = implode(" ", array_slice($argv,2));
	} else {
		$movie = "The Matrix";
	}
	
	$queryTemplate = "START actor=node:actors('name:*') ".
		"MATCH (actor) -[:IN]- (movie)".
		"WHERE movie.title = {title}".
		"RETURN actor";
	$query = new Cypher\Query($client, $queryTemplate, array('title'=>$movie));
	$result = $query->getResultSet();
	
	echo "Found ".count($result)." actors:\n";
	foreach($result as $row) {
		echo "  ".$row['actor']->getProperty('name')."\n";
	}
}

