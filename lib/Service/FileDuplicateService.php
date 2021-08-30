<?php
namespace OCA\DuplicateFinder\Service;

use OCP\IUser;
use OCP\ILogger;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\NotFoundException;

use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Db\FileDuplicate;
use OCA\DuplicateFinder\Db\FileDuplicateMapper;
use OCA\DuplicateFinder\Service\FileInfoService;

class FileDuplicateService
{

    /** @var FileDuplicateMapper */
    private $mapper;
    /** @var ILogger */
    private $logger;
    /** @var FileInfoService */
    private $fileInfoService;

    public function __construct(
        ILogger $logger,
        FileDuplicateMapper $mapper,
        FileInfoService $fileInfoService
    ) {
        $this->mapper = $mapper;
        $this->logger = $logger;
        $this->fileInfoService = $fileInfoService;
    }

    /**
     * @return FileDuplicate
     */
    public function enrich(FileDuplicate $duplicate):FileDuplicate
    {
        foreach ($duplicate->getFiles() as $fileId => $owner) {
            try {
                $fileInfo = $this->fileInfoService->findById($fileId, true);
                $duplicate->addDuplicate($fileId, $fileInfo);
            } catch (DoesNotExistException|NotFoundException $e) {
                $duplicate->resetUpdatedRelationalFields();
                $duplicate->removeDuplicate($fileId);
                $this->update($duplicate);
                $this->logger->info('Removed stale entry '.$fileId
                  .' for duplicate '.$duplicate->getId().' - '
                  .$duplicate->getHash().' - '.$duplicate->getType());
            }
        }
        return $duplicate;
    }

    /**
     * @param string|null $user
     * @param int|null $limit
     * @param int|null $offset
     * @param bool $enrich
     * @param array<array<string>> $orderBy
     * @return array<string, FileDuplicate|int|mixed>
     */
    public function findAll(
        ?string $user = null,
        ?int $limit = 20,
        ?int $offset = null,
        bool $enrich = false,
        ?array $orderBy = [['hash'],['type']]
    ):array {
        $result = array();
        $entities = null;
        $lastKey = null;
        do {
            $entities = $this->mapper->findAll($user, $limit, $offset, $orderBy);
            foreach ($entities as $entity) {
                foreach ($entity->getFiles() as $fileId => $owner) {
                    if (!is_null($user)  && $user !== $owner) {
                        $entity->removeDuplicate($fileId);
                    }
                }
                if ($enrich) {
                    $entity = $this->enrich($entity);
                    $files = $entity->getFiles();
                    uasort($files, function (FileInfo $a, FileInfo $b) {
                        return strnatcmp($a->getPath(), $b->getPath());
                    });
                    $entity->setFiles(array_values($files));
                }
                $lastKey = $entity->id;
                if (count($entity->getFiles()) > 1) {
                    $result[] = $entity;
                    if (count($result) == $limit) {
                        break;
                    }
                }
            }
        } while (count($result) < $limit && count($entities) == $limit);
        return array("entities" => $result, "pageKey" => $lastKey, "isLastFetched" => count($entities) != $limit );
    }

    public function find(string $hash, string $type = 'file_hash'):FileDuplicate
    {
        return $this->mapper->find($hash, $type);
    }

    public function update(FileDuplicate $fileDuplicate):Entity
    {
        $fileDuplicate->setKeepAsPrimary(true);
        $fileDuplicate = $this->mapper->update($fileDuplicate);
        $fileDuplicate->setKeepAsPrimary(false);
        return $fileDuplicate;
    }

    public function getOrCreate(string $hash, string $type = 'file_hash'):FileDuplicate
    {
        try {
            $fileDuplicate = $this->mapper->find($hash, $type);
        } catch (\Exception $e) {
            if (!($e instanceof DoesNotExistException)) {
                $this->logger->logException($e, ['app' => 'duplicatefinder']);
            }
            $fileDuplicate = new FileDuplicate($hash, $type);
            $fileDuplicate->setKeepAsPrimary(true);
            $fileDuplicate = $this->mapper->insert($fileDuplicate);
            $fileDuplicate->setKeepAsPrimary(false);
        }
        return $fileDuplicate;
    }

    public function delete(string $hash, string $type = 'file_hash'):?FileDuplicate
    {
        try {
            $fileDuplicate = $this->mapper->find($hash, $type);
            $this->mapper->delete($fileDuplicate);
            return $fileDuplicate;
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    public function clear():void
    {
        $this->mapper->clear();
    }

    public function clearDuplicates(int $id):void
    {
        $fileDuplicates = $this->mapper->findByDuplicate($id);
        foreach ($fileDuplicates as $fileDuplicate) {
            $fileDuplicate->removeDuplicate($id);
            if ($fileDuplicate->getCount() > 1) {
                $this->update($fileDuplicate);
            } else {
                $this->mapper->delete($fileDuplicate);
            }
        }
    }
}
