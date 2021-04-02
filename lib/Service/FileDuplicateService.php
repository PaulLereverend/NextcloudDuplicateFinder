<?php
namespace OCA\DuplicateFinder\Service;

use OCP\IUser;
use OCP\ILogger;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\DoesNotExistException;

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
            $fileInfo = $this->fileInfoService->findById($fileId, true);
            $duplicate->addDuplicate($fileId, $fileInfo);
        }
        return $duplicate;
    }

    /**
     * @return array<FileDuplicate>
     */
    public function findAll(?string $user = null, ?int $limit = 20, ?int $offset = null, bool $enrich = false):array
    {
        $entities = $this->mapper->findAll($user, $limit, $offset);
        if ($enrich) {
            foreach ($entities as $entity) {
                $entity = $this->enrich($entity);
            }
        }
        return $entities;
    }

    public function find(string $hash, string $type = "file_hash"):FileDuplicate
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

    public function getOrCreate(string $hash, string $type = "file_hash"):FileDuplicate
    {
        try {
            $fileDuplicate = $this->mapper->find($hash, $type);
        } catch (\Exception $e) {
            if (!($e instanceof DoesNotExistException)) {
                $this->logger->logException($e, ["app" => "duplicatefinder"]);
            }
            $fileDuplicate = new FileDuplicate($hash, $type);
            $fileDuplicate->setKeepAsPrimary(true);
            $fileDuplicate = $this->mapper->insert($fileDuplicate);
            $fileDuplicate->setKeepAsPrimary(false);
        }
        return $fileDuplicate;
    }

    public function delete(string $hash, string $type = "file_hash"):?FileDuplicate
    {
        try {
            $fileDuplicate = $this->mapper->find($hash, $type);
            $this->mapper->delete($fileDuplicate);
            return $fileDuplicate;
        } catch (DoesNotExistException $e) {
            return null;
        }
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
