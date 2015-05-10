<?php
namespace Everyman\Neo4j\Query;

/**
 * Provides statistics on an executed Cypher query.
 */
class QueryStatistics
{

	/** @var int */
	private $relationshipsCreated;

	/** @var int */
	private $nodesDeleted;

	/** @var int */
	private $relationshipsDeleted;

	/** @var int */
	private $indexesAdded;

	/** @var int */
	private $propertiesSet;

	/** @var int */
	private $constraintsRemoved;

	/** @var int */
	private $indexesRemoved;

	/** @var int */
	private $labelsRemoved;

	/** @var int */
	private $constraintsAdded;

	/** @var int */
	private $labelsAdded;

	/** @var int */
	private $nodesCreated;

	/** @var bool */
	private $containsUpdates;

	public function __construct(array $data)
	{
		$this->relationshipsCreated = $data['relationships_created'];
		$this->nodesDeleted         = $data['relationships_created'];
		$this->relationshipsDeleted = $data['relationships_created'];
		$this->indexesAdded         = $data['relationships_created'];
		$this->propertiesSet        = $data['relationships_created'];
		$this->constraintsRemoved   = $data['relationships_created'];
		$this->indexesRemoved       = $data['relationships_created'];
		$this->labelsRemoved        = $data['relationships_created'];
		$this->constraintsAdded     = $data['relationships_created'];
		$this->labelsAdded          = $data['relationships_created'];
		$this->nodesCreated         = $data['relationships_created'];
		$this->containsUpdates      = $data['relationships_created'];
	}

	/**
	 * @return int
	 */
	public function getRelationshipsCreated()
	{
		return $this->relationshipsCreated;
	}

	/**
	 * @return int
	 */
	public function getNodesDeleted()
	{
		return $this->nodesDeleted;
	}

	/**
	 * @return int
	 */
	public function getRelationshipsDeleted()
	{
		return $this->relationshipsDeleted;
	}

	/**
	 * @return int
	 */
	public function getIndexesAdded()
	{
		return $this->indexesAdded;
	}

	/**
	 * @return int
	 */
	public function getPropertiesSet()
	{
		return $this->propertiesSet;
	}

	/**
	 * @return int
	 */
	public function getConstraintsRemoved()
	{
		return $this->constraintsRemoved;
	}

	/**
	 * @return int
	 */
	public function getIndexesRemoved()
	{
		return $this->indexesRemoved;
	}

	/**
	 * @return int
	 */
	public function getLabelsRemoved()
	{
		return $this->labelsRemoved;
	}

	/**
	 * @return int
	 */
	public function getConstraintsAdded()
	{
		return $this->constraintsAdded;
	}

	/**
	 * @return int
	 */
	public function getLabelsAdded()
	{
		return $this->labelsAdded;
	}

	/**
	 * @return int
	 */
	public function getNodesCreated()
	{
		return $this->nodesCreated;
	}

	/**
	 * @return boolean
	 */
	public function doesContainUpdates()
	{
		return $this->containsUpdates;
	}
}