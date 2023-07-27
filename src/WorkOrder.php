<?php

namespace App;

class WorkOrder {
	public static function all($config) {
		if (! file_exists($config['data_source'])) {
			die("Data source file doesn't exist");
		}

		$file = file_get_contents($config['data_source']);

        return WorkOrder::parse($file);
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