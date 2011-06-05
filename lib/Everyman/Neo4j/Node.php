<?php
namespace Everyman\Neo4j;

/**
 * Represents a single node in the database
 *
 * @todo: Relationships
 * @todo: Paths
 */
class Node extends PropertyContainer
{
	protected $lazyLoad = true;

	/**
	 * Delete this node
	 *
	 * @return boolean
	 */
	public function delete()
	{
		return $this->client->deleteNode($this);
	}

	/**
	 * Load this node
	 *
	 * @return boolean
	 */
	public function load()
	{
		$this->lazyLoad = false;
		return $this->client->loadNode($this);
	}

	/**
	 * Save this node
	 *
	 * @return boolean
	 */
	public function save()
	{
		return $this->client->saveNode($this);
	}

	/**
	 * Should this node be lazy-loaded if necessary?
	 *
	 * @param boolean $doLazyLoad
	 * @return Node
	 */
	public function useLazyLoad($doLazyLoad)
	{
		$this->lazyLoad = (bool)$doLazyLoad;
		return $this;
	}

	/**
	 * Set up the properties array the first time we need it
	 * Lazy-load them from the client if we need to
	 *
	 * @return boolean true if we loaded for the first time
	 */
	protected function loadProperties()
	{
		$firstTime = parent::loadProperties();
		$shouldLoad = $this->getId() && $firstTime && $this->lazyLoad;
		if ($shouldLoad) {
			$this->load();
		}
		return $firstTime;
	}
}
