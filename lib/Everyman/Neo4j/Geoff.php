<?php
namespace Everyman\Neo4j;

/**
 * Implementation of GEOFF - Graph Export Object File Format
 * From http://py2neo.org/geoff
 * Available at http://github.com/nigelsmall/py2neo
 */
class Geoff
{
	protected $client = null;

	/**
	 * Build the importer/exported
	 *
	 * @param Client $client
	 */
	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	/**
	 * Dump path information to a GEOFF string or file
	 *
	 * @param mixed $paths a single Path object or an array of Path objects
	 * @param stream $handle
	 * @return mixed stream or string
	 */
	public function dump($paths, $handle=null)
	{
		$returnString = false;
		if (!$handle) {
			$returnString = true;
			$handle = fopen('data:text/plain,', 'w+');
		}

		$exporter = new Geoff\Exporter();
		$exporter->dump($paths, $handle);

		if ($returnString) {
			return stream_get_contents($handle, -1, 0);
		}
		return $handle;
	}

	/**
	 * Load a GEOFF string or file
	 *
	 * @param mixed $handle
	 * @param Batch $batch
	 * @return Batch
	 */
	public function load($handle, Batch $batch=null)
	{
		if (is_string($handle)) {
			$handle = fopen('data:text/plain,'.urlencode($handle), 'r');
		}

		$importer = new Geoff\Importer($this->client);
		return $importer->load($handle, $batch);
	}
}
