<?php

return [
	/*
	  |--------------------------------------------------------------------------
	  | redis默认使用的redis连接
	  |--------------------------------------------------------------------------
	  |
	  |
	 */
	'default_connection' => env('REDIS_CONNECTION', 'default'),
	/*
	  |--------------------------------------------------------------------------
	  | redis可使用的redis连接
	  |--------------------------------------------------------------------------
	  |
	  |
	 */
	'connections'        => [
		'default' => [
			'host'     => env('REDIS_HOST', '127.0.0.1'),
			'port'     => env('REDIS_PORT', 6379),
			'password' => env('REDIS_PASSWORD', null),
			'database' => 0,
		],
		'con2'    => [
			'host'     => '127.0.0.1',
			'port'     => 6379,
			'password' => 'XqlyvpDHtvnZI^c8@',
			'database' => 0,
		],
		'con3'    => [
			'host'     => '127.0.0.1',
			'port'     => 6379,
			'password' => 'y30@JK9H^l',
			'database' => 0,
		],
	],
];
