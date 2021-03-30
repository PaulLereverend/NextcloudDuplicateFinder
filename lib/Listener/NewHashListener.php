<?php
namespace OCA\DuplicateFinder\Listener;

use OCP\ILogger;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Event\CalculatedHashEvent;
use OCA\DuplicateFinder\Service\FileInfoService;
use OCA\DuplicateFinder\Service\FileDuplicateService;

class NewHashListener implements IEventListener {

	/** @var FileInfoService */
	private $fileInfoService;
	/** @var FileDuplicateService */
	private $fileDuplicateService;
	private $logger;

  public function __construct(FileInfoService $fileInfoService,
															FileDuplicateService $fileDuplicateService,
															ILogger $logger) {
		$this->fileInfoService = $fileInfoService;
		$this->fileDuplicateService = $fileDuplicateService;
		$this->logger = $logger;
	}

  public function handle(Event $event): void {
    if ($event instanceOf CalculatedHashEvent && $event->isChanged()) {
			$fileInfo = $event->getFileInfo();
			if(!$event->isNew()){
				$this->fileDuplicateService->clearDuplicates($fileInfo->getId());
			}
			$this->updateDuplicates($fileInfo->getFileHash());
    }
  }

	private function updateDuplicates(string $hash, string $type = "file_hash"){
		$duplicates = $this->fileInfoService->findByHash($hash, $type);
		if(count($duplicates) > 1){
			try{
				$fileDuplicate = $this->fileDuplicateService->getOrCreate($hash, $type);
				$fileDuplicate->clear();
				foreach($duplicates as $duplicate){
					$fileDuplicate->addDupplicate($duplicate->getId(), $duplicate->getOwner());
				}
				$this->fileDuplicateService->update($fileDuplicate);
			}catch(\Exception $e){
				$this->logger->logException($e);
			}
		}
	}
}
