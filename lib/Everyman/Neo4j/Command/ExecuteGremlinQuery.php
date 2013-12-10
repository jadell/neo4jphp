<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\EntityMapper,
	Everyman\Neo4j\Exception,
	Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Gremlin\Query,
	Everyman\Neo4j\Query\ResultSet;

/**
 * Perform a query using the Gremlin DSL and return the results
 */
class ExecuteGremlinQuery extends Command
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
		$data = array('script' => $this->query->getQuery());
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
		$url = $this->client->hasCapability(Client::CapabilityGremlin);
		if (!$url) {
			throw new Exception('Gremlin unavailable');
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

		return new ResultSet($this->client, $this->normalizeData($data));
	}

	/**
	 * Normalize the data so a proper ResultSet can be built
	 * Normalized data has 'data' and 'columns' keys for result set.
	 *
	 * @param array $data
	 * @return array
	 */
	protected function normalizeData($data)
	{
		if (is_scalar($data)) {
			$data = array($data);
		}

		if (!array_key_exists('columns', $data)) {
			$columns = array(0);

			if (array_key_exists('self', $data)) {
				$data = array(array($data));
			} else {
				foreach ($data as $i => $entity) {
					$data[$i] = array($entity);
				}
			}

			$data = array(
				'columns' => $columns,
				'data' => $data
			);
		}

		return $data;
	}
}
