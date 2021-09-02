<?php

namespace OCA\DuplicateFinder\Exception;

use OCA\DuplicateFinder\Db\FileInfo;

class ForcedToIgnoreFileException extends \Exception
{
    public function __construct(FileInfo $fileInfo, string $responsibleSetting)
    {
        parent::__construct(
            'Ignored File Info for '.$fileInfo->getPath().' because of setting '.$responsibleSetting,
            1,
            null
        );
    }
}
