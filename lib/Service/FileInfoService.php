<?php
namespace OCA\DuplicateFinder\Service;

use OCP\IUser;
use OCP\ILogger;
use OCP\IDBConnection;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OC\Files\Utils\Scanner;
use Symfony\Component\Console\Output\OutputInterface;

use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Db\FileInfoMapper;
use OCA\DuplicateFinder\Event\CalculatedHashEvent;

class FileInfoService
{

    /** @var IEventDispatcher */
    private $eventDispatcher;

  /** @var FileInfoMapper */
    private $mapper;

  /** @var IRootFolder */
    private $rootFolder;
  /** @var ILogger */
    private $logger;
  /** @var IDBConnection */
    private $connection;

    public function __construct(
        FileInfoMapper $mapper,
        IRootFolder $rootFolder,
        IEventDispatcher $eventDispatcher,
        ILogger $logger,
        IDBConnection $connection
    ) {
        $this->mapper = $mapper;
        $this->rootFolder = $rootFolder;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->connection = $connection;
    }

    /**
     * @return FileInfo
     */
    public function enrich(FileInfo $fileInfo):FileInfo
    {
        $node = $this->rootFolder->get($fileInfo->getPath());
        $fileInfo->setNodeId($node->getId());
        $fileInfo->setMimetype($node->getMimetype());
        $fileInfo->setSize($node->getSize());
        return $fileInfo;
    }

    /**
     * @return array<FileInfo>
     */
    public function findAll(bool $enrich = false):array
    {
        $entities = $this->mapper->findAll();
        if ($enrich) {
            foreach ($entities as $entity) {
                $entity = $this->enrich($entity);
            }
        }
        return $entities;
    }

    public function find(string $path, bool $enrich = false):FileInfo
    {
        $entity = $this->mapper->find($path);
        if ($enrich) {
            $entity = $this->enrich($entity);
        }
        return $entity;
    }

    public function findById(int $id, bool $enrich = false):FileInfo
    {
        $entity = $this->mapper->findById($id);
        if ($enrich) {
            $entity = $this->enrich($entity);
        }
        return $entity;
    }

    /**
     * @return array<FileInfo>
     */
    public function findByHash(string $hash, string $type = "file_hash"):array
    {
        return $this->mapper->findByHash($hash, $type);
    }

    public function countByHash(string $hash, string $type = "file_hash"):int
    {
        return $this->mapper->countByHash($hash, $type);
    }

    public function createOrUpdate(string $path, IUser $owner):FileInfo
    {
        $fileInfo = $this->getOrCreate($owner, $path);
        return $this->calculateHashes($fileInfo);
    }

    public function update(FileInfo $fileInfo):FileInfo
    {
        $fileInfo->setKeepAsPrimary(true);
        $fileInfo = $this->mapper->update($fileInfo);
        $fileInfo->setKeepAsPrimary(false);
        return $fileInfo;
    }

    public function getOrCreate(IUser $owner, string $path):FileInfo
    {
        try {
            $fileInfo = $this->mapper->find($path);
        } catch (\Exception $e) {
            $fileInfo = new FileInfo($path, $owner->getUID());
            $fileInfo->setKeepAsPrimary(true);
            $fileInfo = $this->mapper->insert($fileInfo);
            $fileInfo->setKeepAsPrimary(false);
        }
        return $fileInfo;
    }

    public function delete(FileInfo $fileInfo):FileInfo
    {
        $this->mapper->delete($fileInfo);
        return $fileInfo;
    }

    public function calculateHashes(FileInfo $fileInfo):FileInfo
    {
        $file = $this->rootFolder->get($fileInfo->getPath());
        if ($file->getMtime() >
            $fileInfo->getUpdatedAt()->getTimestamp()
          || $file->getUploadTime() >
            $fileInfo->getUpdatedAt()->getTimestamp()) {
            $oldHash = $fileInfo->getFileHash();
            $fileInfo->setFileHash($file->getStorage()->hash("sha256", $file->getInternalPath()));
            $fileInfo->setUpdatedAt(new \DateTime());
            $this->update($fileInfo);
            $this->eventDispatcher->dispatchTyped(new CalculatedHashEvent($fileInfo, $oldHash));
        }
        return $fileInfo;
    }

    public function scanFiles(
        string $user,
        ?string $path = null,
        ?\Closure $abortIfInterrupted = null,
        ?OutputInterface $output = null
    ): void {
        $scanPath = $this->rootFolder->getUserFolder($user)->getPath();
        if (!is_null($path)) {
            $scanPath .= DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
        }
        $scanner = new Scanner($user, $this->connection, $this->eventDispatcher, $this->logger);
        $scanner->listen('\OC\Files\Utils\Scanner', 'scanFile', function ($path) use ($abortIfInterrupted, $output) {
            if (!is_null($output)) {
                $output->write("Scanning ".$path, false, OutputInterface::VERBOSITY_VERBOSE);
            }
            $file = $this->rootFolder->get($path);
            $fileInfo = $this->createOrUpdate($path, $file->getOwner());
            if ($output) {
                $output->writeln(" => Hash: ".$fileInfo->getFileHash(), OutputInterface::VERBOSITY_VERBOSE);
            }
            if ($abortIfInterrupted) {
                $abortIfInterrupted();
            }
            // Ensure that every scanned file is commited - not only after all files are scanned
            if ($this->connection->inTransaction()) {
                $this->connection->commit();
                $this->connection->beginTransaction();
            }
        });
        if ($output) {
            $output->writeln('Start Searching files for '.$user." in Path ".$scanPath);
        }
        $scanner->scan($scanPath, true);
        if ($output) {
            $output->writeln('Finished Searching files');
        }
    }
}
