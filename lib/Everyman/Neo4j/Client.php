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
		return $this->runCommand(new Command\DeleteNode($this, $node));
	}

	/**
	 * Delete the given relationship
	 *
	 * @param Relationship $relationship
	 * @return boolean
	 */
	public function deleteRelationship(Relationship $relationship)
	{
		return $this->runCommand(new Command\DeleteRelationship($this, $relationship));
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
	 * Using the $force option disables the check
	 * of whether or not the node exists and will
	 * return a Node with the given id even if it
	 * does not. 
	 *
	 * @param integer $id
	 * @param boolean $force
	 * @return Node
	 */
	public function getNode($id, $force=false)
	{
		$node = new Node($this);
		$node->setId($id);

		if ($force) {
			return $node;
		}

		$result = $this->loadNode($node);
		if ($result) {
			return $node;
		}
		return null;
	}

	/**
	 * Get all relationships on a node matching the criteria
	 *
	 * @param Node   $node
	 * @param string $dir
	 * @param mixed  $types a string or array of strings
	 * @return mixed false on error, else an array of Relationship objects
	 */
	public function getNodeRelationships(Node $node, $dir=null, $types=array())
	{
		$command = new Command\GetNodeRelationships($this, $node, $dir, $types);
		$result = $this->runCommand($command);
		if ($result) {
			return $command->getResult();
		} else {
			return false;
		}
	}

	/**
	 * Get an array of paths matching the finder's criteria
	 *
	 * @param PathFinder $finder
	 * @return array
	 */
	public function getPaths(PathFinder $finder)
	{
		$command = new Command\GetPaths($this, $finder);
		$result = $this->runCommand($command);
		if ($result) {
			return $command->getResult();
		} else {
			return false;
		}
	}
	
	/**
	 * Get the requested relationship
	 * Using the $force option disables the check
	 * of whether or not the relationship exists and will
	 * return a Relationship with the given id even if it
	 * does not. 
	 *
	 * @param integer $id
	 * @param boolean $force
	 * @return Relationship
	 */
	public function getRelationship($id, $force=false)
	{
		$rel = new Relationship($this);
		$rel->setId($id);

		if ($force) {
			return $rel;
		}

		$result = $this->loadRelationship($rel);
		if ($result) {
			return $rel;
		}
		return null;
	}

	/**
	 * Get the transport
	 *
	 * @return Transport
	 */
	public function getTransport()
	{
		return $this->transport;
	}

	/**
	 * Load the given node with data from the server
	 *
	 * @param Node $node
	 * @return boolean
	 */
	public function loadNode(Node $node)
	{
		return $this->runCommand(new Command\GetNode($this, $node));
	}

	/**
	 * Load the given relationship with data from the server
	 *
	 * @param Relationship $rel
	 * @return boolean
	 */
	public function loadRelationship(Relationship $rel)
	{
		return $this->runCommand(new Command\GetRelationship($this, $rel));
	}

	/**
	 * Save the given node
	 *
	 * @param Node $node
	 * @return boolean
	 */
	public function saveNode(Node $node)
	{
		if ($node->hasId()) {
			return $this->runCommand(new Command\UpdateNode($this, $node));
		} else {
			return $this->runCommand(new Command\CreateNode($this, $node));
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
		if ($rel->hasId()) {
			return $this->runCommand(new Command\UpdateRelationship($this, $rel));
		} else {
			return $this->runCommand(new Command\CreateRelationship($this, $rel));
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

		$result = $command->execute();
		if ($result) {
			$this->setLastError($result);
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
