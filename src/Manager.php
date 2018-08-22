<?php

declare(strict_types = 1);
namespace Xutengx\Cache;

use Closure;
use Exception;
use Xutengx\Cache\Traits\SupportMethod;
use Xutengx\Contracts\Cache as CacheManagerInterface;
use Xutengx\Contracts\Cache\Driver;

class Manager implements CacheManagerInterface {

	use SupportMethod;

	protected $driver;
	protected $expire;
	protected $identifier;

	/**
	 * Manager constructor.
	 * @param Driver $driver
	 * @param int $expire
	 * @param string $identifier 标识符, 在自动生成键名的情况下使用
	 */
	public function __construct(Driver $driver, int $expire = 1800, string $identifier = null) {
		$this->driver     = $driver;
		$this->expire     = $expire;
		$this->identifier = $identifier;
	}

	/**
	 * 获取&存储
	 * 如果键不存在时,则依据上下文生成自动键
	 * 如果请求的键不存在时给它存储一个默认值
	 * @param mixed ...$params
	 * @return mixed
	 */
	public function remember(...$params) {
		if (reset($params) instanceof Closure)
			return $this->rememberClosureWithoutKey(...$params);
		else
			return $this->rememberEverythingWithKey(...$params);
	}

	/**
	 * 执行某个方法并缓存, 优先读取缓存 (并非依赖注入)
	 * @param string|object $obj 执行对象
	 * @param string $func 执行方法
	 * @param int $expire 缓存过期时间
	 * @param mixed ...$params 非限定参数
	 * @return mixed
	 * @throws Exception
	 */
	public function call($obj, string $func, int $expire = null, ...$params) {
		$key = $this->generateKey($obj, $func, $params);
		return $this->rememberEverythingWithKey($key, function() use ($obj, $func, $params) {
			return $this->runFunc($obj, $func, $params);
		}, $expire);
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
	 * 查询缓存的有效期
	 * @param string $key
	 * @return int 0表示过期, -1表示无过期时间, -2表示未找到key
	 */
	public function ttl(string $key): int {
		return $this->driver->ttl($key);
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
	public function unsetCall($obj, string $func = '', ...$params): bool {
		$key = $this->generateKey($obj, $func, $params);
		return $this->driver->clear($key);
	}

	/**
	 * 清除当前驱动的全部缓存
	 * 清除缓存并不管什么缓存键前缀，而是从缓存系统中移除所有数据，所以在使用这个方法时如果其他应用与本应用有共享缓存时需要格外注意
	 * @return bool
	 */
	public function clear(string $key): bool {
		return $this->driver->clear($key);
	}

	/**
	 * 获取当前驱动的类型
	 * @return string eg:redis
	 */
	public function getDriverName(): string {
		$classNameInfo = explode('\\', get_class($this->driver));
		return strtolower(end($classNameInfo));
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

}
