<?php
namespace OCA\DuplicateFinder\Utils;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use OC\Files\Utils\Scanner;
use OCP\IDBConnection;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\NotFoundException;

use OCA\DuplicateFinder\AppInfo\Application;
use OCA\DuplicateFinder\Exception\ForcedToIgnoreFileException;
use OCA\DuplicateFinder\Service\FileInfoService;
use OCA\DuplicateFinder\Service\ShareService;
use OCA\DuplicateFinder\Utils\CMDUtils;

class ScannerUtil
{


    /** @var IDBConnection */
    private $connection;
    /** @var IEventDispatcher */
    private $eventDispatcher;
    /** @var LoggerInterface */
    private $logger;
    /** @var OutputInterface|null */
    private $output;
    /** @var \Closure|null */
    private $abortIfInterrupted;
    /** @var FileInfoService */
    private $fileInfoService;
    /** @var ShareService */
    private $shareService;

    public function __construct(
        IDBConnection $connection,
        IEventDispatcher $eventDispatcher,
        LoggerInterface $logger,
        ShareService $shareService
    ) {
        $this->connection =$connection;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->shareService = $shareService;
    }

    public function setHandles(
        FileInfoService $fileInfoService,
        ?OutputInterface $output,
        ?\Closure $abortIfInterrupted
    ) : void {
        $this->fileInfoService = $fileInfoService;
        $this->output = $output;
        $this->abortIfInterrupted = $abortIfInterrupted;
    }

    public function scan(string $user, string $path, bool $isShared = false) : void
    {
        if (!$isShared) {
            $this->showOutput('Start searching files for '.$user.' in path '.$path);
        }
        $scanner = $this->initializeScanner($user, $isShared);
        $scanner->scan($path, true);
        if (!$isShared) {
            $this->scanSharedFiles($user, $path);
            $this->showOutput('Finished searching files');
        }
    }

    private function initializeScanner(string $user, bool $isShared = false) : Scanner
    {
        $scanner = new Scanner($user, $this->connection, $this->eventDispatcher, $this->logger);
        $scanner->listen(
            '\OC\Files\Utils\Scanner',
            'postScanFile',
            function ($path) use ($user, $isShared) {
                $this->showOutput('Scanning '.($isShared ? 'Shared Node ':'').$path, true);
                $this->saveScannedFile($path, $user);
            }
        );
        return $scanner;
    }

    private function saveScannedFile(
        string $path,
        string $user
    ) : void {
        try {
            $this->fileInfoService->save($path, $user);
        } catch (NotFoundException $e) {
            $this->logger->error('The given path doesn\'t exists ('.$path.').', [
                'app' => Application::ID,
                'exception' => $e
            ]);
            $this->showOutput('<error>The given path doesn\'t exists ('.$path.').</error>');
        } catch (ForcedToIgnoreFileException $e) {
            $this->logger->info($e->getMessage(), ['exception'=> $e]);
            $this->showOutput('Skipped '.$path, true);
        }
        if ($this->abortIfInterrupted) {
            $abort = $this->abortIfInterrupted;
            $abort();
        }
    }

    private function scanSharedFiles(
        string $user,
        ?string $path
    ): void {
        $shares = $this->shareService->getShares($user);
        
        foreach ($shares as $share) {
            $node = $share->getNode();
            if (is_null($path) || strpos($node->getPath(), $path) == 0) {
                if ($node->getType() === \OCP\Files\FileInfo::TYPE_FILE) {
                    $this->saveScannedFile($node->getPath(), $user);
                } else {
                    $this->scan($share->getSharedBy(), $node->getPath(), true);
                }
            }
        }
        unset($share);
    }

    private function showOutput(string $message, bool $isVerbose = false) : void
    {
        CMDUtils::showIfOutputIsPresent(
            $message,
            $this->output,
            $isVerbose ? OutputInterface::VERBOSITY_VERBOSE : OutputInterface::VERBOSITY_NORMAL
        );
    }
}
