<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command\SetLabels,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Node,
	Everyman\Neo4j\Label;

/**
 * Remove a set of labels from a node
 */
class RemoveLabels extends SetLabels
{
	/**
	 * Set the labels to remove
	 *
	 * @param Client $client
	 * @param Node   $node
	 * @param array  $labels
	 */
	public function __construct(Client $client, Node $node, $labels)
	{
		parent::__construct($client, $node, $labels, true);
	}
}
