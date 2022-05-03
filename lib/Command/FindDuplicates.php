<?php
namespace OCA\DuplicateFinder\Command;

use OC\Core\Command\Base;
use OCP\Encryption\IManager;
use OCP\Files\NotFoundException;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use OCA\DuplicateFinder\AppInfo\Application;
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
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        IUserManager $userManager,
        IManager $encryptionManager,
        IDBConnection $connection,
        FileInfoService $fileInfoService,
        FileDuplicateService $fileDuplicateService,
        LoggerInterface $logger
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

        $scanPath = $input->getOption('path');
        if (is_bool($scanPath)) {
            $this->output->writeln('<error>The given path is invalid.<error>');
        } elseif (is_string($scanPath)) {
            $this->inputPath = [$scanPath];
        } elseif (!empty($scanPath)) {
            $this->inputPath = $scanPath;
        }
        $user = $input->getOption('user');
        $result = 0;
        if ($user) {
            if ($user === true) {
                $this->output->writeln('User parameter has an invalid value.');
                return 1;
            } elseif (is_string($user)) {
                $users = [$user];
            } else {
                $users = $user;
            }
            $result = $this->findDuplicatesForUsers($users);
        } else {
            $this->userManager->callForAllUsers(function (IUser $user): void {
                $this->findDuplicates($user->getUID());
            });
        }

        return $result;
    }

    /**
     * @param array<string> $users
     */
    private function findDuplicatesForUsers(array $users) : int
    {
        $result = 0;
        foreach ($users as $user) {
            if (!$this->userManager->userExists($user)) {
                $this->output->writeln('User '.$user.' is unkown.');
                $result = 1;
                break;
            }

            try {
                $this->findDuplicates($user);
            } catch (NotFoundException $e) {
                $this->logger->error('A given path doesn\'t exists', ['app' => Application::ID, 'exception' => $e]);
                $this->output->writeln('<error>The given path doesn\'t exists ('.$e->getMessage().').<error>');
            }
        }
        unset($user);
        return $result;
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
            unset($inputPath);
        }
        CMDUtils::showDuplicates($this->fileDuplicateService, $this->output, function () {
            $this->abortIfInterrupted();
        }, $user);
    }
}
