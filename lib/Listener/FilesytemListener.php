<?php
namespace OCA\DuplicateFinder\Listener;

use Psr\Log\LoggerInterface;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Node;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Events\Node\AbstractNodeEvent;
use OCA\DuplicateFinder\Service\ConfigService;
use OCA\DuplicateFinder\Service\FileInfoService;
use OCA\DuplicateFinder\Service\FileDuplicateService;
use OCA\DuplicateFinder\Exception\ForcedToIgnoreFileException;

/**
 * @template T of Event
 * @implements IEventListener<T>
 */
class FilesytemListener implements IEventListener
{

    /** @var FileInfoService */
    private $fileInfoService;
    /** @var FileDuplicateService */
    private $fileDuplicateService;
    /** @var LoggerInterface */
    private $logger;
    /** @var ConfigService */
    private $config;

    public function __construct(
        FileInfoService $fileInfoService,
        FileDuplicateService $fileDuplicateService,
        LoggerInterface $logger,
        ConfigService $config
    ) {
        $this->fileInfoService = $fileInfoService;
        $this->fileDuplicateService = $fileDuplicateService;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function handle(Event $event): void
    {
        if ($this->config->areFilesytemEventsDisabled()) {
            return;
        }
        if ($event instanceof NodeDeletedEvent) {
            $node = $event->getNode();
            $this->handleDeleteEvent($node);
        } elseif ($event instanceof NodeRenamedEvent) {
            $source = $event->getSource();
            try {
                $fileInfo = $this->fileInfoService->find($source->getPath(), $source->getOwner()->getUID());
            } catch (\Throwable $e) {
                $fileInfo = $this->fileInfoService->find($source->getPath(), null);
            }
            $target = $event->getTarget();
            $fileInfo->setPath($target->getPath());
            $fileInfo->setOwner($target->getOwner()->getUID());
            $this->fileInfoService->update($fileInfo);
        } elseif ($event instanceof AbstractNodeEvent) {
            $node = $event->getNode();
            try {
                $fileInfo = $this->fileInfoService->save($node->getPath(), $node->getOwner()->getUID());
            } catch (ForcedToIgnoreFileException $e) {
                $this->logger->info($e->getMessage(), ['exception'=> $e]);
            } catch (\Throwable $e) {
                try {
                    $fileInfo = $this->fileInfoService->save($node->getPath(), null);
                } catch (ForcedToIgnoreFileException $e) {
                    $this->logger->info($e->getMessage(), ['exception'=> $e]);
                }
            }
        }
    }

    private function handleDeleteEvent(Node $node) : void
    {
        try {
            $fileInfo = $this->fileInfoService->find($node->getPath(), $node->getOwner()->getUID());
        } catch (\Throwable $e) {
            try {
                $fileInfo = $this->fileInfoService->find($node->getPath(), null);
            } catch (DoesNotExistException $e) {
                return;
            }
        }
        $this->fileInfoService->delete($fileInfo);
        if (!is_null($fileInfo->getFileHash())) {
            $count = $this->fileInfoService->countByHash($fileInfo->getFileHash());
            if ($count < 2) {
                $this->fileDuplicateService->delete($fileInfo->getFileHash());
            }
        }
    }
}
