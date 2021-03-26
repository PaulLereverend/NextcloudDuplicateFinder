<?php
namespace OCA\DuplicateFinder\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use OCA\DuplicateFinder\Utils\JSONDateTime;

class FileInfo extends Entity implements JsonSerializable {

	protected $owner;
	protected $path;
	protected $fileHash;
	protected $otherHashes;
	protected $updatedAt;

	private $keepAsPrimary = false;
	private $internalTypes = [];

	public function __construct(?string $path = null, ?string $owner = null) {
		$this->addInternalType('updatedAt', 'date');
		$this->addInternalType('otherHashes', 'json');

		if(!is_null($path)){
			$this->setPath($path);
		}
		if(!is_null($owner)){
			$this->setOwner($owner);
		}
	}

	private function addInternalType(string $name, $type){
		if($type === "date"){
			$this->internalTypes[$name] = "date";
			$this->addType($name, 'integer');
		}elseif($type === "json"){
			$this->internalTypes[$name] = "json";
			$this->addType($name, 'string');
		}
	}

  /**
   * Method-Wrapper setter of the Entity to support new types (date, json)
   */
  protected function setter($name, $args) {
    $type = $this->getFieldTypeByName($name);
    // If a date fild has another value type than DateTime we exepct,
    // that the db can handle it or the app know what it does
    if($type === "date" && $args[0] instanceof \DateTime ){
      $args[0] = $args[0]->getTimestamp();
    }elseif($type === "json"){
      $args[0] = json_decode($args[0]);
    }
    parent::setter($name, $args);
  }

  /**
   * Method-Wrapper setter of the Entity to support new types (date, json)
   */
  protected function getter($name) {
    $result = parent::getter($name);
		if($this->keepAsPrimary()){
			return $result;
		}
    $type = $this->getFieldTypeByName($name);
    if($type === "date" && (is_null($result) || is_numeric($result))){
      // Use a custom DateTime object that serializes to a well-known date-time-format
      $result = (new JSONDateTime())->setTimestamp($result);
    }elseif($type === "json"){
      $result = json_encode($result);
    }
    return $result;
  }

  /**
   * Helper to prevent code dupplication in getter and setter
   */
  private function getFieldTypeByName($fieldName) {
		if(isset($this->internalTypes[$fieldName])){
			return $this->internalTypes[$fieldName];
		}

		$fieldTypes = $this->getFieldTypes();
    if(isset($fieldTypes[$fieldName])){
      return $fieldTypes[$fieldName];
    }
    return "string";
  }

  /**
   * Dynamically Build the JSON-Array
	 * @return array serialized data
	 * @throws \ReflectionException
	 */
  public function jsonSerialize() {
    $properties = get_object_vars($this);
    $reflection = new \ReflectionClass($this);
    $json = [];
		foreach ($properties as $property => $value) {
      if($this->getFieldTypeByName($property) !== "bool"){
        $methodName = "get";
      }else{
        $methodName = "is";
      }
      $methodName .= ucfirst($property);
      $json[$property] = $this->$methodName();
    }
		return $json;
  }

	public function keepAsPrimary(){
		return $this->keepAsPrimary;
	}

	public function setKeepAsPrimary($keepAsPrimary){
		$this->keepAsPrimary = $keepAsPrimary;
	}

}
