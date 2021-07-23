<?php
namespace OCA\DuplicateFinder\Migration;

use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCA\DuplicateFinder\Service\FileInfoService;

class RepairFileInfos implements IRepairStep
{

    /** @var IConfig */
    private $config;
    /** @var IDBConnection */
    private $connection;
    /** @var FileInfoService */
    private $fileInfoService;



    public function __construct(IConfig $config, IDBConnection $connection, FileInfoService $fileInfoService)
    {
        $this->config = $config;
        $this->connection = $connection;
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
        return 'Repair FileInfo objects';
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

        $invalidObjects = $this->getInvalidPathHashObjects();
        $output->startProgress(count($invalidObjects));
        foreach ($invalidObjects as $row) {
            $fileInfo = $this->fileInfoService->findById($row['id']);
            $fileInfo->setPath($fileInfo->getPath());
            $this->fileInfoService->update($fileInfo);
            $output->advance();
        }
		$output->finishProgress();
    }

    protected function shouldRun() : bool
    {
        $appVersion = $this->config->getAppValue('duplicatefinder', 'installed_version', '0.0.0');
        return version_compare($appVersion, '0.0.9', '>');
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
}
