<?php
namespace Everyman\Neo4j;

/**
 * Represents a query (e. g. Gremlin or Cypher)
 */
interface Query
{
	/**
	 * Get the query script
	 *
	 * @return string
	 */
	public function getQuery();

	/**
	 * Retrieve the query results
	 *
	 * @return Query\ResultSet
	 */
	public function getResultSet();
}
