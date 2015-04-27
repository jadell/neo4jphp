<?php
namespace Everyman\Neo4j\Cache;

class MemcachedTest extends \PHPUnit_Framework_TestCase
{
	protected $memcached = null;
	protected $cache = null;

	public function setUp()
	{
		$memcachedVersion = phpversion('memcached');
		if (!$memcachedVersion) {
			$this->markTestSkipped('Memcached extension not enabled/installed');
		} else if (version_compare($memcachedVersion, '2.2.0', '>=')) {
			$this->markTestSkipped('Memcached tests can only be run with memcached extension 2.1.0 or lower');
		}

		$this->memcached = $this->getMock('\Memcached');
		$this->cache = new Memcached($this->memcached);
	}

	public function testSet_PassesThroughToMemcached()
	{
		$this->memcached->expects($this->once())
			->method('set')
			->with('somekey', 'somevalue', 12345)
			->will($this->returnValue(true));

		$this->assertTrue($this->cache->set('somekey', 'somevalue', 12345));
	}

	public function testGet_PassesThroughToMemcached()
	{
		$this->memcached->expects($this->once())
			->method('get')
			->with('somekey')
			->will($this->returnValue('somevalue'));

		$this->assertEquals('somevalue', $this->cache->get('somekey'));
	}

	public function testDelete_PassesThroughToMemcached()
	{
		$this->memcached->expects($this->once())
			->method('delete')
			->with('somekey')
			->will($this->returnValue(true));

		$this->assertTrue($this->cache->delete('somekey'));
	}
}
