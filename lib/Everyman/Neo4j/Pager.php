<?php
namespace Everyman\Neo4j;

/**
 * Holds the parameters for running a paged traversal
 */
class Pager
{
	protected $traversal = null;
	protected $startNode = null;
	protected $returnType = null;

	protected $id = null;
	protected $leaseTime = null;
	protected $pageSize = null;

	/**
	 * Set the traversal to paginate
	 *
	 * @param Traversal $traversal
	 * @param Node $startNode
	 * @param string $returnType
	 */
	public function __construct(Traversal $traversal, Node $startNode, $returnType)
	{
		$this->traversal = $traversal;
		$this->startNode = $startNode;
		$this->returnType = $returnType;
	}

	/**
	 * Get the paged traversal id
	 *
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Get the lease time, in secods
	 *
	 * @return integer
	 */
	public function getLeaseTime()
	{
		return $this->leaseTime;
	}

	/**
	 * Get the next page of results
	 * If the traversal hasn't been run yet, this will run it
	 *
	 * @return array
	 */
	public function getNextResults()
	{
		return $this->traversal->getClient()->executePagedTraversal($this);
	}

	/**
	 * Get the maximum result page set size
	 *
	 * @return integer
	 */
	public function getPageSize()
	{
		return $this->pageSize;
	}

	/**
	 * Get the return type
	 *
	 * @return string
	 */
	public function getReturnType()
	{
		return $this->returnType;
	}

	/**
	 * Return the start node of the traversal
	 *
	 * @return Node
	 */
	public function getStartNode()
	{
		return $this->startNode;
	}

	/**
	 * Get the traversal being paginated
	 *
	 * @return Traversal
	 */
	public function getTraversal()
	{
		return $this->traversal;
	}

	/**
	 * Set the paged traversal id
	 *
	 * @param string $id
	 * @return Traversal
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * Set the lease time
	 *
	 * @param integer $leaseTime
	 * @return Traversal
	 */
	public function setLeaseTime($leaseTime)
	{
		$this->leaseTime = $leaseTime;
		return $this;
	}

	/**
	 * Set the page size
	 *
	 * @param integer $pageSize
	 * @return Traversal
	 */
	public function setPageSize($pageSize)
	{
		$this->pageSize = $pageSize;
		return $this;
	}
}
