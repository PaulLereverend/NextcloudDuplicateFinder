<?php


namespace OCA\DuplicateFinder\Exception;

use Throwable;

class NotAuthenticatedException extends \Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? 'User is not authenticated', 0, null);
    }
}
