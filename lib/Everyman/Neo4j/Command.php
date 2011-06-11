<?php
namespace Everyman\Neo4j;

/**
 * Abstract the parameters needed to make a request and parse the response
 */
abstract class Command
{
	protected $client;

	/**
	 * Set the client
	 *
	 * @param Client $client
	 */
	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	abstract protected function getData();

	/**
	 * Return the transport method to call
	 *
	 * @return string
	 */
	abstract protected function getMethod();

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	abstract protected function getPath();

	/**
	 * Use the results in some way
	 *
	 * @param integer $code
	 * @param array   $headers
	 * @param array   $data
	 * @return integer on failure
	 */
	abstract protected function handleResult($code, $headers, $data);

	/**
	 * Run the command and return a value signalling the result
	 *
	 * @return integer on failure
	 */
	public function execute()
	{
		$method = $this->getMethod();
		$path = $this->getPath();
		$data = $this->getData();
		$result = $this->getTransport()->$method($path, $data);

		$resultCode = isset($result['code']) ? $result['code'] : Client::ErrorBadRequest;
		$resultHeaders = isset($result['headers']) ? $result['headers'] : array();
		$resultData = isset($result['data']) ? $result['data'] : array();
		$parseResult = $this->handleResult($resultCode,$resultHeaders,$resultData);

		return $parseResult;
	}

	/**
	 * Get an id from a URI
	 *
	 * @param string $uri
	 * @return integer
	 */
	protected function getIdFromUri($uri)
	{
		$uriParts = explode('/', $uri);
		return array_pop($uriParts);
	}

	/**
	 * Get the transport
	 *
	 * @return Transport
	 */
	protected function getTransport()
	{
		return $this->client->getTransport();
	}
}

