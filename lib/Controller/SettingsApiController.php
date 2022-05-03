<?php
namespace OCA\DuplicateFinder\Controller;

use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use OCP\AppFramework\Http\JSONResponse;
use OCA\DuplicateFinder\Exception\UnknownConfigKeyException;
use OCA\DuplicateFinder\Service\ConfigService;

class SettingsApiController extends AbstractAPIController
{
    /** @var ConfigService */
    private $configService;

    public function __construct(
        $appName,
        IRequest $request,
        ?IUserSession $userSession,
        ConfigService $configService,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request, $userSession, $logger);
        $this->configService = $configService;
    }

    /**
     * @return array<mixed>
     */
    private function getConfigArray() : array
    {
        return [
            'ignored_files' => $this->configService->getIgnoreConditions(),
            'backgroundjob_interval_find' => $this->configService->getFindJobInterval(),
            'backgroundjob_interval_cleanup' => $this->configService->getCleanupJobInterval(),
            'disable_filesystem_events' => $this->configService->areFilesytemEventsDisabled(),
            'ignore_mounted_files' => $this->configService->areMountedFilesIgnored(),
            'installed_version' => $this->configService->getInstalledVersion()
        ];
    }

    public function list(): JSONResponse
    {
        return $this->success($this->getConfigArray());
    }

    /**
     * @param array<mixed> $config
     */
    public function save(array $config): JSONResponse
    {
        $configKeys = [
            'ignored_files' => 'setIgnoreConditions',
            'backgroundjob_interval_find' => 'setFindJobInterval',
            'backgroundjob_interval_cleanup' => 'setCleanupJobInterval',
            'disable_filesystem_events' => 'setFilesytemEventsDisabled',
            'ignore_mounted_files' => 'setMountedFilesIgnored'
        ];
        foreach ($config as $key => $value) {
            $found = false;
            foreach (array_keys($configKeys) as $existingConfigKey) {
                if ($key === $existingConfigKey) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return $this->error(new UnknownConfigKeyException($key), 400);
            }
        }
        unset($key);
        unset($value);
        try {
            foreach ($config as $key => $value) {
                $method = $configKeys[$key];
                $this->configService->$method($config[$key]);
            }
            unset($key);
            unset($value);
        } catch (\Exception $e) {
            return $this->error($e, 400);
        }
        return $this->success($this->getConfigArray());
    }
}
