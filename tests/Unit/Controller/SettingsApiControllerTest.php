<?php

namespace OCA\DuplicateFinder\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

use OCP\AppFramework\Http\JSONResponse;

use OCP\IRequest;
use Psr\Log\LoggerInterface;
use OCP\IUser;
use OCP\IUserSession;
use OCA\DuplicateFinder\Controller\SettingsApiController;
use OCA\DuplicateFinder\Service\ConfigService;

class SettingsApiControllerTest extends TestCase
{

    private function getService()
    {
        $service = $this->createMock(ConfigService::class);
        $service->method('getIgnoreConditions')->willReturn([]);
        $service->method('getFindJobInterval')->willReturn(5);
        $service->method('getCleanupJobInterval')->willReturn(2);
        $service->method('areFilesytemEventsDisabled')->willReturn(false);
        $service->method('areMountedFilesIgnored')->willReturn(false);
        $service->method('getInstalledVersion')->willReturn('0.0.11');
        return $service;
    }
    private function getController($service = null) : SettingsApiController
    {
        if (is_null($service)) {
            $service = $this->getService();
        }

        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('admin');
        $session = $this->createMock(IUserSession::class);
        $session->method('getUser')->willReturn($user);

        return new SettingsApiController(
            'duplicatefinder',
            $this->createMock(IRequest::class),
            $session,
            $service,
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testList() : void
    {
        $result = $this->getController()->list();
        $this->assertInstanceOf(JSONResponse::class, $result);
        $result = $result->getData();
        $this->assertTrue($result['success']);
        $this->assertEquals([
            'ignored_files' => [],
            'backgroundjob_interval_find' => 5,
            'backgroundjob_interval_cleanup' => 2,
            'disable_filesystem_events' => false,
            'ignore_mounted_files' => false,
            'installed_version' => '0.0.11'
        ], $result['data']);
    }

    public function testSave() : void
    {
        $config = [
            'ignored_files' => [],
            'backgroundjob_interval_find' => 5,
            'backgroundjob_interval_cleanup' => 2,
            'disable_filesystem_events' => false,
            'ignore_mounted_files' => false
        ];
        $result = $this->getController()->save($config);
        $this->assertInstanceOf(JSONResponse::class, $result);
        $result = $result->getData();
        $this->assertTrue($result['success']);
        $this->assertEquals([
            'ignored_files' => [],
            'backgroundjob_interval_find' => 5,
            'backgroundjob_interval_cleanup' => 2,
            'disable_filesystem_events' => false,
            'ignore_mounted_files' => false,
            'installed_version' => '0.0.11'
        ], $result['data']);

        $service = $this->getService();
        $service->method('setIgnoreConditions')->willThrowException(new \Exception());
        $result = $this->getController($service)->save($config);
        $this->assertInstanceOf(JSONResponse::class, $result);
        $result = $result->getData();
        $this->assertFalse($result['success']);

        $config['abc'] = 2;
        $result = $this->getController()->save($config);
        $this->assertInstanceOf(JSONResponse::class, $result);
        $result = $result->getData();
        $this->assertFalse($result['success']);
    }
}
