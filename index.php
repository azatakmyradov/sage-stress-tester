<?php

require 'vendor/autoload.php';
require 'functions.php';

use App\StressTest;

StressTest::new()
	->requestPerWorkOrder(10)
	->concurrent(10)
	->run();
