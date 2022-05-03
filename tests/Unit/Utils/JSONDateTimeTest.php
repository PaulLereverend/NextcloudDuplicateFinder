<?php

namespace OCA\DuplicateFinder\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use OCA\DuplicateFinder\Utils\JSONDateTime;

class JSONDateTimeTest extends TestCase
{
    public function testDate()
    {
        $date = new JSONDateTime();
        $date->setTimestamp(0);
        $this->assertEquals('"1970-01-01T00:00:00+0000"', json_encode($date));
    }
}
