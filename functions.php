<?php

/**
 * Retrieve completed orders from WooCommerce within the last 30 days.
 *
 * @return array Array of order data containing order ID, total, and date.
 */
function get_woocommerce_completed_orders() {
	$from_date = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
	$to_date   = gmdate( 'Y-m-d H:i:s' );

	$args = array(
		'status'     => array( 'completed', 'processing', 'on_hold', 'pending' ),
		'limit'      => -1,
		'date_query' => array(
			'after'     => $from_date,
			'before'    => $to_date,
			'inclusive' => true,
		),
	);

	$orders_data = array();
	$orders      = wc_get_orders( $args );

	foreach ( $orders as $order ) {
		$order_id    = $order->get_id();
		$order_total = $order->get_total();
		$order_date  = $order->get_date_created()->format( 'Y-m-d' );

		$orders_data[] = array(
			'order_id'    => $order_id,
			'order_total' => $order_total,
			'order_date'  => $order_date,
		);
	}

	return $orders_data;
}

/**
 * Calculate sales within the last thirty days in ten-day intervals.
 *
 * @param array  $data       Array of order data.
 * @param string $from_date  Start date.
 * @param string $to_date    End date.
 * @return array Sales data for ten-day intervals.
 */
function calculate_sales_of_last_thirty_days_in_ten_days_differences( $data, $from_date, $to_date ) {

	$total_sales           = 0;
	$first_ten_days_sales  = 0;
	$second_ten_days_sales = 0;
	$last_ten_days_sales   = 0;

	foreach ( $data as $order ) {
		$order_date  = strtotime( $order['order_date'] );
		$order_total = $order['order_total'];

		$total_sales += $order_total;

		if ( $order_date >= strtotime( $from_date ) && $order_date < strtotime( '+10 days', strtotime( $from_date ) ) ) {
			$first_ten_days_sales += $order_total;
		} elseif ( $order_date >= strtotime( '+10 days', strtotime( $from_date ) ) && $order_date < strtotime( '+20 days', strtotime( $from_date ) ) ) {
			$second_ten_days_sales += $order_total;
		} elseif ( $order_date >= strtotime( '+20 days', strtotime( $from_date ) ) && $order_date <= strtotime( $to_date ) ) {
			$last_ten_days_sales += $order_total;
		}
	}

	return array(
		'total_sales'           => $total_sales,
		'first_ten_days_sales'  => $first_ten_days_sales,
		'second_ten_days_sales' => $second_ten_days_sales,
		'last_ten_days_sales'   => $last_ten_days_sales,
	);
}

/**
 * Get top selling products from given order data.
 *
 * @param array $data Array of order data.
 * @return array Top selling products.
 */
function top_selling_products_in_given_data( $data ) {

	$product_orders = array();
	foreach ( $data as $order ) {
		$order_id = $order['order_id'];

		$order_obj = wc_get_order( $order_id );

		$order_products = array();

		foreach ( $order_obj->get_items() as $item_id => $item ) {
			$product_id = $item->get_name();
			if ( ! in_array( $product_id, $order_products, true ) ) {
				$order_products[] = $product_id;
			}
		}

		foreach ( $order_products as $product_id ) {
			if ( isset( $product_orders[ $product_id ] ) ) {
				++$product_orders[ $product_id ];
			} else {
				$product_orders[ $product_id ] = 1;
			}
		}
	}

	arsort( $product_orders );

	$top_products = array_slice( $product_orders, 0, 5, true );

	return $top_products;
}

/**
 * Get count of cancelled and failed orders within a given time frame.
 *
 * @param string $start_date Start date.
 * @param string $end_date   End date.
 * @return array Count of cancelled and failed orders.
 */
function get_cancelled_failed_orders( $start_date, $end_date ) {

	$start_date_formatted = gmdate( 'Y-m-d H:i:s', strtotime( $start_date ) );
	$end_date_formatted   = gmdate( 'Y-m-d H:i:s', strtotime( $end_date ) );

	$failed_args   = array(
		'status'     => 'failed',
		'limit'      => -1,
		'date_query' => array(
			'after'  => $start_date_formatted,
			'before' => $end_date_formatted,
		),
	);
	$failed_orders = wc_get_orders( $failed_args );

	$cancelled_args   = array(
		'status' => 'cancelled',
		'limit'  => -1,
	);
	$cancelled_orders = wc_get_orders( $cancelled_args );

	$failed_count    = count( $failed_orders );
	$cancelled_count = count( $cancelled_orders );

	return array(
		'cancelled' => $cancelled_count,
		'failed'    => $failed_count,
	);
}

/**
 * Get references and number of orders from each type.
 *
 * @param array $data Array of order data.
 * @return array References and their corresponding order counts.
 */
function get_all_references_and_number_of_orders_from_each_type( $data ) {

	$all_references = array();

	foreach ( $data as $order ) {
		$order_id      = $order['order_id'];
		$order_obj     = wc_get_order( $order_id );
		$referrer_meta = $order_obj->get_meta( '_wc_order_attribution_utm_source' );

		if ( ! empty( $referrer_meta ) ) {
			if ( isset( $all_references[ $referrer_meta ] ) ) {
				++$all_references[ $referrer_meta ];
			} else {
				$all_references[ $referrer_meta ] = 1;
			}
		}
	}

	return $all_references;
}

/**
 * Get orders from different device types.
 *
 * @param array $data Array of order data.
 * @return array Orders from different device types.
 */
function get_orders_from_device_types( $data ) {

	$devices_types = array();

	foreach ( $data as $order ) {
		$order_id      = $order['order_id'];
		$order_obj     = wc_get_order( $order_id );
		$referrer_meta = $order_obj->get_meta( '_wc_order_attribution_device_type' );

		if ( ! empty( $referrer_meta ) ) {
			if ( isset( $devices_types[ $referrer_meta ] ) ) {
				++$devices_types[ $referrer_meta ];
			} else {
				$devices_types[ $referrer_meta ] = 1;
			}
		}
	}

	return $devices_types;
}

/**
 * Retrieve orders from a given time frame.
 *
 * @param string $start_date Start date.
 * @param string $end_date   End date.
 * @return array Orders within the specified time frame.
 */
function get_orders_from_given_time( $start_date, $end_date ) {

	$start_date_formatted = gmdate( 'Y-m-d H:i:s', strtotime( $start_date ) );
	$end_date_formatted   = gmdate( 'Y-m-d H:i:s', strtotime( $end_date ) );

	$args = array(
		'status'     => array( 'completed', 'processing', 'on_hold', 'pending' ),
		'limit'      => -1,
		'date_query' => array(
			'after'  => $start_date_formatted,
			'before' => $end_date_formatted,
		),
	);

	$orders_data = array();

	$orders = wc_get_orders( $args );

	foreach ( $orders as $order ) {
		$order_id    = $order->get_id();
		$order_total = $order->get_total();
		$order_date  = $order->get_date_created()->format( 'Y-m-d' );

		$orders_data[] = array(
			'order_id'    => $order_id,
			'order_total' => $order_total,
			'order_date'  => $order_date,
		);
	}

	return $orders_data;
}

/**
 * Get sales data for each month from the given data.
 *
 * @param array $data Array of order data.
 * @return array Sales data for each month and total sales.
 */
function get_sales_data_for_each_month( $data ) {
	$sales_data_for_each_month = array();
	$total_sales               = 0;

	foreach ( $data as $order ) {

		$year  = gmdate( 'Y', strtotime( $order['order_date'] ) );
		$month = gmdate( 'm', strtotime( $order['order_date'] ) );

		if ( isset( $sales_data_for_each_month[ "$year-$month" ] ) ) {
			$sales_data_for_each_month[ "$year-$month" ] += $order['order_total'];
		} else {
			$sales_data_for_each_month[ "$year-$month" ] = $order['order_total'];
		}

		$total_sales += $order['order_total'];
	}

	return array(
		'sales_data_for_each_month' => $sales_data_for_each_month,
		'total_sales'               => $total_sales,
	);
}

/**
 * Calculate average order value from the given order data.
 *
 * @param array $data Array of order data.
 * @return int Average order value.
 */
function average_order_value( $data ) {
	$total_sales  = 0;
	$total_orders = count( $data );

	foreach ( $data as $order ) {
		$total_sales += $order['order_total'];
	}

	if ( $total_orders > 0 ) {
		$average_order_value = $total_sales / $total_orders;
	} else {
		$average_order_value = 0;
	}

	return round( $average_order_value );
}

/**
 * Get top 5 products, unique customer orders, and referral sources from the last 90 days.
 *
 * @return array Top 5 products, unique customer orders, and referral sources.
 */
function get_top_5_data() {
	$from_date = gmdate( 'Y-m-d', strtotime( '-90 days' ) );
	$to_date   = gmdate( 'Y-m-d' );

	$args = array(
		'limit'      => -1,
		'date_query' => array(
			'after'  => $from_date,
			'before' => $to_date,
		),
	);

	$orders = wc_get_orders( $args );

	$product_orders = array();
	$order_totals   = array();
	$sources        = array();
	$customers      = array();

	foreach ( $orders as $order ) {
		foreach ( $order->get_items() as $item ) {
			$product_name = $item->get_name();
			if ( isset( $product_orders[ $product_name ] ) ) {
				++$product_orders[ $product_name ];
			} else {
				$product_orders[ $product_name ] = 1;
			}
		}

		$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
		$order_total   = $order->get_total();

		if ( ! isset( $customers[ $customer_name ] ) || $order_total > $customers[ $customer_name ]['total'] ) {
			$customers[ $customer_name ] = array(
				'order_id'      => $order->get_id(),
				'total'         => $order_total,
				'customer_name' => $customer_name,
			);
		}

		$source = $order->get_meta( '_wc_order_attribution_utm_source' );
		if ( $source ) {
			if ( isset( $sources[ $source ] ) ) {
				++$sources[ $source ];
			} else {
				$sources[ $source ] = 1;
			}
		}
	}

	arsort( $product_orders );
	$top_5_products = array_slice( $product_orders, 0, 5, true );

	uasort(
		$customers,
		function ( $a, $b ) {
			return $b['total'] <=> $a['total'];
		}
	);
	$top_5_orders           = array_slice( $customers, 0, 5, true );
	$top_5_orders_formatted = array();
	foreach ( $top_5_orders as $order ) {
		$top_5_orders_formatted[ $order['order_id'] ] = array(
			'total'         => $order['total'],
			'customer_name' => $order['customer_name'],
		);
	}

	arsort( $sources );
	$top_5_sources = array_slice( $sources, 0, 5, true );

	return array(
		'top_5_products' => $top_5_products,
		'top_5_orders'   => $top_5_orders_formatted,
		'top_5_sources'  => $top_5_sources,
	);
}

/**
 * Get sales data for each year from the given data.
 *
 * @param array $data Array of order data.
 * @return array Sales data for each year and total sales.
 */
function get_sales_data_for_each_year( $data ) {
	$sales_data_for_each_year = array();
	$total_sales              = 0;

	foreach ( $data as $order ) {
		$year = gmdate( 'Y', strtotime( $order['order_date'] ) );

		if ( isset( $sales_data_for_each_year[ $year ] ) ) {
			$sales_data_for_each_year[ $year ] += $order['order_total'];
		} else {
			$sales_data_for_each_year[ $year ] = $order['order_total'];
		}

		$total_sales += $order['order_total'];
	}

	return array(
		'sales_data_for_each_year' => $sales_data_for_each_year,
		'total_sales'              => $total_sales,
	);
}
