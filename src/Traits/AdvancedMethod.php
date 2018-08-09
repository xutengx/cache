<?php

declare(strict_types = 1);
namespace Gaara\Cache\Traits;

use Closure;
use ReflectionClass;

trait AdvancedMethod {

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
	 */
	public function call($obj, string $func, int $expire = null, ...$params) {
		$key = $this->generateKey($obj, $func, $params);
		return $this->rememberEverythingWithKey($key, function() use ($obj, $func, $params) {
			return $this->runFunc($obj, $func, $params);
		}, $expire);
	}

	/**
	 * 反射执行任意对象的任意方法
	 * @param string|object $obj
	 * @param string $func
	 * @param array $args
	 * @return mixed
	 * @throws \ReflectionException
	 */
	protected function runFunc($obj, string $func, array $args = []) {
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
		return ($content = $this->driver->get($key)) ? $this->unserialize($content) :
			($this->set($key, $value, $expire) ? $this->get($key) : null);
	}

	/**
	 * 自动生成键, 获取一个缓存, 若不存在, 则设置缓存后返回
	 * @param Closure $callback
	 * @param int $expire
	 * @return mixed
	 */
	protected function rememberClosureWithoutKey(Closure $callback, int $expire = null) {
		$class = $this->analysisClosure($callback);
		$debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
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
	 * 返回闭包函数的this指向的类名
	 * @param Closure $closure
	 * @return string
	 */
	protected function analysisClosure(Closure $closure): string {
		ob_start();
		var_dump($closure);
		$info = ob_get_contents();
		ob_end_clean();
		$info  = str_replace([" ", "　", "\t", "\n", "\r"], '', $info);
		$class = '';
		\preg_replace_callback("/\[\"this\"\]=>object\((.*?)\)\#/is", function($matches) use (&$class) {
			$class = $matches[1];
		}, $info);
		return $class;
	}

}
