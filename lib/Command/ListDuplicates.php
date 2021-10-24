<?php
namespace OCA\DuplicateFinder\Command;

use OC\Core\Command\Base;
use OCP\Encryption\IManager;
use OCP\IDBConnection;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use OCA\DuplicateFinder\Service\FileInfoService;
use OCA\DuplicateFinder\Service\FileDuplicateService;
use OCA\DuplicateFinder\Utils\CMDUtils;

class ListDuplicates extends Base
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

    public function __construct(
        IUserManager $userManager,
        IManager $encryptionManager,
        IDBConnection $connection,
        FileInfoService $fileInfoService,
        FileDuplicateService $fileDuplicateService
    ) {
        parent::__construct();
        $this->userManager = $userManager;
        $this->encryptionManager = $encryptionManager;
        $this->connection = $connection;
        $this->fileInfoService = $fileInfoService;
        $this->fileDuplicateService = $fileDuplicateService;
    }

    protected function configure():void
    {
        $this
            ->setName('duplicates:list')
            ->setDescription('List all duplicates files')
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'scan files of the specified user'
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
            if ($result === 0) {
                $result = $this->listDuplicatesForUsers($users);
            }
        } else {
            CMDUtils::showDuplicates($this->fileDuplicateService, $this->output, function () {
                $this->abortIfInterrupted();
            });
            $result = 0;
        }

        return $result;
    }

    /**
     * @param array<string> $users
     */
    private function listDuplicatesForUsers(array $users) : int
    {
        $result = 0;
        foreach ($users as $user) {
            if (!$this->userManager->userExists($user)) {
                $this->output->writeln('User '.$user.' is unkown.');
                $result = 1;
                break;
            }
            CMDUtils::showDuplicates(
                $this->fileDuplicateService,
                $this->output,
                function () {
                    $this->abortIfInterrupted();
                },
                $user
            );
        }
        unset($user);
        return $result;
    }
}
