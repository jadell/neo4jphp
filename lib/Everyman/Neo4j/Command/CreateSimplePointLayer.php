<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\SpatialLayer\SimplePointLayer;

/**
 * Create a SimplePointLayer
 */
class CreateSimplePointLayer extends Command
{
	protected $simplePointLayer = null;

	/**
	 * Set the simplePointLayer to drive the command
	 *
	 * @param Client $client
	 * @param Node $node
	 */
	public function __construct(Client $client, SimplePointLayer $simplePointLayer)
	{
		parent::__construct($client);
		$this->simplePointLayer = $simplePointLayer;
	}
    
    protected function getData()
    {
        return array(
            'layer' => $this->getName(),
            'lat'   => $this->getLat(),
            'lon'   => $this->getLon(),
        );
    }

    /**
	 * Return the name of the layer
	 *
	 * @return string
	 */
    protected function getName()
	{
		return $this->simplePointLayer->getName() ?: null;
	}
    
    /**
     * 
     * @return string
     */
    protected function getLat()
	{
		return $this->simplePointLayer->getLat() ?: null;
	}
    
    /**
     * 
     * @return string
     */
    protected function getLon()
	{
		return $this->simplePointLayer->getLon() ?: null;
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
		return '/ext/SpatialPlugin/graphdb/addSimplePointLayer';
	}

	/**
	 * Use the results
	 *
	 * @param integer $code
	 * @param array   $headers
	 * @param array   $data
	 * @return boolean true on success
	 * @throws Exception on failure
	 */
	protected function handleResult($code, $headers, $data)
	{
		if ((int)($code / 100) != 2) {
			$this->throwException('Unable to create SimplePointLayer', $code, $headers, $data);
		}

		//$nodeId = $this->getEntityMapper()->getIdFromUri($headers['Location']);
		//$this->node->setId($nodeId);
		//$this->getEntityCache()->setCachedEntity($this->node);
		return true;
	}
}
