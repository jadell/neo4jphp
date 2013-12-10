<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Exception,
	Everyman\Neo4j\Index;

/**
 * Create an index
 */
class DeleteIndex extends Command
{
	protected $index = null;

	/**
	 * Set the index to drive the command
	 *
	 * @param Client $client
	 * @param Index $index
	 */
	public function __construct(Client $client, Index $index)
	{
		parent::__construct($client);
		$this->index = $index;
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
		return 'delete';
	}

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	protected function getPath()
	{
		$type = trim((string)$this->index->getType());
		if ($type != Index::TypeNode && $type != Index::TypeRelationship) {
			throw new Exception('No type specified for index');
		}

		$name = trim((string)$this->index->getName());
		if (!$name) {
			throw new Exception('No name specified for index');
		}
		$name = rawurlencode($name);

		return '/index/'.$type.'/'.$name;
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
		if ((int)($code / 100) != 2 && (int)$code != 404) {
			$this->throwException('Unable to delete index', $code, $headers, $data);
		}
		return true;
	}
}
