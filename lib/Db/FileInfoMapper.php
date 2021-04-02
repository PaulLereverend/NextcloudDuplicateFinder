<?php
namespace OCA\DuplicateFinder\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * @extends QBMapper<FileInfo>
 */
class FileInfoMapper extends QBMapper
{

    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, 'duplicatefinder_finfo', FileInfo::class);
    }

  /**
   * @throws \OCP\AppFramework\Db\DoesNotExistException
   */
    public function find(string $path):FileInfo
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
        ->from($this->getTableName())
        ->where(
            $qb->expr()->eq('path', $qb->createNamedParameter($path))
        );
        return $this->findEntity($qb);
    }

  /**
   * @return array<FileInfo>
   */
    public function findByHash(string $hash, string $type = "file_hash"): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
        ->from($this->getTableName())
        ->where(
            $qb->expr()->eq($type, $qb->createNamedParameter($hash))
        );
        return $this->findEntities($qb);
    }

    public function countByHash(string $hash, string $type = "file_hash"):int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
        ->from($this->getTableName())
        ->where(
            $qb->expr()->eq($type, $qb->createNamedParameter($hash))
        );
        $qb = $qb->execute();
        if (!is_int($qb)) {
            return $qb->rowCount();
        }
        return 0;
    }

    public function findById(int $id):FileInfo
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
        ->from($this->getTableName())
        ->where(
            $qb->expr()->eq("id", $qb->createNamedParameter($id), IQueryBuilder::PARAM_INT)
        );
        return $this->findEntity($qb);
    }

  /**
   * @return array<FileInfo>
   */
    public function findAll(): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
        ->from($this->getTableName());
        return $this->findEntities($qb);
    }
}
