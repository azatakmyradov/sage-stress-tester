<?php

namespace App;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

class Logger
{
	protected $logger;

	public function __construct($name) {
		$hash = hash('ripemd160', date(DATE_ATOM));
        $logger = new MonologLogger($name);
        $logger->pushHandler(new StreamHandler("tmp/{$hash}-{$name}.txt"));

        $this->logger = $logger;
	}
	
	public function put($type, ...$args) {
		$this->logger->$type(...$args);

		return $this;
	}

	public function __call($name, $args) {
		return $this->put($name, ...$args);
	}
}