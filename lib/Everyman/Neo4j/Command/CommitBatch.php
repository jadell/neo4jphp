<?php
namespace Everyman\Neo4j\Command;
use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Node,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Batch\Operation,
	Everyman\Neo4j\Batch;

/**
 * Commit a batch operation
 * @todo: Handle the case of empty body or body\data needing to be objects not arrays
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
			if ($op->reserve()) {
				$data = array_merge($data, $this->buildOperation($op));
			}
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
			foreach ($data as $result) {
				$operations[$result['id']]->handleResult($result);
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
	 * @param Operation $op
	 * @return array
	 */
	protected function buildOperation(Operation $op)
	{
		$operation = $op->getOperation();
		$entity = $op->getEntity();
		$opId = $op->getId();
	
		if ($operation == 'save') {
			$opData = $op->buildData();
		} else if ($operation == 'delete' && $entity instanceof Node) {
			$opData = $this->buildDeleteNodeOperation($entity, $opId);
		} else if ($operation == 'delete' && $entity instanceof Relationship) {
			$opData = $this->buildDeleteRelationshipOperation($entity, $opId);
		}
		
		foreach ($opData as &$singleOp) {
			$singleOp['method'] = strtoupper($singleOp['method']);
		}
		return $opData;
	}

	/**
	 * Delete a node
	 *
	 * @param Node $node
	 * @param integer $opId
	 * @return array
	 */
	protected function buildDeleteNodeOperation(Node $node, $opId)
	{
		$command = new DeleteNode($this->client, $node);
		$opData = array(array(
			'method' => $command->getMethod(),
			'to' => $command->getPath(),
			'id' => $opId,
		));
		return $opData;
	}
	
	/**
	 * Delete a relationship
	 *
	 * @param Relationship $rel
	 * @param integer $opId
	 * @return array
	 */
	protected function buildDeleteRelationshipOperation(Relationship $rel, $opId)
	{
		$command = new DeleteRelationship($this->client, $rel);
		$opData = array(array(
			'method' => $command->getMethod(),
			'to' => $command->getPath(),
			'id' => $opId,
		));
		return $opData;
	}
}

