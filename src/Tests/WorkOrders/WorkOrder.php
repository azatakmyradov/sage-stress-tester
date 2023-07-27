<?php

namespace App\Tests\WorkOrders;

use App\SOAP;
use App\Logger;
use GuzzleHttp\Pool;

class WorkOrder {
	protected SOAP $client;

    protected Logger $logger;

    protected array $requests;

	protected $config;

	protected $concurrent;

	protected $loggers;

	protected $max_calls;

    public function __construct($concurrent, $max_calls, $loggers, $client) {
		$this->concurrent = $concurrent;
		$this->max_calls = $max_calls;
		$this->loggers = $loggers;
        $this->client = $client;
		$this->config = config()['stress_test'];
    }

    public function requests(): array
    {
        return $this->requests;
    }

	public function createPool($requests, $fulfilled, $rejected) {
		return new Pool($this->client->getGuzzleClient(), $requests, [
            'concurrency' => $this->concurrent,
            'fulfilled' => $fulfilled,
            'rejected' => $rejected,
        ]);
	}
}