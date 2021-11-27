<?php

namespace OCA\DuplicateFinder\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use OCP\Files\Node;
use OCP\Files\Folder;
use OCP\Share\IManager;
use OCP\Share\IShare;
use OCA\DuplicateFinder\Service\ShareService;

class ShareServiceTest extends TestCase
{

    private $path = '/admin/files';

    public function getService($throwError = false, $manager = null)
    {
        $node = $this->createMock(Node::class);
        $node->method('getPath')->willReturn($this->path.'/test.html');
        $adminFolder = $this->createMock(Folder::class);
        $adminFolder->method('getPath')->willReturn('/admin/files');
        $tuserFolder = $this->createMock(Folder::class);
        $tuserFolder->method('getPath')->willReturn('/tuser/files');

        if ($throwError) {
            $adminFolder->method('get')->willThrowException(new \Exception());
            $tuserFolder->method('get')->willThrowException(new \Exception());
        } else {
            $adminFolder->method('get')->willReturn($node);
            $tuserFolder->method('get')->willReturn($node);
        }
        
        $rootFolder = $this->createMock(IRootFolder::class);
        $rootFolder->method('getUserFolder')->willReturnMap([
            ['admin',$adminFolder],
            ['tuser',$tuserFolder]
        ]);
        if ($throwError) {
            $rootFolder->method('get')->willThrowException(new \Exception());
        } else {
            $rootFolder->method('get')->willReturn($node);
        }

        $share = $this->createMock(IShare::class);
        if (is_null($manager)) {
            $manager = $this->createMock(IManager::class);
            $manager->method('getSharedWith')->willReturn([$share]);
            $manager->method('getAccessList')->willReturn([]);
        }
                
        return new ShareService(
            $rootFolder,
            $manager,
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testGetShares()
    {
        $shares = $this->getService()->getShares('admin');
        $this->assertIsArray($shares);
        $shares = $this->getService()->getShares('admin', null, 1);
        $this->assertIsArray($shares);
    }

    public function testHasAccessRight()
    {
        $node = $this->createMock(Node::class);
        $node->method('getPath')->willReturn('/tuser/files/shared/file');

        $shares = $this->getService()->hasAccessRight($node, 'admin');
        $this->assertEquals(null, $shares);

        $share = $this->createMock(IShare::class);
        $share->method('getTarget')->willReturn('/shared_files/file');
        $share->method('getNodeType')->willReturn('file');
        $share->method('getSharedWith')->willReturn('admin');
        
        $manager = $this->createMock(IManager::class);
        $manager->method('getSharedWith')->willReturn([$share]);
        $manager->method('getAccessList')->willReturn(['users' => ['admin' => ['file']]]);
        $pathWithAccessRight = $this->getService(false, $manager)->hasAccessRight($node, 'admin');
        $this->assertEquals($this->path.'/shared_files/file', $pathWithAccessRight);
    }
}
