<?php

namespace OCA\DuplicateFinder\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\ILogger;
use OCA\DuplicateFinder\Controller\DuplicateApiController;
use OCA\DuplicateFinder\Service\FileDuplicateService;
use OCA\DuplicateFinder\Service\FileInfoService;

class DuplicateApiControllerTest extends TestCase
{

    private function getController(bool $hasSession = true) : DuplicateApiController
    {
        return new DuplicateApiController(
            'duplicatefinder',
            $this->createMock(IRequest::class),
            $hasSession ? $this->createMock(IUserSession::class) : null,
            $this->createMock(FileDuplicateService::class),
            $this->createMock(FileInfoService::class),
            $this->createMock(ILogger::class)
        );
    }

    public function testLoggedInRequest()
    {
        $controller = $this->getController();
        $result = $controller->list();
        
        $this->assertTrue($result instanceof JSONResponse);
    }

    public function testLoggedOutRequest()
    {
        $controller = $this->getController(false);
        $result = $controller->list();

        $this->assertTrue($result instanceof JSONResponse);
    }
}
