<?php
namespace OCA\DuplicateFinder\Service;

use OCP\IUser;
use OCP\AppFramework\Db\DoesNotExistException;

use OCA\DuplicateFinder\Db\FileDuplicate;
use OCA\DuplicateFinder\Db\FileDuplicateMapper;

class FileDuplicateService {

  /** @var FileDuplicateMapper */
  private $mapper;

  public function __construct(FileDuplicateMapper $mapper){
    $this->mapper = $mapper;
  }

  public function findAll(?string $user = null) {
    return $this->mapper->findAll($user);
  }

  public function find(string $hash, string $type = "file_hash") {
    return $this->mapper->find($hash, $type);
  }

  public function createOrUpdate(string $hash, string $owner, int $step = 1, string $type = "file_hash") {
    $fileDuplicate = $this->getOrCreate($hash, $type);
		$fileDuplicate->changeCount($owner, $step);
    return $this->update($fileDuplicate);
  }

  public function update(FileDuplicate $fileDuplicate) {
    $fileDuplicate->setKeepAsPrimary(true);
    $fileDuplicate = $this->mapper->update($fileDuplicate);
    $fileDuplicate->setKeepAsPrimary(false);
    return $fileDuplicate;
  }

  public function getOrCreate(string $hash, string $type = "file_hash"){
    try{
      $fileDuplicate = $this->mapper->find($hash, $type);
    }catch(\Exception $e){
      $fileDuplicate = new FileDuplicate($hash, $type);
      $fileDuplicate->setKeepAsPrimary(true);
      $fileDuplicate = $this->mapper->insert($fileDuplicate);
      $fileDuplicate->setKeepAsPrimary(false);
    }
    return $fileDuplicate;
  }

  public function delete(string $hash, string $type = "file_hash") {
    try {
      $fileDuplicate = $this->mapper->find($hash, $type);
      $this->mapper->delete($fileDuplicate);
      return $fileDuplicate;
    }catch(DoesNotExistException $e){
      return null;
    }
  }

  public function clearDuplicates(int $id){
    $fileDuplicates = $this->mapper->findByDuplicate($id);
    foreach($fileDuplicates as $fileDupplicate){
      $fileDuplicate->removeDuplicate($id);
      $this->update($fileDuplicate);
    }
  }
}
