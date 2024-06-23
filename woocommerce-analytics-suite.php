<?php
/**
 * Plugin Name: WooCommerce Analytics Suite
 * Description: Transform your WooCommerce store data into actionable insights with intuitive charts and graphs.
 * Version: 1.0
 * Author: Atomic house
 *
 * @package WooCommerce_Analytics_Suite
 */

define( 'WOOCOMMERCE_ANALYTICS_SUITE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

define( 'WOOCOMMERCE_ANALYTICS_SUITE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Add a menu page for Woo Analytics in the admin dashboard.
 */
function woo_analytics_add_menu() {
	add_menu_page(
		'Woo Analytics',
		'Woo Analytics',
		'manage_options',
		'woo-analytics',
		'woo_analytics_page',
		'dashicons-chart-line'
	);
}
add_action( 'admin_menu', 'woo_analytics_add_menu' );

/**
 * Display the content of the Woo Analytics page.
 */
function woo_analytics_page() {
	if ( is_plugin_inactive( 'woocommerce/woocommerce.php' ) ) {
		$activate_url = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=woocommerce/woocommerce.php' ), 'activate-plugin_woocommerce/woocommerce.php' );
		?>
			<div class="oaerror warning">
				<strong>Error</strong> - <span>WooCommerce is not activated.</span> <a href="<?php echo esc_url( $activate_url ); ?>">Activate WooCommerce</a>
			</div>
		<?php
	} elseif ( class_exists( 'WooCommerce' ) ) {
		include WOOCOMMERCE_ANALYTICS_SUITE_PLUGIN_PATH . 'chart-content.php';
	} else {
		?>
			<div class="oaerror warning">
				<strong>Error</strong> - WooCommerce is not installed.
			</div>
		<?php
	}
}

/**
 * Enqueue scripts and styles for the Woo Analytics plugin.
 */
function woo_analytics_enqueue_scripts_and_styles() {
	wp_enqueue_script( 'chart_external_library', 'https://www.gstatic.com/charts/loader.js', array(), '3.7.0', false );
	wp_enqueue_style( 'woo-analytics-style', WOOCOMMERCE_ANALYTICS_SUITE_PLUGIN_URL . 'assets/css/style.css', array(), '1.0' );
	wp_enqueue_script( 'woo-analytics-script', WOOCOMMERCE_ANALYTICS_SUITE_PLUGIN_URL . 'assets/js/script.js', array( 'jquery' ), '1.0', true );
}
add_action( 'admin_enqueue_scripts', 'woo_analytics_enqueue_scripts_and_styles' );

/**
 * Add a dashboard widget for displaying the Woo Analytics overview.
 */
function woo_analytics_add_dashboard_widget() {
	wp_add_dashboard_widget(
		'woo_analytics_dashboard_widget',
		'Woo Analytics Overview',
		'woo_analytics_dashboard_widget_display'
	);
}
add_action( 'wp_dashboard_setup', 'woo_analytics_add_dashboard_widget' );

/**
 * Display the content of the Woo Analytics dashboard widget.
 */
function woo_analytics_dashboard_widget_display() {
	if ( class_exists( 'WooCommerce' ) ) {
		include WOOCOMMERCE_ANALYTICS_SUITE_PLUGIN_PATH . 'overview.php';
	}
}

/**
 * Add a custom order column for total order count in WooCommerce orders screen.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function woo_analytics_add_custom_order_column( $columns ) {
	$columns['customer_order_count'] = __( 'Total order count', 'woo-analytics-suite' );
	return $columns;
}
add_filter( 'manage_edit-shop_order_columns', 'woo_analytics_add_custom_order_column' );

/**
 * Display the content for the custom order column.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 */
function woo_analytics_display_custom_order_column_content( $column, $post_id ) {
	if ( 'customer_order_count' === $column ) {
		$order = wc_get_order( $post_id );
		if ( $order ) {
			$customer_email = $order->get_billing_email();
			if ( ! empty( $customer_email ) ) {
				$args            = array(
					'customer' => $customer_email,
					'return'   => 'ids',
				);
				$customer_orders = wc_get_orders( $args );
				$order_count     = count( $customer_orders );
				echo esc_html( $order_count );
			} else {
				echo '-';
			}
		} else {
			echo '-';
		}
	}
}
add_action( 'manage_shop_order_posts_custom_column', 'woo_analytics_display_custom_order_column_content', 10, 2 );
