<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Exception,
	Everyman\Neo4j\PathFinder,
	Everyman\Neo4j\Path;

/**
 * Find paths from one node to another
 */
class GetPaths extends Command
{
	protected $finder = null;

	/**
	 * Set the parameters to search
	 *
	 * @param Client     $client
	 * @param PathFinder $finder
	 */
	public function __construct(Client $client, PathFinder $finder)
	{
		parent::__construct($client);

		$this->finder = $finder;
	}

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		$data = array();

		$end = $this->finder->getEndNode();
		if (!$end || !$end->hasId()) {
			throw new Exception('No end node id specified');
		}

		$endUri = $this->getTransport()->getEndpoint().'/node/'.$end->getId();
		$data['to'] = $endUri;

		$algo = $this->finder->getAlgorithm();
		if ($algo == PathFinder::AlgoDijkstra) {
			$property = $this->finder->getCostProperty();
			if (!$property) {
				throw new Exception('No cost property specified for Dijkstra path search');
			}
			$data['cost_property'] = $property;
			$data['cost property'] = $property;

			$cost = $this->finder->getDefaultCost();
			if ($cost) {
				$data['default_cost'] = $cost;
				$data['default cost'] = $cost;
			}
		}
		$data['algorithm'] = $algo;



		$max = $this->finder->getMaxDepth();
		if (!$max) {
			$max = 1;
		}
		$data['max_depth'] = $max;
		$data['max depth'] = $max;

		$type = $this->finder->getType();
		$dir = $this->finder->getDirection();
		if ($dir && !$type) {
			throw new Exception('No relationship type specified');
		} else if ($type) {
			$rel = array('type'=>$type);
			if ($dir) {
				$rel['direction'] = $dir;
			}
			$data['relationships'] = $rel;
		}

		return $data;
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
		$start = $this->finder->getStartNode();
		if (!$start || !$start->hasId()) {
			throw new Exception('No start node id specified');
		}

		return '/node/'.$start->getId().'/paths';
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
			$this->throwException('Unable to retrieve paths', $code, $headers, $data);
		}

		$paths = array();
		foreach ($data as $pathData) {
			$paths[] = $this->getEntityMapper()->populatePath(new Path($this->client), $pathData);
		}
		return $paths;
	}
}
