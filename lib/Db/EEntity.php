<?php
namespace OCA\DuplicateFinder\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use OCA\DuplicateFinder\Utils\JSONDateTime;

class EEntity extends Entity implements JsonSerializable
{

    /** @var bool */
    private $keepAsPrimary = false;
    /** @var array<string> */
    private $internalTypes = [];
    /** @var array<mixed> */
    private $relationalFields = [];
    /** @var array<array> */
    private $changedRelations = [];
    /** @var array<array<bool>> */
    private $internalProperties = [
        'keepAsPrimary' => [true, true],
        'internalTypes' => [true, true],
        'relationalFields' => [true, true],
        'changedRelations' => [true, true],
        'internalProperties' => [true, true]
    ];

    protected function addInternalType(string $name, string $type):void
    {
        if ($type === 'date') {
            $this->internalTypes[$name] = 'date';
            $this->addType($name, 'integer');
        } elseif ($type === 'json') {
            $this->internalTypes[$name] = 'json';
            $this->addType($name, 'string');
        }
    }

    protected function addInternalProperty(
        string $name,
        bool $excludeFromJSON = false,
        bool $excludeFromDB = true
    ):void {
        $this->internalProperties[$name] = [$excludeFromJSON, $excludeFromDB];
    }

    protected function addRelationalField(string $field):void
    {
        $this->relationalFields[$field] = 1;
        $this->changedRelations[$field] = [];
    }

    public function resetUpdatedRelationalFields():void
    {
        foreach ($this->changedRelations as $field => $value) {
            $this->changedRelations[$field] = [];
        }
        unset($value);
    }

    /**
     * @param string $field
     * @param mixed $key
     * @param mixed $value
     */
    protected function markRelationalFieldUpdated(string $field, $key, $value = null):void
    {
        $this->changedRelations[$field][$key] = $value;
    }

    /**
     * @return array<mixed>;
     */
    public function getRelationalFields():array
    {
        return $this->relationalFields;
    }

    /**
     * @return array<array<bool>>
     */
    public function getInternalProperties():array
    {
        return $this->internalProperties;
    }

    /**
     * @return array<array>;
     */
    public function getUpdatedRelationalFields(?string $field = null):array
    {
        if ($field !== null) {
            return $this->changedRelations[$field];
        }
        return $this->changedRelations;
    }

    /**
     * @return void;
     */
    protected function markFieldUpdated($attribute)
    {
        if (!isset($this->getRelationalFields()[$attribute])
          && !(isset($this->getInternalProperties()[$attribute])
            && $this->getInternalProperties()[$attribute][1])) {
            parent::markFieldUpdated($attribute);
        }
    }

  /**
   * Method-Wrapper setter of the Entity to support new types (date, json)
     * @param string $name
     * @param array<mixed> $args
     * @return void
   */
    protected function setter($name, $args)
    {
        $type = $this->getFieldTypeByName($name);
      // If a date fild has another value type than DateTime we exepct,
      // that the db can handle it or the app know what it does
        if ($type === 'date' && $args[0] instanceof \DateTime) {
            $args[0] = $args[0]->getTimestamp();
        } elseif ($type === 'json') {
            $args[0] = json_encode($args[0]);
        }
        parent::setter($name, $args);
    }

  /**
   * Method-Wrapper setter of the Entity to support new types (date, json)
     * @param string $name
     * @return mixed
   */
    protected function getter($name)
    {
        $result = parent::getter($name);
        if ($this->keepAsPrimary()) {
            return $result;
        }
        $type = $this->getFieldTypeByName($name);
        if ($type === 'date' && (is_null($result) || is_numeric($result))) {
          // Use a custom DateTime object that serializes to a well-known date-time-format
            $result = (new JSONDateTime())->setTimestamp((int)$result);
        } elseif ($type === 'json') {
            $result = json_decode($result);
        }
        return $result;
    }

  /**
   * Helper to prevent code dupplication in getter and setter
     * @param string $fieldName
     * @return string
   */
    private function getFieldTypeByName($fieldName)
    {
        if (isset($this->internalTypes[$fieldName])) {
            return $this->internalTypes[$fieldName];
        }

        $fieldTypes = $this->getFieldTypes();
        if (isset($fieldTypes[$fieldName])) {
            return $fieldTypes[$fieldName];
        }
        return 'string';
    }

  /**
   * Dynamically Build the JSON-Array
     * @return array<mixed> serialized data
     * @throws \ReflectionException
     */
    public function jsonSerialize()
    {
        $properties = get_object_vars($this);
        $json = [];
        foreach ($properties as $property => $value) {
            if (isset($this->getInternalProperties()[$property])
              && $this->getInternalProperties()[$property][0]) {
                continue;
            }
            if ($this->getFieldTypeByName($property) !== 'bool') {
                $methodName = 'get';
            } else {
                $methodName = 'is';
            }
            $methodName .= ucfirst($property);
            $json[$property] = $this->$methodName();
        }
        unset($value);
        return $json;
    }

    /**
     * @return bool
     */
    public function keepAsPrimary()
    {
        return $this->keepAsPrimary;
    }

    /**
     * @param bool $keepAsPrimary
     * @return void
     */
    public function setKeepAsPrimary($keepAsPrimary)
    {
        $this->keepAsPrimary = $keepAsPrimary;
    }
}
