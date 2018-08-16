<?php
declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/dev/test.php';

final class TestDev extends TestCase {

	public function testTest() {
		$value = 'gaara';
		$obj   = new \test;
		$this->assertEquals($obj->index($value), $value);
	}

}


