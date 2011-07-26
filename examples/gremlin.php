#!/usr/bin/env php
<?php
use Everyman\Neo4j\Transport,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Index,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Node,
	Everyman\Neo4j\Gremlin;
error_reporting(-1);
ini_set('display_errors', 1);

function loaderTestAutoloader($sClass)
{
	$sLibPath = __DIR__.'/../lib/';
	$sClassFile = str_replace('\\',DIRECTORY_SEPARATOR,$sClass).'.php';
	$sClassPath = $sLibPath.$sClassFile;
	if (file_exists($sClassPath)) {
		require($sClassPath);
	}
}
spl_autoload_register('loaderTestAutoloader');

$cmd = !empty($argv[1]) ? $argv[1] : null;

if (!$cmd) {
	echo <<<HELP
Usage:
{$argv[0]}
	Display usage instructions

{$argv[0]} init
	Initialize the data.  This only needs to be done once.

{$argv[0]} actors
	Get a list of all actors in the database.

HELP;
	exit(0);
}

$transport = new Transport();
$client = new Client($transport);
$actors = new Index($client, Index::TypeNode, 'actors');

// Initialize the data
if ($cmd == 'init') {
	$keanu = new Node($client);
	$keanu->setProperty('name', 'Keanu Reeves')->save();
	$laurence = new Node($client);
	$laurence->setProperty('name', 'Laurence Fishburne')->save();
	$jennifer = new Node($client);
	$jennifer->setProperty('name', 'Jennifer Connelly')->save();
	$kevin = new Node($client);
	$kevin->setProperty('name', 'Kevin Bacon')->save();

	$actors->add($keanu, 'name', $keanu->getProperty('name'));
	$actors->add($laurence, 'name', $laurence->getProperty('name'));
	$actors->add($jennifer, 'name', $jennifer->getProperty('name'));
	$actors->add($kevin, 'name', $kevin->getProperty('name'));

	$matrix = new Node($client);
	$matrix->setProperty('title', 'The Matrix')->save();
	$higherLearning = new Node($client);
	$higherLearning->setProperty('title', 'Higher Learning')->save();
	$mysticRiver = new Node($client);
	$mysticRiver->setProperty('title', 'Mystic River')->save();

	$keanu->relateTo($matrix, 'IN')->save();
	$laurence->relateTo($matrix, 'IN')->save();

	$laurence->relateTo($higherLearning, 'IN')->save();
	$jennifer->relateTo($higherLearning, 'IN')->save();

	$laurence->relateTo($mysticRiver, 'IN')->save();
	$kevin->relateTo($mysticRiver, 'IN')->save();

// Find all actors in a movie
} else if ($cmd == 'actors') {
	$queryTemplate = "g.V.in('IN').uniqueObject.sort{it.name}.toList()";
	$query = new Gremlin\Query($client, $queryTemplate);
	$result = $query->getResultSet();
	
	foreach ($result as $row) {
		echo "* " . $row[0]->getProperty('name')."\n";
	}
}

