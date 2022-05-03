<?php

namespace OCA\DuplicateFinder\Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Event\CalculatedHashEvent;

class CalculatedHashEventTest extends TestCase
{

    public function testEvent()
    {
        $finfo = new FileInfo('/admin/files/test.file', 'admin');
        $event = new CalculatedHashEvent($finfo, 'abc');
        $this->assertEquals($finfo, $event->getFileInfo());
        $this->assertFalse($event->isNew());
        $this->assertTrue($event->isChanged());
        $this->assertEquals('abc', $event->getOldHash());

        $finfo = new FileInfo('/admin/files/test.file', 'admin');
        $finfo->setFileHash('abc');
        $event = new CalculatedHashEvent($finfo, 'abc');
        $this->assertEquals($finfo, $event->getFileInfo());
        $this->assertFalse($event->isChanged());

        $finfo = new FileInfo('/admin/files/test.file', 'admin');
        $finfo->setFileHash('abc');
        $event = new CalculatedHashEvent($finfo, null);
        $this->assertEquals($finfo, $event->getFileInfo());
        $this->assertTrue($event->isNew());
    }
}
