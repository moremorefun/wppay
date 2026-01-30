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
	 * Table name for payments.
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'paythefly_payments';
	}

	/**
	 * Create database tables.
	 *
	 * @return void
	 */
	public function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name} (
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table for plugin data
		$result = $wpdb->insert(
			$this->table_name,
			[
				'payment_id'  => $data['payment_id'],
				'amount'      => $data['amount'],
				'currency'    => $data['currency'],
				'status'      => $data['status'] ?? 'pending',
				'description' => $data['description'] ?? '',
				'metadata'    => isset( $data['metadata'] ) ? wp_json_encode( $data['metadata'] ) : null,
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		return $result ? $wpdb->insert_id : false;
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, real-time data
		$result = $wpdb->update(
			$this->table_name,
			[ 'status' => $status ],
			[ 'payment_id' => $payment_id ],
			[ '%s' ],
			[ '%s' ]
		);

		return $result !== false;
	}

	/**
	 * Get payment by payment ID.
	 *
	 * @param string $payment_id Payment ID.
	 * @return object|null
	 */
	public function get_payment( string $payment_id ): ?object {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, real-time payment data
		$payment = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table_name is safe
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE payment_id = %s",
				$payment_id
			)
		);

		return $payment ?: null;
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

		$offset = ( $page - 1 ) * $per_page;

		if ( ! empty( $status ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, real-time data
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table_name is safe
			$items = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$this->table_name} WHERE status = %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
					$status,
					$per_page,
					$offset
				)
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, real-time data
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table_name is safe
			$total = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s",
					$status
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, real-time data
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table_name is safe
			$items = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
					$per_page,
					$offset
				)
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, real-time data
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table_name is safe
			$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
		}

		return [
			'items' => $items ?: [],
			'total' => (int) $total,
		];
	}
}
