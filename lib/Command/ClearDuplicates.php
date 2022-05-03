<?php
namespace OCA\DuplicateFinder\Command;

use OC\Core\Command\Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use OCA\DuplicateFinder\Service\FileInfoService;
use OCA\DuplicateFinder\Service\FileDuplicateService;

class ClearDuplicates extends Base
{

    /** @var FileInfoService */
    protected $fileInfoService;

    /** @var FileDuplicateService */
    protected $fileDuplicateService;

    public function __construct(
        FileInfoService $fileInfoService,
        FileDuplicateService $fileDuplicateService
    ) {
        parent::__construct();
        $this->fileInfoService = $fileInfoService;
        $this->fileDuplicateService = $fileDuplicateService;
    }

    protected function configure():void
    {
        $this
            ->setName('duplicates:clear')
            ->setDescription('Clear all duplicates and information for discovery')
            ->setHelp(
                'Remove links to interactively recognized duplicate files from the database of your Nextcloud instance.'
                ."\n".'This action doesn\'t remove the files from your file system.'
            )
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'don\'t ask any questions');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isClearingRequested = $input->getOption('force');
        if ($isClearingRequested !== true) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'Do you realy want to clear all duplicates and information for discovery?',
                false
            );
            $isClearingRequested = $helper->ask($input, $output, $question);
            if ($isClearingRequested === false) {
                return 0;
            }
        }

        if ($isClearingRequested === true) {
            $this->fileDuplicateService->clear();
            $this->fileInfoService->clear();
            return 0;
        }

        return 1;
    }
}
