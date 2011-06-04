<?php
namespace Everyman\Neo4j\Command;
use Everyman\Neo4j\Command,
	Everyman\Neo4j\Node;

/**
 * Update a node
 */
class UpdateNode implements Command
{
	protected $node = null;

	/**
	 * Set the node to drive the command
	 *
	 * @param Node $node
	 */
	public function __construct(Node $node)
	{
		$this->node = $node;
	}

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	public function getData()
	{
		return $this->node->getProperties();
	}

	/**
	 * Return the transport method to call
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return 'put';
	}

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	public function getPath()
	{
		return '/node/'.$this->node->getId().'/properties';
	}

	/**
	 * Use the results
	 *
	 * @param integer $code
	 * @param array   $headers
	 * @param array   $data
	 * @return integer on failure
	 */
	public function handleResult($code, $headers, $data)
	{
		if ((int)($code / 100) == 2) {
			return null;
		}
		return $code;
	}
}

