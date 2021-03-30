<?php
namespace OCA\DuplicateFinder\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;

class FileInfoMapper extends QBMapper {

  public function __construct(IDBConnection $db) {
    parent::__construct($db, 'duplicatefinder_finfo', FileInfo::class);
  }

  public function find(string $path) {
    $qb = $this->db->getQueryBuilder();
    $qb->select('*')
      ->from($this->getTableName())
      ->where(
        $qb->expr()->eq('path', $qb->createNamedParameter($path))
      );
    return $this->findEntity($qb);
  }

  public function findByHash(string $hash, string $type = "file_hash") {
    $qb = $this->db->getQueryBuilder();
    $qb->select('*')
      ->from($this->getTableName())
      ->where(
        $qb->expr()->eq($type, $qb->createNamedParameter($hash))
      );
    return $this->findEntities($qb);
  }

  public function findById(int $id) {
    $qb = $this->db->getQueryBuilder();
    $qb->select('*')
      ->from($this->getTableName())
      ->where(
        $qb->expr()->eq("id", $qb->createNamedParameter($id), IQueryBuilder::PARAM_INT )
      );
    return $this->findEntity($qb);
  }

  public function findAll() {
    $qb = $this->db->getQueryBuilder();
    $qb->select('*')
      ->from($this->getTableName());
    return $this->findEntities($qb);
  }

  public function findDuplicates(?string $owner, ?int $limit = null, ?int $offset = null){
    $duplicates = $this->db->getQueryBuilder();
    $duplicates->select('file_hash', $duplicates->func()->count('id'))
      ->from($this->getTableName())
      ->groupBy('file_hash', "owner")
      ->having('COUNT(id) > 1');
    if($owner){
      $duplicates = $duplicates->where(
        $duplicates->expr()->eq('owner', $duplicates->createNamedParameter($owner))
      );
    }

    if ($limit !== null) {
			$duplicates->setMaxResults($limit);
		}
		if ($offset !== null) {
			$duplicates->setFirstResult($offset);
		}

    $duplicates = $duplicates->execute();
    $entities = [];
    while ($row = $duplicates->fetch()) {
      $qb = $this->db->getQueryBuilder();
      $qb->select('*')
        ->from($this->getTableName())
        ->where($qb->expr()->eq('file_hash', $qb->createNamedParameter($row["file_hash"])));
      if($owner){
        $qb = $qb->andWhere(
          $qb->expr()->eq('owner', $qb->createNamedParameter($owner))
        );
      }
      $entities[] = $this->findEntities($qb);
    }
    $duplicates->closeCursor();
    return $entities;
  }
}
