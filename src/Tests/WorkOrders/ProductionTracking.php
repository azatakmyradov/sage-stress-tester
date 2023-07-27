<?php

namespace App\Tests\WorkOrders;

class ProductionTracking extends WorkOrder {
	public function initiate($item): array
    {
        $params = [
            'GRP1' => [
                "MFGFCY"    => $item['product']['MFGFCY'],
                "MFGNUM"    => $item['product']['MFGNUM'],
                "MFGTRKDAT" => $item['product']['MFGTRKDAT'],
                "TRSNUM"    => $item['product']['TRSNUM'],
                "LOC"       => $item['product']['LOC'],
                "STA"       => $item['product']['STA']
            ],
            'GRP2' => [
                [
                    "QTYSTU" => $item['product']['QTYSTU'],
                    "UOM" => $item['product']['UOM'],
                    "LOT" => $item['product']['LOT'],
                    "MVTDES" => $item['product']['MVTDES']
                ]
            ]
        ];
        
        $this->requests[] = $this->client->getRequest('run', 'ZWSMTK', $params);

        $this->useComponents($item['product'], $item['components']);

        return $this->requests;
    }

    public function useComponents($product, $components): void
    {
        foreach ($components as $component) {
            $params = [
                'PARAM_IN1' => [
                    "P_VCRORINUM"    => "",
                    "P_FCY"    => $product['MFGFCY'],
                    "P_MFGNUM" => $product['MFGNUM'],
                    "P_MFGTRKDAT"    => $product['MFGTRKDAT'],
                    "P_MVTDES"       => $product['MVTDES'],
                ],
                'PARAM_IN2' => [
                    [
                        "P_ITM" => $component['ITM'],
                        "P_QTY" => $component['QTY'],
                        "P_LOC" => $component['LOC'],
                        "P_LOT" => $component['LOT'],
                        "P_BOMSEQ" => "",
                        "P_MFGLIN" => ""
                    ]
                ]
            ];
            
            $this->requests[] = $this->client->getRequest('run', 'ZWSMTRK', $params);
        }
    }

	public function run() {
		$work_orders = \App\WorkOrder::all($this->config);

        foreach ($work_orders as $work_order) {
            for ($i = 0; $i < $this->max_calls; $i++) {
                $this->initiate($work_order);
            }
        }

		$fullfilled = function($response, $index) {
			$id = getUniqueId();

			$requestBody = $this->requests[$index]->getBody();
			$this->loggers['requests']->info("{$id} - {$requestBody}");

			$log = $response->getBody();
			$xml = simplexml_load_string($log);
			// TODO: come back here to see if it changes
			$resultXml = (string) $xml->xpath('//wss:runResponse/runReturn/resultXml')[0];
			$resultJson = json_decode($resultXml, true);

			$this->loggers['workorders']->info($id . ' ' . $response->getBody());

			$this->checkTracking($resultJson, $id);
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