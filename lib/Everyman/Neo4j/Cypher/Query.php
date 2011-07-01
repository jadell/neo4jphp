<?php
namespace Everyman\Neo4j\Cypher;

use Everyman\Neo4j\Client;

/**
 * Represents a Cypher query string and variables
 * Query the database using Cypher. For query syntax, please refer
 * to the Cypher documentation for your server version.
 *
 * Latest documentation:
 * http://docs.neo4j.org/chunked/snapshot/cypher-query-lang.html
 */
class Query
{
	protected $client = null;
	protected $template = null;
	protected $vars = array();

	protected $assembler = null;

	protected $result = null;

	/**
	 * Set the template to use
	 *
	 * @param Client $client
	 * @param string $template A Cypher query string or template
	 * @param object $vars Replacement variables. If you pass
	 *        one or more of these, the $template parameter will be used as a 
	 *        template. All occurrences of '?' in the template will be replaced
	 *        with these variables, in order of occurrence.
	 *        Template variable values must be string or numeric.
	 */
	public function __construct(Client $client, $template, $vars=array())
	{
		$this->client = $client;
		$this->template = $template;
		$this->vars = $vars;
	}

	/**
	 * Get the fully assembled query string
	 *
	 * @return string
	 */
	public function getAssembledQuery()
	{
		$query = $this->getQueryAssembler()->assembleQuery(array_merge(array($this->template), $this->vars));
		return $query;
	}

	/**
	 * Retrieve the query results
	 *
	 * @return ResultSet
	 */
	public function getResultSet()
	{
		if ($this->result === null) {
			$this->result = $this->client->executeCypherQuery($this);
		}

		return $this->result;
	}

	/**
	 * Get the query assembler to use
	 *
	 * @return QueryAssembler
	 */
	protected function getQueryAssembler()
	{
		if ($this->assembler === null) {
			$this->assembler = new QueryAssembler();
		}
		return $this->assembler;
	}
}
