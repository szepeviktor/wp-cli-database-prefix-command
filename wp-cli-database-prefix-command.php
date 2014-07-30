<?php

use \WP_CLI\Utils;

/**
 * Perform operations on prefixed database tables.
 */
class WP_CLI_Database_prefix extends WP_CLI_Command {

	private function get_file_name( $args ) {
		if ( empty( $args ) )
			return sprintf( '%s.sql', DB_NAME );

		return $args[0];
	}

	private static function run( $cmd, $assoc_args = array(), $descriptors = null ) {
		$required = array(
			'host' => DB_HOST,
			'user' => DB_USER,
			'pass' => DB_PASSWORD,
		);

		if ( defined( 'DB_CHARSET' ) && constant( 'DB_CHARSET' ) ) {
			$required['default-character-set'] = constant( 'DB_CHARSET' );
		}

		$final_args = array_merge( $assoc_args, $required );

		Utils\run_mysql_command( $cmd, $final_args, $descriptors );
	}

	/**
	 * Exports only the prefixed database tables to a file or to STDOUT.
	 *
	 * ## OPTIONS
	 *
	 * [<file>]
	 * : The name of the SQL file to export. If '-', then outputs to STDOUT. If omitted, it will be '{dbname}.sql'.
	 *
	 * [--<field>=<value>]
	 * : Extra arguments to pass to mysqldump
	 *
	 * ## EXAMPLES
	 *
	 *     wp db-prefix export
	 *     wp db-prefix export --add-drop-table
	 */
	public function export( $args, $assoc_args ) {
		global $wpdb;

		$result_file = $this->get_file_name( $args );
		$stdout = ( '-' === $result_file );

		if ( ! $stdout ) {
			$assoc_args['result-file'] = $result_file;
		}

		$command = 'mysqldump --no-defaults %s';
		$command_esc_args = array( DB_NAME );

		$tables = $wpdb->get_col( "SHOW TABLES LIKE '$wpdb->prefix%';" );
		$command .= ' --tables';
		foreach ( $tables as $table ) {
			$command .= ' %s';
			$command_esc_args[] = $table;
		}

		$escaped_command = call_user_func_array( '\WP_CLI\Utils\esc_cmd', array_merge( array( $command ), $command_esc_args ) );

		self::run( $escaped_command, $assoc_args );

		if ( ! $stdout ) {
			WP_CLI::success( sprintf( 'Exported to %s', $result_file ) );
		}
	}

	/**
	 * Lists the prefixed database tables.
	 *
	 * ## EXAMPLES
	 *
	 *     wp db-prefix list
	 *
	 * @subcommand list
	 */
	public function _list( $args, $assoc_args ) {
		global $wpdb;

		$table_list = array();
		$tables = $wpdb->get_col( "SHOW TABLES LIKE '$wpdb->prefix%';" );
		foreach ( $tables as $table ) {
			$table_list[] = $table;
		}

		WP_CLI::line( implode( ',', $table_list ) );
	}

}

WP_CLI::add_command( 'db-prefix', 'WP_CLI_Database_prefix' );
