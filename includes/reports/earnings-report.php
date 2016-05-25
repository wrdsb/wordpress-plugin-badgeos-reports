<?php
/**
 * Earners Report
 *
 * @package BadgeOS Reports
 * @subpackage Reports
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Register the earners report
 *
 * @since 1.0.0
 */
function badgeos_reports_register_earners_report() {

	// Setup the report
	$report                 = new BadgeOS_Report();
	$report->achievement_id = isset( $_GET['achievement_id'] ) ? $_GET['achievement_id'] : 0;
	$report->title          = sprintf( __( '"%s" Earnings Report', 'badgeos-reports' ), get_the_title( $report->achievement_id ) );
	$report->slug           = 'earnings-report';
	$report->show_in_menu   = false;
	add_action( "badgeos_reports_render_page_{$report->slug}", 'badgeos_reports_register_earners_report_data' );

}
add_action( 'init', 'badgeos_reports_register_earners_report' );

/**
 * Render the user earners page
 *
 * @since 1.0.0
 */
function badgeos_reports_register_earners_report_data( $report ) {
	global $wpdb;

	$date_range   = $report->__get_query_date_range();
	$report->data = $wpdb->get_results(
		$wpdb->prepare(
			"
			SELECT     user.ID,
					   user.user_login as username,
					   first_name.meta_value as first_name,
					   last_name.meta_value as last_name,
					   user.user_email as email,
					   (
							SELECT     COUNT(*)
							FROM       $wpdb->postmeta as meta
							INNER JOIN $wpdb->posts as post
									   ON post.ID = meta.post_id
							WHERE      meta.meta_key = '_badgeos_log_achievement_id'
									   AND post.post_author = user.ID
									   AND post.post_title LIKE '%%unlocked%%'
									   AND meta.meta_value = %d
									   $date_range
					   ) as earned_count,
					   (
							SELECT     post.post_date
							FROM       $wpdb->postmeta as meta
							INNER JOIN $wpdb->posts as post
									   ON post.ID = meta.post_id
							WHERE      meta.meta_key = '_badgeos_log_achievement_id'
									   AND post.post_author = user.ID
									   AND post.post_title LIKE '%%unlocked%%'
									   AND meta.meta_value = %d
									   $date_range
							ORDER BY   post.post_date DESC
							LIMIT      1
					   ) as last_earned
			FROM       $wpdb->users as user
			INNER JOIN $wpdb->usermeta as first_name
					   ON first_name.user_id = user.ID
					   AND first_name.meta_key = 'first_name'
			INNER JOIN $wpdb->usermeta as last_name
					   ON last_name.user_id = user.ID
					   AND last_name.meta_key = 'last_name'
			INNER JOIN $wpdb->posts as post
					   ON post.post_author = user.ID
					   AND post.post_title LIKE '%%unlocked%%'
					   $date_range
			INNER JOIN $wpdb->postmeta as meta
					   ON post.id = meta.post_id
					   AND meta.meta_key = '_badgeos_log_achievement_id'
					   AND meta.meta_value = %d
			GROUP BY   user.ID
			",
			$report->achievement_id,
			$report->achievement_id,
			$report->achievement_id
		),
		'ARRAY_A'
	);
	$report->columns = array(
		'ID' => array(
			'title'         => __( 'User ID', 'badgeos-reports' ),
			'data-type'     => 'integer',
			'show_in_table' => false,
			'show_in_chart' => false,
			'show_in_csv'   => false,
		),
		'username' => array(
			'title'         => __( 'Username', 'badgeos-reports' ),
			'data-type'     => 'user_login',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
		'first_name' => array(
			'title'         => __( 'First Name', 'badgeos-reports' ),
			'data-type'     => 'string',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
		'last_name' => array(
			'title'         => __( 'Last Name', 'badgeos-reports' ),
			'data-type'     => 'string',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
		'email' => array(
			'title'         => __( 'Email', 'badgeos-reports' ),
			'data-type'     => 'email',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
		'earned_count' => array(
			'title'         => __( 'Earned Count', 'badgeos-reports' ),
			'data-type'     => 'integer',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
		'last_earned' => array(
			'title'         => __( 'Last Earned', 'badgeos-reports' ),
			'data-type'     => 'date',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
	);

	// Add "total users" data point to page
	$total_users = count( $report->data );
	echo $report->render_data_point( __( 'Total Users', 'badgeos-reports' ), $total_users, 'integer' );

	// Add "total earnings" data point to page
	$total_earned = array_sum( wp_list_pluck( $report->data, 'earned_count' ) );
	echo $report->render_data_point( __( 'Total Earnings', 'badgeos-reports' ), $total_earned, 'integer' );

	// Add "avg earnings" data point to page
	$avg_earned = ( $total_users ) ? $total_earned / $total_users : 0;
	echo $report->render_data_point( __( 'Avg Earnings per User', 'badgeos-reports' ), $avg_earned, 'float' );

	// Add report table to page
	echo $report->render_table();
}