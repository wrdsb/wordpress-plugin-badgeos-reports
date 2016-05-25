<?php
/**
 * CSV Export Support Functions
 *
 * @package BadgeOS Reports
 * @subpackage Reporting
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Abstract base class for exporting a BadgeOS report to CSV
 *
 * @since 1.0.0
 * @subpackage Classes
 */
class BadgeOS_Report_CSV {

	/**
	 * WP Uploads Directory information
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	private static $uploads;

	/**
	 * Reports file path
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	private static $path;

	/**
	 * Reports file URL
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	private static $url;

	/**
	 * Setup our variables
	 */
	function init() {

		// Setup our file and path data
		self::$uploads  = wp_upload_dir();
		self::$path     = trailingslashit( self::$uploads['basedir'] ) . 'reports';
		self::$url      = trailingslashit( self::$uploads['baseurl'] ) . 'reports';

		// If our reports directory doesn't exist, create it
		if ( ! file_exists( self::$path ) ) {
		    mkdir(self::$path, 0755, true);
		}

	}

	/**
	 * Attach column names to queried data
	 *
	 * @since  1.0.0
	 * @param  array $data    Our data array
	 * @param  array $columns The column headings array
	 * @return array          An array prefixed with column headings
	 */
	public static function attach_column_heads( $data = array(), $columns = array() ) {
		self::init();

		// Sanity check to make sure we have data
		if ( empty( $data ) || empty( $columns ) )
			return false;

		// Drop our restricted columns
		$columns = self::drop_restricted_columns( $columns );

		// Grab just the title portion of our columns array
		$column_heads = wp_list_pluck( $columns, 'title' );

		// Add our array key to the front of the array
		$data = array_merge( array( $column_heads ), array_values( $data ) );

		// Send back our data array
		return $data;
	}

	/**
	 * Flatten an array into a standard CSV format
	 *
	 * @since  1.0.0
	 * @param  array  $data    The data to flatten
	 * @param  array  $columns The column headings array
	 * @return string          CSV-formatted data
	 */
	public static function build_csv( $data = array(), $columns = array() ) {
		self::init();

		// Setup our empty records array
		$records = array();

		// Sanity check to make sure we have data
		if ( empty( $data ) || empty( $columns ) )
			return false;

		// Drop our restricted columns
		$data = self::drop_restricted_data( $data, $columns );

		// Attach our column heads to the data
		$data = self::attach_column_heads( $data, $columns );

		// Loop through each queried record and flatten its array
		foreach ( $data as $record ) {
			$records[] = implode( ',', array_values( $record ) );
		}

		// Flatten the top-level array
		$csv = implode( "\n", $records );

		// Send back our flattened CSV
		return $csv;
	}

	/**
	 * Drop columns that are explicitly set to NOT appear in CSV
	 *
	 * @since  1.0.0
	 * @param  array $columns Our column info array
	 * @return array          The sanitized column array
	 */
	public static function drop_restricted_columns( $columns = array() ) {

		// Sanity check to make sure we have data
		if ( empty( $columns ) )
			return $columns;

		// Drop any columns that should not be in the CSV
		foreach ( $columns as $key => $column )
			if ( false === $column['show_in_csv'] )
				unset( $columns[$key] );

		// Return our sanitized data
		return $columns;
	}

	/**
	 * Drop data that is explicitly set to NOT appear in CSV
	 *
	 * @since  1.0.0
	 * @param  array  $data    Our data array
	 * @param  array  $columns Our column info array
	 * @return array           The sanitized data array
	 */
	public static function drop_restricted_data( $data = array(), $columns = array() ) {

		// Sanity check to make sure we have data
		if ( empty( $data ) || empty( $columns ) )
			return $data;

		// Drop any columns that should not be in the CSV
		foreach ( $data as $row => $row_data )
			foreach ( $row_data as $key => $record )
				if ( false === $columns[$key]['show_in_csv'] )
					unset( $data[$row][$key] );

		// Return our sanitized data
		return $data;
	}

	/**
	 * Write data to file
	 *
	 * @since  1.0.0
	 * @param  array  $data     The data to write
	 * @param  array  $columns  The column headings array
	 * @param  string $filename The name of the file to write
	 * @return mixed            false on empty data, otherwise filename string
	 */
	public static function save_csv( $data = array(), $columns = array(), $filename = 'badgeos-report' ) {
		self::init();

		// Setup our file
		$file = trailingslashit( self::$path ) . $filename . '.csv';

		// Sanity check to make sure we have data
		if ( empty( $data ) || empty( $columns ) )
			return false;

		// Write our file
		$temp = fopen( $file, 'w' );
		if ( $temp ) {
			fwrite( $temp, self::build_csv( $data, $columns ) );
		}
		fclose( $temp );

		return $file;
	}

	/**
	 * Get URL for saved CSV file path
	 *
	 * @since  1.0.0
	 * @param  string $filename The name of the file to retrieve
	 * @return string           The full path of our saved CSV file, or null if none
	 */
	public static function get_csv_file_path( $filename = 'badgeos-report' ) {
		self::init();

		// Setup our file path
		$file = trailingslashit( self::$path ) . $filename . '.csv';

		// Next, confirm file exists, and return it if so
		if ( file_exists( $file ) )
			return $file;
		else
			return null;
	}

	/**
	 * Get URL for saved CSV file url
	 *
	 * @since  1.0.0
	 * @param  string $filename The name of the file to retrieve
	 * @return string           The full URL of our saved CSV file, or null if none
	 */
	public static function get_csv_file_url( $filename = 'badgeos-report' ) {
		self::init();

		// Setup our file path
		$file = trailingslashit( self::$url ) . $filename . '.csv';

		// Next, confirm file exists, and return it if so
		if ( file_exists( self::get_csv_file_path( $filename ) ) )
			return $file;
		else
			return null;
	}

	/**
	 * Set headers and output CSV file force download
	 *
	 * @since  1.0.0
	 * @param  string $filename The name of the file to serve
	 * @return void
	 */
	public static function serve_csv( $filename = 'badgeos-report' ) {

		// Setup headers
		header("Content-Description: File Transfer");
		header("Content-Type: application/csv") ;
		header("Content-Disposition: attachment; filename={$filename}.csv");
		header("Expires: 0");

		// Output CSV content
		readfile( self::get_csv_file_path( $filename ) );

		// Stop running everything
		die();
	}

}
