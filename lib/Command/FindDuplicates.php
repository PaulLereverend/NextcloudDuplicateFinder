<?php
namespace OCA\DuplicateFinder\Command;

use OC\Core\Command\Base;
use OC\Files\Search\SearchQuery;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchOrder;
use OCP\Encryption\IManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\Files\Search\ISearchComparison;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IPreview;
use OCP\IUser;
use OCP\IUserManager;
use OCP\ILogger;
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

class FindDuplicates extends Base
{

    /** @var IUserManager */
    protected $userManager;

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
    /** @var array<string>|null */
    protected $inputPath;
    /** @var ILogger */
    private $logger;

    public function __construct(
        IUserManager $userManager,
        IManager $encryptionManager,
        IDBConnection $connection,
        FileInfoService $fileInfoService,
        FileDuplicateService $fileDuplicateService,
        ILogger $logger
    ) {
        parent::__construct();
        $this->userManager = $userManager;
        $this->encryptionManager = $encryptionManager;
        $this->connection = $connection;
        $this->fileInfoService = $fileInfoService;
        $this->fileDuplicateService = $fileDuplicateService;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setName('duplicates:find-all')
            ->setDescription('Find all duplicates files')
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'scan files of the specified user'
            )
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'limit scan to this path, eg. --path="./Photos"'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        if ($this->encryptionManager->isEnabled()) {
            $this->output->writeln('Encryption is enabled. Aborted.');
            return 1;
        }

        $inputPath = $input->getOption('path');
        if (is_bool($inputPath)) {
            $this->output->writeln('<error>The given path is invalid.<error>');
        } elseif (is_string($inputPath)) {
            $this->inputPath = [$inputPath];
        } elseif (!empty($inputPath)) {
            $this->inputPath = $inputPath;
        }
        $user = $input->getOption('user');

        try {
            if ($user) {
                if ($user === true) {
                    $this->output->writeln('User parameter has an invalid value.');
                    return 1;
                } elseif (is_string($user)) {
                    $users = [$user];
                } else {
                    $users = $user;
                }
                foreach ($users as $user) {
                    if (!$this->userManager->userExists($user)) {
                        $this->output->writeln('User '.$user.' is unkown.');
                        return 1;
                    }
                    $this->findDuplicates($user);
                }
            } else {
                $users =  $this->userManager->callForAllUsers(function (IUser $user): void {
                    $this->findDuplicates($user->getUID());
                });
            }
        } catch (NotFoundException $e) {
            $this->logger->logException($e, ['app' => 'duplicatefinder']);
            $this->output->writeln('<error>The given path doesn\'t exists.<error>');
        }

        return 0;
    }

    private function findDuplicates(string $user):void
    {
        if (is_null($this->inputPath)) {
            $this->fileInfoService->scanFiles(
                $user,
                null,
                function () {
                    $this->abortIfInterrupted();
                },
                $this->output
            );
        } else {
            foreach ($this->inputPath as $inputPath) {
                $this->fileInfoService->scanFiles(
                    $user,
                    $inputPath,
                    function () {
                        $this->abortIfInterrupted();
                    },
                    $this->output
                );
            }
        }
        CMDUtils::showDuplicates($this->fileDuplicateService, $this->fileInfoService, $this->output, function () {
            $this->abortIfInterrupted();
        }, $user);
    }
}
