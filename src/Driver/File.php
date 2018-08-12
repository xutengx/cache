<?php

declare(strict_types = 1);
namespace Gaara\Cache\Driver;

use Closure;
use Exception;
use Gaara\Contracts\Cache\DriverInterface;

class File implements DriverInterface {

	/**
	 * 缓存文件存放的绝对路径
	 * @var string
	 */
	protected $storageCachePath;
	/**
	 * 缓存文件后缀
	 * @var string
	 */
	protected $cacheFileExt;

	/**
	 * File constructor.
	 * @param string $dir 缓存文件存放的绝对路径
	 * @param string $cacheFileExt 缓存文件后缀
	 */
	public function __construct(string $dir, string $cacheFileExt = '.php') {
		$this->storageCachePath = rtrim($dir, '/') . '/';
		$this->cacheFileExt     = '.' . ltrim($cacheFileExt, '.');
	}

	/**
	 * 递归生成目录(绝对路径)
	 * @param string $dir
	 * @param int $mode
	 * @return bool
	 */
	protected static function recursiveMakeDirectory(string $dir, int $mode = 0777): bool {
		return (is_dir(dirname($dir)) || static::recursiveMakeDirectory(dirname($dir))) ? mkdir($dir, $mode) : true;
	}

	/**
	 * 返回过期剩余时间, -1表示无过期时间
	 * @param int $filemtime
	 * @param int $expire
	 * @return int
	 */
	protected static function getExpire(int $filemtime, int $expire): int {
		if ($expire === -1)
			return -1;
		$time = $filemtime + $expire - time();
		return ($time > 0) ? $time : 0;
	}

	/**
	 * 递归删除目录(绝对路径)下的所有文件,不包括自身
	 * @param string $dirName
	 * @return void
	 */
	protected static function recursiveDeleteDirectory(string $dirName): void {
		if (is_dir($dirName) && $dirArray = scandir($dirName)) {
			foreach ($dirArray as $k => $v) {
				if ($v !== '.' && $v !== '..') {
					if (is_dir($dirName . '/' . $v)) {
						static::recursiveDeleteDirectory($dirName . '/' . $v);
						rmdir($dirName . '/' . $v);
					}
					else
						unlink($dirName . '/' . $v);
				}
			}
		}
	}

	/**
	 * 写入文件
	 * @param string $filename 文件名(绝对路径)
	 * @param string $text
	 * @param int $lockType LOCK_EX LOCK_NB
	 * @return bool
	 */
	protected static function filePutContents(string $filename, string $text, int $lockType = LOCK_EX): bool {
		if (!is_file($filename)) {
			if (is_dir(dirname($filename)) || static::recursiveMakeDirectory(dirname($filename)))
				touch($filename);
		}
		return file_put_contents($filename, $text, $lockType) === false ? false : true;
	}

	/**
	 * 读取缓存
	 * @param $handle
	 * @return string|false
	 */
	protected static function getWithLock($handle) {
		$content = '';
		while (!feof($handle)) {//循环读取，直至读取完整个文件
			$content .= fread($handle, 1024);
		}
		$expire    = (int)substr($content, 8, 12);
		$filemtime = (int)substr($content, 20, 12);
		$time      = static::getExpire($filemtime, $expire);
		if ($time === 0) {
			return false;
		}
		return substr($content, 32, -3);
	}

	/**
	 * 得到一个key的剩余有效时间
	 * @param $handle
	 * @return int 0表示过期, -1表示无过期时间, -2表示未找到key
	 */
	protected static function ttlWithLock($handle): int {
		$content = '';
		while (!feof($handle)) {//循环读取，直至读取完整个文件
			$content .= fread($handle, 1024);
		}
		$expire    = (int)substr($content, 8, 12);
		$filemtime = (int)substr($content, 20, 12);
		$time      = static::getExpire($filemtime, $expire);
		if ($time === 0) {
			return -2;
		}
		return $time;
	}

	/**
	 * 设置缓存
	 * @param $handle
	 * @param string $value 内容
	 * @param int $expire 过期时间
	 * @return int 写入字符数
	 */
	protected static function setWithLock($handle, string $value, int $expire): int {
		$data = "<?php\n//" . sprintf('%012d', $expire) . sprintf('%012d', time()) . $value . "\n?>";
		rewind($handle);  // 重置指针
		ftruncate($handle, 0); // 清空文件
		return fwrite($handle, $data);
	}

	/**
	 * 读取缓存
	 * @param string $key 键
	 * @return string|false
	 */
	public function get(string $key) {
		$filename = $this->generateFilename($key);
		if (is_file($filename) && $content = file_get_contents($filename)) {
			$expire    = (int)substr($content, 8, 12);
			$filemtime = (int)substr($content, 20, 12);
			$time      = $this->getExpire($filemtime, $expire);
			if ($time === 0) {
				//缓存过期删除缓存文件
				unlink($filename);
				return false;
			}
			return substr($content, 32, -3);
		}
		else
			return false;
	}

	/**
	 * 设置缓存
	 * @param string $key 键
	 * @param string $value 值
	 * @param int $expire 缓存有效时间 , -1表示无过期时间
	 * @return bool
	 */
	public function set(string $key, string $value, int $expire): bool {
		$filename = $this->generateFilename($key);
		$data     = "<?php\n//" . sprintf('%012d', $expire) . sprintf('%012d', time()) . $value . "\n?>";
		return $this->filePutContents($filename, $data);
	}

	/**
	 * 删除单一缓存
	 * @param string $key 键
	 * @return bool
	 */
	public function rm(string $key): bool {
		$filename = $this->generateFilename($key);
		return is_file($filename) && unlink($filename);
	}

	/**
	 * 批量清除缓存
	 * @param string $key
	 * @return bool
	 */
	public function clear(string $key): bool {
		$cachedir = $this->storageCachePath . $key;
		$this->recursiveDeleteDirectory($cachedir);
		return rmdir($cachedir);
	}

	/**
	 * 得到一个key的剩余有效时间
	 * @param string $key
	 * @return int -1表示无过期时间, -2表示未找到key
	 */
	public function ttl(string $key): int {
		$filename = $this->generateFilename($key);
		if (is_file($filename) && $content = file_get_contents($filename)) {
			$expire    = (int)substr($content, 8, 12);
			$filemtime = (int)substr($content, 20, 12);
			$time      = $this->getExpire($filemtime, $expire);
			if ($time === 0) {
				unlink($filename); //缓存过期删除缓存文件
				return -2;
			}
			return $time;
		}
		else
			return -2;
	}

	/**
	 * 自减 (原子性)
	 * @param string $key
	 * @param int $step
	 * @return int 自减后的值
	 * @throws Exception
	 */
	public function decrement(string $key, int $step = 1): int {
		return $this->increment($key, $step * -1);
	}

	/**
	 * 自增 (原子性)
	 * @param string $key
	 * @param int $step
	 * @return int 自增后的值
	 * @throws Exception
	 */
	public function increment(string $key, int $step = 1): int {
		$return_value = 0;
		$success      = $this->lock($key, function($handle) use ($step, &$return_value) {
			$value      = $this->getWithLock($handle);
			$new_value  = (int)$value + $step; // (int)false === 0
			$expire     = $this->ttlWithLock($handle);
			$new_expire = ($expire === -2) ? -1 : $expire;
			if ($this->setWithLock($handle, (string)$new_value, $new_expire)) {
				$return_value = $new_value;
			}
		});
		if ($success) {
			return $return_value;
		}
		else
			throw new Exception('Cache Increment Error!');
	}

	/**
	 * 设置缓存
	 * 仅在不存在时设置缓存 set if not exists
	 * @param string $key 键
	 * @param string $value 值
	 * @return bool
	 */
	public function setnx(string $key, string $value): bool {
		$return_value = false;
		$success      = $this->lock($key, function($handle) use ($value, &$return_value) {
			$old_value = $this->getWithLock($handle);
			if (($old_value === false) && $this->setWithLock($handle, $value, -1)) {
				return $return_value = true;
			}
			else
				return $return_value = false;
		});
		return ($success && $return_value);
	}

	/**
	 * 将key转化为目录
	 * @param string $key
	 * @return string
	 */
	protected function generateFilename(string $key): string {
		return $this->storageCachePath . $key . $this->cacheFileExt;
	}

	/**
	 * 以独占锁开启一个文件, 并执行闭包
	 * @param string $key
	 * @param Closure $callback
	 * @param int $lockType LOCK_EX LOCK_NB
	 * @return bool
	 */
	protected function lock(string $key, Closure $callback, int $lockType = LOCK_EX) {
		$filename = $this->generateFilename($key);
		$type     = is_file($filename) ? 'rb+' : 'wb+';
		if ($handle = fopen($filename, $type)) {
			if (flock($handle, $lockType)) {
				$callback($handle);
				flock($handle, LOCK_UN);
				fclose($handle);
				return true;
			}
		}
		return false;
	}

}
