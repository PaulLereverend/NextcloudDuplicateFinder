<?php
namespace OCA\DuplicateFinder\Utils;

use OCP\Files\Node;
use OCP\Files\Folder;
use OCP\Share\IShare;
use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Exception\UnknownOwnerException;

class PathConversionUtils
{

    /*
     *  This method should only be called if the owner of the Node has already stored
     *  in the owner property
     */
    public static function convertRelativePathToUserFolder(FileInfo $fileInfo, Folder $userFolder) : string
    {
        if ($fileInfo->getOwner()) {
            return substr($fileInfo->getPath(), strlen($userFolder->getPath()));
        } else {
            throw new UnknownOwnerException($fileInfo->getPath());
        }
    }

    public static function convertSharedPath(
        Folder $srcUserFolder,
        Folder $targetUserFolder,
        Node $srcNode,
        IShare $share,
        int $strippedFolders
    ) : string {
        if ($share->getNodeType() === 'file') {
            return $targetUserFolder->getPath().$share->getTarget();
        }
        $srcPath = substr(
            $srcNode->getPath(),
            strlen($srcUserFolder->getPath())
        );
        $srcPath = explode('/', $srcPath);
        $srcPath = array_slice($srcPath, -$strippedFolders);
        $srcPath = implode('/', $srcPath);
        return $targetUserFolder->getPath().$share->getTarget().'/'.$srcPath;
    }
}
