<?php

namespace OCA\DuplicateFinder\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use OCA\DuplicateFinder\Service\FileInfoService;
use OCA\DuplicateFinder\Db\FileInfoMapper;

class FileInfoServiceTest extends TestCase {

	public function setUp() : void {
		$mapper =  $this->createMock(FileInfoMapper::class);
    $rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();
		$this->controller = new FileInfoService(
			$mapper, $rootFolder
		);
	}

	public function testSetup() {
		$this->assertTrue(true);
	}

}
