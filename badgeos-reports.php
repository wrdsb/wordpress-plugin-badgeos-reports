<?php
/**
 * Plugin Name: BadgeOS Reports Add-On
 * Plugin URI: http://www.learningtimes.com/
 * Description: This BadgeOS add-on adds a reporting interface and menu.
 * Author: Credly
 * Version: 1.0.1
 * Author URI: https://credly.com/
 * License: GNU AGPLv3
 * License URI: http://www.gnu.org/licenses/agpl-3.0.html
 */

/*
 * Copyright © 2012-2013 Credly, LLC
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Affero General
 * Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>;.
*/

/**
 * Our main plugin instantiation class
 *
 * @since 1.0.0
 */
class BadgeOS_Reports_Addon {

	/**
	 * Get everything running.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugins_url( dirname( $this->basename ) );

		// Load translations
		load_plugin_textdomain( 'badgeos-reports', false, dirname( $this->basename ) . '/languages' );

		// Run our activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_activation_hook( __FILE__, array( $this, 'deactivate' ) );

		// If BadgeOS is unavailable, deactivate our plugin
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );

		// Hook in our dependent files and methods
		add_action( 'init', array( $this, 'updates' ) );
		add_action( 'init', array( $this, 'includes' ), 0 );
		add_action( 'init', array( $this, 'register_scripts_and_styles' ), 1 );
		add_action( 'admin_menu', array( $this, 'report_menu' ) );
		add_action( 'badgeos_settings', array( $this, 'report_settings' ) );
		add_action( 'admin_init', array( $this, 'set_minimum_role' ) );

	} /* __construct() */

	/**
	 * Register our add-on for automatic updates
	 *
	 * @since  1.0.0
	 */
	public function updates() {
		if ( class_exists( 'BadgeOS_Plugin_Updater' ) ) {
			$badgeos_updater = new BadgeOS_Plugin_Updater( array(
					'plugin_file' => __FILE__,
					'item_name'   => 'Reports',
					'author'      => 'Credly',
					'version'     => '1.0.0',
				)
			);
		}
	}

	/**
	 * Include our file dependencies
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		// If BadgeOS is available...
		if ( $this->meets_requirements() ) {
			require_once( $this->directory_path . '/includes/classes/class-badgeos-report.php' );
			require_once( $this->directory_path . '/includes/classes/class-badgeos-report-csv.php' );
			require_once( $this->directory_path . '/includes/classes/class-badgeos-report-chart.php' );
			require_once( $this->directory_path . '/includes/classes/class-badgeos-report-add-action.php' );
			require_once( $this->directory_path . '/includes/reports/achievement-reports.php' );
			require_once( $this->directory_path . '/includes/reports/earnings-report.php' );
			require_once( $this->directory_path . '/includes/reports/user-report.php' );
		}

	} /* includes() */

	/**
	 * Register our included scripts and stles
	 *
	 * @since 1.0.0
	 */
	public function register_scripts_and_styles() {

		wp_register_script( 'dataTables', $this->directory_url . '/includes/js/jquery.dataTables.min.js', array( 'jquery' ), '1.9.4', true );
		wp_register_script( 'chartjs', $this->directory_url . '/includes/js/Chart.min.js', null, '0.2', false );
		wp_register_script( 'badgeos-reports', $this->directory_url . '/includes/js/badgeos-reports.js', array( 'jquery', 'dataTables' ), '1.0.0', true );

		wp_register_style( 'dataTables', $this->directory_url . '/includes/css/jquery.dataTables.css', null, '1.9.4' );
		wp_register_style( 'badgeos-reports', $this->directory_url . '/includes/css/badgeos-reports.css', null, '1.0.0' );


	} /* includes() */

	/**
	 * Create BadgeOS Reports menu
	 *
	 * @since 1.0.0
	 */
	function report_menu() {

		// Get our BadgeOS Settings
		$badgeos_settings = get_option( 'badgeos_settings' );

		// Get minimum role from settings
		$minimum_role = $badgeos_settings['reports_minimum_role'];

		// Create main report menu
		add_menu_page( 'BadgeOS Reports', 'BadgeOS Reports', $minimum_role, 'badgeos_reports', array( $this, 'reports_admin_page' ), $GLOBALS['badgeos']->directory_url . 'images/badgeos_icon.png', 111 );

	} /* report_menu() */

	function reports_admin_page() {

		// Page Header
		echo '<div class="wrap" >';
			echo '<div id="icon-options-general" class="icon32"></div>';
			echo '<h2>' . __( 'BadgeOS Reports', 'badgeos-reports' ) . '</h2>';
		echo '</div>';

		// List all registered reports
		global $submenu;
		if ( is_array( $submenu ) && isset( $submenu['badgeos_reports'] ) ) {
			echo '<h3>' . __( 'Registered Reports', 'badgeos-reports' ) . '</h3>';
			echo '<ul>';
			foreach ( (array) $submenu['badgeos_reports'] as $key => $item ) {
				if ( 0 !== $key )
					// 0 = title, 2 = slug
					echo '<li><a href="' . admin_url( "admin.php?page={$item[2]}" ) . '">' . $item[0] . '</a></li>';
			}
			echo '</ul>';
		}

		// Available action for extensions
		do_action( 'badgeos_report_page' );
	}

	/**
	 * Adds additional options to the BadgeOS Settings page
	 *
	 * @since 1.0.0
	 */
	public function report_settings( $settings ) {
		// Get minimum role from settings
		$reports_minimum_role = $settings['reports_minimum_role'];
	?>
		<tr><td colspan="2"><hr/><h2><?php _e( 'BadgeOS Reports Settings', 'badgeos-reports' ); ?></h2></td></tr>
		<tr valign="top"><th scope="row"><label for="reports_minimum_role"><?php _e( 'Minimum Role to access BadgeOS Reports: ', 'badgeos-reports' ); ?></label></th>
			<td>
				<select id="reports_minimum_role" name="badgeos_settings[reports_minimum_role]">
					<option value="manage_options" <?php selected( $reports_minimum_role, 'manage_options' ); ?>><?php _e( 'Administrator', 'badgeos-reports' ); ?></option>
					<option value="delete_others_posts" <?php selected( $reports_minimum_role, 'delete_others_posts' ); ?>><?php _e( 'Editor', 'badgeos-reports' ); ?></option>
					<option value="publish_posts" <?php selected( $reports_minimum_role, 'publish_posts' ); ?>><?php _e( 'Author', 'badgeos-reports' ); ?></option>
					<option value="edit_posts" <?php selected( $reports_minimum_role, 'edit_posts' ); ?>><?php _e( 'Contributor', 'badgeos-reports' ); ?></option>
					<option value="read" <?php selected( $reports_minimum_role, 'read' ); ?>><?php _e( 'Subscriber', 'badgeos-reports' ); ?></option>
				</select>
			</td>
		</tr>
	<?php
	} /* report_settings() */

	/**
	 * Set minimum role for accessing report pages.
	 *
	 * @since 1.0.1
	 */
	public function set_minimum_role() {
		$badgeos_settings = get_option( 'badgeos_settings' );
		if ( empty( $badgeos_settings['reports_minimum_role'] ) ) {
			$badgeos_settings['reports_minimum_role'] = $badgeos_settings['minimum_role'];
			update_option( 'badgeos_settings', $badgeos_settings );
		}
	} /* set_minimum_role() */

	/**
	 * Activation hook for the plugin.
	 *
	 * @since 1.0.1
	 */
	public function activate() {

		// Do some activation things

	} /* activate() */

	/**
	 * Deactivation hook for the plugin.
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {

		// Do some deactivation things. Note: this plugin may
		// auto-deactivate due to $this->maybe_disable_plugin()

	} /* deactivate() */

	/**
	 * Check if BadgeOS is available
	 *
	 * @since  1.0.0
	 * @return bool True if BadgeOS is available, false otherwise
	 */
	public static function meets_requirements() {

		if ( class_exists('BadgeOS') )
			return true;
		else
			return false;

	} /* meets_requirements() */

	/**
	 * Potentially output a custom error message and deactivate
	 * this plugin, if we don't meet requriements.
	 *
	 * This fires on admin_notices.
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {

		if ( ! $this->meets_requirements() ) {
			// Display our error
			echo '<div id="message" class="error">';
			echo '<p>' . sprintf( __( 'BadgeOS Reports Add-On requires BadgeOS and has been <a href="%s">deactivated</a>. Please install and activate BadgeOS and then reactivate this plugin.', 'badgeos-addon' ), admin_url( 'plugins.php' ) ) . '</p>';
			echo '</div>';

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}

	} /* maybe_disable_plugin() */

} /* BadgeOS_Reports_Addon */

// Instantiate our class to a global variable that we can access elsewhere
$GLOBALS['badgeos_reports_addon'] = new BadgeOS_Reports_Addon();
