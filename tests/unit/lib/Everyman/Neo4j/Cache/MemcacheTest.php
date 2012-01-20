<?php
namespace Everyman\Neo4j\Cache;

class MemcacheTest extends \PHPUnit_Framework_TestCase
{
	protected $memcache = null;
	protected $cache = null;

	public function setUp()
	{
		if (!phpversion('memcache')) {
			$this->markTestSkipped('Memcache extension not enabled/installed');
		}

		$this->memcache = $this->getMock('\Memcache');
		$this->cache = new Memcache($this->memcache);
	}

	public function testSet_PassesThroughToMemcache()
	{
		$this->memcache->expects($this->once())
			->method('set')
			->with('somekey', 'somevalue', 0, 12345)
			->will($this->returnValue(true));

		$this->assertTrue($this->cache->set('somekey', 'somevalue', 12345));
	}

	public function testGet_PassesThroughToMemcache()
	{
		$this->memcache->expects($this->once())
			->method('get')
			->with('somekey')
			->will($this->returnValue('somevalue'));

		$this->assertEquals('somevalue', $this->cache->get('somekey'));
	}

	public function testDelete_PassesThroughToMemcache()
	{
		$this->memcache->expects($this->once())
			->method('delete')
			->with('somekey')
			->will($this->returnValue(true));

		$this->assertTrue($this->cache->delete('somekey'));
	}
}
