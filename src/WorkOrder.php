<?php

namespace App;

class WorkOrder {

    protected SOAP $client;

    protected Logger $logger;

    protected array $requests;

    public function __construct($client) {
        $this->client = $client;
    }
    
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

    public function requests(): array
    {
        return $this->requests;
    }

    /*
     * Parses the work orders from Sage
     */
    public static function parse($value): array
    {
        $work_orders = rtrim($value, '#');
        $work_orders = explode('#', $work_orders);

        $parsed_work_orders = [];

        foreach ($work_orders as $work_order) {
            $data = [];
            [$product_str, $components_str] = explode('|', $work_order);

            // Parse product key-value pairs
            parse_str(str_replace(';', '&', $product_str), $data['product']);

            // Parse components key-value pairs
            $components = explode('&', rtrim($components_str, '&'));
            foreach ($components as $component) {
                parse_str(str_replace(';', '&', $component), $data['components'][]);
            }

            $parsed_work_orders[] = $data;
        }

        return $parsed_work_orders;
    }

}