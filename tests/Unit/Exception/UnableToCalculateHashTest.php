<?php

namespace OCA\DuplicateFinder\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use OCA\DuplicateFinder\Exception\UnableToCalculateHash;

class UnableToCalculateHashTest extends TestCase
{
    public function testException()
    {
        $this->expectException(UnableToCalculateHash::class);
        $this->expectExceptionMessage('Unable to calculate hash for abc');
        throw new UnableToCalculateHash('abc');
    }
}
