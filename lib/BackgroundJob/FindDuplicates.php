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

class FindDuplicates extends \OC\BackgroundJob\TimedJob {
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
	 * @throws \Exception
	 */
	protected function run($argument):  mixed {
    $users =  $this->userManager->callForSeenUsers(function (IUser $user): bool {
      $this->findDuplicates($user->getUID());
			return true;
    });
	}

	private function findDuplicates(string $user): void{
		$scanner = new Scanner($user, null, $this->dispatcher, $this->logger);
		$scanner->listen('\OC\Files\Utils\Scanner', 'scanFile', function ($path) {
				$file = $this->rootFolder->get($path);
				$fileInfo = $this->fileInfoService->createOrUpdate($path, $file->getOwner());
				if($this->connection->inTransaction()){
					$this->connection->commit();
					$this->connection->beginTransaction();
				}
		});

		$folder = $this->rootFolder->getUserFolder($user);
		$scanner->scan($folder->getPath(), true, null);
	}
}
