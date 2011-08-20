<?php
namespace Everyman\Neo4j\Batch\Command;
use Everyman\Neo4j\Command;

/**
 * Create a node
 * Exposes methods for batches to use the command
 */
class CreateNode extends Command\CreateNode
{
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
		return parent::handleResult($code, $headers, $data);
	}
}

