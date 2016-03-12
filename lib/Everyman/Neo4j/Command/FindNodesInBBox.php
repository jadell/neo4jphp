<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
    Everyman\Neo4j\SpatialLayer\SimplePointLayer,
    Everyman\Neo4j\Query\Row;

/**
 * Find nodes with the given bounding box
 */
class FindNodesInBBox extends Command
{
	protected $layer    = null;
	protected $minX     = null;
	protected $maxX     = null;
    protected $minY     = null;
    protected $maxY     = null;

	/**
     * 
     * @param \Everyman\Neo4j\Client $client
     * @param \Everyman\Neo4j\SpatialLayer\SimplePointLayer $layer
     * @param float $minX
     * @param float $maxX
     * @param float $minY
     * @param float $maxY
     */
	public function __construct(Client $client, SimplePointLayer $layer, $minX, $maxX, $minY, $maxY)
	{
		parent::__construct($client);

        // @todo - implement validation here for lat/long values
		$this->layer    = $layer;
		$this->minX     = $minX;
		$this->maxX     = $maxX;
        $this->minY     = $minY;
        $this->maxY     = $maxY;
	}

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		return array(
            'layer' => $this->layer->getName(),
            'minx'  => $this->minX,
            'maxx'  => $this->maxX,
            'miny'  => $this->minY,
            'maxy'  => $this->maxY
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
		return '/ext/SpatialPlugin/graphdb/findGeometriesInBBox';
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
			$this->throwException('Unable to retrieve nodes in layer within BBox', $code, $headers, $data);
		}

		return new Row($this->client, array_keys($data), $data);
	}
}
