<?php

namespace App;

use App\Tests\WorkOrders\InitiateWorkOrder;
use GuzzleHttp\Pool;

class StressTest {

	protected int $concurrent = 1;

	protected int $max_request_per_work_order = 1;

	protected array $config;

	protected SOAP $client;

	protected $loggers;

	public function __construct($loggers) {
		$this->config = config()['stress_test'];
        $this->client = getClient();
		$this->loggers = $loggers;
	}

	// return new instance
	public static function new($loggers) {
		return (new self($loggers));
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

        $workOrder = new InitiateWorkOrder($this->client);
        foreach ($work_orders as $work_order) {
            for ($i = 0; $i < $this->max_request_per_work_order; $i++) {
                $workOrder->initiate($work_order);
            }
        }

		// Create a pool from requests
        $pool = $this->createPool(
			$workOrder->requests()
		);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();
    }

	public function createPool($requests) {
		return new Pool($this->client->getGuzzleClient(), $requests, [
            'concurrency' => $this->concurrent,
            'fulfilled' => function ($response, $index) use ($requests) {
				$id = hash('ripemd160', date(DATE_ATOM) . rand());

				$requestBody = $requests[$index]->getBody();
				$this->loggers['requests']->info("{$id} - {$requestBody}");

				$log = $response->getBody();
				$xml = simplexml_load_string($log);
				// TODO: come back here to see if it changes
				$resultXml = (string) $xml->xpath('//wss:runResponse/runReturn/resultXml')[0];
				$resultJson = json_decode($resultXml, true);

				$this->loggers['workorders']->info($id . ' ' . $response->getBody());

				$this->checkTracking($resultJson, $id);
            },
            'rejected' => function ($reason, $index) {
                // $logger->info($reason->getMessage());
            },
        ]);
	}

	public function checkTracking($response, $id) {
		if (isset($response['GRP3'])) {
			$trackingNumber = $response['GRP3']['MFGTRKNUM'];
		} else if (isset($response['PARAM_OUT'])) {
			$trackingNumber = $response['PARAM_OUT']['P_MFGTRKNUM'];
		} else {
			$this->loggers['tracking']->info($id . ' Tracking number was not found in response');
			return;
		}

		$params = [
			'GRP1' => [
				'TRACKING' => $trackingNumber
			]
		];

		$response = getClient()->run('CHECKMFG', $params);
		$log = $response->getBody();

		$xml = simplexml_load_string($log);
		$statusValue = (int) $xml->xpath('//wss:runResponse/runReturn/status')[0];
		$multiRefs = $xml->xpath('//multiRef');

		if (! $statusValue) {
			$message = (string) $multiRefs[0]->message;
			$message = ltrim($message, ';');
			$this->loggers['tracking']->info($id . ' ' . $message);
			return;
		}

		$this->loggers['tracking']->info($id . ' Tracking numbers created successfully');
	}
}