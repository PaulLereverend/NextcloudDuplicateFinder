<?php

namespace OCA\DuplicateFinder\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use OCA\DuplicateFinder\Utils\JSONResponseTrait;
use \OCP\AppFramework\Http\JSONResponse;

class JSONResponseTraitTest extends TestCase
{

    public function setUp() : void
    {
        $this->mock = $this->getMockForTrait(JSONResponseTrait::class);
    }

    public function testSuccess()
    {
        $result = $this->mock->success('abc', 201);
        $this->assertInstanceOf(JSONResponse::class, $result);
        $result = $result->getData();
        $this->assertTrue($result['success']);
        $this->assertEquals(201, $result['status']);
        $this->assertEquals('abc', $result['data']);
    }

    public function testError()
    {
        $result = $this->mock->error(new \Exception('App Error'), 501);
        $this->assertInstanceOf(JSONResponse::class, $result);
        $result = $result->getData();
        $this->assertFalse($result['success']);
        $this->assertEquals(501, $result['status']);
        $this->assertIsArray($result['error']);
        $this->assertEquals('App Error', $result['error']['message']);
        $this->assertEquals(0, $result['error']['code']);
    }
}
