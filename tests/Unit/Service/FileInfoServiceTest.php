<?php

namespace OCA\DuplicateFinder\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use OCP\ILogger;
use OCP\IDBConnection;
use OCP\IConfig;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Share\IManager;
use OCA\DuplicateFinder\Db\FileInfoMapper;
use OCA\DuplicateFinder\Service\FileInfoService;
use OCA\DuplicateFinder\Service\ShareService;

class FileInfoServiceTest extends TestCase
{

    public function setUp() : void
    {
        $this->controller = new FileInfoService(
            $this->createMock(FileInfoMapper::class),
            $this->createMock(IRootFolder::class),
            $this->createMock(IEventDispatcher::class),
            $this->createMock(ILogger::class),
            $this->createMock(IDBConnection::class),
            $this->createMock(IConfig::class),
            $this->createMock(ShareService::class)
        );
    }

    public function testSetup()
    {
        $this->assertTrue(true);
    }
}
