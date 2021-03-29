<?php
namespace OCA\DuplicateFinder\Event;

use OCP\EventDispatcher\Event;
use OCA\DuplicateFinder\Db\FileInfo;

class CalculatedHashEvent extends Event {

  /** @var FileInfo */
  private $fileInfo;
  /** @var string */
  private $oldHash;

  public function __construct(FileInfo $fileInfo, ?string $oldHash) {
      parent::__construct();
      $this->fileInfo = $fileInfo;
      $this->oldHash = $oldHash;
  }

  public function getFileInfo(): FileInfo {
      return $this->fileInfo;
  }

  public function isNew(): bool {
      return empty($this->oldHash);
  }

  public function isChanged(): bool {
      return $this->fileInfo->getFileHash() !== $this->oldHash;
  }

  public function getOldHash(): string {
      return $this->oldHash;
  }

}
