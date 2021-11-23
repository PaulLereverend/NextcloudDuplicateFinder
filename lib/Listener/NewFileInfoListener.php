<?php
namespace OCA\DuplicateFinder\Listener;

use OCP\ILogger;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Event\NewFileInfoEvent;
use OCA\DuplicateFinder\Service\FileInfoService;
use OCA\DuplicateFinder\Service\FileDuplicateService;

/**
 * @template T of Event
 * @implements IEventListener<T>
 */
class NewFileInfoListener implements IEventListener
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
            if ($event instanceof NewFileInfoEvent) {
                $fileInfo = $event->getFileInfo();
                if ($this->fileInfoService->countBySize($fileInfo->getSize())>1) {
                    $files = $this->fileInfoService->findBySize($fileInfo->getSize());
                    foreach ($files as $finfo) {
                        $this->fileInfoService->calculateHashes($finfo, $event->getUserID());
                    }
                    unset($finfo);
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('Failed to handle NewFileInfoEvent.', ['exception'=> $e]);
            $this->logger->logException($e, ['app'=>'duplicatefinder']);
        }
    }
}
