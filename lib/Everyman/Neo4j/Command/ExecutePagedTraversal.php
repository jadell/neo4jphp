<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Client,
	Everyman\Neo4j\Pager;

/**
 * Perform a paged traversal and return the results
 */
class ExecutePagedTraversal extends ExecuteTraversal
{
	protected $pager = null;

	/**
	 * Set the pager to execute
	 *
	 * @param Client $client
	 * @param Pager $pager
	 */
	public function __construct(Client $client, Pager $pager)
	{
		parent::__construct($client, $pager->getTraversal(), $pager->getStartNode(), $pager->getReturnType());
		$this->pager = $pager;
	}

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		return $this->pager->getId() ? null : parent::getData();
	}

	/**
	 * Return the transport method to call
	 *
	 * @return string
	 */
	protected function getMethod()
	{
		return $this->pager->getId() ? 'get' : parent::getMethod();
	}

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	protected function getPath()
	{
		$path = parent::getPath();
		$path = str_replace('traverse', 'paged/traverse', $path);

		$id = $this->pager->getId();
		if ($id) {
			$path .= "/{$id}";
		} else {
			$queryParams = array();
			$pageSize = $this->pager->getPageSize();
			$leaseTime = $this->pager->getLeaseTime();
			if ($pageSize) {
				$queryParams['pageSize'] = $pageSize;
			}
			if ($leaseTime) {
				$queryParams['leaseTime'] = $leaseTime;
			}
			$queryString = http_build_query($queryParams);
			$path .= $queryString ? "?{$queryString}" : '';
		}

		return $path;
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
		if (isset($headers['Location'])) {
			$traversalId = $this->getEntityMapper()->getIdFromUri($headers['Location']);
			$this->pager->setId($traversalId);
		}

		// No results found or end of result set indicated by not found
		if ($code == Client::ErrorNotFound) {
			return null;
		}
		return parent::handleResult($code, $headers, $data);
	}
}
