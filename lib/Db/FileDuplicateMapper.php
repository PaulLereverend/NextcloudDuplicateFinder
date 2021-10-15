<?php
namespace OCA\DuplicateFinder\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * @extends EQBMapper<FileDuplicate>
 */
class FileDuplicateMapper extends EQBMapper
{

    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, 'duplicatefinder_dups', FileDuplicate::class);
    }

    public function find(string $hash, string $type = 'file_hash'): FileDuplicate
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
        ->from($this->getTableName())
        ->where(
            $qb->expr()->eq('hash', $qb->createNamedParameter($hash)),
            $qb->expr()->eq('type', $qb->createNamedParameter($type))
        );
        return $this->findEntity($qb);
    }

  /**
   * @param string|null $user
   * @param int|null $limit
   * @param int|null $offset
   * @param array<array<string>> $orderBy
   * @return array<FileDuplicate>
   */
    public function findAll(
        ?string $user = null,
        ?int $limit = null,
        ?int $offset = null,
        ?array $orderBy = [['hash'],['type']]
    ):array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('d.id as id', 'type', 'hash')
        ->from($this->getTableName(), 'd');
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->where($qb->expr()->gt('id', $qb->createNamedParameter($offset, IQueryBuilder::PARAM_INT)));
        }
        $qb->addOrderBy('id');
        if ($orderBy !== null) {
            foreach ($orderBy as $order) {
                $qb->addOrderBy($order[0], isset($order[1]) ? $order[1] : null);
            }
            unset($order);
        }
        return $this->findEntities($qb);
    }

    public function clear(?string $table = null):void
    {
        parent::clear($this->getTableName().'_f');
        parent::clear();
    }
}
