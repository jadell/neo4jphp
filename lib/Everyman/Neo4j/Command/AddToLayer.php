<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Exception,
	Everyman\Neo4j\PropertyContainer,
	Everyman\Neo4j\SpatialLayer;

/**
 * Add an entity to an a spatial layer
 */
class AddToLayer extends Command
{
	protected $layer    = null;
	protected $entity   = null;
	protected $key      = null;
	protected $value    = null;

	/**
	 * Set the layer to drive the command
	 *
	 * @param Client $client
	 * @param SpatialLayer $layer
	 * @param PropertyContainer $entity
	 * @param string $key
	 * @param string $value
	 */
	public function __construct(Client $client, SpatialLayer $layer, PropertyContainer $entity)
	{
		parent::__construct($client);
		$this->layer = $layer;
		$this->entity = $entity;
	}

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		if (!$this->entity || !$this->entity->hasId()) {
			throw new Exception('No entity to add to layer specified');
		}

		return array(
            'node'  => $this->getTransport()->getEndpoint().'/'. 'node' .'/'.$this->entity->getId(),
            'layer' => $this->layer->getName()
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
		return '/ext/SpatialPlugin/graphdb/addNodeToLayer';
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
			$this->throwException('Unable to add entity to spatial layer', $code, $headers, $data);
		}
		return true;
	}
}
