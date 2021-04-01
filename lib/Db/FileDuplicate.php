<?php
namespace OCA\DuplicateFinder\Db;

class FileDuplicate extends EEntity{

	protected string $type;
	protected string $hash;
	/** @var array<string> */
	protected array $files = [];

	public function __construct(?string $hash = null, string $type = "file_hash") {
		$this->addRelationalField("files");

		if(!is_null($hash)){
			$this->setHash($hash);
		}
		$this->setType($type);
	}

	public function addDuplicate(int $id, string $owner):void{
		$this->files[$id] = $owner;
		$this->markRelationalFieldUpdated("files", $id, $owner);
	}

	public function removeDuplicate(int $id):void{
		unset($this->files[$id]);
		$this->markRelationalFieldUpdated("files", $id);
	}

	public function clear():void{
		$this->files = [];
	}

  /**
	 * @return array<string>
	 */
	public function getFiles():array{
		return $this->files;
	}

	public function getCount(): int{
		return count($this->getFiles());
	}

	public function getCountForUser(string $user): int{
		$result = 0;
		foreach($this->getFiles() as $u){
			if($u === $user ){
				$result += 1;
			}
		}
		return $result;
	}
}
