<?php
namespace Everyman\Neo4j\Gremlin;

use Everyman\Neo4j\Client;

/**
 * Represents a Gremlin query
 * Query the database using Gremlin syntax. For query syntax, please refer
 * to the Gremlin documentation for your server version.
 *
 * Latest documentation:
 * http://docs.neo4j.org/chunked/snapshot/gremlin-plugin.html
 */
class Query
{
	protected $client = null;
	protected $script = null;

	protected $result = null;

	/**
	 * Set the query script to use
	 *
	 * @param Client $client
	 * @param string $script A Gremlin query script
	 */
	public function __construct(Client $client, $script)
	{
		$this->client = $client;
		$this->script = $script;
	}

	/**
	 * Get the query script
	 *
	 * @return string
	 */
	public function getQuery()
	{
		return $this->script;
	}

	/**
	 * Retrieve the query results
	 *
	 * @return ResultSet
	 */
	public function getResultSet()
	{
		if ($this->result === null) {
			$this->result = $this->client->executeGremlinQuery($this);
		}

		return $this->result;
	}
}
