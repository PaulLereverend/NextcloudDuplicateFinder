<?php
namespace OCA\DuplicateFinder\BackgroundJob;

use OC\Files\Utils\Scanner;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use OCP\IUserManager;
use OCP\IUser;
use OCP\IDBConnection;
use OCA\DuplicateFinder\Service\FileInfoService;
use OCA\DuplicateFinder\Service\ConfigService;

class FindDuplicates extends \OC\BackgroundJob\TimedJob
{
    /** @var IUserManager */
    private $userManager;
    /** @var IEventDispatcher */
    private $dispatcher;
    /** @var LoggerInterface */
    private $logger;
    /** @var FileInfoService*/
    private $fileInfoService;
    /** @var IDBConnection */
    protected $connection;


    /**
     * @param IUserManager $userManager
     * @param IEventDispatcher $dispatcher
     * @param LoggerInterface $logger
     * @param FileInfoService $fileInfoService
     */
    public function __construct(
        IUserManager $userManager,
        IEventDispatcher $dispatcher,
        LoggerInterface $logger,
        IDBConnection $connection,
        FileInfoService $fileInfoService,
        ConfigService $config
    ) {
        $this->setInterval($config->getFindJobInterval());
        $this->userManager = $userManager;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
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
        $this->userManager->callForAllUsers(function (IUser $user): void {
            $this->fileInfoService->scanFiles($user->getUID());
        });
    }
}
