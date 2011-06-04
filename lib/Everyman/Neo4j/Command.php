<?php
namespace Everyman\Neo4j;

/**
 * Abstract the parameters needed to make a request and parse the response
 */
interface Command
{
	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	public function getData();

	/**
	 * Return the transport method to call
	 *
	 * @return string
	 */
	public function getMethod();

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	public function getPath();

	/**
	 * Use the results in some way
	 *
	 * @param integer $code
	 * @param array   $headers
	 * @param array   $data
	 * @return integer on failure
	 */
	public function handleResult($code, $headers, $data);
}

