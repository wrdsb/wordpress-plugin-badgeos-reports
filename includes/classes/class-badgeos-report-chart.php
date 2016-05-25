<?php
/**
 * Chart Rendering Support Functions
 *
 * @package BadgeOS Reports
 * @subpackage Reporting
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Abstract base class for charting data in BadgeOS reports
 *
 * @since 1.0.0
 * @subpackage Classes
 */
class BadgeOS_Report_Chart {

	/**
	 * Get a color for a given dataset count
	 *
	 * @since  1.0.0
	 * @param  integer $dataset_count The current dataset
	 * @param  float   $opacity       The level of opacity desired
	 * @return string                 an rgba formatted CSS color
	 */
	public static function get_color( $dataset_count = 1, $opacity = 0.5 ) {
		switch ( $dataset_count ) {
			case 1:
				return "rgba(148, 159, 177, {$opacity})";
				break;
			case 2:
				return "rgba(77, 83, 96, {$opacity})";
				break;
			case 3:
				return "rgba(212, 204, 197, {$opacity})";
				break;
			case 4:
				return "rgba(226, 234, 233, {$opacity})";
				break;
			case 5:
				return "rgba(247, 70, 74, {$opacity})";
				break;
			default:
				return "rgba(226, 234, 233, {$opacity})";
				break;
		}
	}

	public static function line( $data = array() ) {

		// Setup our dataset data
		foreach ( $data['datasets'] as $key => $dataset ) {
			$datasets[] = '{
				fillColor : "' . self::get_color( $key, 0.5 ) . '",
				strokeColor : "' . self::get_color( $key, 1 ) . '",
				pointColor : "' . self::get_color( $key, 1 ) . '",
				pointStrokeColor : "rgba(255, 255, 255, 1)",
				data : ' . json_encode( array_values( $dataset ) ) . '
			}';
		}

		// Determine the max value across all datasets
		foreach ( $data['datasets'] as $dataset ) {
			$max_values[] = max( $dataset );
		}
		$max_value = max( $max_values );

		// Concatenate our output
		$output = '<canvas id="lineChart" width="960" height="300" style="display:block; clear:both;"></canvas>';
		$output .= '
		<script>
			jQuery(document).ready(function($) {
				// Setup Data
				var data = {
					labels : ' . json_encode( $data['labels'] ) . ',
					datasets : [
						' . implode( ",\n", $datasets ) . '
					]
				}

				// Setup options
				var options = {
					scaleOverride : true,
					scaleSteps : 1,
					scaleStepWidth : ' . absint( $max_value ) . ',
					scaleStartValue : 0
				}

				// Render our chart
				var ctx = document.getElementById("lineChart").getContext("2d");
				var lineChart = new Chart(ctx).Line(data,options);
				lineChart.reDraw();
			});
		</script>
		';

		// Return our filterable output
		return apply_filters( 'badgeos_report_chart_line', $output, $data );
	}

	public static function bar( $data = array() ) {

	}

	public static function pie( $data = array() ) {

	}

	public static function polar( $data = array() ) {

	}

	public static function radar( $data = array() ) {

	}

	public static function doughnut( $data = array() ) {

	}

}
