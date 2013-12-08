<?php
namespace Everyman\Neo4j;

/**
 * Represents a single node in the database
 */
class Node extends PropertyContainer
{
	/**
	 * @var Label[] Our labels, or `null` if not loaded
	 */
	protected $labels = null;


	/**
	 * @inheritdoc
	 * @param Client $client
	 * @return Node
	 */
	public function setClient(Client $client)
	{
		parent::setClient($client);
		// set the client of each label in case it's not set yet
		if ($this->labels) {
			foreach ($this->labels as $label) {
				if (!$label->getClient()) {
					$label->setClient($client);
				}
			}
		}
		return $this;
	}

	/**
	 * Add labels to this node
	 *
	 * @param array $labels
	 * @return array of all the Labels on this node, including those just added
	 */
	public function addLabels($labels)
	{
		$this->labels = $this->client->addLabels($this, $labels);
		return $this->labels;
	}

	/**
	 * Delete this node
	 *
	 * @return PropertyContainer
	 * @throws Exception on failure
	 */
	public function delete()
	{
		$this->client->deleteNode($this);
		return $this;
	}

	/**
	 * Find paths from this node to the given node
	 *
	 * @param Node $to
	 * @param string $type
	 * @param string $dir
	 * @return PathFinder
	 */
	public function findPathsTo(Node $to, $type=null, $dir=null)
	{
		$finder = new PathFinder($this->client);
		$finder->setStartNode($this);
		$finder->setEndNode($to);
		if ($dir) {
			$finder->setDirection($dir);
		}

		if ($type) {
			$finder->setType($type);
		}

		return $finder;
	}

	/**
	 * Get the first relationship of this node that matches the given criteria
	 *
	 * @param mixed  $types string or array of strings
	 * @param string $dir
	 * @return Relationship
	 */
	public function getFirstRelationship($types=array(), $dir=null)
	{
		$rels = $this->client->getNodeRelationships($this, $types, $dir);
		if (count($rels) < 1) {
			return null;
		}
		return $rels[0];
	}

	/**
	 * Get relationships of this node
	 *
	 * @param mixed  $types string or array of strings
	 * @param string $dir
	 * @return array of Relationship
	 */
	public function getRelationships($types=array(), $dir=null)
	{
		return $this->client->getNodeRelationships($this, $types, $dir);
	}

	/**
	 * List labels for this node
	 *
	 * @return array
	 * @throws Exception on failure
	 */
	public function getLabels()
	{
		if (is_null($this->labels)) {
			$this->labels = $this->client->getLabels($this);
		}
		return $this->labels;
	}

	/**
	 * Load this node
	 *
	 * @return PropertyContainer
	 * @throws Exception on failure
	 */
	public function load()
	{
		$this->client->loadNode($this);
		return $this;
	}

	/**
	 * Make a new relationship
	 *
	 * @param Node $to
	 * @param string $type
	 * @return Relationship
	 */
	public function relateTo(Node $to, $type)
	{
		$rel = $this->client->makeRelationship();
		$rel->setStartNode($this);
		$rel->setEndNode($to);
		$rel->setType($type);

		return $rel;
	}

	/**
	 * Remove labels from this node
	 *
	 * @param array $labels
	 * @return array of all the Labels on this node, after removing the given labels
	 */
	public function removeLabels($labels)
	{
		$this->labels = $this->client->removeLabels($this, $labels);
		return $this->labels;
	}

	/**
	 * Save this node
	 *
	 * @return PropertyContainer
	 * @throws Exception on failure
	 */
	public function save()
	{
		$this->client->saveNode($this);
		$this->useLazyLoad(false);
		return $this;
	}

	/**
	 * Be sure to add our properties to the things to serialize
	 *
	 * @return array
	 */
	public function __sleep()
	{
		return array_merge(parent::__sleep(), array('labels'));
	}
}
