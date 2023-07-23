<?php

namespace App;

use GuzzleHttp\Pool;

class StressTest {

	protected int $concurrent = 1;

	protected int $max_request_per_work_order = 1;

	protected array $config;

	protected SOAP $client;

	public function __construct() {
		$this->config = config()['stress_test'];
        $this->client = getClient();
	}

	// return new instance
	public static function new() {
		return (new self);
	}

	public function requestPerWorkOrder(int $value) {
		$this->max_request_per_work_order = $value;

		return $this;
	}

	public function concurrent(int $value) {
		$this->concurrent = $value;

		return $this;
	}

    /*
     * Starts Stress testing
     *
     * @param $concurrency Number of concurrent requests
     *
     * @return void
     */
    public function run(): void
    {
		$work_orders = WorkOrder::all($this->config);

        $logger = Logger::get();

        $workOrder = new WorkOrder($this->client);
        foreach ($work_orders as $work_order) {
            for ($i = 0; $i < $this->max_request_per_work_order; $i++) {
                $workOrder->initiate($work_order);
            }
        }

        $pool = new Pool($this->client->getGuzzleClient(), $workOrder->requests(), [
            'concurrency' => $this->concurrent,
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