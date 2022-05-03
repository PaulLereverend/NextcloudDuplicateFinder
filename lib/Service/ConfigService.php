<?php
namespace OCA\DuplicateFinder\Service;

use OCP\IConfig;
use Psr\Log\LoggerInterface;
use OCA\DuplicateFinder\AppInfo\Application;
use OCA\DuplicateFinder\Exception\UnableToParseException;

class ConfigService
{
    /** @var IConfig */
    private $config;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        IConfig $config,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->logger = $logger;
    }

    private function getIntVal(string $key, int $defaultValue) : int
    {
        return intval($this->config->getAppValue(Application::ID, $key, ''.$defaultValue));
    }

    private function getBoolVal(string $key, bool $defaultValue) : bool
    {
        if ($defaultValue) {
            $value = $this->config->getAppValue(Application::ID, $key, 'true');
        } else {
            $value = $this->config->getAppValue(Application::ID, $key, 'false');
        }
        return $value === 'true';
    }

    private function setIntVal(string $key, int $defaultValue) : void
    {
        $this->config->setAppValue(Application::ID, $key, ''.$defaultValue);
    }

    private function setBoolVal(string $key, bool $defaultValue) : void
    {
        if ($defaultValue) {
            $this->config->setAppValue(Application::ID, $key, 'true');
        } else {
            $this->config->setAppValue(Application::ID, $key, 'false');
        }
    }

    public function getUserValue(string $userId, string $key, string $defaultValue) : string
    {
        return $this->config->getUserValue($userId, Application::ID, $key, $defaultValue);
    }

    public function setUserValue(string $userId, string $key, string $value) : void
    {
        $this->config->setUserValue($userId, Application::ID, $key, $value);
    }

    /**
     * @return array<array>
     */
    public function getIgnoreConditions() : array
    {
        $unparsedConditions = $this->config->getAppValue(Application::ID, 'ignored_files', '[]');
        return json_decode($unparsedConditions, true);
    }

    public function getFindJobInterval() : int
    {
        return $this->getIntVal('backgroundjob_interval_find', 60*60*24*5);
    }

    public function getCleanupJobInterval() : int
    {
        return $this->getIntVal('backgroundjob_interval_cleanup', 60*60*24*2);
    }

    public function areFilesytemEventsDisabled():bool
    {
        return $this->getBoolVal('disable_filesystem_events', false);
    }

    public function areMountedFilesIgnored() : bool
    {
        return $this->getBoolVal('ignore_mounted_files', false);
    }

    public function getInstalledVersion() : string
    {
        return $this->config->getAppValue(Application::ID, 'installed_version', '0.0.0');
    }

    /**
     * @param array<array> $value
     * @throws UnableToParseException
     */
    public function setIgnoreConditions(array $value) : void
    {
        $deocedArray = json_encode($value);
        if (is_string($deocedArray)) {
            $this->config->setAppValue(Application::ID, 'ignored_files', $deocedArray);
        } else {
            throw new UnableToParseException('ignore conditions');
        }
    }

    public function setFindJobInterval(int $value) : void
    {
        $this->setIntVal('backgroundjob_interval_find', $value);
    }

    public function setCleanupJobInterval(int $value) : void
    {
        $this->setIntVal('backgroundjob_interval_cleanup', $value);
    }

    public function setFilesytemEventsDisabled(bool $value):void
    {
        $this->setBoolVal('disable_filesystem_events', $value);
    }

    public function setMountedFilesIgnored(bool $value) : void
    {
        $this->setBoolVal('ignore_mounted_files', $value);
    }
}
