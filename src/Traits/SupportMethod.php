<?php

declare(strict_types = 1);
namespace Xutengx\Cache\Traits;

use Closure;
use InvalidArgumentException;
use ReflectionClass;

trait SupportMethod {
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
	 * 反射执行任意对象的任意方法
	 * @param string|object $obj
	 * @param string $func
	 * @param array $args
	 * @return mixed
	 * @throws \ReflectionException
	 */
	protected static function runFunc($obj, string $func, array $args = []) {
		$reflectionClass = new ReflectionClass($obj);
		$method          = $reflectionClass->getMethod($func);
		$closure         = $method->getClosure($obj);
		return $closure(...$args);
	}

	/**
	 * 按键, 获取一个缓存, 若不存在, 则设置缓存后返回
	 * @param string $key
	 * @param mixed $value
	 * @param int $expire
	 * @return mixed
	 */
	protected function rememberEverythingWithKey(string $key, $value, int $expire = null) {
		return ($content = $this->driver->get($key)) !== false ? $this->unserialize($content) :
			($this->set($key, $value, $expire) ? $this->get($key) : null);
	}

	/**
	 * 自动生成键, 获取一个缓存, 若不存在, 则设置缓存后返回
	 * @param Closure $callback
	 * @param int $expire
	 * @return mixed
	 */
	protected function rememberClosureWithoutKey(Closure $callback, int $expire = null) {
		$debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
		foreach ($debug as $v) {
			if ($v['function'] !== 'rememberClosureWithoutKey' && $v['function'] !== 'remember') {
				$class = $v['class'];
				break;
			}
		}
		$func  = '__';
		foreach ($debug as $v) {
			// 调用类自身调用
			if (isset($v['file']) && strpos($v['file'], str_replace('\\', '/', $class))) {
				$func = 'Closure_' . $v['line'];
				break;
			}
			// 调用类父类调用
			elseif ($v['class'] !== get_class($this)) {
				$class .= '\parent\\' . $v['class'];
				$func  = 'Closure_' . $v['line'];
				break;
			}
		}
		$key = $this->generateKey($class, $func);
		return $this->rememberEverythingWithKey($key, $callback, $expire);
	}

	/**
	 * 生成键名
	 * @param string|object $obj
	 * @param string $funcName
	 * @param array $params
	 * @return string
	 */
	protected function generateKey($obj, string $funcName = '', array $params = []): string {
		$className = is_object($obj) ? get_class($obj) : $obj;
		$key       = ''; // default
		if (!empty($params)) {
			foreach ($params as $v) {
				if (is_object($v))
					throw new InvalidArgumentException('the object is not supported as the parameter in Manager::call. ');
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
