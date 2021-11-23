<?php

namespace OCA\DuplicateFinder\Exception;

use Throwable;

class UnknownOwnerException extends \Exception
{
    public function __construct(?string $path = null)
    {
        parent::__construct('The owner of '.$path.' is not set', 0, null);
    }
}
