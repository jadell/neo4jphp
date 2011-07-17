Neo4jPHP
========
Author: Josh Adell <josh.adell@gmail.com>  
Copyright (c) 2011  

PHP Wrapper for the Neo4j graph database REST interface

Install
-------
Copy or symlink the `lib/Everyman` directory into your `include_path` or autoloader path.


Examples
--------
"Real" usage examples can be found in the `examples` directory.

### Initialize the connection
    $client = new Client(new Transport());

### Create a node
    $node = new Node($client);
	$node->setProperty('name', 'John Smith')
		->setProperty('age', '29')
		->setProperty('role', 'admin')
		->save();

### Index a node
    $users = new Index($client);
	$users->add($node, 'name', $node->getProperty('name'));
	$users->add($node, 'foo', 'bar');

### Find a node
    $user = $users->findOne('name', 'John Smith');
	$allFooBars = $user->find('foo', 'bar');

### Create a relationship
    $relationship = $node->relateTo($otherNode, 'KNOWS');
    $relationship->setProperty('from', 'school')
        ->setProperty('how_long', '38 months')
        ->save();

### Find a path between two nodes
    $path = $node->findPathsTo($otherNode, 'KNOWS', Relationship::DirectionOut)
        ->maxDepth(4)
        ->getSinglePath();

### Traverse the graph
	$traversal = new Traversal($client);
	$traversal->setPruneEvaluator(Traversal::PruneNone)
		->setReturnFilter('javascript', 'return true;')
		->setOrder(Traversal::OrderBreadthFirst);
	$nodes = $traversal->getResults($node, Traversal::ReturnTypeNode);
	$firstRelationship = $traversal->getSingleResult($node, Traversal::ReturnTypeRelationship);
	$paths = $traversal->getResults($node, Traversal::ReturnTypePath);

### Paged traversal
	$pager = new Pager($traversal, $node, Traversal::ReturnTypeNode);
	$pager->setPageSize(10)
		->setLeaseTime(30);

	while ($results = $pager->getNextResults()) {
		foreach ($results as $node) {
			echo $node->getProperty('name');
		}
	}

### Setting a cache
    // By default, the caching back-end is set to `Cache\Null`.
    // New cache back-ends can be created by implementing the `Cache` interface.
    $cacheExpire = 30;
    $client->setCache(new Cache\Variable(), $cacheExpire);

	
API
---

### Transport

    __construct(string $host='localhost', integer $port=7474)
Create a new REST transport.

### Client

    __construct(Transport $transport)
Create a new Neo4j client.

    getLastError() : integer
Return any error code from the last operation.  Returns null if no error occurred.

    setCache(Cache $cache, integer $cacheTimeout)
Set the caching mechanism to use.  $cacheTimeout is indicated in seconds.  There are three built-in cache wrappers that can be used: `Cache\Null` which does not cache anything (this is the default if no cache is set); and `Cache\Variable` which holds all cached values in memory for the length of the process or request; and `Cache\Memcached` which uses the PHP Memcached extension to persist values across requests and processes.  New cache back-ends can be created by extending the `Cache` interface.

### Node

    __construct(Client $client)
Create a new Node instance.  Nodes are not saved to the server until `save` is called.

    delete() : boolean
Delete this node from the server.  Returns true on success, false otherwise.

    findPathsTo(Node $to, string $type=null, string $dir=null) : PathFinder
Return a PathFinder that can be used to get paths from the node to $to.  $dir must be one of `Relationship::DirectionAll`, `Relationship::DirectionIn`, `Relationship::DirectionOut`, or null.  If $dir is specified, $type must also be specified.

    getId() : integer
Return this node's unique identifier.

    getProperties() : array
Return an array of all properties set on this node, indexed by key.

    getProperty(string $property) : string
Return the value of the named property.  Returns null if the named property is not set.

    getRelationships($types=array(), $dir=null) : array
Return an array of Relationships that match the given criteria.  $dir must be one of `Relationship::DirectionAll`, `Relationship::DirectionIn`, `Relationship::DirectionOut`, or null.

    hasId() : boolean
Returns true if the node is identified, false otherwise.

    load() : boolean
Load this node's data from the server.  Returns true on success, false otherwise.

    relateTo(Node $to, string $type) : Relationship
Create a relationship to $to.  Note that the Relationship is not saved to the server until its `save` method is called.

    removeProperty(string $property) : Node
Removes the named property from the node.  Returns the node.

    save() : boolean
Save this node to the server.  Returns true on success, false otherwise.

    setId(integer $id) : Node
Set the id of this node before calling `load` to retrieve the node's data from the server.  Returns the node.

    setProperties(array $properties) : Node
Set multiple properties, indexed by key.  Returns the node.

    setProperty(string $property, mixed $value) : Node
Set the named property to the given value.  $value must be scalar.  Returns the node.

### Relationship

    __construct(Client $client)
Create a new Relationship instance.  Relationships are not saved to the server until `save` is called.

    delete() : boolean
Delete this relationship from the server.  Returns true on success, false otherwise.

    getId() : integer
Return this relationship's unique identifier.

    getEndNode() : Node
Return the node on the incoming end of this relationship.  Returns null of none is set.

    getProperties() : array
Return an array of all properties set on this relationship, indexed by key.

    getProperty(string $property) : string
Return the value of the named property.  Returns null if the named property is not set.

    getStartNode() : Node
Return the node on the outgoing end of this relationship.  Returns null of none is set.

    getType() : string
Return the type of relationship.

    hasId() : boolean
Returns true if the relationship is identified, false otherwise.

    load() : boolean
Load this relationships's data from the server.  Returns true on success, false otherwise.

    removeProperty(string $property) : Relationship
Removes the named property from the relationship.  Returns the relationship.

    save() : boolean
Save this relationship to the server.  Returns true on success, false otherwise.

    setEndNode(Node $end) : Relationship
Set the relationship's incoming node.  Returns the relationship.

    setId(integer $id) : Relationship
Set the id of this relationship before calling `load` to retrieve the relationship's data from the server.  Returns the relationship.

    setProperties(array $properties) : Relationship
Set multiple properties, indexed by key.  Returns the relationship.

    setProperty(string $property, mixed $value) : Relationship
Set the named property to the given value.  $value must be scalar.  Returns the relationship.

    setStartNode(Node $start) : Relationship
Set the relationship's outgoing node. Returns the relationship.

    setType($type) : Relationship
Set the type of relationship.  Returns the relationship.

### Index

    __construct(Client $client, string $type, string $name)
Create a new index.  Indexes are not saved to the server until `save` or `add` are called.  $type must be one of `Index::TypeNode` or `Index::TypeRelationship`

    add(mixed $entity, string $key, mixed $value) : boolean
Add a Node or Relationship to the index.  $value must be scalar.  If the index did not exist on the server previously, it is created.  Returns true on success, false otherwise.

    delete() : boolean
Remove the index from the server.  Returns true on success, false otherwise.

    find(string $key, mixed $value) : array
Find Nodes or Relationships where the named property has the given value.  $value must be scalar.

    findOne(string $key, mixed $value) : mixed
Find the first Node or Relationship where the named property has the given value.  $value must be scalar.  Returns null if no match is found.

    getName() : string
Return the index name.

    getType() : string
Return the index type, one of `Index::TypeNode` or `Index::TypeRelationship`.

    query(string $query) : array
Find Nodes or Relationships matching the given query (query language is Lucene).

    queryOne($query) : mixed
Find the first Node or Relationship matching the given query (query language is Lucene).  Returns null if no match is found.

    remove(mixed $entity, string $key=null, mixed $value=null) : boolean
Remove the given Node or Relationship from the index.  If given, $value must be scalar.

    save() : boolean
Save this index to the server.  Return true on success, false otherwise.

### Path

    __construct()
Create a new Path.  The default context for a Path is nodes.

    count() : integer
If current context is `Path::ContextNode`, returns the number of nodes in the Path.  Otherwise, returns the number of relationships in the Path.

Path implements the `Countable` interface, which means it can be used as `count($path)`.

    getContext() : string
Returns the current context, one of `Path::ContextNode` or `Path::ContextRelationship`.
	
    getEndNode() : Node
Return the Node at the end of the path.  Returns null if there are no nodes.

    getLength() : integer
Alias for `count`

    getIterator() : ArrayIterator
If current context is `Path::ContextNode`, returns the nodes in an ArrayIterator.  Otherwise, returns the relationships in an ArrayIterator.

Path implement the `IteratorAggregate` interface, which means it can be looped over in `foreach` loops.  If the current context is nodes, `foreach` will loop over the nodes in the path, otherwise it will loop over the relationships.

    getNodes() : array
Return the ordered array of Node objects that make up this path.

    getRelationships() : array
Return the ordered array of Relationship objects that make up this path.

    getStartNode() : Node
Return the Node at the beginning of the path.  Returns null if there are no nodes.
	
    setContext($context) : Path
Set whether `count` and `foreach` will refer to the nodes or relationships of this path.  $context should be one of `Path::ContextNode` or `Path::ContextRelationship`.  Returns the Path.

### PathFinder

    __construct(Client $client)
Create a new PathFinder object.

    getAlgorithm() : string
Return the current path search algorithm.  One of `PathFinder::AlgoShortest` (default), `PathFinder::AlgoAll`, `PathFinder::AlgoAllSimple` or `PathFinder::AlgoDijkstra`.

    getCostProperty() : string
Return the current relationship property to use to determine path cost.  Only used when `PathFinder::AlgoDijkstra` is the search algorithm.

    getDefaultCost() : numeric
Return the default cost to assign relationships without a cost property.  Only used when `PathFinder::AlgoDijkstra` is the search algorithm.

    getDirection() : string
Return the current path finding direction, one of the `Relationship::Direction` constants.

    getEndNode() : Node
Return the Node to find paths to.

    getMaxDepth() : integer
Return the current maximum length for found paths.

    getPaths() : array
Return an array of Path objects matching the search criteria.

    getSinglePath() : Path
Return the first Path matching the search criteria.

    getStartNode() : Node
Return the Node to find paths from.

    getType() : string
Return the current relationship type to which path relationships will be limited.

    setAlgorithm(string $algorithm) : PathFinder
Set the path search algorithm.  One of `PathFinder::AlgoShortest` (default), `PathFinder::AlgoAll`, `PathFinder::AlgoAllSimple` or `PathFinder::AlgoDijkstra`.  Returns the PathFinder.

    setCostProperty(string $property) : PathFinder
Set the relationship property to use to determine path cost.  Only used when `PathFinder::AlgoDijkstra` is the search algorithm.  Returns the PathFinder.

    setDefaultCost(numeric $cost) : PathFinder
Set the default cost to assign relationships without a cost property.  Only used when `PathFinder::AlgoDijkstra` is the search algorithm.  Returns the PathFinder.

    setDirection($dir) : PathFinder
Set the path finding direction, one of the `Relationship::Direction` constants.  Returns the PathFinder.

    setEndNode(Node $end) : PathFinder
Set the Node to find paths to.  Returns the PathFinder.

    setMaxDepth($max) : PathFinder
Set the maximum length for found paths.  Returns the PathFinder.

    setStartNode(Node $start) : PathFinder
Set the Node to find paths from.  Returns the PathFinder.

    setType($type) : PathFinder
Set the relationship type to which path relationships will be limited.  Returns the PathFinder.


### Traversal

    __construct(Client $client)
Create a new Traversal object.

    addRelationship(string $type, string $direction=null) : Traversal
Add a new Relationship type to the Traversal.  $direction should be one of `Relationship::DirectionAll`, `Relationship::DirectionOut` or `Relationship::DirectionIn`.  Returns the Traversal.

    getMaxDepth() : integer
Return the maximum length to traverse before pruning.  If no prune evaluator is given, this defaults to 1.  If a prune evaluator is given, this value is ignored.

    getOrder() : string
Return the traversal order.  One of `Traversal::OrderDepthFirst` or `Traversal::OrderBreadthFirst`.

    getPruneEvaluator() : array
Returns the current prune evaluator function if set.  Array contains two elements 'language' and 'body'.

    getRelationships() : array
Return the set relationship types.  Each element is an array with elements 'type' and 'direction' (if direction is set.)

    getResults(Node $startNode, string $returnType) : array
Run the traversal and get the array of results.  $returnType is one of `Traversal::ReturnTypeNode` (returns an array of Node objects), `Traversal::ReturnTypeRelationship` (returns an array of Relationship objects), `Traversal::ReturnTypePath` or `Traversal::ReturnTypeFullPath` (returns an array of Path objects).

    getReturnFilter() : array
Returns the current return filter function if set.  Array contains two elements 'language' and 'body'.

    getSingleResult(Node $startNode, string $returnType)
Return the first result of the traversal.  $returnType is one of `Traversal::ReturnTypeNode` (returns an array of Node objects), `Traversal::ReturnTypeRelationship` (returns an array of Relationship objects), `Traversal::ReturnTypePath` or `Traversal::ReturnTypeFullPath` (returns an array of Path objects).

    getUniqueness() : string
Return the current uniqueness filter.  One of `Traversal::UniquenessNone`, `Traversal::UniquenessNodeGlobal`, `Traversal::UniquenessRelationshipGlobal`, `Traversal::UniquenessNodePath` or `Traversal::UniquenessRelationshipPath`.

    setMaxDepth(integer $max) : Traversal
Set the maximum length to traverse before pruning.  If a prune evaluator is given, this value is ignored.  Returns the Traversal.

    setOrder(string $order) : Traversal
Set the traversal order.  One of `Traversal::OrderDepthFirst` or `Traversal::OrderBreadthFirst`.  Returns the Traversal.

    setPruneEvaluator(string $language=null, string $body=null) : Traversal
Set the prune evaluator function. If language is the `Traversal::PruneNone` constant, the evaluator language will be set to 'builtin' and the body will be set to the value of the constant.  Returns the Traversal.

    setReturnFilter(string $language=null, string $body=null) : Traversal
Set the return filter function. If language is one of the `Traversal::ReturnAll` or `Traversal::ReturnAllButStart` constants, the filter language will be set to 'builtin' and the body will be set to the value of the constant.  Returns the Traversal.

    setUniqueness(string $uniqueness) : Traversal
Set the current uniqueness filter.  One of `Traversal::UniquenessNone`, `Traversal::UniquenessNodeGlobal`, `Traversal::UniquenessRelationshipGlobal`, `Traversal::UniquenessNodePath` or `Traversal::UniquenessRelationshipPath`.  Returns the Traversal.

To Do
-----
* Batch/transaction support? (experimental)
* Gremlin support

