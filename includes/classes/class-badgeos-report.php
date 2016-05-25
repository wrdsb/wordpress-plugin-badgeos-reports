<?php
/**
 * Reporting Class Support Functions
 *
 * @package BadgeOS Reports
 * @subpackage Reporting
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Abstract base class for creating a BadgeOS report
 *
 * This class is extended by subclasses that define
 * specific types of admin reports.
 *
 * @since 1.0.0
 * @subpackage Classes
 */
class BadgeOS_Report {

	/**
	 * Name of this report
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $title;

	/**
	 * Unique slug for this report
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $slug;

	/**
	 * Unique slug for this report
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $show_in_menu = true;

	/**
	 * The query data to use in this report
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	public $data;

	/**
	 * The columns that explain our queried data
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	public $columns;

	/**
	 * The start date for our query data
	 *
	 * @since 1.0.0
	 * @var   integer
	 */
	static $start_date;

	/**
	 * The end date for our query data
	 *
	 * @since 1.0.0
	 * @var   integer
	 */
	static $end_date;

	/**
	 * The base name to use for report files
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $base_filename = 'badgeos-report';

	/**
	 * Initial setup function
	 *
	 * Child classes that extend this should either call
	 * parent::__construct() or else pull just the bits
	 * from this that they need to fuction properly.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Register report menu item
		add_action( 'admin_menu', array( $this, '__register_report' ) );

		// Setup default times
		self::$start_date = isset( $_POST['start_date'] ) ? strtotime( $_POST['start_date'] ) : strtotime( 'January 1, 2013');
		self::$end_date = isset( $_POST['end_date'] ) ? strtotime( $_POST['end_date'] ) : time();
	}

	/**
	 * Setup and return relevant query data.
	 *
	 * Use $this->set_columns() to tell the class what this
	 * query actually contains and how to interpret the data.
	 *
	 * This function should be overridden by the child class.
	 *
	 * @since  1.0.0
	 * @return array The query results
	 */
	public function get_data() {

		// Return query data
		return apply_filters( 'badgeos_reports_get_data', $this->data, $this->slug, $this );
	}

	/**
	 * Setup the column data for our tables, CSVs and charts
	 *
	 * This function should setup an array that explains the
	 * data pulled back via $this->get_data() for use elsewhere.
	 *
	 * This function should be overridden by the child class.
	 *
	 * @since  1.0.0
	 * @return array Column array, including ID, title, data-type, data-field
	 */
	public function get_columns() {

		// Retur our column data
		return apply_filters( 'badgeos_reports_get_columns', $this->columns, $this->slug, $this );
	}

	/**
	 * Set the start date for data queried
	 *
	 * @since 1.0.0
	 * @param integer $start_date UNIX timestamp for the query start date
	 */
	public function set_start_date( $start_date = 0 ) {
		self::$start_date = absint( $start_date );
	}

	/**
	 * Return sanatized start date for data queried
	 *
	 * @since  1.0.0
	 * @return integer UNIX timestamp for the query start date
	 */
	public function get_start_date() {
		return absint( self::$start_date ) ? absint( self::$start_date ) : strtotime( 'January 1, 2013' );
	}

	/**
	 * Set the end date for data queried
	 *
	 * @since 1.0.0
	 * @param integer $end_date   UNIX timestamp for the query end date
	 */
	public function set_end_date( $end_date = 0 ) {
		// If end date is empty, set it to the current time
		self::$end_date = absint( $end_date ) ? absint( $end_date ) : time();
	}

	/**
	 * Return sanatized end date for data queried
	 *
	 * @since  1.0.0
	 * @return integer UNIX timestamp for the query end date
	 */
	public function get_end_date() {
		// If end date is empty, return the current time
		return absint( self::$end_date ) ? absint( self::$end_date ) : time();
	}

	/**
	 * Set the start and end date for data queried
	 *
	 * @since 1.0.0
	 * @param integer $start_date UNIX timestamp for the query start date
	 * @param integer $end_date   UNIX timestamp for the query end date
	 */
	public function set_date_range( $start_date = 0, $end_date = 0 ) {
		$this->set_start_date( $start_date );
		$this->set_end_date( $end_date );
	}

	/**
	 * Build output for a single point of data
	 *
	 * @since  1.0.0
	 * @param  string $title     The title for this data point
	 * @param  string $data      The data to render
	 * @param  string $data_type The type of data being rendered
	 * @return string            The concatenated markup
	 */
	public function render_data_point( $title = '', $data = '', $data_type = '' ) {
		$output = '';

		// Concatenate our output
		$output .= '<div class="metabox-holder">';
		$output .= '<div class="postbox">';
			$output .= '<h3 class="hndle"><span>' . $title . '</span></h3>';
			$output .= '<div class="inside">';
				$output .= '<p class="stat">';
				$output .= $this->__get_formatted_data_point( $data, $data_type );
				$output .= '</p>';
			$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';

		return apply_filters( 'badgeos_reports_render_data_point', $output, $title, $data, $this );
	}

	/**
	 * Helper function to build chart.js chart
	 *
	 * @since  1.0.0
	 * @param  string $type    The type of chart to render (accepts: line, bar, radar, pie, polar, doughnut)
	 * @param  array  $data    The data to render in the chart
	 * @param  array  $columns The column data for chart setup
	 * @return string          Concatenated output for our chart
	 */
	public function render_chart( $type = '', $data = array(), $columns = array() ) {
		wp_enqueue_script( 'chartjs' );

		$output = BadgeOS_Report_Chart::$type( $data, $columns );

		return apply_filters( 'badgeos_reports_render_chart', $output, $data, $columns, $this );
	}

	/**
	 * Build the table to output on the report page
	 *
	 * @since  1.0.0
	 * @param  array  $data    The query data to use for the table
	 * @param  array  $columns The column data to use for table setup
	 * @return string          The concatenated table markup
	 */
	public function render_table( $data = array(), $columns = array() ) {

		wp_enqueue_style( 'dataTables' );
		wp_enqueue_script( 'dataTables' );
		wp_enqueue_script( 'badgeos-reports' );

		$output = '';

		// Use default data if none specified
		if ( empty( $data ) )
			$data = $this->get_data();

		// Use default columns if none specified
		if ( empty( $columns ) )
			$columns = $this->get_columns();

		// Setup our table output
		$output .= '<table class="badgeos-report-table badgeos-report-' . $this->slug . '-table">';

		// Output column heads
		$output .= '<thead>';
			foreach ( $columns as $slug => $column_data ) {
				if ( true == $column_data['show_in_table'] )
					$output .= '<th class="' . $slug . '">' . $column_data['title'] . '</th>';
			}
		$output .= '</thead>';

		// Output each row
		$output .= '<tbody>';
			foreach ( $data as $row ) {
				$output .= '<tr>';
					foreach ( $row as $key => $row_data ) {
						if ( true == $columns[$key]['show_in_table'] )
							$output .= '<td class="' . $key . '"><span data-type="' . $columns[$key]['data-type'] . '" data-value=" ' . $this->__get_table_cell_data_value( $row_data, $columns[$key]['data-type'] ) . ' ">' . $this->__get_table_cell_output_value( $row_data, $columns[$key]['data-type'], $row ) . '</span></td>';
					}
				$output .= '</tr>';
			}
		$output .= '</tbody>';

		$output .= '</table>';

		$output .= $this->render_csv_link( $data, $columns );

		return apply_filters( 'badgeos_reports_render_table', $output, $data, $columns, $this );
	}

	/**
	 * Render a link to a CSV of the report data
	 *
	 * @since  1.0.0
	 * @param  array  $data    The data to use for the CSV
	 * @param  array  $columns The columns to use for the head of the CSV
	 * @return string          The formatted link markup
	 */
	public function render_csv_link( $data = array(), $columns = array() ) {
		$output = '';

		$output .= '<p class="csv-link"><a href="' . $this->__get_csv_link( $data, $columns ) . '" class="button-primary">Download Table as CSV</a></p>';

		return apply_filters( 'badgeos_reports_render_csv_link', $output, $this );
	}

	/**
	 * Render the report date picker
	 *
	 * @since  1.0.0
	 * @return string The concatenated date form markup
	 */
	public function render_date_picker() {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );

		$output = '<p>';
		$output .= sprintf(
			__( 'Limit results from: %1$s to: %2$s', 'badgeos-reports' ),
			'<input type="text" id="start_date" name="start_date" class="datepicker" value="' . date( 'F j, Y', $this->get_start_date() ) . '" />',
			'<input type="text" id="end_date" name="end_date" class="datepicker" value="' . date( 'F j, Y', $this->get_end_date() ) . '" />'
		);
		$output .= '</p>';

		return apply_filters( 'badgeos_reports_render_date_picker', $output, $this );
	}

	public function render_filters() {
		$output = '';

		$output .= '<form method="POST" action="">';
		$output .= $this->render_date_picker();
		$output .= apply_filters( 'badgeos_reports_filters', '', $this );
		$output .= '<p><input type="submit" value="' . __( 'Update Results', 'badgeos-reports' ) . '" class="button-primary" /></p>';
		$output .= '</form>';

		return apply_filters( 'badgeos_reports_render_filters', $output, $this );
	}

	/**
	 * Build the report admin page
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_page() {
		wp_enqueue_style( 'badgeos-reports' );

		if ( ! $this->show_in_menu )
			add_action( 'admin_footer', array( $this, '__expand_reports_menu' ) );
	?>
		<div class="wrap" >
			<div id="icon-options-general" class="icon32"></div>
			<h2><?php echo $this->title; ?></h2>
		</div>
	<?php
		echo $this->render_filters();
		do_action( "badgeos_reports_render_page_{$this->slug}", $this );
	}

	/**
	 * Helper function for adding output to the rendered page
	 *
	 * @since  1.0.0
	 * @param  string  $output   Content to output
	 * @param  integer $priority Sort priority for output
	 * @return void
	 */
	public function add_to_page( $output = '', $priority = 10 ) {
		badgeos_reports_add_action_with_args( "badgeos_reports_render_page_{$this->slug}", array( $this, '__add_to_page' ), $priority, $output );
	}

	/**
	 * Utility function for rendering data sent via add_to_page
	 *
	 * @since  1.0.0
	 * @param  string $output Content to output
	 * @return void
	 */
	public function __add_to_page( $output = '' ) {
		echo $output;
	}

	/**
	 * Return a formatted single point of data
	 *
	 * @since  1.0.0
	 * @param  string $data      The data to render
	 * @param  string $data_type The type of data we're rendering
	 * @return string            The concatenated markup
	 */
	public function __get_formatted_data_point( $data = '', $data_type = '' ) {

		switch ( $data_type ) {
			case 'string' :
				$output = $data;
				break;
			case 'percentage' :
				$output = number_format( $data, 2 ) . '%';
				break;
			case 'float' :
				$output = number_format( $data, 2 );
				break;
			case 'integer' :
			default :
				$output = number_format( $data, 0 );
				break;
		}

		return apply_filters( 'badgeos_reports__get_formatted_data_point', $output, $data, $data_type, $this );
	}

	/**
	 * Return sanitized cell data-value attribute, based on data type
	 *
	 * @since  1.0.0
	 * @param  mixed  $data      The raw data
	 * @param  string $data_type The type of data (e.g. integer, date, etc)
	 * @return string            Sanitized data for sorting
	 */
	public function __get_table_cell_data_value( $data = '', $data_type = '' ) {

		switch ( $data_type ) {
			case 'integer':
			case 'earnings':
				$data_value = absint( $data );
				break;
			case 'date':
			    if ( empty( $data ) )
			    	$data_value = 0;
			    else
					$data_value = strtotime( $data );
				break;
			case 'string':
			case 'post_title':
			case 'user_login':
				$data_value = substr( $data, 0, 20 );
				break;
			default:
				$data_value = $data;
				break;
		}

		return apply_filters( 'badgeos_reports__get_table_cell_data_value', $data_value, $data, $data_type, $this );

	}

	/**
	 * Return sanitized cell output, based on data type
	 *
	 * @since  1.0.0
	 * @param  mixed  $data      The raw data
	 * @param  string $data_type The type of data (e.g. integer, date, etc)
	 * @param  array  $row       The additional row data (ideally containing post ID)
	 * @return string            Sanitized data for sorting
	 */
	public function __get_table_cell_output_value( $data = '', $data_type = '', $row = array() ) {

		switch ( $data_type ) {
			case 'integer':
				$output_value = absint( $data );
				break;
			case 'date':
				if ( empty( $data ) )
					$output_value = 'N/A';
				else
					$output_value = date( 'm-d-Y', strtotime( $data ) );
				break;
			case 'string':
				$output_value = sanitize_text_field( $data );
				break;
			case 'post_title':
				$output_value = '<a href="' . admin_url( 'post.php?post=' . absint( $row['ID'] ) . '&action=edit' ) . '">' . esc_html( $data ) . '</a>';
				break;
			case 'user_login':
				$output_value = '<a href="' . admin_url( 'user-edit.php?user_id=' . absint( $row['ID'] ) . '&action=edit' ) . '">' . esc_html( $data ) . '</a>';
				break;
			case 'earnings':
				if ( $data )
					$output_value = '<a href="' . admin_url( 'admin.php?page=earnings-report&achievement_id=' . absint( $row['ID'] ) ) . '">' . esc_html( $data ) . '</a>';
				else
					$output_value = $data;
				break;
			default:
				$output_value = $data;
				break;
		}

		return apply_filters( 'badgeos_reports__get_table_cell_output_value', $output_value, $data, $data_type, $row, $this );

	}

	/**
	 * Utility function to return date range formatted for a DB query
	 *
	 * @since  1.0.0
	 * @param  string $table_name The cast name for the post table in the query
	 * @return string             Formatted query arg for limiting query to date range
	 */
	public function __get_query_date_range( $table_name = 'post' ) {
		global $wpdb;

		// Assume we have no start nor end date
		$where = '';

		// Sanitize our table name
		$table_name = sanitize_text_field( $table_name );

		// If we have a start date, only include posts created on or after that date
		if ( $this->get_start_date() )
			$where .= $wpdb->prepare( " AND $table_name.post_date >= %s", date( 'Y-m-d', $this->get_start_date() ) );

		// If we have an end date, only include posts created before that date
		if ( $this->get_end_date() )
			$where .= $wpdb->prepare( " AND $table_name.post_date < %s", date( 'Y-m-d', $this->get_end_date() + DAY_IN_SECONDS ) );

		// Return our complete filter
		return apply_filters( 'bageos_reports_get_query_date_range', $where, $this->get_start_date(), $this->get_end_date() );

	}

	/**
	 * Utility function to return date range formatted for a file name
	 *
	 * @since  1.0.0
	 * @return string Formatted date range
	 */
	public function __get_file_date_range() {
		// Assume we have no start nor end date
		$range = '';

		// If we have either a start OR an end date, add a separator
		if ( $this->get_start_date() || $this->get_end_date() )
			$range .= '-';

		// Include our start date (if set)
		if ( $this->get_start_date() )
			$range .= date( 'Y_m_d', $this->get_start_date() );

		// If our start and end dates are the same, we're done
		if ( $this->get_start_date() == $this->get_end_date() )
			return $range;

		// If we have both a start and an end date, add a separator
		if ( $this->get_start_date() && $this->get_end_date() )
			$range .= '-';

		// Include our end date (todays date if not explicitly set)
		if ( $this->get_end_date() )
			$range .= date( 'Y_m_d', $this->get_end_date() );

		// Return our complete filter
		return apply_filters( 'badgeos_reports_get_file_date_range', $range, $this->get_start_date(), $this->get_end_date(), $this );
	}

	/**
	 * Utility function for building a CSV filename
	 *
	 * @since  1.0.0
	 * @return string A formatted filename (e.g. badgeos-report-slug-2013_03_22-2013_04_22)
	 */
	public function __get_csv_filename() {
		$filename = $this->base_filename . '-' . $this->slug . $this->__get_file_date_range();
		return apply_filters( 'badgeos_reports_get_csv_filename', $filename, $this->slug, $this );
	}

	/**
	 * Utility function to convert data array to CSV format
	 *
	 * @since  1.0.0
	 * @param  array  $data     The data to use for the CSV
	 * @param  array  $columns  The columns to use for the head of the CSV
	 * @param  string $filename The name to give the generated file
	 * @return void
	 */
	public function __convert_data_to_csv( $data = array(), $columns = array(), $filename = '' ) {

		// Use default data if none specified
		if ( empty( $data ) )
			$data = $this->data;

		// Use default columns if none specified
		if ( empty( $columns ) )
			$columns = $this->columns;

		// Use default filename if none specified
		if ( empty( $filename ) )
			$filename = $this->__get_csv_filename();

		// Build the CSV
		BadgeOS_Report_CSV::save_csv( $data, $columns, $filename );
	}

	/**
	 * Utility function to build link for CSV formatted file
	 *
	 * @since  1.0.0
	 * @param  array  $data     The data to use for the CSV
	 * @param  array  $columns  The columns to use for the head of the CSV
	 * @param  string $filename The name to give the generated file
	 * @return string           The formatted link markup
	 */
	public function __get_csv_link( $data = array(), $columns = array(), $filename = '' ) {

		// Use default filename if none specified
		if ( empty( $filename ) )
			$filename = $this->__get_csv_filename();

		// Setup our file
		$this->__convert_data_to_csv( $data, $columns, $filename );

		// Grab the file url
		$link = BadgeOS_Report_CSV::get_csv_file_url( $filename );

		return apply_filters( 'badgeos_reports__get_csv_link', $link, $data, $columns, $filename, $this );
	}

	/**
	 * Utility function to register the report
	 *
	 * @since 1.0.0
	 */
	public function __register_report() {
		$badgeos_settings = get_option( 'badgeos_settings' );
		$parent = $this->show_in_menu ? 'badgeos_reports' : null;
		add_submenu_page( $parent, $this->title, $this->title, $badgeos_settings['reports_minimum_role'], $this->slug, array( $this, 'render_page' ) );
	}

	/**
	 * Utility function to expand the BadgeOS Reports menu
	 *
	 * This should only be added to admin_footer on pages that do not show in menu.
	 *
	 * @since 1.0.0
	 */
	public function __expand_reports_menu() {
		?>
		<script type="text/javascript">
		jQuery('#toplevel_page_badgeos_reports, #toplevel_page_badgeos_reports > a').attr( 'class', 'wp-has-submenu wp-has-current-submenu wp-menu-open menu-top toplevel_page_badgeos_reports menu-top-last');
		</script>
		<?php
	}

}
