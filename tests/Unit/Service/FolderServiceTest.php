<?php

namespace OCA\DuplicateFinder\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\Folder;
use OCA\DuplicateFinder\Service\FolderService;
use OCA\DuplicateFinder\Db\FileInfo;
use OCP\Files\NotFoundException;

class FolderServiceTest extends TestCase
{

    private $path = '/admin/files';

    public function getService($throwError = false)
    {
        $node = $this->createMock(Node::class);
        $node->method('getPath')->willReturn($this->path.'/test.html');
        $folder = $this->createMock(Folder::class);
        $folder->method('getPath')->willReturn($this->path);

        if ($throwError) {
            $folder->method('get')->willThrowException(new NotFoundException());
        } else {
            $folder->method('get')->willReturn($node);
        }
        
        $rootFolder = $this->createMock(IRootFolder::class);
        $rootFolder->method('getUserFolder')->willReturn($folder);
        $rootFolder->method('get')->willReturn($node);

        return new FolderService($rootFolder);
    }

    public function testGetUserFolder()
    {
        $result = $this->getService()->getUserFolder('admin');
        $this->assertEquals($this->path, $result->getPath());
    }

    public function testGetNodeByFileInfo()
    {
        $nodePath = $this->path.'/test.html';
        $service = $this->getService();
        $result = $service->getNodeByFileInfo(new FileInfo($nodePath, 'admin'));
        $this->assertInstanceOf(Node::class, $result);
        $this->assertEquals($nodePath, $result->getPath());
        $result = $service->getNodeByFileInfo(new FileInfo($nodePath), 'admin');
        $this->assertInstanceOf(Node::class, $result);
        $this->assertEquals($nodePath, $result->getPath());
        $result = $service->getNodeByFileInfo(new FileInfo($nodePath));
        $this->assertInstanceOf(Node::class, $result);
        $this->assertEquals($nodePath, $result->getPath());


        $service = $this->getService(true);
        $result = $service->getNodeByFileInfo(new FileInfo($nodePath), 'admin');
        $this->assertInstanceOf(Node::class, $result);
        $this->assertEquals($nodePath, $result->getPath());
    }
}
