Neo4jPHP
========
Author: Josh Adell <josh.adell@gmail.com>  
Copyright (c) 2011  

PHP Wrapper for the Neo4j graph database REST interface

In-depth documentation and examples: http://github.com/jadell/neo4jphp/wiki

API documentation: http://jadell.github.com/neo4jphp

Install
-------
1. Download latest PHAR from http://github.com/downloads/jadell/neo4jphp/neo4jphp.phar
2. `include` or `require` neo4jphp.phar in your project

Connection Test
---------------
From the command line, execute the following:

    > php neo4jphp.phar localhost

Change localhost to the host name of your Neo4j instance.  Port defaults to 7474, or can be specified as the second parameter after the host name.

Execute the following to see more command line options:

    > php neo4jphp.phar


Contributions
-------------
* Jacob Hansson <jacob@voltvoodoo.com> - Cypher query support
* Nigel Small <nigel@nigelsmall.name> - GEOFF import/export
  * [http://py2neo.org/](http://py2neo.org/)


Changes
-------

0.0.6-beta

* Create full-text indexes; easier instantiation of common index types
* Client can be initialized with a string and port instead of a Transport object
* Setting a `null` property has the same effect as removing the property
* Handle scalar values from Gremlin scripts properly
* Cypher and Gremlin queries can take an array of named parameters
* Cypher no longer uses positional parameters
* Use server info to determine Cypher plugin endpoint

0.0.5-beta

* Open a batch on the client to apply to all subsequent data manipulation calls
* Batch operations correctly set and update locally cached entities
* Method chaining on node and relationship save, load and delete
* Instantiate new nodes and relationships from the client
* Change to cache initialization; new EntityCache object

0.0.4-beta

* Client::getServerInfo() retrieves server information and connection test
* Add to index brought up to Neo4j server 1.5 specification
* Return paths from Cypher queries
* Properly encode URL entities
* Connection and transport errors throw exceptions
* Fix "unable to connect" bug from returning false positive
