<?php
namespace Everyman\Neo4j;

/**
 * Implementation of GEOFF - Graph Export Object File Format
 * From http://py2neo.org/geoff
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
	 * Load a GEOFF string
	 *
	 * @param string $geoffString
	 * @param Batch $batch
	 * @return Batch
	 */
	public function loadString($geoffString, Batch $batch=null)
	{
		$handle = fopen('data:text/plain,'.urlencode($geoffString), 'r');
		$importer = new Geoff\Importer($this->client);
		return $importer->load($handle, $batch);
	}
}
