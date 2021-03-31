<?php
namespace OCA\DuplicateFinder\Service;

use OCP\IUser;
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

  public function findAll() {
    return $this->mapper->findAll();
  }

  public function find(string $path) {
    return $this->mapper->find($path);
  }

  public function findById(int $id) {
    return $this->mapper->findById($id);
  }

	public function countByHash(string $hash, string $type = "file_hash") {
		return $this->mapper->countByHash($hash, $type);
	}

  public function createOrUpdate(string $path, ?IUser $owner = null) {
    $fileInfo = $this->getOrCreate($owner, $path);
    return $this->calculateHashes($fileInfo);
  }

  public function update(FileInfo $fileInfo) {
    $fileInfo->setKeepAsPrimary(true);
    $fileInfo = $this->mapper->update($fileInfo);
    $fileInfo->setKeepAsPrimary(false);
    return $fileInfo;
  }

  public function getOrCreate(IUser $owner, string $path){
    try{
      $fileInfo = $this->mapper->find($path);
    }catch(\Exception $e){
      $fileInfo = new FileInfo($path, !is_null($owner) ? $owner->getUID(): null);
      $fileInfo->setKeepAsPrimary(true);
      $fileInfo = $this->mapper->insert($fileInfo);
      $fileInfo->setKeepAsPrimary(false);
    }
    return $fileInfo;
  }

  public function delete(string $path) {
    try {
      $fileInfo = $this->mapper->find($path);
      $this->mapper->delete($fileInfo);
      return $fileInfo;
    }catch(DoesNotExistException $e){
      return null;
    }
  }

  public function calculateHashes(FileInfo $fileInfo){
    $file = $this->rootFolder->get($fileInfo->getPath());
    if($file){
      if($file->getMtime() > $fileInfo->getUpdatedAt()->getTimestamp() || $file->getUploadTime() > $fileInfo->getUpdatedAt()->getTimestamp()){
				$oldHash = $fileInfo->getFileHash();
        $fileInfo->setFileHash($file->getStorage()->hash("sha256", $file->getInternalPath()));
        $fileInfo->setUpdatedAt(new \DateTime());
        $this->update($fileInfo);
        $this->eventDispatcher->dispatchTyped(new CalculatedHashEvent($fileInfo, $oldHash));
      }
    }else{
      throw new \Exception("File ".$fileInfo->getId()." doesn't exists.");
    }
    return $fileInfo;
  }

  public function getDuplicates(?string $owner, ?int $limit = null, ?int $offset = null){
    return $this->mapper->findDuplicates($owner, $limit, $offset);
  }
}
