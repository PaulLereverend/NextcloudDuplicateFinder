<?php
namespace OCA\DuplicateFinder\Service;

use OCP\IDBConnection;
use OCP\IUser;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Db\FileInfoMapper;

class FileInfoService {

  private $mapper;
  private $rootFolder;

  public function __construct(
    FileInfoMapper $mapper,
		IRootFolder $rootFolder){
    $this->mapper = $mapper;
    $this->rootFolder = $rootFolder;
  }

  public function findAll() {
    return $this->mapper->findAll();
  }

  public function find(string $path) {
    return $this->mapper->find($path);
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
      if($file->getMtime() > $fileInfo->getUpdatedAt()->getTimestamp()){
        $fileInfo->setFileHash($file->getStorage()->hash("sha256", $file->getInternalPath()));
        $fileInfo->setUpdatedAt(new \DateTime());
        $this->update($fileInfo);
      }
    }else{
      throw new \Exception("File ".$fileInfo->getId()." doesn't exists.");
    }
    return $fileInfo;
  }

  public function getDuplicates(?string $owner){
    $duplicates = $this->mapper->findDupplicates($owner);
    /**
     * If for some reason a delete or rename Event wasn't handled properly we cleanup this up here
     */
    for($i = 0; $i < count($duplicates); $i++){
      for($j = 0; $j < count($duplicates[$i]); $j++){
        try{
          $this->rootFolder->get($duplicates[$i][$j]->getPath());
        }catch(NotFoundException $e){
          $this->delete($duplicates[$i][$j]->getPath());
          unset($duplicates[$i][$j]);
        }
      }
      $duplicates[$i] = array_filter($duplicates[$i]);
      if(count($duplicates[$i]) < 2){
        unset($duplicates[$i]);
      }
    }
    return array_filter($duplicates);
  }
}
