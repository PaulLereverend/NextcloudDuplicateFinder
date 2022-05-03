<?php

namespace OCA\DuplicateFinder\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use OCA\DuplicateFinder\Utils\CMDUtils;
use Symfony\Component\Console\Output\OutputInterface;
use OCA\DuplicateFinder\Db\FileDuplicate;
use OCA\DuplicateFinder\Db\FileInfo;
use OCA\DuplicateFinder\Service\FileDuplicateService;

class CMDUtilsTest extends TestCase
{

    private function getMockService($emptyFiles = false)
    {
        $file = $this->getMockBuilder(FileInfo::class)
        ->addMethods(['getPath'])
        ->getMock();
        $file->method('getPath')->willReturn('/admin/files/test_file');
        $duplicate = $this->getMockBuilder(FileDuplicate::class)
            ->addMethods(['getHash','getType'])
            ->onlyMethods(['getFiles'])
            ->getMock();
        if (!$emptyFiles) {
            $duplicate->method('getFiles')->willReturn([$file]);
        }
        $duplicate->method('getHash')->willReturn('test_hash');
        $duplicate->method('getType')->willReturn('file_hash');
        $service = $this->createMock(FileDuplicateService::class);
        $service->method('findAll')->willReturn([
            'entities' => [$duplicate],
            'isLastFetched' => true
        ]);
        return $service;
    }

    public function testShowDuplicates()
    {
        $outputInterface = $this->createMock(OutputInterface::class);
        $outputInterface
        ->expects($this->exactly(3))
        ->method('writeln')
        ->withConsecutive(
            [ 'Duplicates are: ', 0],
            [ 'test_hash(file_hash)', 0],
            [ '     /admin/files/test_file', 0]
        );
        $service = $this->getMockService();
        CMDUtils::showDuplicates($service, $outputInterface, function () {
            // Test Cases can not be canceld
        });
    }
    public function testShowDuplicatesWithUser()
    {
        $outputInterface = $this->createMock(OutputInterface::class);
        $outputInterface
        ->expects($this->exactly(1))
        ->method('writeln')
        ->withConsecutive(
            [ 'Duplicates for user "admin" are: ', 0]
        );
        $service = $this->getMockService(true);
        CMDUtils::showDuplicates($service, $outputInterface, function () {
            // Test Cases can not be canceld
        }, 'admin');
    }

    public function testShowIfOutputIsPresent()
    {
        $outputInterface = $this->createMock(OutputInterface::class);
        $outputInterface
        ->expects($this->once())
        ->method('writeln')
        ->with('Hello');
        CMDUtils::showIfOutputIsPresent('Hello', $outputInterface);
    }
}
