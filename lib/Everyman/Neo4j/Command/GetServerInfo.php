<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client;

/**
 * Get information about the server
 */
class GetServerInfo extends Command
{
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
	 * Use the results
	 *
	 * @param integer $code
	 * @param array   $headers
	 * @param array   $data
	 * @return integer on failure
	 */
	protected function handleResult($code, $headers, $data)
	{
		if ((int)($code / 100) != 2) {
			$this->throwException('Unable to retrieve server info', $code, $headers, $data);
		}
		$data['version'] = $this->parseVersion($data['neo4j_version']);
		return $data;
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
		if (empty($parts[2])) {
			$versionInfo['release'] = 'GA';
		} else {
			$versionInfo['release'] = current(explode('-', $parts[2], 2));
		}
		return $versionInfo;
	}
}
