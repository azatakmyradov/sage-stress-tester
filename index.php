<?php

require 'vendor/autoload.php';
require 'functions.php';

use App\Logger;
use App\StressTest;

$loggers = [
	'workorders' => new Logger('workorders'),
	'tracking' => new Logger('tracking'),
	'requests' => new Logger('requests')
];

StressTest::new($loggers)
	->requestPerWorkOrder(20)
	->concurrent(1)
	->run();
