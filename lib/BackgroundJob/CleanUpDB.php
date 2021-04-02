<?php
namespace OCA\DuplicateFinder\BackgroundJob;

use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCA\DuplicateFinder\Service\FileInfoService;

class CleanUpDB extends \OC\BackgroundJob\TimedJob
{
  /** @var IRootFolder */
    private $rootFolder;
  /** @var FileInfoService*/
    private $fileInfoService;


    /**
     * @param IRootFolder $rootFolder
     * @param FileInfoService $fileInfoService
     */
    public function __construct(
        IRootFolder $rootFolder,
        FileInfoService $fileInfoService
    ) {
        // Run every 5 days a full scan
        $this->setInterval(60*60*24*2);
        $this->rootFolder = $rootFolder;
        $this->fileInfoService = $fileInfoService;
    }

    /**
     * @param  mixed $argument
     * @throws \Exception
     */
    protected function run($argument): mixed
    {
        /**
         * If for some reason a delete or rename Event wasn't handled properly we cleanup this up here
         */
        $fileInfos = $this->fileInfoService->findAll();
        foreach ($fileInfos as $fileInfo) {
            try {
                $this->rootFolder->get($fileInfo->getPath());
            } catch (NotFoundException $e) {
                $this->fileInfoService->delete($fileInfo);
            }
        }
    }
}
