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
		$this->resetLastError();
		$nodeId = $node->getId();
		if (!$nodeId) {
			throw new Exception('No node id specified for delete');
		}

		$result = $this->transport->delete('/node/'.$nodeId);
		$code = $result['code'];

		if ((int)($code / 100) == 2) {
			return true;
		} else if ($code == 404) {
			$this->setLastError(self::ErrorNotFound);
		} else if ($code == 409) {
			$this->setLastError(self::ErrorConflict);
		}

		return false;
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
	 * Save the given node
	 *
	 * @param Node $node
	 * @return boolean
	 */
	public function saveNode(Node $node)
	{
		// Stub
	}

	/**
	 * Reset the last error
	 */
	protected function resetLastError()
	{
		$this->lastError = null;
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
