<?php

namespace OCA\DuplicateFinder\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use OCA\DuplicateFinder\Exception\NotAuthenticatedException;

class NotAuthenticatedExceptionTest extends TestCase
{
    public function testException()
    {
        $this->expectException(NotAuthenticatedException::class);
        $this->expectExceptionMessage('User is not authenticated');
        throw new NotAuthenticatedException();
    }

    public function testExceptionWithCustomMessage()
    {
        $this->expectException(NotAuthenticatedException::class);
        $this->expectExceptionMessage('abc');
        throw new NotAuthenticatedException('abc');
    }
}
