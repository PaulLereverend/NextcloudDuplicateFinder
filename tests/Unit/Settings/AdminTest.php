<?php

namespace OCA\DuplicateFinder\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;
use OCP\AppFramework\Http\TemplateResponse;
use OCA\DuplicateFinder\Settings\Admin;

class AdminTest extends TestCase
{

    public function testAdmin()
    {
        $response = new Admin();
        $this->assertInstanceOf(TemplateResponse::class, $response->getForm());
        $this->assertEquals('duplicatefinder', $response->getSection());
        $this->assertEquals(0, $response->getPriority());
    }
}
