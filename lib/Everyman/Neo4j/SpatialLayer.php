<?php
namespace Everyman\Neo4j;

use Everyman\Neo4j\Client;

/**
 * Represents an entity that is a spatial layer
 */
class SpatialLayer
{
    /**
     * @var string The type of spatial layer
     */
    protected $type = null;
    /**
	 * @var string the label name
	 */
	protected $name = null;
    /**
	 * @var string The node property that contains the latitude. Default is 'lat'
	 */
	protected $lat;
    /**
	 * @var string The node property that contains the longitude. Default is 'lon'
	 */
	protected $lon;
	/**
	 * @var Client Our client
	 */
	protected $client = null;
    
    /**
     * Types of layer
     */
    const TypeSimplePoint = 'SimplePointLayer';
    
    /**
     * Build the container and set its client
     *  
     * @param \Everyman\Neo4j\Client $client
     * @param string $type
     * @param string $name
     * @param string $lat
     * @param string $lon
     * @throws \InvalidArgumentException
     */
	public function __construct(Client $client, $type, $name, $lat = 'lat', $lon = 'lon')
	{
		if (empty($name) || !(is_string($name) || is_numeric($name))) {
			throw new \InvalidArgumentException("SimplePointLayer name must be a string or number");
		}
        
        if (empty($lat) || !(is_string($lat) || is_numeric($lat))) {
			throw new \InvalidArgumentException("lat name must be a string");
		}
        
        if (empty($lon) || !(is_string($lon) || is_numeric($lon))) {
			throw new \InvalidArgumentException("lon name must be a string");
		}

		$this->setClient($client);
		$this->setName((string)$name);
        $this->setLat((string)$lat);
        $this->setLon((string)$lon);
        $this->setType((string)$type);
	}

	public function __sleep()
	{
		return array('name', 'layerClass', 'goemEncoder', 'goemEncoderConfig', 'createdTime');
	}

	/**
	 * Save this entity
	 *
	 * @return SpatialLayer
	 * @throws Exception on failure
	 */
	public function save()
    {
        return $this->client->saveLayer($this);    
    }
      
    /*
     * Add an entity to the layer
     * 
     * @param PropertyContainer $entity
     * @return boolean
     */
    public function add($entity)
    {
        return $this->client->addToLayer($this, $entity);
    }
    
	/*
     * Find nodes with the given layer within the specified distance and lat/long
     * 
     * @param float $pointX
     * @param float $pointY
     * @param float $distance
     */
    public function findNodesWithinDistance($pointX, $pointY, $distance)
    {
        return $this->client->findNodesWithinDistance($this, $pointX, $pointY, $distance);
    }
    
    /**
     * Search for nodes within a specified bounding box area
     * 
     * @param float $minX
     * @param float $maxX
     * @param float $minY
     * @param float $maxY
     * @return array
     */
    public function findNodesWithinBBox($minX, $maxX, $minY, $maxY)
    {
        return $this->client->findNodesInBBox($this, $minX, $maxX, $minY, $maxY);
    }
    
	/**
	 * Get the entity's client
	 *
	 * @return Client
	 */
	public function getClient()
	{
		return $this->client;
	}

	/**
	 * Set the entity's client
	 *
	 * @param Client $client
	 * @return SpatialLayer
	 */
	public function setClient(Client $client)
	{
		$this->client = $client;
        
		return $this;
	}
    
    /**
     * Get the layer name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the timestamp that this layer was created
     * 
     * @return type
     */
    public function getCreatedTime()
    {
        return $this->createdTime;
    }

    /**
     * Set the layer name
     * 
     * @param string $name
     * @return \Everyman\Neo4j\SpatialLayer
     */
    public function setName($name)
    {
        $this->name = $name;
        
        return $this;
    }

    /**
     * Set the geom encoder
     * 
     * @param string $geomEncoder
     * @return \Everyman\Neo4j\SpatialLayer
     */
    public function setGeomEncoder($geomEncoder)
    {
        $this->geomEncoder = $geomEncoder;
        
        return $this;
    }

    /**
     * Get the layer type
     * 
     * @return type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the type of spatial layer
     * 
     * @param string $type
     * @return \Everyman\Neo4j\SpatialLayer
     */
    public function setType($type)
    {
        $this->type = $type;
        
        return $this;
    }

    /**
     * @return string
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * @return string
     */
    public function getLon()
    {
        return $this->lon;
    }

    /**
     * Set the property name to use for latitude
     * 
     * @param string $lon
     * @return \Everyman\Neo4j\SpatialLayer
     */
    public function setLat($lat)
    {
        $this->lat = $lat;
        
        return $this;
    }

    /**
     * Set the property name to use for longitude
     * 
     * @param string $lon
     * @return \Everyman\Neo4j\SpatialLayer
     */
    public function setLon($lon)
    {
        $this->lon = $lon;
        
        return $this;
    }


}
