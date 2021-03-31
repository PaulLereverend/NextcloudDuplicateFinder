<?php
namespace OCA\DuplicateFinder\Db;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Db\Entity;
use OCP\DB\QueryBuilder\IQueryBuilder;

abstract class EQBMapper extends QBMapper{

	public function delete(Entity $entity): Entity {
    $entity = parent::delete($entity);
    $this->clearRelationalFields($entity);
    return $entity;
  }

  public function insert(Entity $entity): Entity {
    $entity = parent::insert($entity);
    $this->saveRelationalFields($entity);
    return $entity;
  }

  public function update(Entity $entity): Entity {
    $entity = parent::update($entity);
    $this->saveRelationalFields($entity);
    return $entity;
  }

	protected function mapRowToEntity(array $row): Entity {
		$entity = parent::mapRowToEntity($row);
		return $this->loadRelationalFields($entity);
	}

  protected function loadRelationalFields(EEntity $entity) {
    $idType = $this->getParameterTypeForProperty($entity, 'id');
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
		$entity->resetUpdatedRelationalFields();
		return $entity;
  }

  protected function clearRelationalFields(EEntity $entity) {
    $idType = $this->getParameterTypeForProperty($entity, 'id');
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
		$updatedRelations = $entity->getUpdatedRelationalFields();
    foreach($entity->getRelationalFields() as $field => $v){
      foreach($updatedRelations[$field] as $key => $value){
				$qb = $this->db->getQueryBuilder();
				if($value !== null){
					$qb->insert($this->getTableName()."_".substr($field,0,1))
					->setValue("id", $qb->createNamedParameter($entity->getId(), $idType))
					->setValue("rid", $qb->createNamedParameter($key, IQueryBuilder::PARAM_INT))
					->setValue("value", $qb->createNamedParameter($value, IQueryBuilder::PARAM_STR));
				}else{
		      $qb->delete($this->getTableName()."_".substr($field,0,1))
		        ->where(
		          $qb->expr()->eq('id', $qb->createNamedParameter($entity->getId(), $idType)),
		          $qb->expr()->eq('rid', $qb->createNamedParameter($key, IQueryBuilder::PARAM_INT))
		        );
		      $qb->execute();
				}
				$qb->execute();
      }
    }
  }

}
