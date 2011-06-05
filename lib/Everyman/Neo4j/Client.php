<?php
namespace Everyman\Neo4j;

/**
 * Point of interaction between client and neo4j server
 */
class Client
{
	const ErrorBadRequest    = 400;
	const ErrorNotFound      = 404;
	const ErrorConflict      = 409;

	protected $transport = null;
	protected $lastError = null;

	/**
	 * Initialize the client
	 *
	 * @param Transport $transport
	 */
	public function __construct(Transport $transport)
	{
		$this->transport = $transport;
	}

	/**
	 * Delete the given node
	 *
	 * @param Node $node
	 * @return boolean
	 */
	public function deleteNode(Node $node)
	{
		return $this->runCommand(new Command\DeleteNode($node));
	}

	/**
	 * Delete the given relationship
	 *
	 * @param Relationship $relationship
	 * @return boolean
	 */
	public function deleteRelationship(Relationship $relationship)
	{
		return $this->runCommand(new Command\DeleteRelationship($relationship));
	}

	/**
	 * Get the endpoint
	 *
	 * @return string
	 */
	public function getEndpoint()
	{
		return $this->transport->getEndpoint();
	}

	/**
	 * Get the last error generated
	 *
	 * @return integer
	 */
	public function getLastError()
	{
		return $this->lastError;
	}

	/**
	 * Get the requested node
	 *
	 * @param integer $id
	 * @return Node
	 */
	public function getNode($id)
	{
		$node = new Node($this);
		$node->setId($id);
		$result = $this->loadNode($node);
		if ($result) {
			return $node;
		}
		return null;
	}

	/**
	 * Get the requested relationship
	 *
	 * @param integer $id
	 * @return Relationship
	 */
	public function getRelationship($id)
	{
		$rel = new Relationship($this);
		$rel->setId($id);
		$result = $this->loadRelationship($rel);
		if ($result) {
			return $rel;
		}
		return null;
	}

	/**
	 * Load the given node with data from the server
	 *
	 * @param Node $node
	 * @return boolean
	 */
	public function loadNode(Node $node)
	{
		return $this->runCommand(new Command\GetNode($node));
	}

	/**
	 * Load the given relationship with data from the server
	 *
	 * @param Relationship $rel
	 * @return boolean
	 */
	public function loadRelationship(Relationship $rel)
	{
		return $this->runCommand(new Command\GetRelationship($rel));
	}

	/**
	 * Save the given node
	 *
	 * @param Node $node
	 * @return boolean
	 */
	public function saveNode(Node $node)
	{
		if ($node->getId()) {
			return $this->runCommand(new Command\UpdateNode($node));
		} else {
			return $this->runCommand(new Command\CreateNode($node));
		}
	}

	/**
	 * Save the given relationship
	 *
	 * @param Relationship $rel
	 * @return boolean
	 */
	public function saveRelationship(Relationship $rel)
	{
		if ($rel->getId()) {
			return $this->runCommand(new Command\UpdateRelationship($rel));
		} else {
			return $this->runCommand(new Command\CreateRelationship($rel));
		}
	}

	/**
	 * Reset the last error
	 */
	protected function resetLastError()
	{
		$this->lastError = null;
	}

	/**
	 * Run a command that will talk to the transport
	 *
	 * @param Command $command
	 * @return boolean
	 */
	protected function runCommand(Command $command)
	{
		$this->resetLastError();

		$method = $command->getMethod();
		$path = $command->getPath();
		$data = $command->getData();
		$result = $this->transport->$method($path, $data);

		$resultCode = isset($result['code']) ? $result['code'] : self::ErrorBadRequest;
		$resultHeaders = isset($result['headers']) ? $result['headers'] : array();
		$resultData = isset($result['data']) ? $result['data'] : array();
		$parseResult = $command->handleResult($resultCode,$resultHeaders,$resultData);

		if ($parseResult) {
			$this->setLastError($parseResult);
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Set an error condition
	 *
	 * @param integer $error
	 */
	protected function setLastError($error)
	{
		$this->lastError = $error;
	}
}
