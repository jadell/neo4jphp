<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Exception,
	Everyman\Neo4j\EntityMapper,
	Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Cypher\Query,
	Everyman\Neo4j\Query\ResultSet;

/**
 * Perform a query using the Cypher query language and return the results
 */
class ExecuteCypherQuery extends Command
{
	protected $query = null;

	/**
	 * Set the query to execute
	 *
	 * @param Client $client
	 * @param Query $query
	 */
	public function __construct(Client $client, Query $query)
	{
		parent::__construct($client);
		$this->query = $query;
	}

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		$data = array('query' => $this->query->getQuery());
		$params = $this->query->getParameters();
		if ($params) {
			$data['params'] = $params;
		}
		return $data;
	}

	/**
	 * Return the transport method to call
	 *
	 * @return string
	 */
	protected function getMethod()
	{
		return 'post';
	}

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	protected function getPath()
	{
		$url = $this->client->hasCapability(Client::CapabilityCypher);
		if (!$url) {
			throw new Exception('Cypher unavailable');
		}

		return preg_replace('/^.+\/db\/data/', '', $url);
	}

	/**
	 * Use the results
	 *
	 * @param integer $code
	 * @param array   $headers
	 * @param array   $data
	 * @return integer on failure
	 */
	protected function handleResult($code, $headers, $data)
	{
		if ((int)($code / 100) != 2) {
			$this->throwException('Unable to execute query', $code, $headers, $data);
		}

		return new ResultSet($this->client, $data);
	}
}
