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

	const CapabilityCypher        = 'cypher';
	const CapabilityGremlin       = 'gremlin';
	const CapabilityLabel         = 'label';
	const CapabilityTransactions  = 'transactions';

	protected $transport = null;
	protected $entityMapper = null;
	protected $entityCache = null;
	protected $labelCache = null;
	protected $serverInfo = null;
	protected $openBatch = null;

	/**
	 * @var callable The node factory
	 */
	protected $nodeFactory = null;
	/**
	 * @var callable The relation factory
	 */
	protected $relFactory = null;

	/**
	 * Initialize the client
	 *
	 * @param mixed $transport Transport object or string hostname
	 * @param integer $port Ignored unless $transport is a hostname
	 */
	public function __construct($transport=null, $port=7474)
	{
		try {
			if ($transport === null) {
				$transport = new Transport\Curl();
			} else if (is_string($transport)) {
				$transport = new Transport\Curl($transport, $port);
			}
		} catch (Exception $e) {
			if ($transport === null) {
				$transport = new Transport\Stream();
			} else if (is_string($transport)) {
				$transport = new Transport\Stream($transport, $port);
			}
		}

		$this->setTransport($transport);
		$this->setNodeFactory(function (Client $client, $properties=array()) {
			return new Node($client);
		});
		$this->setRelationshipFactory(function (Client $client, $properties=array()) {
			return new Relationship($client);
		});

		$this->labelCache = new Cache\Variable();
	}

	/**
	 * Add a set of labels to a node
	 *
	 * @param Node  $node
	 * @param Label[] $labels list of Label objects to add
	 * @return Label[] of Label objects; the entire list of labels on the given node
	 *   including the ones just added
	 */
	public function addLabels(Node $node, $labels)
	{
		$command = new Command\AddLabels($this, $node, $labels);
		return $this->runCommand($command);
	}

	/**
	 * Add statements to a transaction, and optionally commit the transaction
	 *
	 * @param Transaction $transaction
	 * @param array $statements an array of Cypher\Query objects
	 * @param boolean $commit should this transaction be committed on this request?
	 * @return Query\ResultSet
	 */
	public function addStatementsToTransaction(Transaction $transaction, $statements=array(), $commit=false)
	{
		$command = new Command\AddStatementsToTransaction($this, $transaction, $statements, $commit);
		return $this->runCommand($command);
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
	 * Begin a Cypher transaction
	 *
	 * @return Transaction
	 */
	public function beginTransaction()
	{
		return new Transaction($this);
	}

	/**
	 * Commit a batch of operations
	 *
	 * @param Batch $batch
	 * @throws Exception
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

		if (count($batch->getOperations()) < 1) {
			return true;
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
	 * List the labels already saved on the server
	 *
	 * If a $node is given, only return labels for
	 * that node.
	 *
	 * @param Node $node
	 * @return array
	 */
	public function getLabels(Node $node=null)
	{
		$command = new Command\GetLabels($this, $node);
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
	 * @throws Exception
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
			$this->loadNode($node);
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
	 * Get the nodes matching the given label
	 *
	 * If a property and value are given, only return
	 * nodes where the given property equals the value
	 *
	 * @param Label  $label
	 * @param string $propertyName
	 * @param mixed  $propertyValue
	 * @return Query\Row
	 */
	public function getNodesForLabel(Label $label, $propertyName=null, $propertyValue=null)
	{
		$command = new Command\GetNodesForLabel($this, $label, $propertyName, $propertyValue);
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
	 * @throws Exception
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
			$this->loadRelationship($rel);
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
	 * Does the connected database have the requested capability?
	 *
	 * @param string $capability
	 * @return mixed true or string if yes, false otherwise
	 */
	public function hasCapability($capability)
	{
		$info = $this->getServerInfo();

		switch ($capability) {
			case self::CapabilityLabel:
			case self::CapabilityTransactions:
				return $info['version']['major'] > 1;

			case self::CapabilityCypher:
				if (isset($info['cypher'])) {
					return $info['cypher'];
				} else if (isset($info['extensions']['CypherPlugin']['execute_query'])) {
					return $info['extensions']['CypherPlugin']['execute_query'];
				}
				return false;

			case self::CapabilityGremlin:
				if (isset($info['extensions']['GremlinPlugin']['execute_script'])) {
					return $info['extensions']['GremlinPlugin']['execute_script'];
				}
				return false;

			default:
				return false;
		}
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
	 * Retrieve a Label object for the given name
	 *
	 * If the name has already been seen, the same
	 * Label object wil be returned, i. e. only one
	 * Label will exist per name.
	 *
	 * @param string $name
	 * @return Label
	 */
	public function makeLabel($name)
	{
		$label = $this->labelCache->get($name);
		if (!$label) {
			$label = new Label($this, $name);
			$this->labelCache->set($name, $label);
		}

		return $label;
	}

	/**
	 * Create a new node object bound to this client
	 *
	 * @param array $properties
	 * @throws Exception
	 * @return Node
	 */
	public function makeNode($properties=array())
	{
		$nodeFactory = $this->nodeFactory;
		$node = $nodeFactory($this, $properties);
		if (!($node instanceof Node)) {
			throw new Exception('Node factory did not return a Node object.');
		}
		return $node->setProperties($properties);
	}

	/**
	 * Create a new relationship object bound to this client
	 *
	 * @param array $properties
	 * @throws Exception
	 * @return Relationship
	 */
	public function makeRelationship($properties=array())
	{
		$relFactory = $this->relFactory;
		$rel = $relFactory($this, $properties);
		if (!($rel instanceof Relationship)) {
			throw new Exception('Relationship factory did not return a Relationship object.');
		}
		return $rel->setProperties($properties);
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
	 * Rollback the transaction
	 *
	 * @param Transaction $transaction
	 * @return mixed
	 */
	public function rollbackTransaction(Transaction $transaction)
	{
		$command = new Command\RollbackTransaction($this, $transaction);
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
	 * Remove a set of labels from a node
	 *
	 * @param Node  $node
	 * @param array $labels list of Label objects to remove
	 * @return array of Label objects; the entire list of labels on the given node
	 *   including the ones just added
	 */
	public function removeLabels(Node $node, $labels)
	{
		$command = new Command\RemoveLabels($this, $node, $labels);
		return $this->runCommand($command);
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
	 * Set the callback to use to create new Node objects
	 *
	 * Takes a callback of the signature callback(Client $client, $properties=array())
	 * and returns a new Node object.
	 * The properties can be used to determine what type of Node
	 * should be returned, but are not set by the factory function.
	 *
	 * @param callable $factory
	 * @throws Exception
	 * @return Client
	 */
	public function setNodeFactory($factory)
	{
		if (!is_callable($factory)) {
			throw new Exception('Node factory must be callable.');
		}

		$this->nodeFactory = $factory;
		return $this;
	}

	/**
	 * Set the callback to use to create new Relationship objects
	 *
	 * Takes a callback of the signature callback(Client $client, $properties=array())
	 * and returns a new Relationship object.
	 * The properties can be used to determine what type of Relationship
	 * should be returned, but are not set by the factory function.
	 *
	 * @param callable $factory
	 * @throws Exception
	 * @return Client
	 */
	public function setRelationshipFactory($factory)
	{
		if (!is_callable($factory)) {
			throw new Exception('Relationship factory must be callable.');
		}

		$this->relFactory = $factory;
		return $this;
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
