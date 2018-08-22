<?php
declare(strict_types = 1);

use Xutengx\Cache\Driver\{File, Redis};
use Xutengx\Cache\Manager;
use Xutengx\Contracts\Cache\Driver;
use Xutengx\Contracts\Cache as ManagerInterface;
use PHPUnit\Framework\TestCase;

final class TestSrc extends TestCase {

	/**
	 * 实例化文件缓存驱动
	 * @return File
	 */
	public function testMakeFileDriver(): File {
		$dir = dirname(__DIR__) . '/storage/cache';
		$ext = '.txt';
		$this->assertInstanceOf(File::class, $driver = new File($dir, $ext));
		$this->assertClassHasAttribute('storageCachePath', File::class);
		$this->assertClassHasAttribute('cacheFileExt', File::class);
		$this->driverFileTest($driver);
		return $driver;
	}

	/**
	 * 实例化Redis缓存驱动
	 * @return Redis
	 */
	public function testMakeRedisDriver(): Redis {
		$host                 = '127.0.0.1';
		$port                 = 6379;
		$password             = '';
		$database             = 0;
		$persistentConnection = false;
		$this->assertInstanceOf(Redis::class,
			$driver = new Redis($host, $port, $password, $database, $persistentConnection));
		$this->driverFileTest($driver);
		return $driver;
	}

	/**
	 * 实例化Manager
	 * @depends testMakeFileDriver
	 * @param Driver $File
	 */
	public function testCacheManagerWithFileDriver(Driver $File) {
		$this->assertInstanceOf(Manager::class, $Manager = new Manager($File));
		$this->ManagerTest($Manager);
		$this->assertEquals('file', $Manager->getDriverName(), '当前缓存类型');
	}

	/**
	 * 实例化Manager
	 * @depends testMakeRedisDriver
	 * @param Driver $Redis
	 */
	public function testCacheManagerWithRedisDriver(Driver $Redis) {
		$this->assertInstanceOf(Manager::class, $Manager = new Manager($Redis));
		$this->ManagerTest($Manager);
		$this->assertEquals('redis', $Manager->getDriverName(), '当前缓存类型');
	}

	protected function ManagerTest(ManagerInterface $Manager) {
		$key   = 'key_of_cache';
		$value = 'value_of_cache';
		$ttl   = 1;
		$this->assertNull($Manager->get($key), '获取一个不存在的键');
		$this->assertTrue($Manager->set($key, $value, $ttl), '设置一个值');
		$this->assertEquals($Manager->get($key), $value, '获取刚刚设置的缓存的值');
		$this->assertLessThanOrEqual($ttl, $Manager->ttl($key), '获取缓存的有效时间值');
		usleep(1100000); // 使之过期
		$this->assertEquals(-2, $Manager->ttl($key), '获取已过期的缓存的有效时间值');
		$this->assertEquals($Manager->get($key), null, '获取已过期的缓存的值');

		$this->assertEquals($Manager->increment($key), 1, '自增一个不存在的键');
		$this->assertEquals($Manager->ttl($key), -1, '自增的不存在的键的有效期应该是无限');
		$this->assertEquals($Manager->increment($key), 2, '自增一个已存在的键');
		$this->assertEquals($Manager->increment($key, 3), 5, '自增一个已存在的键, 步幅为3');
		$this->assertTrue($Manager->rm($key), '移除一个键');
		$this->assertNull($Manager->get($key), '获取已被移除的缓存的值');

		$this->assertEquals($Manager->decrement($key), -1, '自减一个不存在的键');
		$this->assertEquals($Manager->ttl($key), -1, '自减的不存在的键的有效期应该是无限');
		$this->assertEquals($Manager->decrement($key), -2, '自减一个已存在的键');
		$this->assertEquals($Manager->decrement($key, 3), -5, '自减一个已存在的键, 步幅为3');
		$this->assertTrue($Manager->rm($key), '移除一个键');
		$this->assertFalse($Manager->rm($key), '移除一个不存在的键');
		$this->assertNull($Manager->get($key), '获取已被移除的缓存的值');

		$this->assertTrue($Manager->setnx($key, '55'), '仅在键不存在时,设置一个值');
		$this->assertFalse($Manager->setnx($key, '55'), '仅在键不存在时,设置一个值');
		$this->assertEquals($Manager->decrement($key, 1), 54, '自减一个数子类型的字符');
		$this->assertTrue($Manager->rm($key), '移除一个键');
		$this->assertNull($Manager->get($key), '获取已被移除的缓存的值');

	}

	/**
	 * 测试缓存驱动
	 * @depends testMakeFileDriver
	 * @param Driver $driver
	 * @return void
	 */
	protected function driverFileTest(Driver $driver): void {
		$key   = 'key_of_cache';
		$value = 'value_of_cache';
		$ttl   = 1;
		$this->批量删除($driver);

		$this->assertFalse($driver->get($key), '获取一个不存在的键');
		$this->assertTrue($driver->set($key, $value, $ttl), '设置一个值');
		$this->assertEquals($driver->get($key), $value, '获取刚刚设置的缓存的值');
		$this->assertLessThanOrEqual($ttl, $driver->ttl($key), '获取缓存的有效时间值');
		usleep(1100000); // 使之过期
		$this->assertEquals(-2, $driver->ttl($key), '获取已过期的缓存的有效时间值');
		$this->assertEquals($driver->get($key), null, '获取已过期的缓存的值');

		$this->assertEquals($driver->increment($key), 1, '自增一个不存在的键');
		$this->assertEquals($driver->ttl($key), -1, '自增的不存在的键的有效期应该是无限');
		$this->assertEquals($driver->increment($key), 2, '自增一个已存在的键');
		$this->assertEquals($driver->increment($key, 3), 5, '自增一个已存在的键, 步幅为3');
		$this->assertTrue($driver->rm($key), '移除一个键');
		$this->assertFalse($driver->get($key), '获取已被移除的缓存的值');

		$this->assertEquals($driver->decrement($key), -1, '自减一个不存在的键');
		$this->assertEquals($driver->ttl($key), -1, '自减的不存在的键的有效期应该是无限');
		$this->assertEquals($driver->decrement($key), -2, '自减一个已存在的键');
		$this->assertEquals($driver->decrement($key, 3), -5, '自减一个已存在的键, 步幅为3');
		$this->assertTrue($driver->rm($key), '移除一个键');
		$this->assertFalse($driver->rm($key), '移除一个不存在的键');
		$this->assertFalse($driver->get($key), '获取已被移除的缓存的值');

		$this->assertTrue($driver->setnx($key, '55'), '仅在键不存在时,设置一个值');
		$this->assertFalse($driver->setnx($key, '55'), '仅在键不存在时,设置一个值');
		$this->assertEquals($driver->decrement($key, 1), 54, '自减一个数子类型的字符');
		$this->assertTrue($driver->rm($key), '移除一个键');
		$this->assertFalse($driver->get($key), '获取已被移除的缓存的值');

		$this->批量删除($driver);
	}

	protected function 批量删除(Driver $driver): void {
		for ($i = 0; $i <= 9; $i++) {
			$this->assertTrue($driver->set('-' . $i . $i, $i . $i, 5), '批量设置缓存');
		}
		for ($i = 0; $i <= 9; $i++) {
			$this->assertTrue($driver->set('+' . $i . $i, $i . $i, 5), '批量设置缓存');
		}
		$this->assertTrue($driver->clear('-'), '移除部分缓存');
		for ($i = 0; $i <= 9; $i++) {
			$this->assertNotFalse($driver->get('+' . $i . $i), '获取缓存');
		}
		for ($i = 0; $i <= 9; $i++) {
			$this->assertFalse($driver->get('-' . $i . $i), '获取缓存');
		}
		$this->assertTrue($driver->clear(''), '移除所有缓存');
		for ($i = 0; $i <= 9; $i++) {
			$this->assertFalse($driver->get('+' . $i . $i), '获取缓存');
		}
	}

}


