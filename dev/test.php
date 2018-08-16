<?php

declare(strict_types = 1);

use Gaara\Cache\Driver\{File, Redis};
use Gaara\Cache\Manager;

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (!function_exists('env')) {
	function env(string $envName, $default = null) {
		return $default;
	}
}

class test {

	public function index($value) {
		$cacheConfig = require_once (dirname(__DIR__)) . '/config/cache.php';

		if ($cacheConfig['driver'] === 'redis') {
			$redisConfig    = require_once (dirname(__DIR__)) . '/config/redis.php';
			$connectionInfo = $redisConfig['connections'][$cacheConfig['redis']['connection']];
			$dirver         = new Redis(...array_values($connectionInfo));
		}
		else {
			$dirver = new File($cacheConfig['file']);
		}

		$key   = 'test';
		$cache = new Manager($dirver);
		$cache->set($key, $value, 30);
		return $cache->get($key);

	}

}
