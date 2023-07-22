<?php

namespace App;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

class Logger
{
    public static function get(): MonologLogger
    {
        $timestamp = date(DATE_ATOM);
        $logger = new MonologLogger('logs');
        $logger->pushHandler(new StreamHandler('tmp/' . $timestamp . 'log.txt'));

        return $logger;
    }
}