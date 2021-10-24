<?php
namespace OCA\DuplicateFinder\Service;

use OCP\IConfig;
use OCP\ILogger;
use OCA\DuplicateFinder\AppInfo\Application;

class ConfigService
{
    /** @var IConfig */
    private $config;
    /** @var ILogger */
    private $logger;

    public function __construct(
        IConfig $config,
        ILogger $logger
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
}
