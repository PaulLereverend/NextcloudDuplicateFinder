<?php

namespace OCA\DuplicateFinder\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use OCP\IUser;
use OCA\DuplicateFinder\Controller\DuplicateApiController;
use OCA\DuplicateFinder\Db\FileDuplicate;
use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Service\FileDuplicateService;
use OCA\DuplicateFinder\Service\FileInfoService;

class DuplicateApiControllerTest extends TestCase
{
    private function getMockService()
    {
        $file = $this->getMockBuilder(FileInfo::class)
        ->addMethods(['getPath'])
        ->getMock();
        $file->method('getPath')->willReturn('/admin/files/test_file');
        $duplicate = $this->getMockBuilder(FileDuplicate::class)
            ->addMethods(['getHash','getType'])
            ->onlyMethods(['getFiles'])
            ->getMock();
        $duplicate->method('getFiles')->willReturn([$file]);
        $duplicate->method('getHash')->willReturn('test_hash');
        $duplicate->method('getType')->willReturn('file_hash');
        $service = $this->createMock(FileDuplicateService::class);
        $service->method('findAll')->willReturn([
            'entities' => [$duplicate],
            'isLastFetched' => true
        ]);
        return $service;
    }

    private function getController($session = null, $service = null) : DuplicateApiController
    {
        if (is_null($service)) {
            $service = $this->getMockService();
        }
        return new DuplicateApiController(
            'duplicatefinder',
            $this->createMock(IRequest::class),
            $session,
            $service,
            $this->createMock(FileInfoService::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testLoggedInRequest()
    {

        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('admin');
        $session = $this->createMock(IUserSession::class);
        $session->method('getUser')->willReturn($user);

        $controller = $this->getController($session);
        $result = $controller->list();
        
        $this->assertInstanceOf(JSONResponse::class, $result);
        $result = $result->getData();
        $this->assertTrue($result['success']);
    }

    public function testLoggedOutRequest()
    {
        $controller = $this->getController();
        $result = $controller->list();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $result = $result->getData();
        $this->assertFalse($result['success']);
    }

    public function testAnonymousRequest()
    {

        $session = $this->createMock(IUserSession::class);
        $session->method('getUser')->willReturn(null);

        $controller = $this->getController($session);
        $result = $controller->list();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $result = $result->getData();
        $this->assertFalse($result['success']);
    }

    public function testErrorRequest()
    {

        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('admin');
        $session = $this->createMock(IUserSession::class);
        $session->method('getUser')->willReturn($user);
        
        $service = $this->getMockService();
        $service->method('findAll')->willThrowException(new \Exception());

        $controller = $this->getController($session, $service);
        $result = $controller->list();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $result = $result->getData();
        $this->assertFalse($result['success']);
    }
}
