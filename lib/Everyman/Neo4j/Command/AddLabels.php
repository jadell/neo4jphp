<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Node,
	Everyman\Neo4j\Label;

/**
 * Add a set of labels to a node
 */
class AddLabels extends Command
{
	protected $node;
	protected $labels;

	/**
	 * Set the labels to add
	 *
	 * @param Client $client
	 * @param Node   $node
	 * @param array  $labels
	 */
	public function __construct(Client $client, Node $node, Array $labels)
	{
		if (!$labels) {
			throw new \InvalidArgumentException("No labels given to set on node");
		}
/*
		if (!$client->hasCapability(Client::CapabilityLabel)) {
			throw new \RuntimeException('The connected Neo4j version does not have label capability');
		}
*/
		parent::__construct($client);
		$this->node = $node;
		$this->labels = $labels;
	}

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		$data = array_map(function ($label) {
			if (!($label instanceof Label)) {
				throw new \InvalidArgumentException("Cannot set a non-label");
			}
			return $label->getName();
		}, $this->labels);

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
		$nodeId = $this->node->getId();
		if (!$nodeId) {
			throw new \InvalidArgumentException("Cannot set labels on an unsaved node");
		}
				
		return '/node/'.$nodeId.'/labels';
	}

	/**
	 * Use the results
	 *
	 * @param integer $code
	 * @param array   $headers
	 * @param array   $data
	 * @return boolean true on success
	 * @throws Exception on failure
	 */
	protected function handleResult($code, $headers, $data)
	{
		if ((int)($code / 100) != 2) {
			$this->throwException('Unable to add labels', $code, $headers, $data);
		}

		return true;
	}
}

