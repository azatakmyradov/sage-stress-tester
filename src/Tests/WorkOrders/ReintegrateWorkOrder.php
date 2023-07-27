<?php

namespace App\Tests\WorkOrders;

class ReintegrateWorkOrder extends WorkOrder {
	public function initiate($component): array
    {
		$params['PARAM_IN'] = [
			'I_FCY' => $component['I_FCY'],
			'I_MFGNUM' => $component['I_MFGNUM'],
			'I_ITM' => $component['I_ITM'],
			'I_QTY' => $component['I_QTY'],
			'I_LOC' => $component['I_LOC'],
			'I_LOT' => $component['I_LOT'],
			'I_MVTDES' => "description here"
		];
        
        $this->requests[] = $this->client->getRequest('run', 'ZWSMTRKR', $params);

        return $this->requests;
    }

	public function run($component) {
        for ($i = 0; $i < $this->max_calls; $i++) {
			$this->initiate($component);
		}

		$fullfilled = function($response, $index) {
			$id = getUniqueId();

			$this->loggers['response']->info("{$id} - {$response->getBody()}");
			$this->loggers['requests']->info("{$id} - {$this->requests()[$index]->getBody()}");
		};

		$rejected = function ($reason, $index) {};

		// Create a pool from requests
        $pool = $this->createPool(
			$this->requests(), $fullfilled, $rejected
		);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();
	}
}