<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Exception,
	Everyman\Neo4j\PropertyContainer,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Node,
	Everyman\Neo4j\Index;

/**
 * Removes an entity from an index
 */
class RemoveFromIndex extends Command
{
	protected $index = null;
	protected $entity = null;
	protected $key = null;
	protected $value = null;

	/**
	 * Remove an entity from an index
	 * If $value is not given, all reference of the entity for the key
	 * are removed.
	 * If $key is not given, all reference of the entity are removed.
	 *
	 * @param Client $client
	 * @param Index $index
	 * @param PropertyContainer $entity
	 * @param string $key
	 * @param string $value
	 * @return boolean
	 */
	public function __construct(Client $client, Index $index, PropertyContainer $entity, $key=null, $value=null)
	{
		parent::__construct($client);
		$this->index = $index;
		$this->entity = $entity;
		$this->key = $key;
		$this->value = $value;
	}

	/**
	 * Return the data to pass
	 *
	 * @return mixed
	 */
	protected function getData()
	{
		return null;
	}

	/**
	 * Return the transport method to call
	 *
	 * @return string
	 */
	protected function getMethod()
	{
		return 'delete';
	}

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	protected function getPath()
	{
		if (!$this->entity || !$this->entity->hasId()) {
			throw new Exception('No entity to index specified');
		}

		$type = trim((string)$this->index->getType());
		if ($type != Index::TypeNode && $type != Index::TypeRelationship) {
			throw new Exception('No type specified for index');
		} else if ($type == Index::TypeNode && !($this->entity instanceof Node)) {
			throw new Exception('Cannot remove a node from a non-node index');
		} else if ($type == Index::TypeRelationship && !($this->entity instanceof Relationship)) {
			throw new Exception('Cannot remove a relationship from a non-relationship index');
		}

		$name = trim((string)$this->index->getName());
		if (!$name) {
			throw new Exception('No name specified for index');
		}

		$name = rawurlencode($name);
		$key = trim((string)$this->key);
		$value = trim((string)$this->value);

		$uri = '/index/'.$type.'/'.$name.'/';
		if ($key) {
			$uri .= rawurlencode($key).'/';
			if ($value) {
				$uri .= rawurlencode($value).'/';
			}
		}
		$uri .= $this->entity->getId();

		return $uri;
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
			$this->throwException('Unable to remove entity from index', $code, $headers, $data);
		}
		return true;
	}
}
