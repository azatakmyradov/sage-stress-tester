<?php

namespace App;

use GuzzleHttp\Pool;

class StressTest {

    /*
     * Starts Stress testing
     *
     * @param $concurrency Number of concurrent requests
     *
     * @return void
     */
    public static function start($concurrency): void
    {
        $config = config()['stress_test'];
        $client = getClient();

		if (! file_exists($config['data_source'])) {
			die("Data source file doesn't exist");
		}

		$file = file_get_contents($config['data_source']);

        $work_orders = WorkOrder::parse($file);

        $logger = Logger::get();

        $workOrder = new WorkOrder($client);
        foreach ($work_orders as $work_order) {
            for ($i = 0; $i < $config['max_calls_per_work_order']; $i++) {
                $workOrder->initiate($work_order);
            }
        }

        $pool = new Pool($client->getGuzzleClient(), $workOrder->requests(), [
            'concurrency' => $concurrency,
            'fulfilled' => function ($response, $index) use ($logger) {
                $logger->info($response->getBody());
            },
            'rejected' => function ($reason, $index) use ($logger) {
                $logger->info($reason->getMessage());
            },
        ]);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();
    }
}