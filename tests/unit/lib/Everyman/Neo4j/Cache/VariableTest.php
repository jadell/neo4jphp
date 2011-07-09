<?php
namespace Everyman\Neo4j\Cache;

class VariableTest extends \PHPUnit_Framework_TestCase
{
	protected $cache = null;

	public function setUp()
	{
		$this->cache = new Variable();
	}

	public function testSet_ReturnsTrue()
	{
		$this->assertTrue($this->cache->set('somekey', 'somevalue', 12345));
	}

	public function testGet_KeyDoesNotExist_ReturnsFalse()
	{
		$this->assertFalse($this->cache->get('somekey'));
	}

	public function testGet_KeyExists_ReturnsValue()
	{
		$this->cache->set('somekey', 'somevalue', 12345);
		$this->assertEquals('somevalue', $this->cache->get('somekey'));
	}

	public function testGet_ExpiredValue_ReturnsFalse()
	{
		$this->cache->set('somekey', 'somevalue', time()-10000);
		$this->assertFalse($this->cache->get('somekey'));
	}

	public function testDelete_KeyDoesNotExist_ReturnsTrue()
	{
		$this->assertTrue($this->cache->delete('somekey'));
	}

	public function testDelete_KeyExists_ReturnsTrue()
	{
		$this->cache->set('somekey', 'somevalue', 12345);
		$this->assertTrue($this->cache->delete('somekey'));
		$this->assertFalse($this->cache->get('somekey'));
	}
}
