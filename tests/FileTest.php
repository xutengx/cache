<?php
declare(strict_types = 1);

use Gaara\Cache\Driver\File;
use PHPUnit\Framework\TestCase;

final class FileTest extends TestCase {

	protected $dir;
	protected $ext;

	public function testGetNewObject(): void {
		$this->dir = __DIR__ . '.storage/cache';
		$this->ext = '.php';

		$this->assertInstanceOf(File::class, $fileDriver = new File($this->dir, $this->ext));
		$this->assertClassHasAttribute('storageCachePath', File::class);
		$this->assertClassHasAttribute('cacheFileExt', File::class);

		return $fileDriver;

	}

	/**
	 * @depends testGetNewObject
	 */
	public function testRecursiveMakeDirectory(File $fileDriver): void{
		$dir_not_exit = __DIR__ . '.storage/cache';




	}
}


