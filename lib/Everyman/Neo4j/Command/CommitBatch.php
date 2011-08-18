<?php
namespace Everyman\Neo4j\Command;
use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Node,
	Everyman\Neo4j\Batch;

/**
 * Commit a batch operation
 */
class CommitBatch extends Command
{
	protected $batch = null;

	/**
	 * Set the batch to drive the command
	 *
	 * @param Client $client
	 * @param Batch $batch
	 */
	public function __construct(Client $client, Batch $batch)
	{
		parent::__construct($client);
		$this->batch = $batch;
	}
	
	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		$operations = $this->batch->getOperations();
		$data = array();
		foreach ($operations as $op) {
			$data[] = $this->buildOperation($op);
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
		return '/batch';
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
		if ((int)($code / 100) == 2) {
			$operations = $this->batch->getOperations();
			foreach ($data as $i => $result) {
				$this->handleOperationResult($operations[$i], $result);
			}
			return null;
		}
		return $code;
	}
	
	//////////////////////////////////////////////////////////////////////
	// Operation builders ///////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////

	/**
	 * Build the data needed for a single operation
	 *
	 * @param array $op
	 * @return array
	 */
	protected function buildOperation($op)
	{
		$operation = $op['operation'];
		$entity = $op['entity'];
	
		if ($operation == 'save' && $entity instanceof Node) {
			if ($entity->hasId()) {
				$opData = $this->buildUpdateNodeOperation($entity);
			} else {
				$opData = $this->buildCreateNodeOperation($entity);
			}
		} else if ($operation == 'delete' && $entity instanceof Node) {
			$opData = $this->buildDeleteNodeOperation($entity);
		}
		
		$opData['method'] = strtoupper($opData['method']);
		return $opData;
	}
	
	/**
	 * Create a node
	 *
	 * @param Node $node
	 * @return array
	 */
	protected function buildCreateNodeOperation(Node $node)
	{
		$command = new CreateNode($this->client, $node);
		$opData = array(
			'method' => $command->getMethod(),
			'to' => $command->getPath(),
			'body' => $command->getData(),
		);
		return $opData;
	}
	
	/**
	 * Delete a node
	 *
	 * @param Node $node
	 * @return array
	 */
	protected function buildDeleteNodeOperation(Node $node)
	{
		$command = new DeleteNode($this->client, $node);
		$opData = array(
			'method' => $command->getMethod(),
			'to' => $command->getPath(),
		);
		return $opData;
	}
	
	/**
	 * Update a node
	 *
	 * @param Node $node
	 * @return array
	 */
	protected function buildUpdateNodeOperation(Node $node)
	{
		$command = new UpdateNode($this->client, $node);
		$opData = array(
			'method' => $command->getMethod(),
			'to' => $command->getPath(),
			'body' => $command->getData(),
		);
		return $opData;
	}

	//////////////////////////////////////////////////////////////////////
	// Result handlers //////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////

	/**
	 * Handle a single operation's results
	 *
	 * @param array $op
	 * @param array $result
	 */
	protected function handleOperationResult($op, $result)
	{
		$operation = $op['operation'];
		$entity = $op['entity'];
	
		if ($operation == 'save' && $entity instanceof Node) {
			if (!$entity->hasId()) {
				$opData = $this->handleCreateNodeOperationResult($entity, $result);
			}
		}
	}

	/**
	 * Handle node creation
	 *
	 * @param Node $node
	 * @param array $result
	 */
	protected function handleCreateNodeOperationResult(Node $node, $result)
	{
		$command = new CreateNode($this->client, $node);
		$command->handleResult(200, array('Location'=>$result['location']), array());
	}
}

