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
	 * Load a GEOFF string or file
	 *
	 * @param mixed $handle
	 * @param Batch $batch
	 * @return Batch
	 */
	public function load($handle, Batch $batch=null)
	{
		if (is_string($handle)) {
			if (file_exists($handle)) {
				$handle = fopen($handle, 'r');
			} else {
				$handle = fopen('data:text/plain,'.urlencode($handle), 'r');
			}
		}

		$importer = new Geoff\Importer($this->client);
		return $importer->load($handle, $batch);
	}
}
