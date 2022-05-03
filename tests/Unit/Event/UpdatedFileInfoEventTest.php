<?php

namespace OCA\DuplicateFinder\Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Event\UpdatedFileInfoEvent;

class UpdatedFileInfoEventTest extends TestCase
{

    public function testEvent()
    {
        $finfo = new FileInfo('/admin/files/test.file', 'admin');
        $event = new UpdatedFileInfoEvent($finfo, 'admin');
        $this->assertEquals($finfo, $event->getFileInfo());
        $this->assertEquals('admin', $event->getUserID());
    }
}
