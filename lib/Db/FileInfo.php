<?php
namespace OCA\DuplicateFinder\Db;

class FileInfo extends EEntity{

	protected $owner;
	protected $path;
	protected $fileHash;
	protected $imageHash;
	protected $updatedAt;

	public function __construct(?string $path = null, ?string $owner = null) {
		$this->addInternalType('updatedAt', 'date');

		if(!is_null($path)){
			$this->setPath($path);
		}
		if(!is_null($owner)){
			$this->setOwner($owner);
		}
	}
}
