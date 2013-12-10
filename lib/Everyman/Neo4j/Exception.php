<?php
namespace Everyman\Neo4j;

class Exception extends \Exception
{
	protected $headers;
	protected $data;

	public function __construct($message, $code=0, $headers=array(), $data=array())
	{
		$this->headers = $headers;
		$this->data = $data;
		parent::__construct($message, $code);
	}

	/**
	 * Return response headers
	 * @return array Response headers
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

	/**
	 * Return response data
	 * @return array Response data
	 */
	public function getData()
	{
		return $this->data;
	}
}
