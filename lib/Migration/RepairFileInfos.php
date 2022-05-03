<?php
namespace OCA\DuplicateFinder\Migration;

use \Psr\Log\LoggerInterface;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Files\NotFoundException;
use OCA\DuplicateFinder\AppInfo\Application;
use OCA\DuplicateFinder\Service\ConfigService;
use OCA\DuplicateFinder\Service\FileInfoService;

class RepairFileInfos implements IRepairStep
{

    /** @var ConfigService */
    private $config;
    /** @var IDBConnection */
    private $connection;
    /** @var LoggerInterface */
    private $logger;
    /** @var FileInfoService */
    private $fileInfoService;



    public function __construct(
        ConfigService $config,
        IDBConnection $connection,
        FileInfoService $fileInfoService,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->fileInfoService = $fileInfoService;
    }

    /**
     * Returns the step's name
     *
     * @return string
     * @since 9.1.0
     */
    public function getName()
    {
        return Application::ID.': Repair FileInfo objects';
    }

    /**
     * @param IOutput $output
     * @return mixed
     */
    public function run(IOutput $output)
    {
        if (!$this->shouldRun()) {
            return;
        }

        $output->info('Recalculating Path Hashes');
        $this->updatePathHashes($output);
        $output->info('Clearing duplicated records');
        $this->clearDuplicateObjects($output);
    }

    protected function shouldRun() : bool
    {
        return version_compare($this->config->getInstalledVersion(), '0.0.9', '>');
    }

    private function updatePathHashes(IOutput $output) : void
    {
        $invalidObjects = $this->getInvalidPathHashObjects();
        $output->startProgress(count($invalidObjects));
        foreach ($invalidObjects as $row) {
            $fileInfo = null;
            try {
                $fileInfo = $this->fileInfoService->findById($row['id']);
                $fileInfo->setPath($fileInfo->getPath());
                $this->fileInfoService->update($fileInfo);
            } catch (NotFoundException $e) {
                if (!is_null($fileInfo)) {
                    $this->fileInfoService->delete($fileInfo);
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), ['exception'=> $e]);
            }
            $output->advance();
        }
        unset($row);
        $output->finishProgress();
    }

    /**
     * @return array<string,mixed>
     */
    private function getInvalidPathHashObjects() : array
    {
        $qb = $this->connection->getQueryBuilder();
        $qb->select('*')
            ->from('duplicatefinder_finfo')
            ->where($qb->expr()->isNull('path_hash'))
            ->orWhere($qb->expr()->eq('path_hash', $qb->createNamedParameter('')));
        $qb = $qb->execute();
        if (is_int($qb)) {
            return array();
        }
        $rows = $qb->fetchAll();
        $qb->closeCursor();
        return $rows;
    }

    private function clearDuplicateObjects(IOutput $output) : void
    {
        $entities = $this->fileInfoService->findAll(false);
        $paths = array();
        $output->startProgress(count($entities));
        foreach ($entities as $entity) {
            try {
                $hash = $entity->getPathHash();
                $owner = $entity->getOwner();
                if (isset($paths[$hash])) {
                    if (isset($paths[$hash][$owner])) {
                        $this->fileInfoService->delete($entity);
                    } else {
                        $paths[$hash][$owner] = true;
                    }
                } else {
                    $paths[$hash] = array();
                    $paths[$hash][$owner] = true;
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), ['exception'=> $e]);
            }
            $output->advance();
        }
        unset($entity);
        $output->finishProgress();
    }
}
