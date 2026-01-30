<?php
/**
 * Database class.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Database;

/**
 * Handles database operations for payments.
 */
class Database {

	/**
	 * Table name for payments (without prefix).
	 */
	const TABLE_NAME = 'paythefly_payments';

	/**
	 * Cache group for payment data.
	 */
	const CACHE_GROUP = 'paythefly';

	/**
	 * Get the full table name with prefix.
	 *
	 * @return string
	 */
	private function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Create database tables.
	 *
	 * @return void
	 */
	public function create_tables(): void {
		global $wpdb;

		$table_name      = $this->get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			payment_id varchar(64) NOT NULL,
			amount decimal(18,8) NOT NULL,
			currency varchar(10) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			description text,
			metadata longtext,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY payment_id (payment_id),
			KEY status (status),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert a payment record.
	 *
	 * @param array<string, mixed> $data Payment data.
	 * @return int|false
	 */
	public function insert_payment( array $data ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table for plugin data.
		$result = $wpdb->insert(
			$this->get_table_name(),
			array(
				'payment_id'  => $data['payment_id'],
				'amount'      => $data['amount'],
				'currency'    => $data['currency'],
				'status'      => isset( $data['status'] ) ? $data['status'] : 'pending',
				'description' => isset( $data['description'] ) ? $data['description'] : '',
				'metadata'    => isset( $data['metadata'] ) ? wp_json_encode( $data['metadata'] ) : null,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( $result ) {
			wp_cache_delete( 'payments_list', self::CACHE_GROUP );
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Update payment status.
	 *
	 * @param string $payment_id Payment ID.
	 * @param string $status     New status.
	 * @return bool
	 */
	public function update_status( string $payment_id, string $status ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table for plugin data.
		$result = $wpdb->update(
			$this->get_table_name(),
			array( 'status' => $status ),
			array( 'payment_id' => $payment_id ),
			array( '%s' ),
			array( '%s' )
		);

		if ( false !== $result ) {
			wp_cache_delete( 'payment_' . $payment_id, self::CACHE_GROUP );
			wp_cache_delete( 'payments_list', self::CACHE_GROUP );
		}

		return false !== $result;
	}

	/**
	 * Get payment by payment ID.
	 *
	 * @param string $payment_id Payment ID.
	 * @return object|null
	 */
	public function get_payment( string $payment_id ): ?object {
		global $wpdb;

		$cache_key = 'payment_' . $payment_id;
		$payment   = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $payment ) {
			return $payment ? $payment : null;
		}

		$table_name = $this->get_table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// Table name is safe - generated from $wpdb->prefix constant. Custom table for plugin data.
		$payment = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table_name}` WHERE payment_id = %s",
				$payment_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		wp_cache_set( $cache_key, $payment ? $payment : '', self::CACHE_GROUP, HOUR_IN_SECONDS );

		return $payment ? $payment : null;
	}

	/**
	 * Get payments with pagination.
	 *
	 * @param int    $page     Page number.
	 * @param int    $per_page Items per page.
	 * @param string $status   Filter by status (optional).
	 * @return array{items: array<object>, total: int}
	 */
	public function get_payments( int $page = 1, int $per_page = 20, string $status = '' ): array {
		global $wpdb;

		$table_name = $this->get_table_name();
		$offset     = ( $page - 1 ) * $per_page;
		$cache_key  = 'payments_list_' . md5( $page . '_' . $per_page . '_' . $status );
		$cached     = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// Table name is safe - generated from $wpdb->prefix constant. Custom table for plugin data.
		if ( ! empty( $status ) ) {
			$items = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM `{$table_name}` WHERE status = %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
					$status,
					$per_page,
					$offset
				)
			);

			$total = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$table_name}` WHERE status = %s",
					$status
				)
			);
		} else {
			$items = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM `{$table_name}` ORDER BY created_at DESC LIMIT %d OFFSET %d",
					$per_page,
					$offset
				)
			);

			$total = $wpdb->get_var(
				"SELECT COUNT(*) FROM `{$table_name}`"
			);
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$result = array(
			'items' => $items ? $items : array(),
			'total' => (int) $total,
		);

		wp_cache_set( $cache_key, $result, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS );

		return $result;
	}
}
