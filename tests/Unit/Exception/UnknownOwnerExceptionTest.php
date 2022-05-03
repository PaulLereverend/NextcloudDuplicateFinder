<?php

namespace OCA\DuplicateFinder\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use OCA\DuplicateFinder\Exception\UnknownOwnerException;

class UnknownOwnerExceptionTest extends TestCase
{
    public function testException()
    {
        $this->expectException(UnknownOwnerException::class);
        $this->expectExceptionMessage('The owner of abc is not set');
        throw new UnknownOwnerException('abc');
    }
}
