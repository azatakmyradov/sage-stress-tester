<?php

require 'vendor/autoload.php';
require 'functions.php';

use App\Logger;
use App\Tests\WorkOrders\ProductionTracking;
use App\Tests\WorkOrders\ReintegrateWorkOrder;
use App\WorkOrder;

// START TEST
// $loggers = [
// 	'workorders' => new Logger('workorders'),
// 	'tracking' => new Logger('tracking'),
// 	'requests' => new Logger('requests')
// ];

// $productionTracking = new ProductionTracking(
// 	concurrent: 20,
// 	max_calls: 1,
// 	loggers: $loggers,
// 	client: getClient()
// );
// $productionTracking->run();
// END TEST

// START TEST
$loggers = [
	'response' => new Logger('response'),
	'requests' => new Logger('requests')
];

$reintegrate = new ReintegrateWorkOrder(
	concurrent: 1,
	max_calls: 200,
	loggers: $loggers,
	client: getClient()
);

$workOrder = WorkOrder::all(config()['stress_test'])[0];
$component = $workOrder['components'][0];
$workOrder = $workOrder['product'];
$reintegrate->run([
	'I_FCY' => $workOrder['MFGFCY'],
	'I_MFGNUM' => $workOrder['MFGNUM'],
	'I_ITM' => $component['ITM'],
	'I_QTY' => $component['QTY'],
	'I_LOC' => 'QUA01',
	'I_LOT' => $component['LOT'],
	'I_MVTDES' => "description here"
]);
// END TEST