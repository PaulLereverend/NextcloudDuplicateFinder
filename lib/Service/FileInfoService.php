<?php
namespace OCA\DuplicateFinder\Service;

use Psr\Log\LoggerInterface;
use OCP\IDBConnection;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OC\User\NoUserException;
use Symfony\Component\Console\Output\OutputInterface;

use OCA\DuplicateFinder\AppInfo\Application;
use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Db\FileInfoMapper;
use OCA\DuplicateFinder\Event\CalculatedHashEvent;
use OCA\DuplicateFinder\Event\UpdatedFileInfoEvent;
use OCA\DuplicateFinder\Event\NewFileInfoEvent;
use OCA\DuplicateFinder\Exception\ForcedToIgnoreFileException;
use OCA\DuplicateFinder\Exception\UnableToCalculateHash;
use OCA\DuplicateFinder\Utils\CMDUtils;
use OCA\DuplicateFinder\Utils\ScannerUtil;

class FileInfoService
{

    /** @var IEventDispatcher */
    private $eventDispatcher;
    /** @var FileInfoMapper */
    private $mapper;
    /** @var LoggerInterface */
    private $logger;
    /** @var ShareService */
    private $shareService;
    /** @var FolderService */
    private $folderService;
    /** @var FilterService */
    private $filterService;
    /** @var ScannerUtil */
    private $scannerUtil;

    public function __construct(
        FileInfoMapper $mapper,
        IEventDispatcher $eventDispatcher,
        LoggerInterface $logger,
        ShareService $shareService,
        FolderService $folderService,
        FilterService $filterService,
        ScannerUtil $scannerUtil
    ) {
        $this->mapper = $mapper;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->shareService = $shareService;
        $this->folderService = $folderService;
        $this->filterService = $filterService;
        $this->scannerUtil = $scannerUtil;
    }

    /**
     * @return FileInfo
     */
    public function enrich(FileInfo $fileInfo):FileInfo
    {
        $node = $this->folderService->getNodeByFileInfo($fileInfo);
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
            $this->eventDispatcher->dispatchTyped(new UpdatedFileInfoEvent($fileInfo, $fallbackUID));
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
        $file = $this->folderService->getNodeByFileInfo($fileInfo, $fallbackUID);
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
        $fileInfo->setIgnored($this->filterService->isIgnored($fileInfo, $file));
        return $fileInfo;
    }

    /**
     * @return false|string
     */
    public function isRecalculationRequired(FileInfo $fileInfo, ?string $fallbackUID = null, ?Node $file = null)
    {
        if ($fileInfo->isIgnored()) {
            return false;
        }
        if (is_null($file)) {
            $file = $this->folderService->getNodeByFileInfo($fileInfo, $fallbackUID);
        }
        if ($file->getType() === \OCP\Files\FileInfo::TYPE_FILE
          && ( empty($fileInfo->getFileHash())
               || $file->getMtime() > $fileInfo->getUpdatedAt()->getTimestamp()
               || $file->getUploadTime() > $fileInfo->getUpdatedAt()->getTimestamp())
          || $file->isMounted()) {
            return $file->getInternalPath();
        }
        return false;
    }

    public function calculateHashes(FileInfo $fileInfo, ?string $fallbackUID = null, bool $requiresHash = true):FileInfo
    {
        $oldHash = $fileInfo->getFileHash();
        $file = $this->folderService->getNodeByFileInfo($fileInfo, $fallbackUID);
        $path = $this->isRecalculationRequired($fileInfo, $fallbackUID, $file);
        if ($path !== false) {
            if ($requiresHash) {
                $hash = $file->getStorage()->hash('sha256', $path);
                if (!is_bool($hash)) {
                    $fileInfo->setFileHash($hash);
                    $fileInfo->setUpdatedAt(new \DateTime());
                } else {
                    throw new UnableToCalculateHash($file->getInternalPath());
                }
            } else {
                $fileInfo->setFileHash(null);
            }
            $this->update($fileInfo, $fallbackUID);
            $this->eventDispatcher->dispatchTyped(new CalculatedHashEvent($fileInfo, $oldHash));
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
        $userFolder = $this->folderService->getUserFolder($user);
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

        try {
            $this->scannerUtil->setHandles($this, $output, $abortIfInterrupted);
            $this->scannerUtil->scan($user, $scanPath);
        } catch (NotFoundException $e) {
            $this->logger->error('The given scan path doesn\'t exists.', ['app' => Application::ID, 'exception' => $e]);
            CMDUtils::showIfOutputIsPresent(
                '<error>The given scan path doesn\'t exists.</error>',
                $output
            );
        }
    }

    public function hasAccessRight(FileInfo $fileInfo, string $user) : ?FileInfo
    {
        $result = null;
        if ($fileInfo->getOwner() === $user) {
            $result = $fileInfo;
        } else {
            try {
                $path = $this->shareService->hasAccessRight(
                    $this->folderService->getNodeByFileInfo($fileInfo, $user),
                    $user
                );
                if (!is_null($path)) {
                    $fileInfo->setPath($path);
                    $result = $fileInfo;
                }
            } catch (NoUserException | NotFoundException $e) {
                $result = null;
            }
        }
        
        return $result;
    }
}
