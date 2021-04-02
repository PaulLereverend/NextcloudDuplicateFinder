<?php

namespace OCA\DuplicateFinder\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

use OCP\AppFramework\Http\TemplateResponse;

use OCP\IRequest;
use OCP\Files\IRootFolder;
use OCA\DuplicateFinder\Controller\PageController;
use OCA\DuplicateFinder\Service\FileDuplicateService;
use OCA\DuplicateFinder\Service\FileInfoService;

class PageControllerTest extends TestCase {
	private $controller;
	private $userId = 'john';

	public function setUp() : void {
		$this->controller = new PageController('duplicatefinder',
			$this->createMock(IRequest::class), $this->userId,
			$this->createMock(FileDuplicateService::class), $this->createMock(FileInfoService::class),
			$this->createMock(IRootFolder::class)
		);
	}

	public function testIndex() {
		$result = $this->controller->index();

		$this->assertEquals('index', $result->getTemplateName());
		$this->assertTrue($result instanceof TemplateResponse);
	}

}
