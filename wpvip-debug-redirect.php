<?php
/**
 * Plugin Name: WPVIP Debug Redirect
 * Description: Stops redirects and adds debugging info to find out which file/function triggered a redirect.
 * Author:      Chris Budd, Alexis Kulash, Automattic
 * Version:     1.0.0
 *
 * ----------------------------------------------------------------------------
 */

class WPVIP_Debug_Redirect {

	/**
	 * Returns the singleton instance.
	 *
	 * @since  1.0.0
	 * @return WPVIP_Debug_Redirect
	 */
	static public function instance() {
		static $Inst = null;

		if ( null === $Inst ) {
			$Inst = new WPVIP_Debug_Redirect();
		}

		return $Inst;
	}

	/**
	 * Constructor.
	 * Hooks up this module.
	 *
	 * @since  1.0.0
	 */
	protected function __construct() {
		add_filter(
			'wp_redirect',
			array( $this, 'redirect_debug' ),
			9999
		);
	}

	/**
	 * Stop the request and output redirect debugging info.
	 *
	 * @since  1.0.0
	 */
	public function redirect_debug( $location ) {

		// Only fire on front end
		if ( ! is_admin() ) {
			$full_trace = $this->get_trace();
			$output = '<h2 style="text-align: center;">Redirect</h2>';
			$output .= '<p style="font-weight: bold; text-align: center; margin-bottom: 40px;">' .$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '<span style="font-weight: normal;"> ---> </span>' . $location . '</p>';
			$output .= '<code>';
			$output .= '<div style="text-align: center; margin-bottom: 40px;"><a class="button button-primary" href="' . $location . '">Continue</a></div>';
			foreach ($full_trace as $trace) {
				$output .= '<p style="margin-bottom: 8px; margin-top: 8px;">' . $trace . '</p>';
			}
			$output .= "</code>";
			wp_die($output);
		}

		return $location;
	}

	/**
	 * Generates an array of stack-trace information. Each array item is a
	 * simple string that can be directly output.
	 *
	 * @since  1.0.0
	 * @return array Trace information
	 */
	public function get_trace() {
		$result = array();

		$trace = debug_backtrace();
		$trace_count = count( $trace );
		$_num = 0;
		$start_at = 0;

		// Skip the first 4 trace lines (filter call inside wp_redirect)
		if ( $trace_count > 4 ) { $start_at = 4; }

		for ( $i = $start_at; $i < $trace_count; $i += 1 ) {
			$trace_info = $trace[$i];
			$line_info = $trace_info;
			$j = $i;

			while ( empty( $line_info['line'] ) && $j < $trace_count ) {
				$line_info = $trace[$j];
				$j += 1;
			}

			$_file = empty( $line_info['file'] ) ? '' : $line_info['file'];
			$_line = empty( $line_info['line'] ) ? '' : $line_info['line'];
			$_args = empty( $trace_info['args'] ) ? array() : $trace_info['args'];
			$_class = empty( $trace_info['class'] ) ? '' : $trace_info['class'];
			$_type = empty( $trace_info['type'] ) ? '' : $trace_info['type'];
			$_function = empty( $trace_info['function'] ) ? '' : $trace_info['function'];

			$_num += 1;
			$_arg_string = '';
			$_args_arr = array();

			if ( $i > 0 && is_array( $_args ) && count( $_args ) ) {
				foreach ( $_args as $arg ) {
					if ( is_scalar( $arg ) ) {
						if ( is_bool( $arg ) ) {
							$_args_arr[] = ( $arg ? 'true' : 'false' );
						} elseif ( is_string( $arg ) ) {
							$_args_arr[] = '"' . $arg . '"';
						} else {
							$_args_arr[] = $arg;
						}
					} elseif ( is_array( $arg ) ) {
						$_args_arr[] = '[Array]';
					} elseif ( is_object( $arg ) ) {
						$_args_arr[] = '[' . get_class( $arg ) . ']';
					} elseif ( is_null( $arg ) ) {
						$_args_arr[] = 'NULL';
					} else {
						$_args_arr[] = '[?]';
					}
				}

				$_arg_string = implode( ',', $_args_arr );
			}

			$_file = str_replace("/var/www", "", $_file);

			if ( strlen( $_file ) > 80 ) {
				$_file = '...' . substr( $_file, -77 );
			}


			$result_item = sprintf(
				'<span style="font-style: italic; opacity: 0.7;">%s:%s</span> %s(%s)',
				$_file,
				$_line,
				$_class . $_type . $_function,
				$_arg_string
			);

			$_num_str = str_pad( $_num, 2, '0', STR_PAD_LEFT );
			$result[$_num_str] = $result_item;
		}

		return $result;
	}

}
WPVIP_Debug_Redirect::instance();
