Neo4jPHP
========
Author: Josh Adell <josh.adell@gmail.com>  
Copyright (c) 2011-2012

PHP Wrapper for the Neo4j graph database REST interface

In-depth documentation and examples: http://github.com/jadell/neo4jphp/wiki

API documentation: http://jadell.github.com/neo4jphp

[![Build Status](https://secure.travis-ci.org/jadell/neo4jphp.png?branch=master)](http://travis-ci.org/jadell/neo4jphp)

Install
-------

### Using Composer
1. From the command line `echo '{"require":{"everyman/neo4jphp":"dev-master"}}' > composer.json && composer install`
1. In your PHP script `require("vendor/autoload.php");`

Connection Test
---------------
Create a script named `neo4jphp_connect_test.php`:

```php
    <?php
    require('vendor/autoload.php');
    
    $client = new Everyman\Neo4j\Client('localhost', 7474);
    print_r($client->getServerInfo());
```

Change `localhost` or `7474` to the host name and port of your Neo4j instance.

Execute the script:

    > php neo4jphp_connect_test.php

If you see your server's information, then you have successfully connected!


Get Started
-----------
Full documentation on all the features of Neo4jPHP is available on the wiki: https://github.com/jadell/neo4jphp/wiki


Contributions
-------------
http://github.com/jadell/neo4jphp/graphs/contributors

All contributions are welcome! If you wish to contribute, please read the following guidelines:

* Before implementing new features, [open an issue](https://github.com/jadell/neo4jphp/issues) describing the feature.
* Include unit tests for any bug fixes or new features.
* Include only one bug fix or new feature per pull request.
* Make sure all unit tests run before submitting a pull request.
* Follow the coding style of the existing code: tabs for indentation, class/method braces on newlines, spaces after commas, etc.
* Contributing code means that you agree that any contributed code, documentation, or other artifacts may be released under the same license as the rest of the library.

### Quick Contributor Setup
Install the developer tools:

    > composer install --dev
    
After making your changes, run the unit tests and code style checker:

    > vendor/bin/phing ci
    
Run only unit tests:

    > vendor/bin/phing test
    
Run only style checker:

    > vendor/bin/phing cs

Pull requests will not be accepted unless all tests pass and all code meets the existing style guidelines.

### Special Thanks
* Jacob Hansson <jacob@voltvoodoo.com> - Cypher query support
* Nigel Small <nigel@nigelsmall.name> - GEOFF import/export
  * [http://py2neo.org/](http://py2neo.org/)


Changes
-------

0.1.0

* Cypher and Gremlin results handle nested arrays of nodes/relationships
* Batch request with no operations succeeds
* Delete index where index does not exist succeeds

0.0.7-beta

* Retrieve reference node in one operation
* Find and return only the first matching relationship
* Optionally use HTTPS and basic authentication
* Keep index configuration when retrieved from server
* Add Memcache caching plugin
* Do not allow use if cUrl is not detected
* PHAR is uncompressed by default

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
