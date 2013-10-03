<?php
namespace Everyman\Neo4j\Command;
use Everyman\Neo4j\PropertyContainer;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Index;
use Everyman\Neo4j\Client;

/**
 * Add a unique entity to an index
 */
class AddToIndexUnique extends AddToIndex
{
	protected $type = null;

	/**
	 * @param Client            $client
	 * @param Index             $index
	 * @param PropertyContainer $entity
	 * @param string            $key
	 * @param string            $value
	 * @param null|int          $type
	 */
	public function __construct(Client $client, Index $index, PropertyContainer $entity, $key, $value, $type = null)
	{
		parent::__construct($client, $index, $entity, $key, $value);
		$this->type = $type;
	}

	/**
	 * do not check if a node key exists
	 *
	 * @return array|mixed
	 * @throws \Exception
	 */
	protected function getData()
	{
		$data = array();

		$key = trim((string)$this->key);
		if (!$key) {
			throw new \Exception('No key specified to add to index');
		}
		$data['key'] = $key;
		$data['value'] = $this->value;

		$data['properties'] = $this->entity->getProperties();

		return $data;
	}

	/**
	 * @return string
	 */
	protected function getPath()
	{
		$path = parent::getPath();

		if ($this->type) {
			if ($this->type === Index::GetOrCreate) {
				$path .= '?uniqueness=' . Index::GetOrCreate;
			} else {
				$path .= '?uniqueness=' . Index::CreateOrFail;
			}
		} else {
			$path .= '?unique';
		}
		return $path;
	}

	/**
	 * Return the created or getted node or in case of get_or_fail false
	 *
	 * @param int   $code
	 * @param array $headers
	 * @param array $data
	 *
	 * @return Node|void
	 * @throws \Everyman\Neo4j\Exception
	 */
	protected function handleResult($code, $headers, $data)
	{
		if ($code === 200 || $code === 201) {
			$this->getEntityMapper()->populateNode($this->entity, $data);
			$this->entity->setId($this->getEntityMapper()->getIdFromUri($data['self']));

			return true;
		}
		if ($code === 409) {
			$this->throwException('Node already exists!', $code, $headers, $data);
		}

		$this->throwException('Unable to add entity to index', $code, $headers, $data);
	}
}