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
        Folder $userFolder,
        Node $node,
        IShare $share,
        int $strippedFolders
    ) : string {
        $paths = explode('/', $node->getPath());
        $paths = array_slice($paths, -$strippedFolders);
        return $userFolder->getPath().$share->getTarget().'/'.implode('/', $paths);
    }
}
