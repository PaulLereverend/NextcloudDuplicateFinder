<?php
namespace OCA\DuplicateFinder\BackgroundJob;

use OC\Files\Utils\Scanner;
use OCP\Files\IRootFolder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ILogger;
use OCP\IUserManager;
use OCP\IUser;
use OCP\IDBConnection;
use OCA\DuplicateFinder\Service\FileInfoService;

class FindDuplicates extends \OC\BackgroundJob\TimedJob
{
    /** @var IUserManager */
    private $userManager;
    /** @var IEventDispatcher */
    private $dispatcher;
    /** @var ILogger */
    private $logger;
    /** @var IRootFolder */
    private $rootFolder;
    /** @var FileInfoService*/
    private $fileInfoService;
    /** @var IDBConnection */
    protected $connection;


    /**
     * @param IUserManager $userManager
     * @param IEventDispatcher $dispatcher
     * @param ILogger $logger
     * @param IRootFolder $rootFolder
     * @param FileInfoService $fileInfoService
     */
    public function __construct(
        IUserManager $userManager,
        IEventDispatcher $dispatcher,
        ILogger $logger,
        IRootFolder $rootFolder,
        IDBConnection $connection,
        FileInfoService $fileInfoService
    ) {
        // Run every 5 days a full scan
        $this->setInterval(60*60*24*5);
        $this->userManager = $userManager;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->rootFolder = $rootFolder;
        $this->connection = $connection;
        $this->fileInfoService = $fileInfoService;
    }

    /**
     * @param mixed $argument
     * @return void
     * @throws \Exception
     */
    protected function run($argument): void
    {
        $users =  $this->userManager->callForAllUsers(function (IUser $user): void {
            $this->fileInfoService->scanFiles($user->getUID());
        });
    }
}
