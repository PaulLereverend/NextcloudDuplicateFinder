<?php

namespace OCA\DuplicateFinder\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use OCP\IDBConnection;
use OCP\Files\Node;
use OCP\EventDispatcher\IEventDispatcher;
use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Db\FileInfoMapper;
use OCA\DuplicateFinder\Service\FileInfoService;
use OCA\DuplicateFinder\Service\FilterService;
use OCA\DuplicateFinder\Service\FolderService;
use OCA\DuplicateFinder\Service\ShareService;
use OCA\DuplicateFinder\Utils\ScannerUtil;

class FileInfoServiceTest extends TestCase
{

    public function setUp() : void
    {
        $node = $this->createMock(Node::class);
        $node->method('getId')->willReturn(1);
        $node->method('getMimetype')->willReturn('text/html');
        $node->method('getSize')->willReturn(4811);
        $folderService = $this->createMock(FolderService::class);
        $folderService->method('getNodeByFileInfo')->willReturn($node);

        $this->files = [new FileInfo('/admin/files/test.html', 'admin')];
        $mapper = $this->createMock(FileInfoMapper::class);
        $mapper->method('findAll')->willReturn($this->files);
        $mapper->method('find')->willReturn($this->files[0]);
        $mapper->method('findById')->willReturn($this->files[0]);
        $mapper->method('findByHash')->willReturn($this->files);

        $this->service = new FileInfoService(
            $mapper,
            $this->createMock(IEventDispatcher::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(ShareService::class),
            $folderService,
            $this->createMock(FilterService::class),
            $this->createMock(ScannerUtil::class)
        );
    }

    private function assertFileInfoAttrs($result)
    {
        $this->assertEquals(1, $result->getNodeId());
        $this->assertEquals('text/html', $result->getMimetype());
        $this->assertEquals(4811, $result->getSize());
    }

    public function testFindAll()
    {
        $result = $this->service->findAll(false);
        $this->assertEquals($this->files, $result);
        $this->assertEquals(null, $result[0]->getNodeId());

        $result = $this->service->findAll(true);
        $this->assertEquals($this->files, $result);
        $this->assertFileInfoAttrs($result[0]);
    }

    public function testFind()
    {
        $result = $this->service->find('/admin/files/test.html');
        $this->assertEquals($this->files[0], $result);
        $this->assertEquals(null, $result->getNodeId());

        $result = $this->service->find('/admin/files/test.html', null, true);
        $this->assertEquals($this->files[0], $result);
        $this->assertFileInfoAttrs($result);
    }

    public function testFindById()
    {
        $result = $this->service->findById(1);
        $this->assertEquals($this->files[0], $result);
        $this->assertEquals(null, $result->getNodeId());

        $result = $this->service->findById(1, true);
        $this->assertEquals($this->files[0], $result);
        $this->assertFileInfoAttrs($result);
    }

    public function testFindByHash()
    {
        $result = $this->service->findByHash('abc');
        $this->assertEquals($this->files, $result);
        $this->assertEquals(null, $result[0]->getNodeId());
    }
}
