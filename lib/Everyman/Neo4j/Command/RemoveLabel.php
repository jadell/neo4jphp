<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Exception,
	Everyman\Neo4j\Label,
	Everyman\Neo4j\Node;

/**
 * Add or remove a set of labels on a node
 *
 * @todo: Don't extend ExecuteCypherQuery, extract and use a more generic Command interface
 *        that proxies to an ExecuteCypherQuery command
 */
class RemoveLabel extends Command
{
	protected $label = null;
	protected $node = null;

	/**
	 * Set the relationship to drive the command
	 *
	 * @param Client $client
	 * @param Relationship $rel
	 */
	public function __construct(Client $client, Node $node, $label)
	{
		parent::__construct($client);
		$this->label = $label;
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
		return 'delete';
	}

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	protected function getPath()
	{
		$labelName = rawurlencode($this->label->getName());
		return '/node/'.$this->node->getId().'/labels/'.$labelName;
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
