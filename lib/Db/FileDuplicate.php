<?php
namespace OCA\DuplicateFinder\Db;

class FileDuplicate extends EEntity{

	protected $type;
	protected $hash;
	protected $files = [];

	public function __construct(?string $hash = null, string $type = "file_hash") {
		$this->addRelationalField("files");

		if(!is_null($hash)){
			$this->setHash($hash);
		}
		if(!is_null($type)){
			$this->setType($type);
		}
	}

	public function addDuplicate(int $id, string $owner){
		$this->files[$id] = $owner;
		$this->markRelationalFieldUpdated("files", $id, $owner);
	}

	public function removeDuplicate(int $id){
		unset($this->files[$id]);
		$this->markRelationalFieldUpdated("files", $id);
	}

	public function clear(){
		$this->files = [];
	}

	public function getFiles(){
		return $this->files;
	}

	public function getCount(){
		return count($this->getFiles());
	}

	public function getCountForUser(string $user){
		$result = 0;
		foreach($this->getFiles() as $u){
			if($u === $user ){
				$result += 1;
			}
		}
		return $result;
	}
}
