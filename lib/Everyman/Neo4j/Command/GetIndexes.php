<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Index,
	Everyman\Neo4j\Exception;

/**
 * Get all indexes of the requested type known by the server
 */
class GetIndexes extends Command
{
	protected $type = null;

	/**
	 * Set the type of index to retrieve
	 *
	 * @param Client $client
	 * @param string $type
	 */
	public function __construct(Client $client, $type)
	{
		parent::__construct($client);
		$this->type = $type;
	}

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		return null;
	}

	/**
	 * Return the transport method to call
	 *
	 * @return string
	 */
	protected function getMethod()
	{
		return 'get';
	}

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	protected function getPath()
	{
		$type = trim((string)$this->type);
		if ($type != Index::TypeNode && $type != Index::TypeRelationship) {
			throw new Exception('No type specified for index');
		}

		return '/index/'.$type;
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
			$this->throwException('Unable to retrieve indexes', $code, $headers, $data);
		}

		if (!$data) {
			$data = array();
		}

		$indexes = array();
		foreach ($data as $name => $indexData) {
			$indexes[] = new Index($this->client, $this->type, $name, $indexData);
		}
		return $indexes;
	}
}
