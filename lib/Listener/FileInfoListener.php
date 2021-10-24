<?php
namespace OCA\DuplicateFinder\Listener;

use OCP\ILogger;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\DuplicateFinder\Event\AbstractFileInfoEvent;
use OCA\DuplicateFinder\Service\FileInfoService;

/**
 * @template T of Event
 * @implements IEventListener<T>
 */
class FileInfoListener implements IEventListener
{

    /** @var FileInfoService */
    private $fileInfoService;
    /** @var Ilogger */
    private $logger;

    public function __construct(
        FileInfoService $fileInfoService,
        ILogger $logger
    ) {
        $this->fileInfoService = $fileInfoService;
        $this->logger = $logger;
    }

    public function handle(Event $event): void
    {
        try {
            if ($event instanceof AbstractFileInfoEvent) {
                $fileInfo = $event->getFileInfo();
                $count = $this->fileInfoService->countBySize($fileInfo->getSize());
                if ($count > 1) {
                    $files = $this->fileInfoService->findBySize($fileInfo->getSize());
                    foreach ($files as $finfo) {
                        $this->fileInfoService->calculateHashes($finfo, $event->getUserID());
                    }
                    unset($finfo);
                } else {
                    $this->fileInfoService->calculateHashes($fileInfo, $event->getUserID(), false);
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('Failed to handle NewFileInfoEvent.', ['exception'=> $e]);
            $this->logger->logException($e, ['app'=>'duplicatefinder']);
        }
    }
}
