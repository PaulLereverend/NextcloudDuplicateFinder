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
    public function find(string $path, ?string $userID = null):FileInfo
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
        ->from($this->getTableName())
        ->where(
            $qb->expr()->eq('path_hash', $qb->createNamedParameter(sha1($path)))
        );
        if (!is_null($userID)) {
            $qb->andWhere($qb->expr()->eq('owner', $qb->createNamedParameter($userID)));
        }
        $entities = $this->findEntities($qb);
        if ($entities) {
            if (is_null($userID)) {
                return $entities[0];
            }
            foreach ($entities as $entity) {
                if ($entity->getOwner() === $userID) {
                    return $entity;
                }
            }
            unset($entity);
        }
        throw new \OCP\AppFramework\Db\DoesNotExistException('FileInfo not found');
    }

  /**
   * @return array<FileInfo>
   */
    public function findByHash(string $hash, string $type = 'file_hash'): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
        ->from($this->getTableName())
        ->where(
            $qb->expr()->eq($type, $qb->createNamedParameter($hash)),
            $qb->expr()->eq('ignored', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL))
        );
        return $this->entitiesToIdArray($this->findEntities($qb));
    }

    public function countByHash(string $hash, string $type = 'file_hash'):int
    {
        return $this->countBy($type, $hash);
    }

    public function countBySize(int $size):int
    {
        return $this->countBy('size', $size, IQueryBuilder::PARAM_INT);
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
            $qb->expr()->eq('size', $qb->createNamedParameter($size, IQueryBuilder::PARAM_INT))
        );
        if ($onlyEmptyHash) {
            $qb->andWhere($qb->expr()->isNull('file_hash'));
        }
        return $this->entitiesToIdArray($this->findEntities($qb));
    }

    public function findById(int $id):FileInfo
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
        ->from($this->getTableName())
        ->where(
            $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
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
        return $this->entitiesToIdArray($this->findEntities($qb));
    }

    /**
     * @param array<FileInfo> $entities
     * @return array<FileInfo>
     */
    private function entitiesToIdArray(array $entities) : array
    {
        $result = array();
        foreach ($entities as $entity) {
            $result[$entity->getId()] = $entity;
        }
        unset($entity);
        return $result;
    }
}
