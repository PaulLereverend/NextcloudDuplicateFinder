<?php
namespace OCA\DuplicateFinder\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use OCA\DuplicateFinder\Utils\JSONDateTime;

class EEntity extends Entity implements JsonSerializable {

	private $keepAsPrimary = false;
	private $internalTypes = [];
	private $_relationalFields = [];
	private $_changedRelations = [];

	protected function addInternalType(string $name, $type){
		if($type === "date"){
			$this->internalTypes[$name] = "date";
			$this->addType($name, 'integer');
		}elseif($type === "json"){
			$this->internalTypes[$name] = "json";
			$this->addType($name, 'string');
		}
	}

	protected function addRelationalField(string $field){
		$this->_relationalFields[$field] = 1;
		$this->_changedRelations[$field] = [];
	}

  public function resetUpdatedRelationalFields(){
		$this->_changedRelations = [];
	}

	protected function markRelationalFieldUpdated(string $field, $key, $value = null){
		$this->_changedRelations[$field][$key] = $value;
	}

	public function getRelationalFields(){
		return $this->_relationalFields;
	}

	public function getUpdatedRelationalFields(?string $field = null){
		if($field !== null){
			return $this->_changedRelations[$field];
		}
		return $this->_changedRelations;
	}

	protected function markFieldUpdated($attribute) {
		if(!isset($this->getRelationalFields()[$attribute])){
			parent::markFieldUpdated($attribute);
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
      $args[0] = json_encode($args[0]);
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
      $result = json_decode($result);
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
