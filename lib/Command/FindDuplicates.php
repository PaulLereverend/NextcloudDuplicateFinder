<?php
namespace OCA\DuplicateFinder\Command;

use OC\Core\Command\Base;
use OCP\Encryption\IManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
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

class FindDuplicates extends Base {

	/** @var IUserManager */
	protected $userManager;

	/** @var IRootFolder */
	protected $rootFolder;

	/** @var OutputInterface */
	protected $output;

	/** @var IManager */
	protected $encryptionManager;

	public function __construct(IRootFolder $rootFolder,
								IUserManager $userManager,
								IManager $encryptionManager) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->encryptionManager = $encryptionManager;
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
		if ($this->encryptionManager->isEnabled()) {
			$output->writeln('Encryption is enabled. Aborted.');
			return 1;
		}
		$output->writeln("Start scan...");
		$inputPath = $input->getOption('path');
		$user = $input->getOption('user');
		if($user){
			$files = $this->readFiles($user, $inputPath);
		}else{
			$this->userManager->callForSeenUsers(function (IUser $user) {
				$files = $this->readFiles($user->getUID(), $inputPath);
			});
		}
		$results = \OCA\Files\Helper::formatFileInfos($files);
		$hashArr = array();
		foreach ($results as $key => $result) {
			$path = $this->getRelativePath($files[$key]->getPath()). $result['name'];
			if($info = Filesystem::getLocalFile($path)) {
				$fileHash = hash_file('md5', $info);
				if($fileHash){
					$hashArr[$path] = $fileHash;
				}
			}
		}
		$arr_unique = array_unique($hashArr);
		$arr_duplicates = array_diff_assoc($hashArr, $arr_unique);
		$duplicates = array_intersect($hashArr, $arr_duplicates);
		asort($duplicates);
		$previousHash = 0;
		foreach($duplicates as $filePath=>$fileHash) {
			if($previousHash != $fileHash){
				$output->writeln("\/----".$fileHash."---\/");
			}
			$output->writeln($filePath);
			$previousHash = $fileHash;
		}
		$output->writeln("end scan");
		return 0;
	}
	private function readFiles(string $user, $path){
		if(!$path){
			$path = '';
		}
		\OC_Util::tearDownFS();
		if(!\OC_Util::setupFS($user)){
			throw new Exception("Utilisateur inconnu", 1);
		}
		return $this->getFilesRecursive($path);
	}

	private function getFilesRecursive($path , & $results = []) {
		$files = Filesystem::getDirectoryContent($path);
		foreach($files as $file) {
			if ($file->getType() === 'dir') {
				$this->getFilesRecursive($path . '/' . $file->getName(), $results);
			} else {
				$results[] = $file;
			}
		}

		return $results;
	}
	private function getRelativePath($path) {
		$path = Filesystem::getView()->getRelativePath($path);
		return substr($path, 0, strlen($path) - strlen(basename($path)));
	}
}
