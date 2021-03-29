<?php
namespace OCA\DuplicateFinder\Db;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;

abstract class EQBMapper extends QBMapper{

	public function delete(EEntity $entity): EEntity {
    $entity = parent::delete($entity);
    $this->clearRelationalFields($entity);
    return $entity;
  }

  public function insert(EEntity $entity): EEntity {
    $entity = parent::insert($entity);
    $this->saveRelationalFields($entity);
    return $entity;
  }

  public function update(EEntity $entity): EEntity {
    $entity = parent::update($entity);
    $this->clearRelationalFields($entity);
    $this->saveRelationalFields($entity);
    return $entity;
  }

	protected function mapRowToEntity(array $row): EEntity {
		$entity = parent::mapRowToEntity($row);
		return $this->loadRelationalFields($entity);
	}

  protected function loadRelationalFields(EEntity $entity) {
    foreach($entity->getRelationalFields() as $field => $v){
      $qb = $this->db->getQueryBuilder();
      $qb->select("*")
				->from($this->getTableName()."_".substr($field,0,1))
        ->where(
          $qb->expr()->eq('id', $qb->createNamedParameter($entity->getId(), $idType))
        );
      $qb = $qb->execute();
			$values = [];
			foreach($qb->fetchAll() as $row){
				$values[$row["rid"]] = $row["value"];
			}
			$setter = 'set' . ucfirst($field);
			$entity->$setter($values);
    }
		return $entity;
  }

  protected function clearRelationalFields(EEntity $entity) {
    foreach($entity->getRelationalFields() as $field => $v){
      $qb = $this->db->getQueryBuilder();
      $qb->delete($this->getTableName()."_".substr($field,0,1))
        ->where(
          $qb->expr()->eq('id', $qb->createNamedParameter($entity->getId(), $idType))
        );
      $qb->execute();
    }
  }

  protected function saveRelationalFields(EEntity $entity) {
    $idType = $this->getParameterTypeForProperty($entity, 'id');
    foreach($entity->getRelationalFields() as $field => $v){
      $method = 'get' . ucfirst($field);
      foreach($entity->$method() as $key => $value){
        $qb = $this->db->getQueryBuilder();
        $qb->insert($this->getTableName()."_".substr($field,0,1))
          ->setValue("id", $qb->createNamedParameter($entity->getId(), $idType))
          ->setValue("rid", $qb->createNamedParameter($key, IQueryBuilder::PARAM_INT));
				if($value !== null){
					$qb->setValue("value", $qb->createNamedParameter($value, IQueryBuilder::PARAM_STR));
				}
        $qb->execute();
      }
    }
  }

}
