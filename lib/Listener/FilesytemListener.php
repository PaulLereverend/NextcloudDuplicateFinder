<?php
namespace OCA\DuplicateFinder\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Events\Node\AbstractNodeEvent;
use OCA\DuplicateFinder\Service\FileInfoService;
use OCA\DuplicateFinder\Service\FileDuplicateService;

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

    public function __construct(
        FileInfoService $fileInfoService,
        FileDuplicateService $fileDuplicateService
    ) {
        $this->fileInfoService = $fileInfoService;
        $this->fileDuplicateService = $fileDuplicateService;
    }

    public function handle(Event $event): void
    {
        if ($event instanceof NodeDeletedEvent) {
            $node = $event->getNode();
            try {
                $fileInfo = $this->fileInfoService->find($node->getPath(), $node->getOwner()->getUID());
            } catch (\Throwable $e) {
                $fileInfo = $this->fileInfoService->find($node->getPath(), null);
            }
            $this->fileDuplicateService->clearDuplicates($fileInfo->getId());
            $this->fileInfoService->delete($fileInfo);
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
            } catch (\Throwable $e) {
                $fileInfo = $this->fileInfoService->save($node->getPath(), null);
            }
        }
    }
}
