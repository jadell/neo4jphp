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
	protected $paths  = array();

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
		if (!$end || !$end->getId()) {
			throw new Exception('No end node id specified');
		}

		$endUri = $this->getTransport()->getEndpoint().'/node/'.$end->getId();
		$data['to'] = $endUri;
		$data['algorithm'] = 'shortestPath';
		
		$max = $this->finder->getMaxLength();
		if ($max) {
			$data['max_depth'] = $max;
		}
		
		$type = $this->finder->getType();
		$dir = $this->finder->getDirection();
		if ($type || $dir) {
			$rel = array();
			if ($type) {
				$rel['type'] = $type;
			}
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
		if (!$start || !$start->getId()) {
			throw new Exception('No start node id specified');
		}

		$nodeId = $start->getId();
		return "/node/{$nodeId}/paths";
	}

	/**
	 * Get the result array of paths
	 *
	 * @return array
	 */
	public function getResult()
	{
		return $this->paths;
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
			foreach ($data as $pathData) {
				$this->paths[] = $this->makePath($pathData);
			}
			return null;
		}
		return $code;
	}

	/**
	 * Parse data into a path object
	 *
	 * @param array $data
	 * @return Path
	 */
	protected function makePath($data)
	{
		$path = new Path($this->client);
		foreach ($data['relationships'] as $relUri) {
			$uriParts = explode('/', $relUri);
			$relId = array_pop($uriParts);
			$rel = $this->client->getRelationship($relId, true);
			$path->appendRelationship($rel);
		}

		return $path;
	}
}

