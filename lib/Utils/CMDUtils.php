<?php
namespace OCA\DuplicateFinder\Utils;

use Symfony\Component\Console\Output\OutputInterface;
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
                self::showFiles($output, $duplicate->getFiles());
            }
            unset($duplicate);
            $abortIfInterrupted();
        } while (!$duplicates["isLastFetched"]);
    }

    /**
     * @param array<\OCA\DuplicateFinder\Db\FileInfo> $files
     */
    private static function showFiles(OutputInterface $output, array $files) : void
    {
        $shownPaths = [];
        $hiddenPaths = 0;
        $indent = '     ';
        foreach ($files as $file) {
            if ($file instanceof \OCA\DuplicateFinder\Db\FileInfo) {
                if (!isset($shownPaths[$file->getPath()])) {
                    $output->writeln($indent.$file->getPath());
                    $shownPaths[$file->getPath()] = 1;
                } else {
                    $hiddenPaths += 1;
                }
            }
        }
        unset($file);
        $message = '';
        if ($hiddenPaths == 1) {
            $message = $hiddenPaths.' path is hidden because it references';
        } elseif ($hiddenPaths > 1) {
            $message = $hiddenPaths.' paths are hidden because they reference';
        }
        if ($hiddenPaths > 0) {
            $output->writeln($indent.'<info>'.$message.' to a similiar file.</info>');
        }
    }


    public static function showIfOutputIsPresent(
        string $message,
        ?OutputInterface $output = null,
        int $verbosity = OutputInterface::VERBOSITY_NORMAL
    ) : void {
        if (!is_null($output)) {
            $output->writeln($message, $verbosity);
        }
    }
}
