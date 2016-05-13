<?php
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}
define( 'WP_DELTA_ROOT', dirname( __FILE__ ) );
require_once WP_DELTA_ROOT . '/includes/class-wp-dbdelta.php';

