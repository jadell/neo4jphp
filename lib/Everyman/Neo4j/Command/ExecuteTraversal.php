<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Exception,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Traversal,
	Everyman\Neo4j\Path,
	Everyman\Neo4j\Node;

/**
 * Perform a traversal and return the results
 */
class ExecuteTraversal extends Command
{
	protected $traversal = null;
	protected $node = null;
	protected $returnType = null;

	protected $results;

	/**
	 * Set the traversal to execute
	 *
	 * @param Client $client
	 * @param Traversal $traversal
	 * @param Node $node
	 * @param string $returnType
	 */
	public function __construct(Client $client, Traversal $traversal, Node $node, $returnType)
	{
		parent::__construct($client);
		$this->traversal = $traversal;
		$this->node = $node;
		$this->returnType = $returnType;
	}

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		$data = array();

		$order = $this->traversal->getOrder();
		if ($order) {
			$data['order'] = $order;
		}

		$uniqueness = $this->traversal->getUniqueness();
		if ($uniqueness) {
			$data['uniqueness'] = $uniqueness;
		}

		$maxDepth = $this->traversal->getMaxDepth();
		if ($maxDepth) {
			$data['max_depth'] = $maxDepth;
		}

		$relationships = $this->traversal->getRelationships();
		if (count($relationships) > 0) {
			$data['relationships'] = $relationships;
		}

		$prune = $this->traversal->getPruneEvaluator();
		if ($prune) {
			if ($prune['language'] == Traversal::Builtin) {
				$prune['name'] = $prune['body'];
				unset($prune['body']);
			}
			$data['prune_evaluator'] = $prune;
		}

		$filter = $this->traversal->getReturnFilter();
		if ($filter) {
			if ($filter['language'] == Traversal::Builtin) {
				$filter['name'] = $filter['body'];
				unset($filter['body']);
			}
			$data['return_filter'] = $filter;
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
		if (!$this->node->hasId()) {
			throw new Exception('No node id specified');
		}

		if ($this->returnType != Traversal::ReturnTypeNode
			&& $this->returnType != Traversal::ReturnTypeRelationship
			&& $this->returnType != Traversal::ReturnTypePath
			&& $this->returnType != Traversal::ReturnTypeFullPath) {
			throw new Exception('No return type specified for traversal');
		}

		return '/node/'.$this->node->getId().'/traverse/'.$this->returnType;
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
			$this->throwException('Unable to execute traversal', $code, $headers, $data);
		}

		$this->results = array();
		if ($this->returnType == Traversal::ReturnTypeNode) {
			$this->handleNodes($data);
		} else if ($this->returnType == Traversal::ReturnTypeRelationship) {
			$this->handleRelationships($data);
		} else if ($this->returnType == Traversal::ReturnTypePath) {
			$this->handlePaths($data);
		} else if ($this->returnType == Traversal::ReturnTypeFullPath) {
			$this->handlePaths($data, true);
		}
		return $this->results;
	}

	/**
	 * Handle nodes
	 *
	 * @param array $data
	 */
	protected function handleNodes($data)
	{
		foreach ($data as $nodeData) {
			$this->results[] = $this->getEntityMapper()->makeNode($nodeData);
		}
	}

	/**
	 * Handle relationships
	 *
	 * @param array $data
	 */
	protected function handleRelationships($data)
	{
		foreach ($data as $relData) {
			$this->results[] = $this->getEntityMapper()->makeRelationship($relData);
		}
	}

	/**
	 * Handle paths
	 *
	 * @param array   $data
	 * @param boolean $full
	 */
	protected function handlePaths($data, $full=false)
	{
		foreach ($data as $pathData) {
			foreach ($data as $pathData) {
				$this->results[] = $this->getEntityMapper()->populatePath(new Path($this->client), $pathData, $full);
			}
		}
	}
}
