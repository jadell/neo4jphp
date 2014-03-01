<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Node;

/**
 * List all labels on the server
 */
class GetLabels extends Command
{
	protected $node;

	/**
	 * Optionally provide a node to limit to
	 *
	 * @param Client $client
	 * @param Node   $node
	 */
	public function __construct(Client $client, Node $node=null)
	{
		parent::__construct($client);
		$this->node = $node;
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
		if (!$this->client->hasCapability(Client::CapabilityLabel)) {
			throw new \RuntimeException('The connected Neo4j version does not have label capability');
		}

		$path = "/labels";
		if ($this->node) {
			$id = $this->node->getId();
			if (!is_numeric($id)) {
				throw new \InvalidArgumentException("Node given with no id");
			}

			$path = "/node/{$id}{$path}";
		}
		return $path;
	}

	/**
	 * Use the results
	 *
	 * @param integer $code
	 * @param array   $headers
	 * @param array   $data
	 * @return Row
	 * @throws Exception on failure
	 */
	protected function handleResult($code, $headers, $data)
	{
		if ((int)($code / 100) != 2) {
			$this->throwException('Unable to labels', $code, $headers, $data);
		}

		$labels = array_map(array($this->client, 'makeLabel'), $data);

		return $labels;
	}
}
