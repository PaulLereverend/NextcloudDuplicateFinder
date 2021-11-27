<?php

namespace OCA\DuplicateFinder\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Exception\ForcedToIgnoreFileException;

class ForcedToIgnoreFileExceptionTest extends TestCase
{
    public function testException()
    {
        $this->expectException(ForcedToIgnoreFileException::class);
        $this->expectExceptionMessage('Ignored File Info for abc because of setting IgnoreSetting');
        throw new ForcedToIgnoreFileException(new FileInfo('abc'), 'IgnoreSetting');
    }
}
