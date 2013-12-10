<?php
namespace Everyman\Neo4j\Geoff;

use Everyman\Neo4j\Exception,
	Everyman\Neo4j\Path;

/**
 * Export an array of Paths to a file
 */
class Exporter
{
	/**
	 * Dump an array of Paths
	 *
	 * @param mixed $paths a single Path object or an array of Path objects
	 * @param stream $handle
	 */
	public function dump($paths, $handle)
	{
		if (!is_resource($handle) || get_resource_type($handle) != 'stream') {
			throw new Exception("Not a stream resource");
		}

		if (!is_array($paths)) {
			$paths = array($paths);
		}

		$nodes = array();
		$rels = array();
		foreach ($paths as $path) {
			if (!($path instanceof Path)) {
				throw new Exception("Not a Path");
			}

			$pathNodes = $path->getNodes();
			foreach ($pathNodes as $node) {
				$nodes[$node->getId()] = $node;
			}

			$pathRels = $path->getRelationships();
			foreach ($pathRels as $rel) {
				$rels[$rel->getId()] = $rel;
			}
		}

		foreach ($nodes as $id => $node) {
			$properties = $node->getProperties();
			$format = $properties ? "(%s)\t%s\n" : "(%s)\n";
			fprintf(
				$handle,
				$format,
				$id,
				json_encode($properties)
			);
		}

		foreach ($rels as $id => $rel) {
			$properties = $rel->getProperties();
			$format = "(%s)-[%s:%s]->(%s)";
			$format .= $properties ? "\t%s\n" : "\n";
			fprintf(
				$handle,
				$format,
				$rel->getStartNode()->getId(),
				$id,
				$rel->getType(),
				$rel->getEndNode()->getId(),
				json_encode($properties)
			);
		}
	}
}
