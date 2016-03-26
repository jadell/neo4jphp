<?php
namespace Everyman\Neo4j\SpatialLayer;

use Everyman\Neo4j\SpatialLayer,
    Everyman\Neo4j\Client;

/**
 * Represents a Simple Point Layer
 *
 * Simple Point Layer needs can be created/retrieved
 * (doesn't have to exist first)
 */
class SimplePointLayer extends SpatialLayer
{
    /**
	 * Build the SimplePointLayer and set its client, name, lat and lon
	 *
	 * @param Everyman\Neo4j\Client $client
	 * @param string $name
     * @param string $lat
     * @param string $lon
	 * @throws \InvalidArgumentException
	 */
	public function __construct(Client $client, $name, $lat = 'lat', $lon = 'lon')
	{
		parent::__construct($client, self::TypeSimplePoint, $name, $lat, $lon);
	}
}
