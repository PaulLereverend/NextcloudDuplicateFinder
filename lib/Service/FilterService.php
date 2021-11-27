<?php
namespace OCA\DuplicateFinder\Service;

use Psr\Log\LoggerInterface;
use OCP\Files\Node;

use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Exception\ForcedToIgnoreFileException;
use OCA\DuplicateFinder\Service\ConfigService;

class FilterService
{
    /** @var LoggerInterface */
    private $logger;
    /** @var ConfigService */
    private $config;
    /** @var array<array> */
    private $ignoreConditions;

    public function __construct(
        LoggerInterface $logger,
        ConfigService $config
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->ignoreConditions = $config->getIgnoreConditions();
    }

    public function isIgnored(FileInfo $fileInfo, Node $node) : bool
    {
        if ($node->isMounted() && $this->config->areMountedFilesIgnored()) {
            throw new ForcedToIgnoreFileException($fileInfo, 'app:ignore_mounted_files');
        }
        foreach ($this->ignoreConditions as $orCondition) {
            if ($this->isCondtionFullfilled($orCondition, $fileInfo, $node)) {
                return true;
            }
        }
        unset($orCondition);
        return false;
    }

    /**
     * @param array<array> $conditions
     */
    private function isCondtionFullfilled(array $conditions, FileInfo $fileInfo, Node $node) : bool
    {
        $result = true;
        foreach ($conditions as $condition) {
            if ($condition['attribute'] === 'size') {
                $result = $this->testIntegerCondition(
                    $condition['operator'],
                    $condition['value'],
                    $fileInfo->getSize()
                );
            } elseif ($condition['attribute'] === 'filename') {
                $result = $this->testStringCondition($condition['operator'], $condition['value'], $node->getName());
            } elseif ($condition['attribute'] === 'path') {
                $result = $this->testStringCondition($condition['operator'], $condition['value'], $fileInfo->getPath());
            } else {
                $this->logger->warning('Condtion can not be evaluated '.print_r($condition, true));
            }
            if (!$result) {
                break;
            }
        }
        unset($condition);
        return $result;
    }

    private function testIntegerCondition(string $operator, int $requestedValue, int $testedValue) : bool
    {
        switch ($operator) {
            case '>':
                $result = $testedValue > $requestedValue;
                break;
            case '<':
                $result = $testedValue < $requestedValue;
                break;
            case '>=':
                $result = $testedValue >= $requestedValue;
                break;
            case '<=':
                $result = $testedValue <= $requestedValue;
                break;
            case '=':
                $result = $testedValue === $requestedValue;
                break;
            default:
                $result = false;
        }
        return $result;
    }

    private function testStringCondition(string $operator, string $a, string $b) : bool
    {
        switch ($operator) {
            case 'GLOB':
                $result = fnmatch($a, $b);
                break;
            case 'REGEX':
                $result = preg_match($a, $b);
                $result = ($result === 0 || $result === false);
                break;
            default:
                $result = $a === $b;
        }
        return $result;
    }
}
