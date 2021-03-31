<?php
namespace OCA\DuplicateFinder\Command;

use OC\Core\Command\Base;
use OC\Files\Search\SearchQuery;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchOrder;
use OC\Files\Utils\Scanner;
use OCP\Encryption\IManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\Search\ISearchComparison;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IPreview;
use OCP\IUser;
use OCP\IUserManager;
use OCP\AppFramework\Http\DataResponse;
use OCA\Files\Helper;
use OC\Files\Filesystem;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use OCA\DuplicateFinder\Service\FileInfoService;
use OCA\DuplicateFinder\Service\FileDuplicateService;
use OCA\DuplicateFinder\Utils\CMDUtils;

class FindDuplicates extends Base {

	/** @var IUserManager */
	protected $userManager;

	/** @var IRootFolder */
	protected $rootFolder;

	/** @var OutputInterface */
	protected $output;

	/** @var IManager */
	protected $encryptionManager;

	/** @var IDBConnection */
	protected $connection;

	/** @var FileInfoService */
	protected $fileInfoService;

	/** @var FileDuplicateService */
	protected $fileDuplicateService;

	public function __construct(IRootFolder $rootFolder,
								IUserManager $userManager,
								IManager $encryptionManager,
								IDBConnection $connection,
								FileInfoService $fileInfoService,
								FileDuplicateService $fileDuplicateService) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->encryptionManager = $encryptionManager;
		$this->connection = $connection;
		$this->fileInfoService = $fileInfoService;
		$this->fileDuplicateService = $fileDuplicateService;
	}

	protected function configure() {
		$this
			->setName('duplicates:find-all')
			->setDescription('Find all duplicates files')
			->addOption('recursive', 'r', InputOption::VALUE_OPTIONAL, 'scan folder recursively')
			->addOption('user','u', InputOption::VALUE_OPTIONAL, 'scan files of the specified user')
			->addOption('path','p', InputOption::VALUE_OPTIONAL, 'limit scan to this path, eg. --path="/alice/files/Photos"');

		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->output = $output;
		if ($this->encryptionManager->isEnabled()) {
			$this->output->writeln('Encryption is enabled. Aborted.');
			return 1;
		}
		$inputPath = $input->getOption('path');
		$user = $input->getOption('user');

		if($user){
			if(!$this->userManager->userExists($user)){
				$this->output->writeln('User '.$user.' is unkown.');
				return 1;
			}
			$this->findDuplicates($user);
		}else{
			$users =  $this->userManager->callForSeenUsers(function (IUser $user): bool {
				$this->findDuplicates($user->getUID());
				return true;
			});
		}

		return 0;
	}

	private function findDuplicates(string $user){
		$scanner = new Scanner($user, $this->connection, \OC::$server->query(IEventDispatcher::class), \OC::$server->getLogger());
		$scanner->listen('\OC\Files\Utils\Scanner', 'scanFile', function ($path) {
				$this->output->write("Scanning ".$path, false, OutputInterface::VERBOSITY_VERBOSE);
				$file = $this->rootFolder->get($path);
				$fileInfo = $this->fileInfoService->createOrUpdate($path, $file->getOwner());
				$this->output->writeln(" => Hash: ".$fileInfo->getFileHash(), OutputInterface::VERBOSITY_VERBOSE);
				$this->abortIfInterrupted();
				// Ensure that every scanned file is commited - not only after all files are scanned
				if($this->connection->inTransaction()){
					$this->connection->commit();
					$this->connection->beginTransaction();
				}

		});

		$folder = $this->rootFolder->getUserFolder($user);
		$this->output->writeln('Start Searching files for '.$user);
		$scanner->scan($folder->getPath(), true, null);
		$this->output->writeln('Finished Searching files');
		CMDUtils::showDuplicates($this->fileDuplicateService, $this->fileInfoService, $this->output, function() {$this->abortIfInterrupted();}, $user);
	}
}
