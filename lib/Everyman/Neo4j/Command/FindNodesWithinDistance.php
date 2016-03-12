<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Query\Row,
    Everyman\Neo4j\SpatialLayer\SimplePointLayer;

/**
 * Find nodes with the given layer within the specified distance and lat/long
 */
class FindNodesWithinDistance extends Command
{
	protected $layer    = null;
	protected $pointX   = null;
	protected $pointY   = null;
    protected $distance = null;

	/**
     * 
     * @param \Everyman\Neo4j\Client $client
     * @param \Everyman\Neo4j\SimplePointLayer $layer
     * @param float $pointX
     * @param float $pointY
     * @param float $distance
     */
	public function __construct(Client $client, SimplePointLayer $layer, $pointX, $pointY, $distance)
	{
		parent::__construct($client);

        // @todo - implement validation here for lat/long values
		$this->layer    = $layer;
		$this->pointX   = $pointX;
		$this->pointY   = $pointY;
        $this->distance = $distance;
	}

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		return array(
            'layer'         => $this->layer->getName(),
            'pointX'        => $this->pointX,
            'pointY'        => $this->pointY,
            'distanceInKm'  => $this->distance
        );
	}

	/**
	 * Return the transport method to call
	 *
	 * @return string
	 */
	protected function getMethod()
	{
		return 'post';
	}

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	protected function getPath()
	{
		return '/ext/SpatialPlugin/graphdb/findGeometriesWithinDistance';
	}

	/**
	 * Use the results
	 *
	 * @param integer $code
	 * @param array   $headers
	 * @param array   $data
	 * @return Everyman\Neo4j\Query\Row
	 * @throws Exception on failure
	 */
	protected function handleResult($code, $headers, $data)
	{
        if ((int)($code / 100) != 2) {
			$this->throwException('Unable to retrieve nodes in layer within distance', $code, $headers, $data);
		}
        
		return new Row($this->client, array_keys($data), $data);
	}
}
