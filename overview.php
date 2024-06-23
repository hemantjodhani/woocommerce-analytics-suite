<?php
require 'functions.php';

$top_5_data = get_top_5_data();

?>
<h4 style="margin-bottom:10px;font-weight:bold;">Overview of last 90 days.</h4>
<script type="text/javascript">
	google.charts.load('current', {'packages':['table']});
	google.charts.setOnLoadCallback(drawTable);

	function drawTable() {
		var data = new google.visualization.DataTable();
		data.addColumn('string', 'Top 5 products');
		data.addColumn('string', 'Top 5 customers');
		data.addColumn('string', 'Top 5 sources');

		var top_5_products = <?php echo wp_json_encode( array_keys( $top_5_data['top_5_products'] ) ); ?>;
		var top_5_customers = <?php echo wp_json_encode( array_values( $top_5_data['top_5_orders'] ) ); ?>;
		var top_5_sources = <?php echo wp_json_encode( array_keys( $top_5_data['top_5_sources'] ) ); ?>;

		var rowCount = Math.max(top_5_products.length, top_5_customers.length, top_5_sources.length);

		for (var i = 0; i < rowCount; i++) {
			data.addRow([
				top_5_products[i] || '',
				(top_5_customers[i] ? top_5_customers[i]['customer_name'] + ' - ' + top_5_customers[i]['total'] : ''),
				top_5_sources[i] || ''
			]);
		}

		var table = new google.visualization.Table(document.getElementById('was-overview-table'));
		table.draw(data, {showRowNumber: true, width: '100%', height: '100%'});
	}
</script>

<div id="was-overview-table"></div>
