<?php
namespace OCA\DuplicateFinder\Command;

use OC\Core\Command\Base;
use OC\Files\Search\SearchQuery;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchOrder;
use OCP\Encryption\IManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\Search\ISearchComparison;
use OCP\IConfig;
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

class FindDuplicates extends Base {

	/** @var IUserManager */
	protected $userManager;

	/** @var IRootFolder */
	protected $rootFolder;

	/** @var OutputInterface */
	protected $output;

	/** @var IManager */
	protected $encryptionManager;

	/** @var FileInfoService */
	protected $fileInfoService;

	public function __construct(IRootFolder $rootFolder,
								IUserManager $userManager,
								IManager $encryptionManager,
								FileInfoService $fileInfoService) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->encryptionManager = $encryptionManager;
		$this->fileInfoService = $fileInfoService;
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
			$this->findDupplicates($user);
		}else{
			$users =  $this->userManager->callForSeenUsers(function (IUser $user) {
				$this->findDupplicates($user->getUID());
			});
		}

		return 0;
	}

	private function findDupplicates(string $user){
		$folder = $this->rootFolder->getUserFolder($user);
		$this->output->writeln('Start Searching files for '.$user);
		$this->findAndImportFiles($folder);
		$this->output->writeln('Finished Searching files');
		$this->output->writeln("Duplicates for user '$user' are: ");
		foreach($this->fileInfoService->getDuplicates($user) as $dup){
			$this->output->writeln($dup[0]->getFileHash());
			foreach($dup as $f){
				$this->output->writeln('     '.$f->getPath());
			};
		};
	}

	private function findAndImportFiles( $node){
		$this->abortIfInterrupted();
		if($node->getType() === "dir"){
			foreach($node->getDirectoryListing() as $f){
				$this->findAndImportFiles($f);
			}
		}else{
			$this->output->write("Scanning ".$node->getPath(), false, OutputInterface::VERBOSITY_VERBOSE);
			$fileInfo = $this->fileInfoService->createOrUpdate($node->getOwner(), $node->getPath());
			$this->output->writeln(" => Hash: ".$fileInfo->getFileHash(), OutputInterface::VERBOSITY_VERBOSE);
		}
	}

}
