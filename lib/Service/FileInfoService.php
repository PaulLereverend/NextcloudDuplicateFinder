<?php
namespace OCA\DuplicateFinder\Service;

use OCP\IUser;
use OCP\ILogger;
use OCP\IDBConnection;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Node;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OC\Files\Utils\Scanner;
use Symfony\Component\Console\Output\OutputInterface;

use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Db\FileInfoMapper;
use OCA\DuplicateFinder\Event\CalculatedHashEvent;
use OCA\DuplicateFinder\Event\NewFileInfoEvent;

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
        $node = $this->getNode($fileInfo);
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

    /**
     * @return array<FileInfo>
     */
    public function findBySize(int $size, bool $onlyEmptyHash = true):array
    {
        return $this->mapper->findBySize($size, $onlyEmptyHash);
    }

    public function countByHash(string $hash, string $type = "file_hash"):int
    {
        return $this->mapper->countByHash($hash, $type);
    }

    public function countBySize(int $size):int
    {
        return $this->mapper->countBySize($size);
    }

    public function update(FileInfo $fileInfo):FileInfo
    {
        $fileInfo = $this->updateFileMeta($fileInfo);
        $fileInfo->setKeepAsPrimary(true);
        $fileInfo = $this->mapper->update($fileInfo);
        $fileInfo->setKeepAsPrimary(false);
        return $fileInfo;
    }

    public function save(string $path):FileInfo
    {
        try {
            $fileInfo = $this->mapper->find($path);
            $fileInfo = $this->update($fileInfo);
        } catch (\Exception $e) {
            $fileInfo = new FileInfo($path);
            $fileInfo = $this->updateFileMeta($fileInfo);
            $fileInfo->setKeepAsPrimary(true);
            $fileInfo = $this->mapper->insert($fileInfo);
            $fileInfo->setKeepAsPrimary(false);
            $this->eventDispatcher->dispatchTyped(new NewFileInfoEvent($fileInfo));
        }
        return $fileInfo;
    }

    public function delete(FileInfo $fileInfo):FileInfo
    {
        $this->mapper->delete($fileInfo);
        return $fileInfo;
    }

    public function clear():void
    {
        $this->mapper->clear();
    }

    public function updateFileMeta(FileInfo $fileInfo) : FileInfo
    {
        $file = $this->getNode($fileInfo);
        $fileInfo->setSize($file->getSize());
        $fileInfo->setMimetype($file->getMimetype());
        try {
            $fileInfo->setOwner($file->getOwner()->getUID());
        } catch (\Throwable $e) {
            //Even though  this should not happen - the result of getOwner can be null
            $this->logger->error("There is a problem with the owner of ".$fileInfo->getPath());
            $this->logger->logException($e, ["app" => "duplicatefinder"]);
        }
        return $fileInfo;
    }

    public function calculateHashes(FileInfo $fileInfo):FileInfo
    {
        $oldHash = $fileInfo->getFileHash();
        $file = $this->getNode($fileInfo);
        if (empty($oldHash)
          || $file->getMtime() >
            $fileInfo->getUpdatedAt()->getTimestamp()
          || $file->getUploadTime() >
            $fileInfo->getUpdatedAt()->getTimestamp()) {
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
                $output->writeln("Scanning ".$path, OutputInterface::VERBOSITY_VERBOSE);
            }
            $fileInfo = $this->save($path);
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
            $output->writeln('Start searching files for '.$user." in path ".$scanPath);
        }

        try {
            $scanner->scan($scanPath, true);
        } catch (NotFoundException $e) {
            if ($output) {
                $output->writeln("<error>The given path doesn't exists.</error>");
            }
        }
        if ($output) {
            $output->writeln('Finished searching files');
        }
    }

    private function getNode(FileInfo $fileInfo): Node
    {
        if (!is_null($fileInfo->getOwner())) {
            // Ensure that user folder has been initialized
            $this->rootFolder->getUserFolder($fileInfo->getOwner());
        }
        return $this->rootFolder->get($fileInfo->getPath());
    }
}
