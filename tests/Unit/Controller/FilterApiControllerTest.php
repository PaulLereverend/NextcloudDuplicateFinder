<?php

namespace OCA\DuplicateFinder\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

use OCP\AppFramework\Http\JSONResponse;

use OCP\IRequest;
use Psr\Log\LoggerInterface;
use OCP\IUser;
use OCP\IUserSession;
use OCA\DuplicateFinder\Controller\FilterApiController;
use OCA\DuplicateFinder\Service\ConfigService;

class FilterApiControllerTest extends TestCase
{
    private $filter = '[[{"attribute":"filename","operator":"=","value":"1_1.txt"}]]';

    private function getController($service = null) : FilterApiController
    {
        if (is_null($service)) {
            $service = $this->createMock(ConfigService::class);
        }

        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('admin');
        $session = $this->createMock(IUserSession::class);
        $session->method('getUser')->willReturn($user);

        return new FilterApiController(
            'duplicatefinder',
            $this->createMock(IRequest::class),
            $session,
            $service,
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testListError() : void
    {
        $result = $this->getController()->list();
        $this->assertInstanceOf(JSONResponse::class, $result);
        $result = $result->getData();
        $this->assertFalse($result['success']);
    }

    public function testList() : void
    {
        $service = $this->createMock(ConfigService::class);
        $service->method('getUserValue')->willReturn('[]');
        $result = $this->getController($service)->list();
        $this->assertInstanceOf(JSONResponse::class, $result);
        $result = $result->getData();
        $this->assertTrue($result['success']);
        $this->assertEquals([], $result['data']);

        $service = $this->createMock(ConfigService::class);
        $service->method('getUserValue')->willReturn($this->filter);
        $result = $this->getController($service)->list();
        $this->assertInstanceOf(JSONResponse::class, $result);
        $result = $result->getData();
        $this->assertTrue($result['success']);
        $this->assertJsonStringEqualsJsonString($this->filter, json_encode($result['data']));
    }

    public function testSave() : void
    {
        $service = $this->createMock(ConfigService::class);
        $service->method('getUserValue')->willReturn($this->filter);
        
        $parsedFilter = json_decode($this->filter);
        $result = $this->getController($service)->save($parsedFilter);
        $this->assertInstanceOf(JSONResponse::class, $result);
        $result = $result->getData();
        $this->assertTrue($result['success']);

        $service->method('setUserValue')->willThrowException(new \Exception());
        $result = $this->getController($service)->save(["false"]);
        $this->assertInstanceOf(JSONResponse::class, $result);
        $result = $result->getData();
        $this->assertFalse($result['success']);
    }
}
