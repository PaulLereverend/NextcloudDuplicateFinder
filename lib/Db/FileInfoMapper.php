<?php
namespace OCA\DuplicateFinder\Db;

use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * @extends EQBMapper<FileInfo>
 */
class FileInfoMapper extends EQBMapper
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
        return $this->countBy($type, $hash);
    }

    public function countBySize(int $size):int
    {
        return $this->countBy("size", $size, IQueryBuilder::PARAM_INT);
    }

    /**
     * @return array<FileInfo>
     */
    public function findBySize(int $size, bool $onlyEmptyHash = true) : array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
        ->from($this->getTableName())
        ->where(
            $qb->expr()->eq("size", $qb->createNamedParameter($size), IQueryBuilder::PARAM_INT)
        );
        if ($onlyEmptyHash) {
            $qb->andWhere($qb->expr()->isNull("file_hash"));
        }
        return $this->findEntities($qb);
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
