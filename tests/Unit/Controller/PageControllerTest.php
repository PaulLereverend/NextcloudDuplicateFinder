<?php

namespace OCA\DuplicateFinder\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

use OCP\AppFramework\Http\TemplateResponse;

use OCP\IRequest;
use OCA\DuplicateFinder\Controller\PageController;
use OCA\DuplicateFinder\Service\FileDuplicateService;
use OCA\DuplicateFinder\Service\FileInfoService;

class PageControllerTest extends TestCase
{
    private $controller;
    private $userId = 'admin';

    public function setUp() : void
    {
        $this->controller = new PageController(
            'duplicatefinder',
            $this->createMock(IRequest::class),
            $this->userId,
            $this->createMock(FileDuplicateService::class),
            $this->createMock(FileInfoService::class)
        );
    }

    public function testIndex() : void
    {
        $result = $this->controller->index();

        $this->assertInstanceOf(TemplateResponse::class, $result);
        $this->assertEquals('index', $result->getTemplateName());
    }
}
