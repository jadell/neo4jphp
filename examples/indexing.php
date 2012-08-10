#!/usr/bin/env php
<?php
use Everyman\Neo4j\Client,
    Everyman\Neo4j\Index\NodeIndex,
    Everyman\Neo4j\Index\RelationshipIndex,
    Everyman\Neo4j\Index\NodeFulltextIndex,
    Everyman\Neo4j\Node,
    Everyman\Neo4j\Batch;

require_once 'example_bootstrap.php';

$client = new Client();

$actorIndex = new NodeIndex($client, 'actors');
$roleIndex = new RelationshipIndex($client, 'roles');
$plotIndex = new NodeFulltextIndex($client, 'plots');
$plotIndex->save();

$leslie = $client->makeNode()
	->setProperty('name', 'Leslie Nielsen')
	->save();

$airplane = $client->makeNode()
	->setProperty('title', 'Airplane')
	->save();

$rumack = $leslie->relateTo($airplane, 'PLAYED')
	->setProperty('character', 'Dr. Rumack')
	->save();

$actorIndex->add($leslie, 'name', $leslie->getProperty('name'));
$roleIndex->add($rumack, 'character', $rumack->getProperty('character'));
$plotIndex->add($airplane, 'synopsis', 'An airplane crew takes ill. Surely the only person capable of landing the plane is an ex-pilot afraid to fly. But don\'t call him Shirley.');

echo $actorIndex->queryOne('name:Leslie*')->getProperty('name') . "\n";
echo $roleIndex->queryOne('character:*u*')->getProperty('character') . "\n";
echo $plotIndex->queryOne('synopsis:lend~0.2')->getProperty('title') . "\n";


