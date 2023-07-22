<?php

require 'vendor/autoload.php';
require 'functions.php';

use App\StressTest;

StressTest::start(10);
