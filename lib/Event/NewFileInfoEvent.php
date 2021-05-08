<?php
namespace OCA\DuplicateFinder\Event;

use OCP\EventDispatcher\Event;
use OCA\DuplicateFinder\Db\FileInfo;

class NewFileInfoEvent extends Event
{

  /** @var FileInfo */
    private $fileInfo;

    public function __construct(FileInfo $fileInfo)
    {
        parent::__construct();
        $this->fileInfo = $fileInfo;
    }

    public function getFileInfo(): FileInfo
    {
        return $this->fileInfo;
    }
}
