<?php
namespace OCA\DuplicateFinder\Service;

use OCP\IUser;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;

use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Db\FileInfoMapper;
use OCA\DuplicateFinder\Event\CalculatedHashEvent;

class FileInfoService {

	/** @var IEventDispatcher */
	private $eventDispatcher;

  /** @var FileInfoMapper */
  private $mapper;

  /** @var IRootFolder */
  private $rootFolder;

  public function __construct(FileInfoMapper $mapper,
                          		IRootFolder $rootFolder,
                          		IEventDispatcher $eventDispatcher){
    $this->mapper = $mapper;
    $this->rootFolder = $rootFolder;
		$this->eventDispatcher = $eventDispatcher;
  }

	/**
	 * @return array<FileInfo>
	 */
  public function findAll():array {
    return $this->mapper->findAll();
  }

  public function find(string $path):FileInfo {
    return $this->mapper->find($path);
  }

  public function findById(int $id):FileInfo {
    return $this->mapper->findById($id);
  }

	/**
	 * @return array<FileInfo>
	 */
	public function findByHash(string $hash, string $type = "file_hash"):array {
		return $this->mapper->findByHash($hash, $type);
	}

	public function countByHash(string $hash, string $type = "file_hash"):int {
		return $this->mapper->countByHash($hash, $type);
	}

  public function createOrUpdate(string $path, IUser $owner):FileInfo {
    $fileInfo = $this->getOrCreate($owner, $path);
    return $this->calculateHashes($fileInfo);
  }

  public function update(FileInfo $fileInfo):FileInfo {
    $fileInfo->setKeepAsPrimary(true);
    $fileInfo = $this->mapper->update($fileInfo);
    $fileInfo->setKeepAsPrimary(false);
    return $fileInfo;
  }

  public function getOrCreate(IUser $owner, string $path):FileInfo{
    try{
      $fileInfo = $this->mapper->find($path);
    }catch(\Exception $e){
      $fileInfo = new FileInfo($path, $owner->getUID());
      $fileInfo->setKeepAsPrimary(true);
      $fileInfo = $this->mapper->insert($fileInfo);
      $fileInfo->setKeepAsPrimary(false);
    }
    return $fileInfo;
  }

  public function delete(FileInfo $fileInfo):FileInfo {
    $this->mapper->delete($fileInfo);
    return $fileInfo;
  }

  public function calculateHashes(FileInfo $fileInfo):FileInfo{
    $file = $this->rootFolder->get($fileInfo->getPath());
    if($file->getMtime() > $fileInfo->getUpdatedAt()->getTimestamp() || $file->getUploadTime() > $fileInfo->getUpdatedAt()->getTimestamp()){
			$oldHash = $fileInfo->getFileHash();
      $fileInfo->setFileHash($file->getStorage()->hash("sha256", $file->getInternalPath()));
      $fileInfo->setUpdatedAt(new \DateTime());
      $this->update($fileInfo);
      $this->eventDispatcher->dispatchTyped(new CalculatedHashEvent($fileInfo, $oldHash));
    }
    return $fileInfo;
  }
}
