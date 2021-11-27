<?php

namespace OCA\DuplicateFinder\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use OCA\DuplicateFinder\Exception\UnableToParseException;

class UnableToParseExceptionTest extends TestCase
{
    public function testException()
    {
        $this->expectException(UnableToParseException::class);
        $this->expectExceptionMessage('Unable to parse abc');
        throw new UnableToParseException('abc');
    }
}
