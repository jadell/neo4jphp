<?php
namespace Everyman\Neo4j\Transport;

use Everyman\Neo4j\Transport as BaseTransport,
	Everyman\Neo4j\Version,
	Everyman\Neo4j\Exception;

/**
 * Class for communicating with an HTTP JSON endpoint over PHP streams
 */
class Stream extends BaseTransport
{
	/**
	 * @inherit
	 */
	public function makeRequest($method, $path, $data=array())
	{
		$url = $this->getEndpoint().$path;

		$context_options = array (
			$this->scheme => array (
				'method' => 'GET',
				'ignore_errors' => true,
				'header'=>
					"Content-type: application/json\r\n"
					. "Accept: application/json;stream=true\r\n"
					. "User-Agent: ".Version::userAgent()."\r\n"
					. "X-Stream: true\r\n"
			)
		);

		if ($this->username && $this->password) {
			$encodedAuth = base64_encode($this->username.':'.$this->password);
			$context_options[$this->scheme]['header'] .= 'Authorization: Basic ' . $encodedAuth . "\r\n";
		}

		switch ($method) {
			case self::DELETE:
				$context_options[$this->scheme]['method'] = self::DELETE;
				break;

			case self::POST:
			case self::PUT:
				$dataString = $this->encodeData($data);
				$context_options[$this->scheme]['method'] = $method;
				$context_options[$this->scheme]['content'] = $dataString;
				$context_options[$this->scheme]['header'] .= 'Context-Length: ' . strlen($dataString) . "\r\n";
				break;
		}

		$context = stream_context_create($context_options);
		$response = file_get_contents($url, false, $context);

		if ($response === false) {
			throw new Exception("Can't open connection to ".$url);
		}

		// $http_response_header is set by file_get_contents with the http:// wrapper

		preg_match('/^HTTP\/1\.[0-1] (\d{3})/', $http_response_header[0], $parts);
		$code = $parts[1];

		if (!$code) {
			$code = 500;
			$response = json_encode(array("error"=>'error [' . $code . ']'));
		}

		$bodyData = json_decode($response, true);

		$headers = array();
		foreach ($http_response_header as $header) {
			$parts = explode(':', $header, 2);

			if (count($parts) == 2) {
				$headers[$parts[0]] = $parts[1];
			}
		}

		return array(
			'code' => $code,
			'headers' => $headers,
			'data' => $bodyData,
		);
	}
}
