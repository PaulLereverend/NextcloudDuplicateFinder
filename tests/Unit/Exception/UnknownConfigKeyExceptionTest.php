<?php

namespace OCA\DuplicateFinder\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use OCA\DuplicateFinder\Exception\UnknownConfigKeyException;

class UnknownConfigKeyExceptionTest extends TestCase
{
    public function testException()
    {
        $this->expectException(UnknownConfigKeyException::class);
        $this->expectExceptionMessage('The config key abc is unknown');
        throw new UnknownConfigKeyException('abc');
    }
}
