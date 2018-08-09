<?php

declare(strict_types = 1);
namespace Gaara\Cache;

use Closure;
use Exception;
use Gaara\Cache\Traits\AdvancedMethod;
use Gaara\Contracts\Cache\DriverInterface;
use InvalidArgumentException;

class Cache {

	use AdvancedMethod;

	protected $driver;
	protected $expire;
	protected $identifier;

	/**
	 * Cache constructor.
	 * @param DriverInterface $driver
	 * @param int $expire
	 * @param string $identifier
	 */
	public function __construct(DriverInterface $driver, int $expire = 1800, string $identifier = null) {
		$this->driver     = $driver;
		$this->expire     = $expire;
		$this->identifier = $identifier;
	}

	/**
	 * 序列化.
	 * @param mixed $value
	 * @return string
	 */
	protected static function serialize($value): string {
		return is_numeric($value) ? (string)$value : serialize($value);
	}

	/**
	 * 反序列化.
	 * @param string $value
	 * @return mixed
	 */
	protected static function unserialize(string $value) {
		return is_numeric($value) ? $value : unserialize($value);
	}

	/**
	 * 获取一个缓存
	 * @param string $key
	 * @return mixed
	 */
	public function get(string $key) {
		return ($content = $this->driver->get($key)) ? static::unserialize($content) : null;
	}

	/**
	 * 设置缓存
	 * 仅在不存在时设置缓存 set if not exists
	 * @param string $key 键
	 * @param mixed $value 值
	 * @return bool
	 */
	public function setnx(string $key, $value): bool {
		if ($value instanceof Closure) {
			$value = $value();
		}
		return $this->driver->setnx($key, static::serialize($value));
	}

	/**
	 * 设置一个缓存
	 * @param string $key
	 * @param mixed $value 闭包不可被序列化,将会执行
	 * @param int $expire 有效时间, -1表示不过期
	 * @return bool
	 */
	public function set(string $key, $value, int $expire = null): bool {
		if ($value instanceof Closure) {
			$value = $value();
		}
		return $this->driver->set($key, static::serialize($value), $expire ?? $this->expire);
	}

	/**
	 * 自增 (原子性)
	 * 当$key不存在时,将以 $this->set($key, 0, -1); 初始化
	 * @param string $key
	 * @param int $step
	 * @return int 自增后的值
	 */
	public function increment(string $key, int $step = 1): int {
		return $this->driver->increment($key, abs($step));
	}

	/**
	 * 自减 (原子性)
	 * @param string $key
	 * @param int $step
	 * @return int 自减后的值
	 */
	public function decrement(string $key, int $step = 1): int {
		return $this->driver->decrement($key, abs($step));
	}

	/**
	 * 删除单个key
	 * @param string $key
	 * @return bool
	 */
	public function rm(string $key): bool {
		return $this->driver->rm($key);
	}

	/**
	 * 删除call方法的缓存
	 * @param string|object $obj
	 * @param string $func
	 * @param mixed ...$params
	 * @return bool
	 * @throws Exception
	 */
	public function clear($obj, string $func = '', ...$params): bool {
		$key = $this->generateKey($obj, $func, $params);
		return $this->driver->clear($key);
	}

	/**
	 * 清除当前驱动的全部缓存
	 * 清除缓存并不管什么缓存键前缀，而是从缓存系统中移除所有数据，所以在使用这个方法时如果其他应用与本应用有共享缓存时需要格外注意
	 * @return bool
	 */
	public function flush(): bool {
		return $this->driver->clear('');
	}

	/**
	 * 获取当前驱动的类型
	 * @return string eg:redis
	 */
	public function getDriverName(): string {
		return end(explode('\\', get_class($this->driver)));
	}

	/**
	 * 执行驱动中的一个方法
	 * @param string $fun
	 * @param array $par
	 * @return mixed
	 */
	public function __call(string $fun, array $par = []) {
		return call_user_func_array([$this->driver, $fun], $par);
	}

	/**
	 * 生成键名
	 * @param string|object $obj
	 * @param string $funcName
	 * @param array $params
	 * @return string
	 * @throws Exception
	 */
	protected function generateKey($obj, string $funcName = '', array $params = []): string {
		$className = is_object($obj) ? get_class($obj) : $obj;
		$key       = ''; // default
		if (!empty($params)) {
			foreach ($params as $v) {
				if (is_object($v))
					throw new InvalidArgumentException('the object is not supported as the parameter in Cache::call. ');
				if ($v === true)
					$key .= '_bool-t';
				elseif ($v === false)
					$key .= '_bool-f';
				else
					$key .= '_' . gettype($v) . '-' . (is_array($v) ? serialize($v) : $v);
			}
			$key = '/' . md5($key);
		}
		$str = $className . '/' . $funcName . $key;
		$str = is_null($this->identifier) ? $str : '@' . $this->identifier . '/' . $str;
		return str_replace('\\', '/', $str);
	}

}
