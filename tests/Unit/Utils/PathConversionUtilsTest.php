<?php

namespace OCA\DuplicateFinder\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Share\IShare;
use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Utils\PathConversionUtils;
use OCA\DuplicateFinder\Exception\UnknownOwnerException;

class PathConversionUtilsTest extends TestCase
{

    public function setUp() : void
    {
        $this->folder = $this->createMock(Folder::class);
        $this->folder->method('getPath')
            ->willReturn('/tuser/files');
        $this->targetFolder = $this->createMock(Folder::class);
        $this->targetFolder->method('getPath')
            ->willReturn('/admin/files');
        $this->node = $this->createMock(Node::class);
        $this->node->method('getPath')
            ->willReturn('/tuser/files/shared/file');
        $this->share = $this->createMock(IShare::class);
        $this->share->method('getTarget')
            ->willReturn('/shared_files');
    }

    public function testConvertRelativePathToUserFolderException()
    {
        $this->expectException(UnknownOwnerException::class);
        $this->expectExceptionMessage('The owner of /admin/files/abc is not set');
        
        PathConversionUtils::convertRelativePathToUserFolder(
            new FileInfo('/admin/files/abc'),
            $this->targetFolder
        );
    }

    public function testConvertRelativePathToUserFolder()
    {
        $result = PathConversionUtils::convertRelativePathToUserFolder(
            new FileInfo('/admin/files/abc', 'admin'),
            $this->targetFolder
        );
        $this->assertEquals('/abc', $result);
    }

    public function testConvertSharedPath()
    {
        $result = PathConversionUtils::convertSharedPath(
            $this->folder,
            $this->targetFolder,
            $this->node,
            $this->share,
            1
        );
        $this->assertEquals('/admin/files/shared_files/file', $result);
    }
}
