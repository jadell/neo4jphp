<?php
namespace Everyman\Neo4j\Cypher;

use Everyman\Neo4j\Client;

/**
 * Assembles strings from strings with '?' placeholders and 
 * a list of variables.
 */
class QueryAssembler
{
	/**
	 * Assemble a query, possibly with template vars
	 *
	 * @param array $args If args is a single element array,
	 *        the first element is assumed to be the entire query with no variables.
	 *        Otherwise, it is assumed to be a query template and all remaining elements
	 *        are substituted in order for each '?' in the template.
	 *
	 * @return string
	 */
	public function assembleQuery($args)
	{
		$template = array_shift($args);
		if(count($args) > 0) {
			return $this->injectVariables($template, $args);
		} else {
			// No variables to substitute
			return $template;
		}
	}
	
	/**
	 * Slow and naive template parser & replacer.
	 *
	 * @param string $template
	 * @param array $vars
	 * @return string
	 */
	protected function injectVariables($template, $vars)
	{
		# Parser state
		$escaped = false;
		$doubleQuoted = false;
		$singleQuoted = false;
		
		# Output
		$query = array();
		
		for($i=0,$l=strlen($template);$i<$l;$i++) {
			$char = $template[$i];

			if ($char==='?' && !$escaped && !$doubleQuoted && !$singleQuoted) {
				array_push($query, 
				$this->formatQueryVariable(array_shift($vars)));
				continue;
			}

			array_push($query, $char);

			switch($char) {
				case '\\':
					$escaped = !$escaped;
					break;
				case '"':
					if(!$escaped && !$singleQuoted) {
						$doubleQuoted = !$doubleQuoted;
					}
					$escaped = false;
					break;
				case '\'':
					if(!$escaped && !$doubleQuoted) {
						$singleQuoted = !$singleQuoted;
					}
					$escaped = false;
					break;
				default: 
					$escaped = false;
					break;
			}
		}

		return implode($query);
	}

	/**
	 * Properly escape and format substitution variable values
	 *
	 * @param mixed $variable Must be string or numeric
	 * @return mixed
	 */	
	protected function formatQueryVariable($variable)
	{
		if(is_string($variable)) {
			return "'".addslashes($variable)."'";
		} elseif (is_numeric($variable)) {
			return $variable;
		} else {
			$type = gettype($variable);
			throw new \InvalidArgumentException("Variables for query substitution must be either strings or numeric variables, got $type.");
		}
	}
}
