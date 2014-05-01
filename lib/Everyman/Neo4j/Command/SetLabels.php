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
class SetLabels extends Command
{
	protected $labels = null;
	protected $node = null;
	protected $add = false;

	/**
	 * Set the relationship to drive the command
	 *
	 * @param Client $client
	 * @param Relationship $rel
	 */
	public function __construct(Client $client, Node $node, $labels, $add=false)
	{
		parent::__construct($client);
		$this->labels = $labels;
		$this->node = $node;
		$this->add = $add;
	}


	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		$data = array();
		foreach ($this->labels as $label){
			if (!($label instanceof Label)) {
				throw new \InvalidArgumentException("Cannot set a non-label");
			}
			array_push($data, $label->getName());
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
		if($this->add == true)
			return 'post';
		else
			return 'put';
	}

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	protected function getPath()
	{
		return '/node/'.$this->node->getId().'/labels';
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
