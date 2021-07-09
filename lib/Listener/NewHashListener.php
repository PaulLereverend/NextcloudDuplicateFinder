<?php
namespace OCA\DuplicateFinder\Listener;

use OCP\ILogger;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Event\CalculatedHashEvent;
use OCA\DuplicateFinder\Service\FileInfoService;
use OCA\DuplicateFinder\Service\FileDuplicateService;

/**
 * @template T of Event
 * @implements IEventListener<T>
 */
class NewHashListener implements IEventListener
{

    /** @var FileInfoService */
    private $fileInfoService;
    /** @var FileDuplicateService */
    private $fileDuplicateService;
    /** @var Ilogger */
    private $logger;

    public function __construct(
        FileInfoService $fileInfoService,
        FileDuplicateService $fileDuplicateService,
        ILogger $logger
    ) {
        $this->fileInfoService = $fileInfoService;
        $this->fileDuplicateService = $fileDuplicateService;
        $this->logger = $logger;
    }

    public function handle(Event $event): void
    {
        try {
            if ($event instanceof CalculatedHashEvent && $event->isChanged()) {
                $fileInfo = $event->getFileInfo();
                if (!$event->isNew()) {
                    $this->fileDuplicateService->clearDuplicates($fileInfo->getId());
                }
                $this->updateDuplicates($fileInfo);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Failed to handle new hash event .', ['exception'=> $e]);
            $this->logger->logException($e, ['app'=>'duplicatefinder']);
        }
    }

    private function updateDuplicates(FileInfo $fileInfo, string $type = 'file_hash'): void
    {
        $count = $this->fileInfoService->countByHash($fileInfo->getFileHash(), $type);
        if ($count > 1) {
            try {
                $fileDuplicate = $this->fileDuplicateService->getOrCreate($fileInfo->getFileHash(), $type);
                if ($count > 2) {
                    $fileDuplicate->addDuplicate($fileInfo->getId(), $fileInfo->getOwner());
                } else {
                    $files = $this->fileInfoService->findByHash($fileInfo->getFileHash(), $type);
                    foreach ($files as $fileInfo) {
                        $fileDuplicate->addDuplicate($fileInfo->getId(), $fileInfo->getOwner());
                    }
                }
                $this->fileDuplicateService->update($fileDuplicate);
            } catch (\Exception $e) {
                $this->logger->logException($e, ['app' => 'duplicatefinder']);
            }
        }
    }
}
