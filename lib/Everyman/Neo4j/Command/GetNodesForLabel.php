<?php
namespace Everyman\Neo4j\Command;

use Everyman\Neo4j\Command,
	Everyman\Neo4j\Client,
	Everyman\Neo4j\Label,
	Everyman\Neo4j\Query\Row;

/**
 * Find nodes with the given label
 */
class GetNodesForLabel extends Command
{
	protected $label           = null;
	protected $propertyName    = null;
	protected $propertyValue   = null;

	/**
	 * Set the parameters to search
	 *
	 * @param Client $client
	 * @param Label  $label
	 * @param string $propertyName
	 * @param mixed  $propertyValue
	 */
	public function __construct(Client $client, Label $label, $propertyName=null, $propertyValue=null)
	{
		parent::__construct($client);

		$this->label = $label;
		$this->propertyName = $propertyName;
		$this->propertyValue = $propertyValue;
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
		return 'get';
	}

	/**
	 * Return the path to use
	 *
	 * @return string
	 */
	protected function getPath()
	{
		if (!$this->client->hasCapability(Client::CapabilityLabel)) {
			throw new \RuntimeException('The connected Neo4j version does not have label capability');
		}

		$labelName = rawurlencode($this->label->getName());
		$path = "/label/{$labelName}/nodes";
		if ($this->propertyName || $this->propertyValue) {
			if (!$this->propertyName || !$this->propertyValue) {
				throw new \InvalidArgumentException('Cannot specify a property name without a value, or vice versa');
			}

			$propertyName = rawurlencode($this->propertyName);

			if (is_numeric($this->propertyValue)) {
				$propertyValue = rawurlencode($this->propertyValue);
			} else {
				$propertyValue = rawurlencode('"'.$this->propertyValue.'"');
			}

			$path .= "?{$propertyName}={$propertyValue}";
		}
		return $path;
	}

	/**
	 * Use the results
	 *
	 * @param integer $code
	 * @param array   $headers
	 * @param array   $data
	 * @return Row
	 * @throws Exception on failure
	 */
	protected function handleResult($code, $headers, $data)
	{
		if ((int)($code / 100) != 2) {
			$this->throwException('Unable to retrieve nodes for label', $code, $headers, $data);
		}

		return new Row($this->client, array_keys($data), $data);
	}
}
