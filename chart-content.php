<?php

require 'functions.php';
$wca_current_user = wp_get_current_user();
?>
<div class="was-greeting-text">
	<h1 class="was-greet-time"></h1>
	<h1>&#160;<?php echo esc_html( $wca_current_user->user_login ); ?></h1>
</div>


<div class="was-filter-data">
	<form action="" method="post">
		<?php wp_nonce_field( 'filter_dates_action', 'filter_dates_nonce' ); ?>
		<span>From</span> <input type="date" id="from_date" name="from_date" required> <span>to</span> <input type="date" id="to_date" name="to_date" required>
		<input type="submit" value="Filter" class="was-filter-date-submission-btn" name="filter_dates">
	</form>
</div>


<?php
$from_date = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
$to_date   = gmdate( 'Y-m-d' );

$all_orders = get_woocommerce_completed_orders();

$sales_data = calculate_sales_of_last_thirty_days_in_ten_days_differences( $all_orders, $from_date, $to_date );


$total_sales           = $sales_data['total_sales'];
$first_ten_days_sales  = $sales_data['first_ten_days_sales'];
$second_ten_days_sales = $sales_data['second_ten_days_sales'];
$last_ten_days_sales   = $sales_data['last_ten_days_sales'];
$top_selling_products  = top_selling_products_in_given_data( $all_orders );
$refernces_types       = get_all_references_and_number_of_orders_from_each_type( $all_orders );
$device_types          = get_orders_from_device_types( $all_orders );

$cancelled_failed_orders = get_cancelled_failed_orders( $from_date, $to_date );

$average_order_val = average_order_value( $all_orders );


if ( isset( $_POST['filter_dates'] ) && isset( $_POST['filter_dates_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['filter_dates_nonce'] ) ), 'filter_dates_action' ) ) {
	$from_date       = isset( $_POST['from_date'] ) ? sanitize_text_field( wp_unslash( $_POST['from_date'] ) ) : '';
	$to_date         = isset( $_POST['to_date'] ) ? gmdate( 'Y-m-d', strtotime( sanitize_text_field( wp_unslash( $_POST['to_date'] ) ) . '+1 day' ) ) : '';
	$filtered_orders = get_orders_from_given_time( $from_date, $to_date );

	if ( gmdate( 'm', strtotime( $from_date ) ) === gmdate( 'm', strtotime( $to_date ) ) ) {
		$sales_data            = calculate_sales_of_last_thirty_days_in_ten_days_differences( $filtered_orders, $from_date, $to_date );
		$total_sales           = $sales_data['total_sales'];
		$first_ten_days_sales  = $sales_data['first_ten_days_sales'];
		$second_ten_days_sales = $sales_data['second_ten_days_sales'];
		$last_ten_days_sales   = $sales_data['last_ten_days_sales'];
	} elseif ( gmdate( 'm', strtotime( $from_date ) ) !== gmdate( 'm', strtotime( $to_date ) ) && gmdate( 'y', strtotime( $from_date ) ) === gmdate( 'y', strtotime( $to_date ) ) ) {
		$monthly_data     = get_sales_data_for_each_month( $filtered_orders );
		$total_sales      = $monthly_data['total_sales'];
		$every_month_data = $monthly_data['sales_data_for_each_month'];
	} elseif ( gmdate( 'y', strtotime( $from_date ) ) !== gmdate( 'y', strtotime( $to_date ) ) ) {
		$yearly_data     = get_sales_data_for_each_year( $filtered_orders );
		$total_sales     = $yearly_data['total_sales'];
		$every_year_data = $yearly_data['sales_data_for_each_year'];
	}

	$top_selling_products = top_selling_products_in_given_data( $filtered_orders );
	$refernces_types      = get_all_references_and_number_of_orders_from_each_type( $filtered_orders );
	$device_types         = get_orders_from_device_types( $filtered_orders );

	$average_order_val = average_order_value( $filtered_orders );

	$cancelled_failed_orders = get_cancelled_failed_orders( $from_date, $to_date );
}
?>
<div class="was-all-chart-wrap">
	<div class="was-chart-wrap">
		<span>Total orders: <?php echo number_format( $total_sales ); ?></span>
		<div id="myChart2" style="width:340px; height:300px;"></div>
	</div>
	<div class="was-chart-wrap">
		<span>Total sales:
		<?php
		if ( isset( $_POST['filter_dates'] ) ) {
			echo count( $filtered_orders );
		} else {
			echo count( $all_orders );
		}
		?>
		</span>
		<div id="myChart" style="width:340px; height:300px;"></div>
	</div>

	<div class="was-chart-wrap">
		<div id="was-average-sales" style="width: 340px; height: 300px;">
			<span>Average order value</span>
			<div class="was-average-order-val-amt">
				<h1><?php echo esc_html( get_woocommerce_currency_symbol() ) . ' ' . esc_attr( $average_order_val ); ?></h1>
			</div>
		</div>
	</div>

	<div class="was-chart-wrap">
		<div id="donutchart" style="width: 340px; height: 300px;"></div>
	</div>
	<div class="was-chart-wrap">
		<div id="dual_x_div" style="width: 340px; height: 300px;"></div>
	</div>
	<div class="was-chart-wrap">
		<div id="chart_div2" style="width: 340px; height: 300px;"></div>
	</div>
</div>

<script>
google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawCharts);

function drawCharts() {
	const data1 = google.visualization.arrayToDataTable([
	['Product', 'Number of orders'],
	<?php
	foreach ( $top_selling_products as $product => $orders ) {
		echo "['" . esc_attr( $product ) . "', " . esc_attr( $orders ) . "],\n";
	}
	?>
]);

	const options1 = {
		title: 'World Wide Wine Production'
	};

	const data2 = google.visualization.arrayToDataTable([
	['Period', 'Sales'],
	<?php
	if ( ! isset( $_POST['filter_dates'] ) ) {
		?>
		['First Ten Days', <?php echo esc_html( $first_ten_days_sales ); ?>],
		['Second Ten Days', <?php echo esc_html( $second_ten_days_sales ); ?>],
		['Last Ten Days', <?php echo esc_html( $last_ten_days_sales ); ?>],
		<?php
	}
	?>
	<?php
	if ( isset( $_POST['filter_dates'] ) ) {
		if ( gmdate( 'm', strtotime( $from_date ) ) !== gmdate( 'm', strtotime( $to_date ) ) && gmdate( 'y', strtotime( $from_date ) ) === gmdate( 'y', strtotime( $to_date ) ) ) {
			foreach ( $every_month_data as $date => $month ) {
				$formatted_date = gmdate( 'M Y', strtotime( $date ) );
				echo "['" . esc_html( $formatted_date ) . "', " . esc_html( $month ) . "],\n";
			}
		} elseif ( gmdate( 'y', strtotime( $from_date ) ) !== gmdate( 'y', strtotime( $to_date ) ) ) {
			foreach ( $every_year_data as $year_sales => $sales ) {
				echo "['" . esc_html( $year_sales ) . "', " . esc_html( $sales ) . "],\n";
			}
		} else {
			?>
			['First Ten Days', <?php echo esc_html( $first_ten_days_sales ); ?>],
			['Second Ten Days', <?php echo esc_html( $second_ten_days_sales ); ?>],
			['Last Ten Days', <?php echo esc_html( $last_ten_days_sales ); ?>],
			<?php
		}
	}
	?>
]);

 
	const options2 = {
		title: 'House Prices vs. Size',
		hAxis: { title: 'Square Meters' },
		vAxis: { title: 'Price in Millions' },
		legend: 'none'
	};

	const data4 = google.visualization.arrayToDataTable([
		['Source', 'Orders'],
		<?php foreach ( $refernces_types as $refernce => $orders ) : ?>
			['<?php echo esc_html( $refernce ); ?>', <?php echo esc_html( $orders ); ?>],
		<?php endforeach; ?>
	]);

	const options4 = {
		title: 'My Daily Activities',
		pieHole: 0.4,
	};

	const chart1 = new google.visualization.BarChart(document.getElementById('myChart'));
	chart1.draw(data1, options1);

	const chart2 = new google.visualization.LineChart(document.getElementById('myChart2'));
	chart2.draw(data2, options2);

	const chart4 = new google.visualization.PieChart(document.getElementById('donutchart'));
	chart4.draw(data4, options4);

	google.charts.load('current', {'packages':['bar']});
	google.charts.setOnLoadCallback(drawStuff);

	function drawStuff() {
		var data = new google.visualization.arrayToDataTable([
			['Devices', 'Orders',],
			<?php foreach ( $device_types as $device => $orders ) : ?>
				['<?php echo esc_html( $device ); ?>', <?php echo esc_html( $orders ); ?>],
			<?php endforeach; ?>
		]);

		var options = {
			bars: 'horizontal',
			series: {
				0: { axis: 'distance' }, 
				1: { axis: 'brightness' } 
			},
			axes: {
				x: {
					distance: {label: 'parsecs'},
					brightness: {side: 'top', label: 'apparent magnitude'}
				}
			}
		};

		var chart = new google.charts.Bar(document.getElementById('dual_x_div'));
		chart.draw(data, options);
	};

	google.charts.load('current', {'packages':['corechart']});
	google.charts.setOnLoadCallback(drawChart);

	function drawChart() {
		var data = google.visualization.arrayToDataTable([
			['Cancelled orders', 'Failed orders'],
			[ <?php echo esc_html( $cancelled_failed_orders['cancelled'] ); ?>  , <?php echo esc_html( $cancelled_failed_orders['failed'] ); ?>],
		]);

		var options = {
			title: 'Cancelled and failed orders',
			legend: { position: 'none' },
			colors: ['orange', 'red']
		};

		var chart = new google.visualization.Histogram(document.getElementById('chart_div2'));
		chart.draw(data, options);
	}
}
</script>
