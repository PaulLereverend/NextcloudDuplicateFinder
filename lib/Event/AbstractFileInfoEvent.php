<?php
namespace OCA\DuplicateFinder\Event;

use OCP\EventDispatcher\Event;
use OCA\DuplicateFinder\Db\FileInfo;

abstract class AbstractFileInfoEvent extends Event
{

  /** @var FileInfo */
    private $fileInfo;
    /** @var null|string */
    private $userId;

    public function __construct(FileInfo $fileInfo, ?string $userId)
    {
        parent::__construct();
        $this->fileInfo = $fileInfo;
        $this->userId = $userId;
    }

    public function getFileInfo(): FileInfo
    {
        return $this->fileInfo;
    }

    public function getUserID(): ?string
    {
        return $this->userId;
    }
}
