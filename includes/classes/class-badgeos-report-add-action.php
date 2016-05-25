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
 * Helper class for add_action_with_args()
 *
 * @since 1.0.0
 * @param array  $args     Arguments to pass through to a given callback
 * @param string $callback The callback to run
 */
class BadgeOS_Report_Add_Action_Helper {

	private $args;
	private $callback;

	function __construct( $args, $callback = null ) {
		$this->args = $args;
		$this->callback = $callback;
	}

	function action() {

		call_user_func_array( $this->callback, $this->args );

		if( func_num_args() )
			return func_get_arg(0);

		return null;
	}
}

/**
 * Hooks a function to a specific action, AND allows you to pass custom arguments to that function
 *
 * @since 1.0.0
 * @param string  $tag      The hook we're connecting to
 * @param string  $callback The function we're connecting
 * @param integer $priority The load priority for this function
 * @param null    $args     One or more arguments to pass to hooked function.
 */
function badgeos_reports_add_action_with_args( $tag, $callback, $priority = 10, $args = null ) {

	// Accept as many different args as we're passed
	$args = array_slice( func_get_args(), 3 );

	return add_action( $tag, array( new BadgeOS_Report_Add_Action_Helper( $args, $callback ), 'action' ), $priority, 1 );
}