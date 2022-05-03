<?php

namespace OCA\DuplicateFinder\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCA\DuplicateFinder\Settings\Section;

class SectionTest extends TestCase
{

    public function testSection()
    {
        $l10n = $this->createMock(IL10N::class);
        $l10n->method('t')->willReturn('Duplicate Finder');
        $url = $this->createMock(IURLGenerator::class);
        $url->method('imagePath')->willReturn('app.svg');
        $section = new Section($url, $l10n);
        $this->assertEquals('duplicatefinder', $section->getID());
        $this->assertEquals('Duplicate Finder', $section->getName());
        $this->assertEquals(25, $section->getPriority());
        $this->assertEquals('app.svg', $section->getIcon());
    }
}
