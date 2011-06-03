<?php
namespace Everyman\Neo4j;

/**
 * Class for communicating with an HTTP JSON endpoint
 */
class Transport
{
	protected $scheme = 'http';
	protected $host = 'localhost';
	protected $port = 7474;
	protected $path = '/db/data/';

	/**
	 * Set the host and port of the endpoint
	 *
	 * @param string $host
	 * @param integer $port
	 */
	public function __construct($host='localhost', $port=7474)
	{
		$this->host = $host;
		$this->port = $port;
	}

	/**
	 * Return the Neo4j REST endpoint
	 *
	 * @return string
	 */
	public function getEndpoint()
	{
		return "{$this->scheme}://{$this->host}:{$this->port}{$this->path}";
	}
}
