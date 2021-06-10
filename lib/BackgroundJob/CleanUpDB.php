<?php
namespace OCA\DuplicateFinder\BackgroundJob;

use OCP\ILogger;
use OCP\Files\NotFoundException;
use OCA\DuplicateFinder\Service\FileInfoService;

class CleanUpDB extends \OC\BackgroundJob\TimedJob
{
    /** @var FileInfoService*/
    private $fileInfoService;
    /** @var ILogger */
    private $logger;

    /**
     * @param FileInfoService $fileInfoService
     * @param ILogger $logger
     */
    public function __construct(
        FileInfoService $fileInfoService,
        ILogger $logger
    ) {
        // Run every 5 days a full scan
        $this->setInterval(60*60*24*2);
        $this->fileInfoService = $fileInfoService;
        $this->logger = $logger;
    }

    /**
     * @param  mixed $argument
     * @throws \Exception
     */
    protected function run($argument): void
    {
        /**
         * If for some reason a delete or rename Event wasn't handled properly we cleanup this up here
         */
        $fileInfos = $this->fileInfoService->findAll();
        foreach ($fileInfos as $fileInfo) {
            try {
                $this->fileInfoService->getNode($fileInfo);
            } catch (NotFoundException $e) {
                $this->logger->info('FileInfo '.$fileInfo->getPath(). ' will be deleted');
                $this->fileInfoService->delete($fileInfo);
            }
        }
    }
}
