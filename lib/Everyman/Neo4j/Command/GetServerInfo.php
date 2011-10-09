<?php
namespace Everyman\Neo4j\Command;
use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client;

/**
 * Get information about the server
 */
class GetServerInfo extends Command
{
	protected $info  = array();

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		return null;
	}

	/**
	 * Return the transport method to call
	 *
	 * @return string
	 */
	protected function getMethod()
	{
		return 'get';
	}

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	protected function getPath()
	{
		return '/';
	}

	/**
	 * Get the result array of types
	 *
	 * @return array
	 */
	public function getResult()
	{
		return $this->info;
	}

	/**
	 * Use the results
	 *
	 * @param integer $code
	 * @param array   $headers
	 * @param array   $data
	 * @return integer on failure
	 */
	protected function handleResult($code, $headers, $data)
	{
		if ((int)($code / 100) == 2) {
			$this->info = $data;
			$this->info['version'] = $this->parseVersion($data['neo4j_version']);
			return null;
		}
		return $code;
	}

	/**
	 * Parse the version into usable bits
	 *
	 * @param string $fullVersion
	 * @return array
	 */
	protected function parseVersion($fullVersion)
	{
		$parts = explode('.', $fullVersion);
		$versionInfo = array(
			'full'  => $fullVersion,
			'major' => $parts[0],
			'minor' => $parts[1],
		);
		list($versionInfo['release'], $ignore) = explode('-', $parts[2], 2);
		return $versionInfo;
	}
}

