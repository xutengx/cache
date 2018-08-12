<?php
declare(strict_types = 1);

use Gaara\Cache\Driver\File;
use PHPUnit\Framework\TestCase;

final class FileTest extends TestCase {

	protected $dir;
	protected $ext = '.php';

	/**
	 * @return void
	 */
	public function testDriverFile(): void {
		$this->dir = dirname(__DIR__) . '/storage/cache';
		is_dir($this->dir) ? rmdir($this->dir) : '';
		$this->ext = (string)mt_rand(1, 9999); // 随机后缀

		$this->assertDirectoryNotExists($this->dir, '这应该是一个不存在的目录, 请手动删除它[' . $this->dir . ']');
		$this->assertInstanceOf(File::class, $driver = new File($this->dir, $this->ext));
		$this->assertClassHasAttribute('storageCachePath', File::class);
		$this->assertClassHasAttribute('cacheFileExt', File::class);

		$key   = 'key_of_cache';
		$value = 'value_of_cache';
		$ttl   = 1;
		$this->assertFalse($driver->get($key), '获取一个不存在的键');
		$this->assertTrue($driver->set($key, $value, $ttl), '设置一个值');
		$this->assertDirectoryExists($this->dir, '缓存文件存放目录生成');
		$this->assertFileExists($this->dir . '/' . $key . '.' . $this->ext, '缓存文件后缀验证');
		$this->assertEquals($driver->get($key), $value, '获取刚刚设置的缓存的值');
		$this->assertLessThanOrEqual($ttl, $driver->ttl($key), '获取缓存的有效时间值');
		sleep(1); // 使之过期
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
		$this->assertFalse($driver->get($key), '获取已被移除的缓存的值');

		$this->assertTrue($driver->set($key, '55' ,5), '设置一个数子类型的字符');
		$this->assertEquals($driver->decrement($key, 1), 54,'自减一个数子类型的字符');
		$this->assertTrue($driver->rm($key), '移除一个键');
		$this->assertFalse($driver->get($key), '获取已被移除的缓存的值');
	}

}


