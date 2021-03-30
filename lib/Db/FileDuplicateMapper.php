<?php
namespace OCA\DuplicateFinder\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;

class FileDuplicateMapper extends EQBMapper {

  public function __construct(IDBConnection $db) {
    parent::__construct($db, 'duplicatefinder_dups', FileDuplicate::class);
  }

  public function find(string $hash, string $type = "file_hash") {
    $qb = $this->db->getQueryBuilder();
    $qb->select('*')
      ->from($this->getTableName())
      ->where(
        $qb->expr()->eq("hash", $qb->createNamedParameter($hash)),
        $qb->expr()->eq("type", $qb->createNamedParameter($type))
      );
    return $this->findEntity($qb);
  }

  public function findAll(?string $user = null) {
    $qb = $this->db->getQueryBuilder();
    $qb->select('d.id as id', 'type', 'hash')
      ->from($this->getTableName(), "d");
    if($user !== null){
      $qb->leftJoin("d", $this->getTableName()."_f", "f", $qb->expr()->eq('d.id', 'f.id'))
        ->where($qb->expr()->eq("value", $qb->createNamedParameter($user)))
        ->groupBy('id');
    }
    return $this->findEntities($qb);
  }

  public function findByDuplicate(int $duplicateId) {
    $qb = $this->db->getQueryBuilder();
    $qb->select('*')
      ->from($this->getTableName()."_f")
      ->where(
        $qb->expr()->eq("rid", $qb->createNamedParameter($duplicateId, IQueryBuilder::PARAM_INT))
      );
    $qb = $qb->execute();
    $duplicates = [];
    foreach($qb->fetchAll() as $row){
      $fQB = $this->db->getQueryBuilder();
      $fQB->select('*')
        ->from($this->getTableName())
        ->where(
          $fQB->expr()->eq("id", $fQB->createNamedParameter($row["id"], IQueryBuilder::PARAM_INT))
        );
       $duplicates[] = $this->findEntity($fQB);
    }
    return $duplicates;
  }
}
