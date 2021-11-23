<?php
namespace OCA\DuplicateFinder\Utils;

use Symfony\Component\Console\Output\OutputInterface;
use OCA\DuplicateFinder\Service\FileInfoService;
use OCA\DuplicateFinder\Service\FileDuplicateService;

class CMDUtils
{

    public static function showDuplicates(
        FileDuplicateService $fileDuplicateService,
        OutputInterface $output,
        \Closure $abortIfInterrupted,
        ?string $user = null
    ): void {
        if ($user === null) {
            $output->writeln('Duplicates are: ');
        } else {
            $output->writeln('Duplicates for user "'.$user.'" are: ');
        }
        $duplicates = array("pageKey" => 0, "isLastFetched" => true);
        do {
            $duplicates = $fileDuplicateService->findAll($user, 20, $duplicates["pageKey"], true);
            foreach ($duplicates["entities"] as $duplicate) {
                if (!$duplicate->getFiles()) {
                    continue;
                }
                $output->writeln($duplicate->getHash().'('.$duplicate->getType().')');
                foreach ($duplicate->getFiles() as $file) {
                    if ($file instanceof \OCA\DuplicateFinder\Db\FileInfo) {
                        $output->writeln('     '.$file->getPath());
                    }
                }
                unset($file);
            }
            unset($duplicate);
            $abortIfInterrupted();
        } while (!$duplicates["isLastFetched"]);
    }
}
