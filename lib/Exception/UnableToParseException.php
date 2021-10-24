<?php

namespace OCA\DuplicateFinder\Exception;

use Throwable;

class UnableToParseException extends \Exception
{
    public function __construct(?string $subject = null)
    {
        parent::__construct('Unable to parse '.$subject, 1, null);
    }
}
