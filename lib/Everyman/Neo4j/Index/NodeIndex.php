<?php
namespace Everyman\Neo4j\Index;

use Everyman\Neo4j\Client,
	Everyman\Neo4j\Index;

/**
 * Represents a node index in the database
 */
class NodeIndex extends Index
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
		parent::__construct($client, Index::TypeNode, $name, $config);
	}
}
