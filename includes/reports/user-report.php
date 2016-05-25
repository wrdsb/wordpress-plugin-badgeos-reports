<?php
/**
 * User Report
 *
 * @package BadgeOS Reports
 * @subpackage Reports
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Register the user report
 *
 * @since 1.0.0
 */
function badgeos_reports_register_active_user_report() {

	// Setup the report
	$report = new BadgeOS_Report();
	$report->title = __( 'Active Users Report', 'badgeos-reports' );
	$report->slug  = 'active-users-report';
	add_action( "badgeos_reports_render_page_{$report->slug}", 'badgeos_reports_register_active_user_report_data' );

}
add_action( 'init', 'badgeos_reports_register_active_user_report' );

/**
 * Render the user report page
 *
 * @since 1.0.0
 */
function badgeos_reports_register_active_user_report_data( $report ) {
	global $wpdb;

	$report->data = $wpdb->get_results(
		$wpdb->prepare(
			"
			SELECT     user.ID,
			           user_login as username,
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
			           ) as total_achievements,
			           (
			                SELECT     COUNT(*)
			                FROM       $wpdb->postmeta as meta
			                INNER JOIN $wpdb->posts as post
			                           ON post.ID = meta.post_id
			                WHERE      meta.meta_key = '_badgeos_log_achievement_id'
			                           AND post.post_author = user.ID
			                           AND achievements.post_date >= %s
			                           AND post.post_date < %s
			                           AND post.post_title LIKE '%%unlocked%%'
			           ) as recent_achievements,
			           (
			                SELECT     post.post_date
			                FROM       $wpdb->postmeta as meta
			                INNER JOIN $wpdb->posts as post
			                           ON post.ID = meta.post_id
			                WHERE      meta.meta_key = '_badgeos_log_achievement_id'
			                           AND post.post_author = user.ID
			                           AND post.post_title LIKE '%%unlocked%%'
			                ORDER BY   post.post_date DESC
			                LIMIT      1
			           ) as last_earned,
			           user_registered as join_date
			FROM       $wpdb->users as user
			INNER JOIN $wpdb->usermeta as first_name
			           ON first_name.user_id = user.ID
			           AND first_name.meta_key = 'first_name'
			INNER JOIN $wpdb->usermeta as last_name
			           ON last_name.user_id = user.ID
			           AND last_name.meta_key = 'last_name'
			INNER JOIN $wpdb->posts as achievements
			           ON achievements.post_author = user.ID
			           AND achievements.post_type = 'badgeos-log-entry'
			INNER JOIN $wpdb->postmeta as achievement_meta
			           ON achievements.ID = achievement_meta.post_ID
			           AND achievement_meta.meta_key = '_badgeos_log_achievement_id'
			WHERE      achievements.post_date >= %s
			           AND achievements.post_date < %s
					   AND achievements.post_title LIKE '%%unlocked%%'
			GROUP BY   user.ID
			",
			date( 'Y-m-d', $report->get_start_date() ),
			date( 'Y-m-d', $report->get_end_date() + DAY_IN_SECONDS ),
			date( 'Y-m-d', $report->get_start_date() ),
			date( 'Y-m-d', $report->get_end_date() + DAY_IN_SECONDS )
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
		'total_achievements' => array(
			'title'         => __( 'Total Achievements', 'badgeos-reports' ),
			'data-type'     => 'integer',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
		'recent_achievements' => array(
			'title'         => __( 'Achievements Earned in Range', 'badgeos-reports' ),
			'data-type'     => 'integer',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
		'last_earned' => array(
			'title'         => __( 'Last Achievement Date', 'badgeos-reports' ),
			'data-type'     => 'date',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
		'join_date' => array(
			'title'         => __( 'Date Joined', 'badgeos-reports' ),
			'data-type'     => 'date',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
	);

	// Output report description
	echo '<h2>' . __( 'This report lists users who have earned achievements within this time period.', 'badgeos-reports' ) . '</h2>';

	// Add user count data points to page
	$user_count     = count_users();
	$active_users   = count( $report->data );
	$total_users    = $user_count['total_users'];
	$percent_active = $total_users ? ( $active_users / $total_users * 100 ) : 0;
	echo $report->render_data_point( __( 'Active Users', 'badgeos-reports' ), $active_users, 'integer' );
	echo $report->render_data_point( __( 'Total Users', 'badgeos-reports' ), $total_users, 'integer' );
	echo $report->render_data_point( __( 'Percent Active', 'badgeos-reports' ), $percent_active, 'percentage' );

	// // Count up our join dates
	// $join_totals   = wp_list_pluck( $report->data, 'total_users' );
	// $join_dates    = array_map( 'badgeos_reports_reformat_date', wp_list_pluck( $report->data, 'join_date' ) );
	// $new_by_date   = array_count_values( $join_dates ); // Date => Count
	// $total_by_date = ( ! empty( $join_dates ) && ! empty( $join_totals ) ) ? array_combine( $join_dates, $join_totals ) : array();

	// // Setup our chart data array
	// if ( ! empty( $total_by_date ) ) {
	// 	$chart_data = array();
	// 	$chart_data['labels'] = array_unique( $join_dates );
	// 	$chart_data['datasets'][] = $total_by_date;
	// 	$chart_data['datasets'][] = $new_by_date;

	// 	// Add join chart to page
	// 	echo $report->render_chart( 'line', $chart_data );
	// }

	// Add report table to page
	echo $report->render_table();
}

/**
 * Rewrite a given date string into a given format
 *
 * @since  1.0.0
 * @param  string $date   The given date string
 * @param  string $format A PHP Date format
 * @return string         A reformatted date string
 */
function badgeos_reports_reformat_date( $date = '', $format = 'M d, Y' ) {
	return date( $format, strtotime( $date ) );
}