<?php

namespace App;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

class Logger
{
    public static function get(): MonologLogger
    {
        $hash = hash('ripemd160', date(DATE_ATOM));
        $logger = new MonologLogger('logs');
        $logger->pushHandler(new StreamHandler('tmp/' . $hash . 'log.txt'));

        return $logger;
    }
}