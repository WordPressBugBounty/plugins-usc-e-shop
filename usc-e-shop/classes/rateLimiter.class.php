<?php
/**
 * Rate Limiter Class.
 *
 * @package Welcart
 */
class RateLimiter {

	/**
	 * Log type of member login failed.
	 *
	 * @since 2.12.5
	 */
	const LOG_TYPE_LOGIN_FAILED = 'member_login_failed';

	/**
	 * Log type of IP addresses blocked.
	 *
	 * @since 2.12.5
	 */
	const LOG_TYPE_IP_BLOCKED = 'ip_addresses_blocked';

	public $monitoring; /* minutes */
	public $num_of_errors;
	public $rejection_time; /* minutes */
	public $status;
	public $excluded_ip;

	/**
	 * Construct.
	 */
	public function __construct() {

		// get brute force config.
		$options              = get_option( 'usces_ex', array() );
		$this->monitoring     = ( ! isset( $options['system']['brute_force']['monitoring_span'] ) ) ? 5 : (int) $options['system']['brute_force']['monitoring_span'];
		$this->num_of_errors  = ( ! isset( $options['system']['brute_force']['num_of_errors'] ) ) ? 3 : (int) $options['system']['brute_force']['num_of_errors'];
		$this->rejection_time = ( ! isset( $options['system']['brute_force']['rejection_time'] ) ) ? 10 : (int) $options['system']['brute_force']['rejection_time'];
		$this->status         = ( ! isset( $options['system']['brute_force']['status'] ) ) ? 0 : (int) $options['system']['brute_force']['status'];
		$this->excluded_ip    = ( ! isset( $options['system']['brute_force']['excluded_ip'] ) ) ? array() : $options['system']['brute_force']['excluded_ip'];
	}

	/**
	 * Check block IP
	 *
	 * @return bool
	 */
	public function checkBlockIP() {
		if ( $this->status ) {
			try {
				$ip = $_SERVER['REMOTE_ADDR'];
				if ( in_array( $ip, $this->excluded_ip, true ) ) {
					return false;
				}

				if ( $this->is_blocked( $ip ) ) {
					$this->saveLoginFailed();
					return true;
				}

				if ( $this->count_login_failed( $ip ) >= $this->num_of_errors ) {
					$this->insert_log( self::LOG_TYPE_IP_BLOCKED, $ip );
					return true;
				}
			} catch ( Throwable $exception ) {

			}
		}
		return false;
	}

	/**
	 * Save login failed
	 */
	public function saveLoginFailed() {
		global $wp_query;

		try {
			if ( $this->status ) {
				$ip = $_SERVER['REMOTE_ADDR'];
				if ( in_array( $ip, $this->excluded_ip, true ) ) {
					return;
				}

				$this->delete_expired_logs();
				$this->insert_log( self::LOG_TYPE_LOGIN_FAILED, $ip );

				if ( $this->count_login_failed( $ip ) >= $this->num_of_errors ) {
					$this->insert_log( self::LOG_TYPE_IP_BLOCKED, $ip );

					$wp_query->set_403();
					status_header( 403 );
					exit();
				}
			}
		} catch ( Throwable $exception ) {

		}
	}

	/**
	 * Clear login failed
	 */
	public function clear_login_failed() {
		global $wpdb;

		try {
			if ( $this->status ) {
				$ip = $_SERVER['REMOTE_ADDR'];
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->prefix}usces_log WHERE `log_type` = %s AND `log_key` = %s",
						self::LOG_TYPE_LOGIN_FAILED,
						$ip
					)
				);
			}
		} catch ( Throwable $exception ) {

		}
	}

	/**
	 * Clear all logs of login failed and IP addresses blocked.
	 *
	 * @since 2.12.5
	 */
	public static function clear_all() {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}usces_log WHERE `log_type` IN ( %s, %s )",
				self::LOG_TYPE_LOGIN_FAILED,
				self::LOG_TYPE_IP_BLOCKED
			)
		);
	}

	/**
	 * Insert log.
	 *
	 * @since 2.12.5
	 *
	 * @param string $log_type Log type.
	 * @param string $ip IP Address.
	 */
	private function insert_log( $log_type, $ip ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}usces_log ( `datetime`, `log_type`, `log_key` ) VALUES ( %s, %s, %s )",
				current_time( 'mysql' ),
				$log_type,
				$ip
			)
		);
	}

	/**
	 * Count login failed within the monitoring span.
	 *
	 * @since 2.12.5
	 *
	 * @param string $ip IP Address.
	 * @return int
	 */
	private function count_login_failed( $ip ) {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(`ID`) FROM {$wpdb->prefix}usces_log WHERE `datetime` > %s AND `log_type` = %s AND `log_key` = %s",
				$this->get_cutoff_datetime( $this->monitoring ),
				self::LOG_TYPE_LOGIN_FAILED,
				$ip
			)
		);
		return (int) $count;
	}

	/**
	 * Is the IP address blocked within the rejection time.
	 *
	 * @since 2.12.5
	 *
	 * @param string $ip IP Address.
	 * @return bool
	 */
	private function is_blocked( $ip ) {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(`ID`) FROM {$wpdb->prefix}usces_log WHERE `datetime` > %s AND `log_type` = %s AND `log_key` = %s",
				$this->get_cutoff_datetime( $this->rejection_time ),
				self::LOG_TYPE_IP_BLOCKED,
				$ip
			)
		);
		return ( 0 < (int) $count );
	}

	/**
	 * Delete logs older than the monitoring span and the rejection time.
	 *
	 * @since 2.12.5
	 */
	private function delete_expired_logs() {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}usces_log WHERE `datetime` <= %s AND `log_type` = %s",
				$this->get_cutoff_datetime( $this->monitoring ),
				self::LOG_TYPE_LOGIN_FAILED
			)
		);
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}usces_log WHERE `datetime` <= %s AND `log_type` = %s",
				$this->get_cutoff_datetime( $this->rejection_time ),
				self::LOG_TYPE_IP_BLOCKED
			)
		);
	}

	/**
	 * Get the datetime the specified minutes ago.
	 *
	 * @since 2.12.5
	 *
	 * @param int $minutes Minutes.
	 * @return string
	 */
	private function get_cutoff_datetime( $minutes ) {
		return wp_date( 'Y-m-d H:i:s', time() - ( $minutes * MINUTE_IN_SECONDS ) );
	}
}
