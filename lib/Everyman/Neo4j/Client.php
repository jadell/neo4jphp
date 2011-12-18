<?php
namespace Everyman\Neo4j;

/**
 * Point of interaction between client and neo4j server
 */
class Client
{
	const ErrorUnknown       = 500;
	const ErrorBadRequest    = 400;
	const ErrorNotFound      = 404;
	const ErrorConflict      = 409;

	const RefNodeId = 0;

	protected $transport = null;
	protected $entityMapper = null;
	protected $entityCache = null;
	protected $serverInfo = null;
	protected $openBatch = null;

	/**
	 * Initialize the client
	 *
	 * @param mixed $transport Transport object or string hostname
	 * @param integer $port Ignored unless $transport is a hostname
	 */
	public function __construct($transport=null, $port=7474)
	{
		if ($transport === null) {
			$transport = new Transport();
		} elseif (is_string($transport)) {
			$transport = new Transport($transport, $port);
		}

		$this->setTransport($transport);
	}

	/**
	 * Add an entity to an index
	 *
	 * @param Index $index
	 * @param PropertyContainer $entity
	 * @param string $key
	 * @param string $value
	 * @return boolean
	 */
	public function addToIndex(Index $index, PropertyContainer $entity, $key, $value)
	{
		if ($this->openBatch) {
			$this->openBatch->addToIndex($index, $entity, $key, $value);
			return true;
		}

		return $this->runCommand(new Command\AddToIndex($this, $index, $entity, $key, $value));
	}

	/**
	 * Commit a batch of operations
	 *
	 * @param Batch $batch
	 * @return boolean true on success
	 */
	public function commitBatch(Batch $batch=null)
	{
		if (!$batch) {
			if (!$this->openBatch) {
				throw new Exception('No open batch to commit.');
			}
			$batch = $this->openBatch;
		}

		if ($batch === $this->openBatch) {
			$this->endBatch();
		}

		return $this->runCommand(new Command\Batch\Commit($this, $batch));
	}

	/**
	 * Delete the given index
	 *
	 * @param Index $index
	 * @return boolean
	 */
	public function deleteIndex(Index $index)
	{
		return $this->runCommand(new Command\DeleteIndex($this, $index));
	}

	/**
	 * Delete the given node
	 *
	 * @param Node $node
	 * @return boolean
	 */
	public function deleteNode(Node $node)
	{
		if ($this->openBatch) {
			$this->openBatch->delete($node);
			return true;
		}

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
		if ($this->openBatch) {
			$this->openBatch->delete($relationship);
			return true;
		}
		return $this->runCommand(new Command\DeleteRelationship($this, $relationship));
	}

	/**
	 * Detach the current open batch.
	 *
	 * The batch can still be committed via the batch returned
	 * by Client::startBatch()
	 *
	 * @return Client
	 */
	public function endBatch()
	{
		$this->openBatch = null;
		return $this;
	}

	/**
	 * Execute the given Cypher query and return the result
	 *        
	 * @param Cypher\Query $query A Cypher query, or a query template.
	 * @return Query\ResultSet
	 */
	public function executeCypherQuery(Cypher\Query $query)
	{
		$command = new Command\ExecuteCypherQuery($this, $query);
		return $this->runCommand($command);
	}

	/**
	 * Execute the given Gremlin query and return the result
	 *        
	 * @param Gremlin\Query $query
	 * @return Query\ResultSet
	 */
	public function executeGremlinQuery(Gremlin\Query $query)
	{
		$command = new Command\ExecuteGremlinQuery($this, $query);
		return $this->runCommand($command);
	}

	/**
	 * Execute a paged traversal and return the result
	 *        
	 * @param Pager $pager
	 * @return array
	 */
	public function executePagedTraversal(Pager $pager)
	{
		$command = new Command\ExecutePagedTraversal($this, $pager);
		return $this->runCommand($command);
	}

	/**
	 * Execute the given traversal and return the result
	 *        
	 * @param Traversal $traversal
	 * @param Node $startNode
	 * @param string $returnType
	 * @return array
	 */
	public function executeTraversal(Traversal $traversal, Node $startNode, $returnType)
	{
		$command = new Command\ExecuteTraversal($this, $traversal, $startNode, $returnType);
		return $this->runCommand($command);
	}

	/**
	 * Get the cache
	 *
	 * @return Cache\EntityCache
	 */
	public function getEntityCache()
	{
		if ($this->entityCache === null) {
			$this->setEntityCache(new Cache\EntityCache($this));
		}
		return $this->entityCache;
	}

	/**
	 * Get the entity mapper
	 *
	 * @return EntityMapper
	 */
	public function getEntityMapper()
	{
		if ($this->entityMapper === null) {
			$this->setEntityMapper(new EntityMapper($this));
		}
		return $this->entityMapper;
	}

	/**
	 * Get all indexes of the given type
	 *
	 * @param string $type
	 * @return mixed false on error, else an array of Index objects
	 */
	public function getIndexes($type)
	{
		$command = new Command\GetIndexes($this, $type);
		return $this->runCommand($command);
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
		$cached = $this->getEntityCache()->getCachedEntity($id, 'node');
		if ($cached) {
			return $cached;
		}

		$node = $this->makeNode();
		$node->setId($id);

		if ($force) {
			return $node;
		}

		try {
			$result = $this->loadNode($node);
		} catch (Exception $e) {
			if ($e->getCode() == self::ErrorNotFound) {
				return null;
			} else {
				throw $e;
			}
		}
		return $node;
	}

	/**
	 * Get all relationships on a node matching the criteria
	 *
	 * @param Node   $node
	 * @param mixed  $types a string or array of strings
	 * @param string $dir
	 * @return mixed false on error, else an array of Relationship objects
	 */
	public function getNodeRelationships(Node $node, $types=array(), $dir=null)
	{
		$command = new Command\GetNodeRelationships($this, $node, $types, $dir);
		return $this->runCommand($command);
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
		return $this->runCommand($command);
	}
	
	/**
	 * Retrieve the reference node (id: 0) from the server
	 *
	 * @return Node
	 */
	public function getReferenceNode()
	{
		return $this->getNode(self::RefNodeId);
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
		$cached = $this->getEntityCache()->getCachedEntity($id, 'relationship');
		if ($cached) {
			return $cached;
		}

		$rel = $this->makeRelationship();
		$rel->setId($id);

		if ($force) {
			return $rel;
		}


		try {
			$result = $this->loadRelationship($rel);
		} catch (Exception $e) {
			if ($e->getCode() == self::ErrorNotFound) {
				return null;
			} else {
				throw $e;
			}
		}
		return $rel;
	}

	/**
	 * Get a list of all relationship types on the server
	 *
	 * @return array
	 */
	public function getRelationshipTypes()
	{
		$command = new Command\GetRelationshipTypes($this);
		return $this->runCommand($command);
	}

	/**
	 * Retrieve information about the server
	 *
	 * @param boolean $force Don't use previous results
	 * @return array
	 */
	public function getServerInfo($force=false)
	{
		if ($this->serverInfo === null || $force) {
			$command = new Command\GetServerInfo($this);
			$this->serverInfo = $this->runCommand($command);
		}
		return $this->serverInfo;
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
		$cached = $this->getEntityCache()->getCachedEntity($node->getId(), 'node');
		if ($cached) {
			$node->setProperties($cached->getProperties());
			return true;
		}

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
		$cached = $this->getEntityCache()->getCachedEntity($rel->getId(), 'relationship');
		if ($cached) {
			$rel->setProperties($cached->getProperties());
			return true;
		}

		return $this->runCommand(new Command\GetRelationship($this, $rel));
	}

	/**
	 * Create a new node object bound to this client
	 *
	 * @return Node
	 */
	public function makeNode()
	{
		$node = new Node($this);
		return $node;
	}

	/**
	 * Create a new relationship object bound to this client
	 *
	 * @return Relationship
	 */
	public function makeRelationship()
	{
		$rel = new Relationship($this);
		return $rel;
	}

	/**
	 * Query an index using a query string.
	 * The default query language in Neo4j is Lucene
	 *
	 * @param Index $index
	 * @param string $query
	 * @return array
	 */
	public function queryIndex(Index $index, $query)
	{
		$command = new Command\QueryIndex($this, $index, $query);
		return $this->runCommand($command);
	}

	/**
	 * Remove an entity from an index
	 * If $value is not given, all reference of the entity for the key
	 * are removed.
	 * If $key is not given, all reference of the entity are removed.
	 *
	 * @param Index $index
	 * @param PropertyContainer $entity
	 * @param string $key
	 * @param string $value
	 * @return boolean
	 */
	public function removeFromIndex(Index $index, PropertyContainer $entity, $key=null, $value=null)
	{
		if ($this->openBatch) {
			$this->openBatch->removeFromIndex($index, $entity, $key, $value);
			return true;
		}

		return $this->runCommand(new Command\RemoveFromIndex($this, $index, $entity, $key, $value));
	}

	/**
	 * Save the given index
	 *
	 * @param Index $index
	 * @return boolean
	 */
	public function saveIndex(Index $index)
	{
		return $this->runCommand(new Command\SaveIndex($this, $index));
	}

	/**
	 * Save the given node
	 *
	 * @param Node $node
	 * @return boolean
	 */
	public function saveNode(Node $node)
	{
		if ($this->openBatch) {
			$this->openBatch->save($node);
			return true;
		}

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
		if ($this->openBatch) {
			$this->openBatch->save($rel);
			return true;
		}

		if ($rel->hasId()) {
			return $this->runCommand(new Command\UpdateRelationship($this, $rel));
		} else {
			return $this->runCommand(new Command\CreateRelationship($this, $rel));
		}
	}

	/**
	 * Search an index for matching entities
	 *
	 * @param Index $index
	 * @param string $key
	 * @param string $value
	 * @return array
	 */
	public function searchIndex(Index $index, $key, $value)
	{
		$command = new Command\SearchIndex($this, $index, $key, $value);
		return $this->runCommand($command);
	}

	/**
	 * Set the cache to use
	 *
	 * @param Cache\EntityCache $cache
	 */
	public function setEntityCache(Cache\EntityCache $cache)
	{
		$this->entityCache = $cache;
	}

	/**
	 * Set the entity mapper to use
	 *
	 * @param EntityMapper $mapper
	 */
	public function setEntityMapper(EntityMapper $mapper)
	{
		$this->entityMapper = $mapper;
	}

	/**
	 * Set the transport to use
	 *
	 * @param Transport $transport
	 */
	public function setTransport(Transport $transport)
	{
		$this->transport = $transport;
	}

	/**
	 * Start an implicit batch
	 *
	 * Any data manipulation calls that occur between this call
	 * and the subsequent Client::commitBatch() call will be
	 * wrapped in a batch operation.
	 *
	 * @return Batch
	 */
	public function startBatch()
	{
		if (!$this->openBatch) {
			$this->openBatch = new Batch($this);
		}
		return $this->openBatch;
	}

	/**
	 * Run a command that will talk to the transport
	 *
	 * @param Command $command
	 * @return mixed
	 */
	protected function runCommand(Command $command)
	{
		$result = $command->execute();
		return $result;
	}
}
