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
            $fileInfo = $this->fileInfoService->find($node->getPath());
            $this->fileDuplicateService->clearDuplicates($fileInfo->getId());
            $this->fileInfoService->delete($fileInfo);
        } elseif ($event instanceof NodeRenamedEvent) {
            $fileInfo = $this->fileInfoService->find($event->getSource()->getPath());
            $target = $event->getTarget();
            $fileInfo->setPath($target->getPath());
            $fileInfo->setOwner($target->getOwner()->getUID());
            $this->fileInfoService->update($fileInfo);
        } elseif ($event instanceof AbstractNodeEvent) {
            $node = $event->getNode();
            $this->fileInfoService->createOrUpdate($node->getPath(), $node->getOwner());
        }
    }
}
