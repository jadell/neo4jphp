<?php
namespace Everyman\Neo4j\Index;

use Everyman\Neo4j\Client,
	Everyman\Neo4j\Index;

/**
 * Represents a relationship index in the database
 */
class RelationshipIndex extends Index
{
	/**
	 * Initialize the index
	 *
	 * @param Client $client
	 * @param string $name
	 * @param array  $config
	 */
	public function __construct(Client $client, $name, $config=array())
	{
		parent::__construct($client, Index::TypeRelationship, $name, $config);
	}
}
