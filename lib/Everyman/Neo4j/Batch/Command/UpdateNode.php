<?php
namespace Everyman\Neo4j\Batch\Command;
use Everyman\Neo4j\Command;

/**
 * Update a node
 * Exposes methods for batches to use the command
 */
class UpdateNode extends Command\UpdateNode
{
	/**
	 * Return the data to pass
	 *
	 * @param integer $opId
	 * @return array
	 */
	public function getData($opId=null)
	{
		$opData = array(array(
			'method' => strtoupper(parent::getMethod()),
			'to' => parent::getPath(),
			'body' => parent::getData(),
			'id' => $opId,
		));
		return $opData;
	}

	/**
	 * Use the results
	 *
	 * @param integer $code
	 * @param array   $headers
	 * @param array   $data
	 * @return integer on failure
	 */
	public function handleResult($code, $headers, $data)
	{
		if ((int)($code / 100) == 2) {
			return null;
		}
		return $code;
	}
}

