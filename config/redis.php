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
			'password' => env('REDIS_PASSWORD', null),
			'port'     => env('REDIS_PORT', 6379),
			'database' => 0,
		],
		'con2'    => [
			'host'     => '127.0.0.1',
			'password' => 'XqlyvpDHtvnZI^c8@',
			'port'     => 6379,
			'database' => 0,
		],
		'con3'    => [
			'host'     => '127.0.0.1',
			'password' => 'y30@JK9H^l',
			'port'     => 6379,
			'database' => 0,
		],
	],
];
