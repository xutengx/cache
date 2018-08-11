<?php

declare(strict_types = 1);
namespace Gaara\Cache\dev;

use Gaara\Cache;
use Gaara\Cache\Driver\{
	Redis, File
};

if(!function_exists('env')){
	function env(string $envName, $default = null){
		return $default;
	}
}

class test {

	public function index() {

		/**
			ykj-prod-public
		 * ykj-prod-protected
		 *
		 * ykj-beta-public
		 * ykj-beta-protected
		 *
		 * ykj-test-public
		 * ykj-test-protected
		 */


		$redisDriver = new Redis('');

		$fileDriver = new File('');

		$cacheUseRedis = new Cache($redisDriver);

		$cacheUseFile = new Cache($fileDriver);

		$cacheUseRedis->get();

		$cacheUseFile->set();
	}

}
