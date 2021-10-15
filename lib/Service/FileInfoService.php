<?php
namespace OCA\DuplicateFinder\Service;

use OCP\IUser;
use OCP\Share\IShare;
use OCP\ILogger;
use OCP\IDBConnection;
use OCP\IConfig;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Node;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OC\Files\Utils\Scanner;
use Symfony\Component\Console\Output\OutputInterface;

use OCA\DuplicateFinder\AppInfo\Application;
use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Db\FileInfoMapper;
use OCA\DuplicateFinder\Event\CalculatedHashEvent;
use OCA\DuplicateFinder\Event\NewFileInfoEvent;
use OCA\DuplicateFinder\Exception\ForcedToIgnoreFileException;
use OCA\DuplicateFinder\Exception\UnableToCalculateHash;
use OCA\DuplicateFinder\Exception\UnknownOwnerException;
use OCA\DuplicateFinder\Utils\CMDUtils;
use OCA\DuplicateFinder\Utils\PathConversionUtils;

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
    /** @var IConfig */
    private $config;
    /** @var ShareService */
    private $shareService;

    public function __construct(
        FileInfoMapper $mapper,
        IRootFolder $rootFolder,
        IEventDispatcher $eventDispatcher,
        ILogger $logger,
        IDBConnection $connection,
        IConfig $config,
        ShareService $shareService
    ) {
        $this->mapper = $mapper;
        $this->rootFolder = $rootFolder;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->connection = $connection;
        $this->config = $config;
        $this->shareService = $shareService;
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
            unset($entity);
        }
        return $entities;
    }

    public function find(string $path, ?string $fallbackUID = null, bool $enrich = false):FileInfo
    {
        $entity = $this->mapper->find($path, $fallbackUID);
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
    public function findByHash(string $hash, string $type = 'file_hash'):array
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

    public function countByHash(string $hash, string $type = 'file_hash'):int
    {
        return $this->mapper->countByHash($hash, $type);
    }

    public function countBySize(int $size):int
    {
        return $this->mapper->countBySize($size);
    }

    public function update(FileInfo $fileInfo, ?string $fallbackUID = null):FileInfo
    {
        $fileInfo = $this->updateFileMeta($fileInfo, $fallbackUID);
        $fileInfo->setKeepAsPrimary(true);
        $fileInfo = $this->mapper->update($fileInfo);
        $fileInfo->setKeepAsPrimary(false);
        return $fileInfo;
    }

    public function save(string $path, ?string $fallbackUID = null):FileInfo
    {
        try {
            $fileInfo = $this->mapper->find($path, $fallbackUID);
            $fileInfo = $this->update($fileInfo, $fallbackUID);
        } catch (\Exception $e) {
            $fileInfo = new FileInfo($path);
            $fileInfo = $this->updateFileMeta($fileInfo, $fallbackUID);
            $fileInfo->setKeepAsPrimary(true);
            $fileInfo = $this->mapper->insert($fileInfo);
            $fileInfo->setKeepAsPrimary(false);
            $this->eventDispatcher->dispatchTyped(new NewFileInfoEvent($fileInfo, $fallbackUID));
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

    public function updateFileMeta(FileInfo $fileInfo, ?string $fallbackUID = null) : FileInfo
    {
        $file = $this->getNode($fileInfo, $fallbackUID);
        // Default should be false but isn't supported by the api
        $ignoreMountedFiles = $this->config->getAppValue(Application::ID, 'ignore_mounted_files', '');
        if ($file->isMounted() && $ignoreMountedFiles) {
            throw new ForcedToIgnoreFileException($fileInfo, 'app:ignore_mounted_files');
        }
        $fileInfo->setSize($file->getSize());
        $fileInfo->setMimetype($file->getMimetype());
        try {
            $fileInfo->setOwner($file->getOwner()->getUID());
        } catch (\Throwable $e) {
            if (!is_null($fallbackUID)) {
                $fileInfo->setOwner($fallbackUID);
            } elseif (!$fileInfo->getOwner()) {
                throw $e;
            }
        }
        return $fileInfo;
    }

    public function calculateHashes(FileInfo $fileInfo, ?string $fallbackUID = null):FileInfo
    {
        $oldHash = $fileInfo->getFileHash();
        $file = $this->getNode($fileInfo, $fallbackUID);
        if ($file->getType() === \OCP\Files\FileInfo::TYPE_FILE
          && ( empty($oldHash)
        || $file->getMtime() >
          $fileInfo->getUpdatedAt()->getTimestamp()
        || $file->getUploadTime() >
          $fileInfo->getUpdatedAt()->getTimestamp())
        || $file->isMounted()) {
             $hash = $file->getStorage()->hash('sha256', $file->getInternalPath());
            if (!is_bool($hash)) {
                $fileInfo->setFileHash($hash);
                $fileInfo->setUpdatedAt(new \DateTime());
                $this->update($fileInfo, $fallbackUID);
                $this->eventDispatcher->dispatchTyped(new CalculatedHashEvent($fileInfo, $oldHash));
            } else {
                throw new UnableToCalculateHash($file->getInternalPath());
            }
        }
        return $fileInfo;
    }

    public function scanFiles(
        string $user,
        ?string $path = null,
        ?\Closure $abortIfInterrupted = null,
        ?OutputInterface $output = null,
        ?bool $isShared = false
    ): void {
        $userFolder = $this->rootFolder->getUserFolder($user);
        $scanPath = $userFolder->getPath();
        if (!is_null($path) && !$isShared) {
            $scanPath .= DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
            if (!$userFolder->nodeExists(ltrim($path, DIRECTORY_SEPARATOR))) {
                CMDUtils::showIfOutputIsPresent(
                    'Skipped '.$scanPath.' because it doesn\'t exists.',
                    $output,
                    OutputInterface::VERBOSITY_VERBOSE
                );
                return;
            }
        } elseif ($isShared) {
            if (is_null($path)) {
                return;
            }
            $scanPath = $path;
        }

        $scanner = new Scanner($user, $this->connection, $this->eventDispatcher, $this->logger);
        $scanner->listen(
            '\OC\Files\Utils\Scanner',
            'postScanFile',
            function ($path) use ($abortIfInterrupted, $output, $user, $isShared) {
                CMDUtils::showIfOutputIsPresent(
                    'Scanning '.($isShared ? 'Shared Node ':'').$path,
                    $output,
                    OutputInterface::VERBOSITY_VERBOSE
                );
                $this->saveScannedFile($path, $user, $abortIfInterrupted, $output);
                // Ensure that every scanned file is commited - not only after all files are scanned
                if ($this->connection->inTransaction()) {
                    $this->connection->commit();
                    $this->connection->beginTransaction();
                }
            }
        );
        if (!$isShared) {
            CMDUtils::showIfOutputIsPresent(
                'Start searching files for '.$user.' in path '.$scanPath,
                $output
            );
        }
        

        try {
            $scanner->scan($scanPath, true);
        } catch (NotFoundException $e) {
            $this->logger->logException($e, ['app' => 'duplicatefinder']);
            CMDUtils::showIfOutputIsPresent(
                '<error>The given scan path doesn\'t exists.</error>',
                $output
            );
        }
        if (!$isShared) {
            $this->scanSharedFiles($user, $path, $abortIfInterrupted, $output);
            CMDUtils::showIfOutputIsPresent(
                'Finished searching files',
                $output
            );
        }
    }

    public function scanSharedFiles(
        string $user,
        ?string $path,
        ?\Closure $abortIfInterrupted = null,
        ?OutputInterface $output = null
    ): void {
        $shares = $this->shareService->getShares($user);
        
        foreach ($shares as $share) {
            $node = $share->getNode();
            if (is_null($path) || strpos($node->getPath(), $path) == 0) {
                if ($node->getType() === \OCP\Files\FileInfo::TYPE_FILE) {
                    $this->saveScannedFile($node->getPath(), $user, $abortIfInterrupted, $output);
                } else {
                    $this->scanFiles($share->getSharedBy(), $node->getPath(), $abortIfInterrupted, $output, true);
                }
            }
        }
        unset($share);
    }

    private function saveScannedFile(
        string $path,
        string $user,
        ?\Closure $abortIfInterrupted = null,
        ?OutputInterface $output = null
    ) : void {

        try {
            $this->save($path, $user);
        } catch (NotFoundException $e) {
            $this->logger->logException($e, ['app' => 'duplicatefinder']);
            CMDUtils::showIfOutputIsPresent(
                '<error>The given path doesn\'t exists ('.$path.').</error>',
                $output
            );
        } catch (ForcedToIgnoreFileException $e) {
            $this->logger->info($e->getMessage(), ['exception'=> $e]);
            CMDUtils::showIfOutputIsPresent(
                'Skipped '.$path,
                $output,
                OutputInterface::VERBOSITY_VERBOSE
            );
        }
        if ($abortIfInterrupted) {
            $abortIfInterrupted();
        }
    }

    /*
     *  The Node specified by the FileInfo isn't always in the cache.
        *  if so, a get on the root folder will raise an |OCP\Files\NotFoundException
        *  To avoid this, it is first tried to get the Node by the user folder. Because
        *  the user folder supports lazy loading, it works even if the file isn't in the cache
     *  If the owner is unknown, it is at least tried to get the Node from the root folder
     */
    public function getNode(FileInfo $fileInfo, ?string $fallbackUID = null): Node
    {
        $userFolder = null;
        if ($fileInfo->getOwner()) {
            $userFolder = $this->rootFolder->getUserFolder($fileInfo->getOwner());
        } elseif (!is_null($fallbackUID)) {
            $userFolder = $this->rootFolder->getUserFolder($fallbackUID);
            $fileInfo->setOwner($fallbackUID);
        }
        if (!is_null($userFolder)) {
            try {
                $relativePath = PathConversionUtils::convertRelativePathToUserFolder($fileInfo, $userFolder);
                return $userFolder->get($relativePath);
            } catch (NotFoundException $e) {
                //If the file is not known in the user root (cached) it's fine to use the root
            }
        }
        return $this->rootFolder->get($fileInfo->getPath());
    }

    public function hasAccessRight(FileInfo $fileInfo, string $user) : ?FileInfo
    {
        if ($fileInfo->getOwner() === $user) {
            return $fileInfo;
        }
        $path = $this->shareService->hasAccessRight($this->getNode($fileInfo, $user), $user);
        if (!is_null($path)) {
            $fileInfo->setPath($path);
            return $fileInfo;
        }
        return null;
    }
}
