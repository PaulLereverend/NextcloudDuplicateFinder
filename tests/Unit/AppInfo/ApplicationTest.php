<?php

namespace OCA\DuplicateFinder\Tests\Unit\AppInfo;

use PHPUnit\Framework\TestCase;
use OCA\DuplicateFinder\AppInfo\Application;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class ApplicationTest extends TestCase
{

    public function testApplication()
    {
        $this->assertEquals('duplicatefinder', Application::ID);
        $app = new Application();
        $app->register($this->createMock(IRegistrationContext::class));
        $app->boot($this->createMock(IBootContext::class));
    }
}
