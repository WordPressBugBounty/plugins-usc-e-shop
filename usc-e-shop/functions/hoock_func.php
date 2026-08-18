<?php
/**
 * Hoock functions.
 *
 * @package Welcart
 */

add_action( 'usces_construct', 'usces_action_acting_construct', 10 );
add_action( 'usces_after_cart_instant', 'usces_action_acting_transaction', 10 );
// A3 (Layer 3 / uscesid removal): restore the shopper's cart/entry for settlement notifications
// from the transaction-keyed DB store, uscesid-independently. Must run after usces_session_start()
// (constructor end) and before usces_main()'s `new usces_cart()`, hence the usces_main hook.
add_action( 'usces_main', 'usces_restore_acting_session_from_store', 1 );

/**
 * Settlement Result Notification Processing.
 * usces_construct
 */
function usces_action_acting_construct() {
	/* remise */
	if ( isset( $_POST['X-TRANID'] ) && ! isset( $_POST['OPT'] ) ) {

		$rand  = wp_unslash( $_POST['X-S_TORIHIKI_NO'] );
		$datas = array();
		/* 決済成功の時のみセッション復帰 */
		if ( 0 == $_POST['X-ERRLEVEL'] ) {
			$datas = usces_get_order_acting_data( $rand );
			// A3: uscesid ブリッジは廃止。セッション復元は usces_restore_acting_session_from_store()（usces_main）で DBストアから実施.
			if ( empty( $datas['sesid'] ) ) {
				/* sesidが無い場合はファイルログのみ */
				usces_log( 'remise construct : error1', 'acting_transaction.log' );
			} else {
				/* 復帰できた場合のログ */
				usces_log( 'remise construct OK : ' . wp_unslash( $_POST['X-TRANID'] ), 'acting_transaction.log' );
			}
		}

		/* digitalcheck_conv */
	} elseif ( isset( $_POST['SID'] ) && isset( $_POST['FUKA'] ) && isset( $_POST['CVS'] ) ) {

		$sid   = wp_unslash( $_POST['SID'] );
		$datas = usces_get_order_acting_data( $sid );
		// A3: uscesid ブリッジは廃止。セッション復元は usces_restore_acting_session_from_store()（usces_main）で DBストアから実施.
		if ( empty( $datas['sesid'] ) ) {
			$log = array(
				'acting' => 'digitalcheck',
				'key'    => $sid,
				'result' => 'SESSION ERROR',
				'data'   => wp_unslash( $_POST ),
			);
			usces_save_order_acting_error( $log );
			usces_log( 'digitalcheck construct : error1', 'acting_transaction.log' );
		} else {
			usces_log( 'digitalcheck construct : ' . $sid, 'acting_transaction.log' );
		}

		/* AnotherLane */
	} elseif ( isset( $_REQUEST['SiteId'] ) && isset( $_REQUEST['rand'] ) ) {

		$rand  = wp_unslash( $_REQUEST['rand'] );
		$datas = usces_get_order_acting_data( $rand );
		// A3: uscesid ブリッジは廃止。セッション復元は usces_restore_acting_session_from_store()（usces_main）で DBストアから実施.
		if ( empty( $datas['sesid'] ) ) {
			$log = array(
				'acting' => 'anotherlane',
				'key'    => $rand,
				'result' => 'SESSION ERROR',
				'data'   => wp_unslash( $_REQUEST ),
			);
			usces_save_order_acting_error( $log );
			usces_log( 'anotherlane construct : error1', 'acting_transaction.log' );
		} else {
			usces_log( 'anotherlane construct : ' . $rand, 'acting_transaction.log' );
		}

		/* Veritrans */
	} elseif ( isset( $_POST['orderId'] ) && isset( $_POST['merchantEncryptionKey'] ) ) {

		$orderid = wp_unslash( $_POST['orderId'] );
		$datas   = usces_get_order_acting_data( $orderid );
		// A3: uscesid ブリッジは廃止（$_GET['uscesid'] を読む処理は撤廃済みのため代入は無意味だった）。
		// ベリトランス Air-Web は選択可能な決済から除外済み（settlement.class.php）のため、DBストアからの
		// セッション復元（usces_restore_acting_session_from_store）の対象外とする.
		if ( empty( $datas['sesid'] ) ) {
			$log = array(
				'acting' => 'veritrans',
				'key'    => $orderid,
				'result' => 'SESSION ERROR',
				'data'   => wp_unslash( $_POST ),
			);
			usces_save_order_acting_error( $log );
			usces_log( 'Veritrans construct : error1', 'acting_transaction.log' );
		} else {
			usces_log( 'Veritrans construct : ' . $orderid, 'acting_transaction.log' );
		}
	}
}

/**
 * 決済通知（サーバー間・ブラウザ Cookie 無し）で、確定時に保存したカート/エントリを DBストア
 * （usces_access）から $_SESSION へ復元する。uscesid セッション橋渡しの置き換え（Layer 3 / A3）。
 *
 * usces_session_start()（コンストラクタ末尾）より後・usces_main() の `new usces_cart()` より前に
 * 実行する必要があるため usces_main フックに登録する（construct では直後の @session_start() に
 * 上書きされてしまうため）。復元後、usces_cart 生成 → usces_after_cart_instant
 * （usces_action_acting_transaction）が $usces->cart->get_cart() を参照して処理する。
 *
 * 対象はサーバー間通知でセッション復元が要る remise / digitalcheck_conv / AnotherLane。
 *
 * usces_main
 */
function usces_restore_acting_session_from_store() {
	$rand = '';
	if ( isset( $_POST['X-TRANID'] ) && ! isset( $_POST['OPT'] ) && isset( $_POST['X-S_TORIHIKI_NO'] ) ) {
		/* remise: 決済成功時（X-ERRLEVEL == 0）のみ復元 */
		if ( isset( $_POST['X-ERRLEVEL'] ) && 0 == $_POST['X-ERRLEVEL'] ) {
			$rand = wp_unslash( $_POST['X-S_TORIHIKI_NO'] );
		}
	} elseif ( isset( $_POST['SID'] ) && isset( $_POST['FUKA'] ) && isset( $_POST['CVS'] ) ) {
		/* digitalcheck_conv */
		$rand = wp_unslash( $_POST['SID'] );
	} elseif ( isset( $_REQUEST['SiteId'] ) && isset( $_REQUEST['rand'] ) ) {
		/* AnotherLane: 通知は $_REQUEST（GET/POST 両様）。旧 usces_action_acting_construct の
		 * $_GET['uscesid']=$datas['sesid'] ブリッジの置き換え。KickType サーバー通知で order_processing()
		 * がカートを読む前にここで DBストアから復元する。 */
		$rand = wp_unslash( $_REQUEST['rand'] );
	}

	if ( '' === $rand ) {
		return;
	}

	$datas = usces_get_order_acting_data( $rand );
	if ( empty( $datas ) || empty( $datas['order_data'] ) ) {
		return;
	}

	$order_data = maybe_unserialize( $datas['order_data'] );
	if ( ! is_array( $order_data ) ) {
		return;
	}

	if ( isset( $order_data['cart'] ) && is_array( $order_data['cart'] ) ) {
		/*
		 * DBストア（usces_access）の cart は save_order_acting_data() が保存した
		 * $usces->cart->get_cart() の「出力形式」＝ [ i => array( serial, post_id, sku, options,
		 * price, unit_price, quantity, advance ) ]（インデックス配列）。
		 * 一方 $_SESSION['usces_cart'] の「内部形式」は
		 * [ '<serial>' => array( 'price', 'unit_price', 'quant', 'advance' ) ]（serial をキーにしたマップ）で、
		 * usces_cart::get_cart()/key_unserialize() はこの内部形式を前提に読む
		 * （キー=serial／数量キーは 'quant'）。そのまま代入すると整数キー 0 が serial として
		 * unserialize され false → array_keys(false) で致命的エラーになるため、内部形式へ変換して復元する。
		 */
		$session_cart = array();
		foreach ( $order_data['cart'] as $item ) {
			if ( ! is_array( $item ) || ! isset( $item['serial'] ) ) {
				continue;
			}
			$session_cart[ $item['serial'] ] = array(
				'price'      => isset( $item['price'] ) ? $item['price'] : 0,
				'unit_price' => isset( $item['unit_price'] ) ? $item['unit_price'] : 0,
				'quant'      => isset( $item['quantity'] ) ? $item['quantity'] : 0,
				'advance'    => isset( $item['advance'] ) ? $item['advance'] : '',
			);
		}
		$_SESSION['usces_cart'] = $session_cart;
	}
	if ( isset( $order_data['entry'] ) ) {
		$_SESSION['usces_entry'] = $order_data['entry'];
	}
	usces_log( 'acting session restored from DB store (uscesid-independent) : ' . $rand, 'acting_transaction.log' );
}

/**
 * 決済結果通知の送信元IPが許可リストに含まれるか判定する。
 *
 * @param  string       $remote_addr Source IP address of the notification.
 * @param  array|string $allowed     Allowed IP addresses / CIDR ranges.
 * @return bool True when the source IP is allowed.
 */
function usces_acting_notice_ip_allowed( $remote_addr, $allowed ) {
	if ( empty( $remote_addr ) || empty( $allowed ) ) {
		return false;
	}
	$remote_long = ip2long( $remote_addr );
	if ( false === $remote_long ) {
		return false;
	}
	foreach ( (array) $allowed as $entry ) {
		$entry = trim( (string) $entry );
		if ( '' === $entry ) {
			continue;
		}
		if ( false !== strpos( $entry, '/' ) ) {
			list( $subnet, $bits ) = explode( '/', $entry, 2 );
			$subnet_long           = ip2long( $subnet );
			$bits                  = (int) $bits;
			if ( false === $subnet_long || $bits < 1 || 32 < $bits ) {
				continue;
			}
			$mask = ( 0xFFFFFFFF << ( 32 - $bits ) ) & 0xFFFFFFFF;
			if ( ( $remote_long & $mask ) === ( $subnet_long & $mask ) ) {
				return true;
			}
		} elseif ( ip2long( $entry ) === $remote_long ) {
			return true;
		}
	}
	return false;
}

/**
 * Settlement Result Notification Processing.
 * usces_after_cart_instant
 */
function usces_action_acting_transaction() {
	global $usces, $wpdb;

	/* remise_card */
	if ( isset( $_POST['X-TRANID'] ) && ! isset( $_POST['OPT'] ) ) {
		$data = array();
		foreach ( $_POST as $key => $value ) {
			$data[ $key ] = mb_convert_encoding( $value, 'UTF-8', 'SJIS' );
		}
		/* 決済エラーの場合は、エラーログを残してdie */
		if ( 0 != $data['X-ERRLEVEL'] ) {
			$log = array(
				'acting' => 'remise_card',
				'key'    => $data['S_TORIHIKI_NO'],
				'result' => $data['X-ERRCODE'],
				'data'   => $data,
			);
			usces_save_order_acting_error( $log );
			$status = ( isset( $_POST['CARIER_TYPE'] ) ) ? '900' : '800';
			die( '<SDBKDATA>STATUS=' . $status . '</SDBKDATA>' );
		}
		usces_log( 'remise acting_transaction : ' . print_r( $data, true ), 'acting_transaction.log' );

		$rand = wp_unslash( $_POST['X-S_TORIHIKI_NO'] );

		/* カード情報更新 */
		if ( '0000000' === substr( $rand, 0, 7 ) ) { /* card up */
			usces_log( 'remise card_update : ' . print_r( $data, true ), 'acting_transaction.log' );
			if ( isset( $_POST['X-AC_S_KAIIN_NO'] ) ) {
				if ( isset( $_POST['X-EXPIRE'] ) ) {
					$expire      = wp_unslash( $_POST['X-EXPIRE'] );
					$limitofcard = substr( $expire, 0, 2 ) . '/' . substr( $expire, 2, 2 );
					$usces->set_member_meta_value( 'limitofcard', $limitofcard, wp_unslash( $_POST['X-AC_S_KAIIN_NO'] ) );
				}
				if ( isset( $_POST['X-PARTOFCARD'] ) ) {
					$usces->set_member_meta_value( 'partofcard', wp_unslash( $_POST['X-PARTOFCARD'] ), wp_unslash( $_POST['X-AC_S_KAIIN_NO'] ) );
				}
			} elseif ( isset( $_POST['X-PAYQUICKID'] ) ) {
				$member_id = substr( $rand, 7 );
				if ( $member_id ) {
					$usces->set_member_meta_value( 'remise_pcid', wp_unslash( $_POST['X-PAYQUICKID'] ), (int) $member_id );
					if ( isset( $_POST['X-EXPIRE'] ) ) {
						$expire      = wp_unslash( $_POST['X-EXPIRE'] );
						$limitofcard = substr( $expire, 0, 2 ) . '/' . substr( $expire, 2, 2 );
						$usces->set_member_meta_value( 'limitofcard', $limitofcard, (int) $member_id );
					}
					if ( isset( $_POST['X-PARTOFCARD'] ) ) {
						$usces->set_member_meta_value( 'partofcard', wp_unslash( $_POST['X-PARTOFCARD'] ), (int) $member_id );
					}
				}
			}

			$status = ( isset( $_POST['CARIER_TYPE'] ) ) ? '900' : '800';
			die( '<SDBKDATA>STATUS=' . $status . '</SDBKDATA>' );
		}

		$cart = $usces->cart->get_cart();
		/* カートが無い（セッションが無い）場合、エラーログを残し、ルミーズにはerrorを返却 */
		if ( empty( $cart ) ) {
			usces_log( 'remise card error3 : ' . print_r( $data, true ), 'acting_transaction.log' );
			$log = array(
				'acting' => 'remise',
				'key'    => wp_unslash( $_POST['X-S_TORIHIKI_NO'] ),
				'result' => 'SESSION TIME OUT',
				'data'   => wp_unslash( $_POST ),
			);
			usces_save_order_acting_error( $log );
			die( 'SESSION TIME OUT' );

			/* セッションが存在する場合は、STATUSを返却 */
		} else {
			if ( isset( $_POST['X-PAYQUICKID'] ) ) {
				$usces->set_member_meta_value( 'remise_pcid', wp_unslash( $_POST['X-PAYQUICKID'] ) );
			}
			if ( isset( $_POST['X-AC_MEMBERID'] ) ) {
				$usces->set_member_meta_value( 'remise_memid', wp_unslash( $_POST['X-AC_MEMBERID'] ) );
			}
			if ( isset( $_POST['X-EXPIRE'] ) ) {
				$expire      = wp_unslash( $_POST['X-EXPIRE'] );
				$limitofcard = substr( $expire, 0, 2 ) . '/' . substr( $expire, 2, 2 );
				$usces->set_member_meta_value( 'limitofcard', $limitofcard );
			}
			if ( isset( $_POST['X-PARTOFCARD'] ) ) {
				$usces->set_member_meta_value( 'partofcard', wp_unslash( $_POST['X-PARTOFCARD'] ) );
			}
			usces_log( 'remise card transaction : ' . wp_unslash( $_POST['X-TRANID'] ), 'acting_transaction.log' );
			$status = ( isset( $_POST['CARIER_TYPE'] ) ) ? '900' : '800';
			die( '<SDBKDATA>STATUS=' . $status . '</SDBKDATA>' );
		}

		/* remise_conv */
	} elseif ( isset( $_POST['S_TORIHIKI_NO'] ) && isset( $_POST['REC_FLG'] ) ) {
		$data = array();
		foreach ( $_POST as $key => $value ) {
			$data[ $key ] = mb_convert_encoding( $value, 'UTF-8', 'SJIS' );
		}
		REMISE_SETTLEMENT::get_instance()->acting_notice_ip_guard( $data );

		$table_name      = $wpdb->prefix . 'usces_order';
		$table_meta_name = $wpdb->prefix . 'usces_order_meta';

		$mquery   = $wpdb->prepare( "SELECT order_id FROM $table_meta_name WHERE meta_key = %s AND meta_value = %s", 'settlement_id', wp_unslash( $_POST['S_TORIHIKI_NO'] ) );
		$order_id = $wpdb->get_var( $mquery );
		if ( null == $order_id ) {
			$log = array(
				'acting' => 'remise_conv',
				'key'    => wp_unslash( $_POST['S_TORIHIKI_NO'] ),
				'result' => 'ORDER DATA KEY ERROR',
				'data'   => wp_unslash( $_POST ),
			);
			usces_save_order_acting_error( $log );
			usces_log( 'remise conv error1 : ' . print_r( $data, true ), 'acting_transaction.log' );
			die( 'error1' );
		}

		$res = usces_change_order_receipt( $order_id, 'receipted' );
		if ( false === $res ) {
			$log = array(
				'acting' => 'remise_conv',
				'key'    => wp_unslash( $_POST['S_TORIHIKI_NO'] ),
				'result' => 'ORDER DATA UPDATE ERROR',
				'data'   => wp_unslash( $_POST ),
			);
			usces_save_order_acting_error( $log );
			usces_log( 'remise conv error2 : ' . print_r( $data, true ), 'acting_transaction.log' );
			die( 'error2' );
		}

		$datastr = serialize( $data );
		$mquery  = $wpdb->prepare( "UPDATE $table_meta_name SET meta_value = %s WHERE meta_key = %s AND order_id = %d", $datastr, 'settlement_id', $order_id );
		$res = $wpdb->query( $mquery );
		if ( false === $res ) {
			$log = array(
				'acting' => 'remise_conv',
				'key'    => wp_unslash( $_POST['S_TORIHIKI_NO'] ),
				'result' => 'ORDER META DATA UPDATE ERROR',
				'data'   => wp_unslash( $_POST ),
			);
			usces_save_order_acting_error( $log );
			usces_log( 'remise conv error3 : ' . print_r( $data, true ), 'acting_transaction.log' );
			die( 'error3' );
		}

		usces_action_acting_getpoint( $order_id );

		usces_log( 'remise conv transaction : ' . wp_unslash( $_POST['S_TORIHIKI_NO'] ), 'acting_transaction.log' );
		die( '<SDBKDATA>STATUS=800</SDBKDATA>' );

		/* jpayment_card */
	} elseif ( isset( $_REQUEST['acting'] ) && 'jpayment_card' == $_REQUEST['acting'] ) {

		/* jpayment_conv */
	} elseif ( isset( $_REQUEST['acting'] ) && 'jpayment_conv' == $_REQUEST['acting'] && isset( $_GET['ap'] ) ) {
		switch ( $_GET['ap'] ) {
			case 'CPL_PRE': /* コンビニペーパーレス決済識別コード */
				break;

			case 'CPL': /* 入金確定 */
				JPAYMENT_SETTLEMENT::get_instance()->acting_notice_ip_guard();
				$acting = wp_unslash( $_REQUEST['acting'] );
				$data   = array();
				foreach ( $_GET as $key => $value ) {
					$data[ $key ] = esc_sql( $value );
				}
				$cod = ( isset( $data['cod'] ) ) ? $data['cod'] : '';

				$table_name      = $wpdb->prefix . 'usces_order';
				$table_meta_name = $wpdb->prefix . 'usces_order_meta';

				$query    = $wpdb->prepare( "SELECT order_id FROM $table_meta_name WHERE meta_key = %s AND meta_value = %s", 'settlement_id', $cod );
				$order_id = $wpdb->get_var( $query );
				if ( null == $order_id ) {
					$log = array(
						'acting' => $acting,
						'key'    => $cod,
						'result' => 'ORDER DATA KEY ERROR',
						'data'   => $data,
					);
					usces_save_order_acting_error( $log );
					usces_log( 'ROBOT PAYMENT conv error1 : ' . print_r( $data, true ), 'acting_transaction.log' );
					die( 'error1' );
				}

				$res = usces_change_order_receipt( $order_id, 'receipted' );
				if ( false === $res ) {
					$log = array(
						'acting' => $acting,
						'key'    => $cod,
						'result' => 'ORDER DATA UPDATE ERROR',
						'data'   => $data,
					);
					usces_save_order_acting_error( $log );
					usces_log( 'ROBOT PAYMENT conv error2 : ' . print_r( $data, true ), 'acting_transaction.log' );
					die( 'error2' );
				}

				$res = $usces->set_order_meta_value( 'acting_' . $acting, serialize( $data ), $order_id );
				if ( false === $res ) {
					$log = array(
						'acting' => $acting,
						'key'    => $cod,
						'result' => 'ORDER META DATA UPDATE ERROR',
						'data'   => $data,
					);
					usces_save_order_acting_error( $log );
					usces_log( 'ROBOT PAYMENT conv error3 : ' . print_r( $data, true ), 'acting_transaction.log' );
					die( 'error3' );
				}

				usces_action_acting_getpoint( $order_id );

				usces_log( 'ROBOT PAYMENT conv transaction : ' . $data['gid'], 'acting_transaction.log' );
				die( 'ROBOT PAYMENT' );
				break;

			case 'CVS_CAN': /* 入金取消 */
				JPAYMENT_SETTLEMENT::get_instance()->acting_notice_ip_guard();
				$acting = wp_unslash( $_REQUEST['acting'] );
				$data   = array();
				foreach ( $_GET as $key => $value ) {
					$data[ $key ] = esc_sql( $value );
				}
				$cod = ( isset( $data['cod'] ) ) ? $data['cod'] : '';

				$table_name      = $wpdb->prefix . 'usces_order';
				$table_meta_name = $wpdb->prefix . 'usces_order_meta';

				$query    = $wpdb->prepare( "SELECT `order_id` FROM $table_meta_name WHERE `meta_key` = %s AND `meta_value` = %s", 'settlement_id', $cod );
				$order_id = $wpdb->get_var( $query );
				if ( null == $order_id ) {
					$log = array(
						'acting' => $acting,
						'key'    => $cod,
						'result' => 'ORDER DATA KEY ERROR',
						'data'   => $data,
					);
					usces_save_order_acting_error( $log );
					usces_log( 'ROBOT PAYMENT conv error1 : ' . print_r( $data, true ), 'acting_transaction.log' );
					die( 'error1' );
				}

				$res = usces_change_order_receipt( $order_id, 'noreceipt' );
				if ( false === $res ) {
					$log = array(
						'acting' => $acting,
						'key'    => $cod,
						'result' => 'ORDER DATA UPDATE ERROR',
						'data'   => $data,
					);
					usces_save_order_acting_error( $log );
					usces_log( 'ROBOT PAYMENT conv error2 : ' . print_r( $data, true ), 'acting_transaction.log' );
					die( 'error2' );
				}

				$res = $usces->set_order_meta_value( 'acting_' . $acting, serialize( $data ), $order_id );
				if ( false === $res ) {
					$log = array(
						'acting' => $acting,
						'key'    => $cod,
						'result' => 'ORDER META DATA UPDATE ERROR',
						'data'   => $data,
					);
					usces_save_order_acting_error( $log );
					usces_log( 'ROBOT PAYMENT conv error3 : ' . print_r( $data, true ), 'acting_transaction.log' );
					die( 'error3' );
				}

				usces_action_acting_getpoint( $order_id, false );

				usces_log( 'ROBOT PAYMENT conv transaction : ' . $data['gid'], 'acting_transaction.log' );
				die( 'ROBOT PAYMENT' );
				break;
		}

		/* jpayment_bank */
	} elseif ( isset( $_REQUEST['acting'] ) && 'jpayment_bank' == $_REQUEST['acting'] && isset( $_GET['ap'] ) ) {
		switch ( $_GET['ap'] ) {
			case 'BANK': /* 受付完了 */
				break;

			case 'BAN_SAL': /* 入金完了 */
				JPAYMENT_SETTLEMENT::get_instance()->acting_notice_ip_guard();
				if ( isset( $_GET['mf'] ) && '1' == wp_unslash( $_GET['mf'] ) ) { /* 入金マッチングの場合 */
					$acting = wp_unslash( $_REQUEST['acting'] );
					$data   = array();
					foreach ( $_GET as $key => $value ) {
						$data[ $key ] = esc_sql( $value );
					}
					$cod = ( isset( $data['cod'] ) ) ? $data['cod'] : '';

					$table_name      = $wpdb->prefix . 'usces_order';
					$table_meta_name = $wpdb->prefix . 'usces_order_meta';

					$query    = $wpdb->prepare( "SELECT `order_id` FROM $table_meta_name WHERE `meta_key` = %s AND `meta_value` = %s", 'settlement_id', $cod );
					$order_id = $wpdb->get_var( $query );
					if ( null == $order_id ) {
						$log = array(
							'acting' => $acting,
							'key'    => $cod,
							'result' => 'ORDER DATA KEY ERROR',
							'data'   => $data,
						);
						usces_save_order_acting_error( $log );
						usces_log( 'ROBOT PAYMENT bank error1 : ' . print_r( $data, true ), 'acting_transaction.log' );
						die( 'error1' );
					}

					$res = usces_change_order_receipt( $order_id, 'receipted' );
					if ( false === $res ) {
						$log = array(
							'acting' => $acting,
							'key'    => $cod,
							'result' => 'ORDER DATA UPDATE ERROR',
							'data'   => $data,
						);
						usces_save_order_acting_error( $log );
						usces_log( 'ROBOT PAYMENT bank error2 : ' . print_r( $data, true ), 'acting_transaction.log' );
						die( 'error2' );
					}

					$res = $usces->set_order_meta_value( 'acting_' . $acting, serialize( $data ), $order_id );
					if ( false === $res ) {
						$log = array(
							'acting' => $acting,
							'key'    => $cod,
							'result' => 'ORDER META DATA UPDATE ERROR',
							'data'   => $data,
						);
						usces_save_order_acting_error( $log );
						usces_log( 'ROBOT PAYMENT bank error3 : ' . print_r( $data, true ), 'acting_transaction.log' );
						die( 'error3' );
					}

					usces_action_acting_getpoint( $order_id );
				}

				usces_log( 'ROBOT PAYMENT bank transaction : ' . $data['gid'], 'acting_transaction.log' );
				die( 'ROBOT PAYMENT' );
				break;
		}

		/* PayPal ipn */
	} elseif ( ! isset( $_GET['acting_return'] ) && ( isset( $_GET['acting'] ) && 'paypal_ipn' == wp_unslash( $_GET['acting'] ) ) ) {
		if ( file_exists( $usces->options['settlement_path'] . 'paypal.php' ) ) {
			$data = array();
			foreach ( $_REQUEST as $key => $value ) {
				$data[ $key ] = $value;
			}
			usces_log( 'paypal_ipn in ' . print_r( $data, true ), 'acting_transaction.log' );
			require_once( $usces->options['settlement_path'] . 'paypal.php' );
			$ipn_res = paypal_ipn_check( $usces_paypal_url );
			if ( true === $ipn_res[0] ) {
				$res = $usces->order_processing( $ipn_res );
				if ( 'ordercompletion' == $res ) {
					$usces->cart->crear_cart();
				} else {
					usces_log( 'paypal_ipn error : ' . print_r( $data, true ), 'acting_transaction.log' );
					die( 'error1' );
				}
				do_action( 'usces_action_paypal_ipn', $res, $ipn_res );
			}
			usces_log( 'PayPal IPN transaction : ' . wp_unslash( $_REQUEST['txn_id'] ), 'acting_transaction.log' );
		}
		die( 'PayPal' );

		/* telecom edy */
	} elseif ( isset( $_REQUEST['clientip'] ) && isset( $_REQUEST['sendid'] ) && ( isset( $_REQUEST['acting'] ) && 'telecom_edy' == $_REQUEST['acting'] ) ) {
		$data = array();
		foreach ( $_REQUEST as $key => $value ) {
			$data[ $key ] = $value;
		}
		usces_log( 'telecom edy : ' . print_r( $data, true ), 'acting_transaction.log' );

	} elseif ( isset( $_REQUEST['clientip'] ) && isset( $_REQUEST['sendid'] ) && isset( $_REQUEST['edy'] ) ) {
		$data = array();
		foreach ( $_REQUEST as $key => $value ) {
			$data[ $key ] = $value;
		}
		if ( 'yes' === wp_unslash( $_REQUEST['rel'] ) && isset( $_REQUEST['option'] ) ) {
			$table_meta_name          = $wpdb->prefix . 'usces_order_meta';
			$mquery                   = $wpdb->prepare( "SELECT order_id, meta_value FROM $table_meta_name WHERE meta_key = %s", wp_unslash( $_REQUEST['option'] ) );
			$mvalue                   = $wpdb->get_row( $mquery, ARRAY_A );
			$value                    = unserialize( $mvalue['meta_value'] );
			$_SESSION['usces_cart']   = $value['usces_cart'];
			$_SESSION['usces_entry']  = $value['usces_entry'];
			$_SESSION['usces_member'] = $value['usces_member'];
			$res                      = $usces->order_processing();
			if ( 'ordercompletion' == $res ) {
				$query = $wpdb->prepare( "DELETE FROM $table_meta_name WHERE meta_key = %s", wp_unslash( $_REQUEST['option'] ) );
				$res   = $wpdb->query( $query );
				usces_log( 'telecom edy - Payment confirmation : ' . print_r( $data, true ), 'acting_transaction.log' );
			} else {
				$log = array(
					'acting' => 'telecom_edy',
					'key'    => wp_unslash( $_REQUEST['option'] ),
					'result' => 'ORDER DATA REGISTERED ERROR',
					'data'   => wp_unslash( $_REQUEST ),
				);
				usces_save_order_acting_error( $log );
				usces_log( 'telecom edy - Error 1 : ' . print_r( $data, true ), 'acting_transaction.log' );
			}
		} else {
			$log = array(
				'acting' => 'telecom_edy',
				'key'    => wp_unslash( $_REQUEST['option'] ),
				'result' => wp_unslash( $_REQUEST['rel'] ),
				'data'   => wp_unslash( $_REQUEST ),
			);
			usces_save_order_acting_error( $log );
			usces_log( 'telecom edy - Error 2 : ' . print_r( $data, true ), 'acting_transaction.log' );
		}
		die( 'SuccessOK' );

		/* telecom credit */
	} elseif ( isset( $_REQUEST['clientip'] ) && isset( $_REQUEST['sendid'] ) && isset( $_REQUEST['rel'] ) ) {
		$data = array();
		foreach ( $_REQUEST as $key => $value ) {
			$data[ $key ] = $value;
		}
		if ( 'yes' === wp_unslash( $_REQUEST['rel'] ) && isset( $_REQUEST['option'] ) ) {
		} else {
			$log = array(
				'acting' => 'telecom_card',
				'key'    => wp_unslash( $_REQUEST['option'] ),
				'result' => wp_unslash( $_REQUEST['rel'] ),
				'data'   => wp_unslash( $_REQUEST ),
			);
			usces_save_order_acting_error( $log );
		}
		usces_log( 'telecom card : ' . print_r( $data, true ), 'acting_transaction.log' );
		die( 'SuccessOK' );

		/* digitalcheck card */
	} elseif ( isset( $_REQUEST['SID'] ) && isset( $_REQUEST['FUKA'] ) && 'acting_digitalcheck_card' == substr( wp_unslash( $_REQUEST['FUKA'] ), 0, 24 ) ) {
		if ( isset( $_GET['acting_return'] ) && isset( $_GET['acting'] ) && 'digitalcheck_card' === $_GET['acting'] ) {
			return;
		}

		$data = array();
		foreach ( $_REQUEST as $key => $value ) {
			$data[ $key ] = $value;
		}
		$sid = ( isset( $data['SID'] ) ) ? $data['SID'] : '';

		if ( isset( $data['SEQ'] ) ) {
			$table_meta_name = $wpdb->prefix . 'usces_order_meta';
			$query           = $wpdb->prepare( "SELECT `order_id` FROM $table_meta_name WHERE `meta_key` = %s AND `meta_value` = %s", 'SID', $sid );
			$order_id        = $wpdb->get_var( $query );
			if ( ! $order_id ) {
				$acting_opts = $usces->options['acting_settings']['digitalcheck'];
				$ip_user_id  = substr( $data['FUKA'], 24 );
				if ( 'on' == $acting_opts['card_user_id'] && ! empty( $ip_user_id ) ) {
					$usces->set_member_meta_value( 'digitalcheck_ip_user_id', $ip_user_id, $ip_user_id );
				}

				$res = $usces->order_processing();
				if ( 'ordercompletion' == $res ) {
					$order_id = $usces->cart->get_order_entry( 'ID' );
					$usces->set_order_meta_value( 'acting_digitalcheck_card', serialize( $data ), $order_id );
					$usces->set_order_meta_value( 'wc_trans_id', $sid, $order_id );
				} else {
					$log = array(
						'acting' => 'digitalcheck_card',
						'key'    => $sid,
						'result' => 'ORDER DATA REGISTERED ERROR',
						'data'   => wp_unslash( $_REQUEST ),
					);
					usces_save_order_acting_error( $log );
					// usces_log( 'digitalcheck card : order processing error', 'acting_transaction.log' );
				}
			}

			header( 'Content-Type: text/plain; charset=Shift_JIS' );
			die( "0\r\n" );

		} elseif ( isset( $data['purchase'] ) ) {
			return;

		} else {
			$table_meta_name = $wpdb->prefix . 'usces_order_meta';
			$query           = $wpdb->prepare( "SELECT `order_id` FROM $table_meta_name WHERE `meta_key` = %s AND `meta_value` = %s", 'SID', $sid );
			$order_id        = $wpdb->get_var( $query );
			if ( $order_id ) {
				header( 'Content-Type: text/plain; charset=Shift_JIS' );
				die( "0\r\n" );
			}
		}

		header( 'Content-Type: text/plain; charset=Shift_JIS' );
		die( "0\r\n" );

		/* digitalcheck conv */
	} elseif ( isset( $_REQUEST['SID'] ) && isset( $_REQUEST['FUKA'] ) && 'acting_digitalcheck_conv' == substr( wp_unslash( $_REQUEST['FUKA'] ), 0, 24 ) ) {
		if ( isset( $_GET['acting_return'] ) && isset( $_GET['acting'] ) && 'digitalcheck_conv' === $_GET['acting'] ) {
			return;
		}

		$data = array();
		foreach ( $_REQUEST as $key => $value ) {
			$data[ $key ] = $value;
		}
		$sid = ( isset( $data['SID'] ) ) ? $data['SID'] : '';

		if ( isset( $data['SEQ'] ) ) {
			DIGITALCHECK_SETTLEMENT::get_instance()->acting_notice_ip_guard( $data );
			$table_name      = $wpdb->prefix . 'usces_order';
			$table_meta_name = $wpdb->prefix . 'usces_order_meta';
			$query           = $wpdb->prepare( "SELECT `order_id` FROM $table_meta_name WHERE `meta_key` = %s AND `meta_value` = %s", 'SID', $sid );
			$order_id        = $wpdb->get_var( $query );
			if ( null == $order_id ) {
				$log = array(
					'acting' => 'digitalcheck_conv',
					'key'    => $sid,
					'result' => 'ORDER DATA KEY ERROR',
					'data'   => wp_unslash( $_REQUEST ),
				);
				usces_save_order_acting_error( $log );
				usces_log( 'digitalcheck conv error1 : ' . print_r( $data, true ), 'acting_transaction.log' );
				header( 'Content-Type: text/plain; charset=Shift_JIS' );
				die( "9\r\n" );
			}

			if ( isset( $data['CVS'] ) ) { /* 入金 */
				$res = usces_change_order_receipt( $order_id, 'receipted' );
				if ( false === $res ) {
					$log = array(
						'acting' => 'digitalcheck_conv',
						'key'    => $sid,
						'result' => 'ORDER DATA UPDATE ERROR',
						'data'   => wp_unslash( $_REQUEST ),
					);
					usces_save_order_acting_error( $log );
					usces_log( 'digitalcheck conv error2 : ' . print_r( $data, true ), 'acting_transaction.log' );
					header( 'Content-Type: text/plain; charset=Shift_JIS' );
					die( "9\r\n" );
				}

				usces_action_acting_getpoint( $order_id );

			} else { /* 取消 */
				$res = usces_change_order_receipt( $order_id, 'noreceipt' );
				if ( false === $res ) {
					$log = array(
						'acting' => 'digitalcheck_conv',
						'key'    => $sid,
						'result' => 'ORDER DATA UPDATE ERROR',
						'data'   => wp_unslash( $_REQUEST ),
					);
					usces_save_order_acting_error( $log );
					usces_log( 'digitalcheck conv error3 : ' . print_r( $data, true ), 'acting_transaction.log' );
					header( 'Content-Type: text/plain; charset=Shift_JIS' );
					die( "9\r\n" );
				}

				usces_action_acting_getpoint( $order_id, false );
			}

			$usces->set_order_meta_value( 'acting_digitalcheck_conv', serialize( $data ), $order_id );

			$dquery = $wpdb->prepare( "DELETE FROM $table_meta_name WHERE meta_key = %s", $sid );
			$res    = $wpdb->query( $dquery );

			header( 'Content-Type: text/plain; charset=Shift_JIS' );
			die( "0\r\n" );

		} else {
			if ( isset( $data['CVS'] ) && isset( $data['SHNO'] ) ) { /* 決済 */
				$table_meta_name = $wpdb->prefix . 'usces_order_meta';
				$query           = $wpdb->prepare( "SELECT `order_id` FROM $table_meta_name WHERE `meta_key` = %s AND `meta_value` = %s", 'SID', $sid );
				$order_id        = $wpdb->get_var( $query );
				if ( $order_id ) {
					$usces->set_order_meta_value( 'acting_digitalcheck_conv', serialize( $data ), $order_id );

				} else {
					$res = $usces->order_processing();
					if ( 'ordercompletion' == $res ) {
						$usces->set_order_meta_value( 'acting_digitalcheck_conv', serialize( $data ), $order_id );
						$usces->set_order_meta_value( 'wc_trans_id', $sid, $order_id );
					} else {
						$log = array(
							'acting' => 'digitalcheck_conv',
							'key'    => $sid,
							'result' => 'ORDER DATA REGISTERED ERROR',
							'data'   => wp_unslash( $_REQUEST ),
						);
						usces_save_order_acting_error( $log );
						usces_log( 'digitalcheck conv : order processing error', 'acting_transaction.log' );
					}
				}

				header( 'Content-Type: text/plain; charset=Shift_JIS' );
				die( "0\r\n" );

			} elseif ( isset( $data['purchase'] ) ) {

			} else {
				$permalink_structure = get_option( 'permalink_structure' );
				$delim               = ( ! $usces->use_ssl && $permalink_structure ) ? '?' : '&';
				header( 'location: ' . USCES_CART_URL . $delim . 'acting=digitalcheck_conv&acting_return=1&SID=' . $sid );
				exit;
			}
		}

		/* mizuho card */
	} elseif ( ( isset( $_GET['p_ver'] ) && '0200' == wp_unslash( $_GET['p_ver'] ) ) && ( isset( $_GET['bkcode'] ) && 'bg01' == wp_unslash( $_GET['bkcode'] ) ) ) {
		usces_log( 'mizuho card : ' . print_r( wp_unslash( $_GET ), true ), 'acting_transaction.log' );
		$stran               = ( array_key_exists( 'stran', $_REQUEST ) ) ? wp_unslash( $_REQUEST['stran'] ) : '';
		$mbtran              = ( array_key_exists( 'mbtran', $_REQUEST ) ) ? wp_unslash( $_REQUEST['mbtran'] ) : '';
		$rsltcd              = ( array_key_exists( 'rsltcd', $_REQUEST ) ) ? wp_unslash( $_REQUEST['rsltcd'] ) : '';
		$rsltdcd             = ( array_key_exists( 'rsltdcd', $_REQUEST ) ) ? wp_unslash( $_REQUEST['rsltdcd'] ) : '';
		$permalink_structure = get_option( 'permalink_structure' );
		$delim               = ( ! $usces->use_ssl && $permalink_structure ) ? '?' : '&';
		if ( '108' == substr( $rsltcd, 0, 3 ) || '208' == substr( $rsltcd, 0, 3 ) || '308' == substr( $rsltcd, 0, 3 ) ) { /* キャンセル */
			header( 'location: ' . USCES_CART_URL . $delim . 'confirm=1' );
		} elseif ( '109' == substr( $rsltcd, 0, 3 ) || '209' == substr( $rsltcd, 0, 3 ) || '309' == substr( $rsltcd, 0, 3 ) ) { /* エラー */
			$log = array(
				'acting' => 'mizuho_card',
				'key'    => $stran,
				'result' => $rsltcd,
				'data'   => wp_unslash( $_GET ),
			);
			usces_save_order_acting_error( $log );
			usces_log( 'mizuho card : ' . print_r( wp_unslash( $_GET ), true ), 'acting_transaction.log' );
			header( 'location: ' . USCES_CART_URL . $delim . 'acting=mizuho_card&acting_return=0&rsltdcd=' . $rsltdcd );
		} else {
			header( 'location: ' . USCES_CART_URL . $delim . 'acting=mizuho_card&acting_return=1&stran=' . $stran . '&mbtran=' . $mbtran . '&rsltcd=' . $rsltcd );
		}
		die();

		/* mizuho conv 提供対象外のため無効化。
	} elseif ( ( isset( $_GET['p_ver'] ) && '0200' == wp_unslash( $_GET['p_ver'] ) ) && ( isset( $_GET['bkcode'] ) && ( 'cv01' == wp_unslash( $_GET['bkcode'] ) || 'cv02' == wp_unslash( $_GET['bkcode'] ) ) ) ) {
		usces_log( 'mizuho conv : ' . print_r( $_GET, true ), 'acting_transaction.log' );
		$stran               = ( array_key_exists( 'stran', $_REQUEST ) ) ? wp_unslash( $_REQUEST['stran'] ) : '';
		$mbtran              = ( array_key_exists( 'mbtran', $_REQUEST ) ) ? wp_unslash( $_REQUEST['mbtran'] ) : '';
		$bktrans             = ( array_key_exists( 'bktrans', $_REQUEST ) ) ? wp_unslash( $_REQUEST['bktrans'] ) : '';
		$tranid              = ( array_key_exists( 'tranid', $_REQUEST ) ) ? wp_unslash( $_REQUEST['tranid'] ) : '';
		$tdate               = ( array_key_exists( 'tdate', $_REQUEST ) ) ? wp_unslash( $_REQUEST['tdate'] ) : '';
		$rsltcd              = ( array_key_exists( 'rsltcd', $_REQUEST ) ) ? wp_unslash( $_REQUEST['rsltcd'] ) : '';
		$permalink_structure = get_option( 'permalink_structure' );
		$delim               = ( ! $usces->use_ssl && $permalink_structure ) ? '?' : '&';
		if ( '' != $tdate ) { // 入金通知
			$data = array();
			foreach ( $_REQUEST as $key => $value ) {
				$data[ $key ] = $value;
			}
			if ( '0000000000000' == $rsltcd ) {
				$table_name      = $wpdb->prefix . 'usces_order';
				$table_meta_name = $wpdb->prefix . 'usces_order_meta';

				$query    = $wpdb->prepare( "SELECT order_id FROM $table_meta_name WHERE meta_key = %s AND meta_value = %s", 'stran', $stran );
				$order_id = $wpdb->get_var( $query );
				if ( null == $order_id ) {
					$log = array(
						'acting' => 'mizuho_conv',
						'key'    => $stran,
						'result' => 'ORDER DATA KEY ERROR',
						'data'   => wp_unslash( $_GET ),
					);
					usces_save_order_acting_error( $log );
					usces_log( 'mizuho conv error1 : ' . print_r( $data, true ), 'acting_transaction.log' );
				} else {
					$res = usces_change_order_receipt( $order_id, 'receipted' );
					if ( false === $res ) {
						$log = array(
							'acting' => 'mizuho_conv',
							'key'    => $stran,
							'result' => 'ORDER DATA UPDATE ERROR',
							'data'   => wp_unslash( $_GET ),
						);
						usces_save_order_acting_error( $log );
						usces_log( 'mizuho conv error2 : ' . print_r( $data, true ), 'acting_transaction.log' );

					} else {
						usces_action_acting_getpoint( $order_id );

						$usces->set_order_meta_value( 'acting_mizuho_conv', serialize( $data ), $order_id );

						$dquery = $wpdb->prepare( "DELETE FROM $table_meta_name WHERE meta_key = %s", $stran );
						$res    = $wpdb->query( $dquery );
					}
				}
			} else {
				usces_log( 'mizuho conv : ' . print_r( wp_unslash( $_GET ), true ), 'acting_transaction.log' );
			}
		} elseif ( '108' == substr( $rsltcd, 0, 3 ) || '208' == substr( $rsltcd, 0, 3 ) || '308' == substr( $rsltcd, 0, 3 ) ) { // キャンセル
			header( 'location: ' . USCES_CART_URL . $delim . 'confirm=1' );
		} elseif ( '109' == substr( $rsltcd, 0, 3 ) || '209' == substr( $rsltcd, 0, 3 ) || '309' == substr( $rsltcd, 0, 3 ) ) { // エラー
			$log = array(
				'acting' => 'mizuho_conv',
				'key'    => $stran,
				'result' => $rsltcd,
				'data'   => wp_unslash( $_GET ),
			);
			usces_save_order_acting_error( $log );
			usces_log( 'mizuho conv : ' . print_r( wp_unslash( $_GET ), true ), 'acting_transaction.log' );
			header( 'location: ' . USCES_CART_URL . $delim . 'acting=mizuho_card&acting_return=0' );
		} else {
			header( 'location: ' . USCES_CART_URL . $delim . 'acting=mizuho_conv&acting_return=1&stran=' . $stran . '&mbtran=' . $mbtran . '&bktrans=' . wp_unslash( $_GET['bktrans'] ) . '&tranid=' . $tranid . '&rsltcd=' . $rsltcd );
		}
		die();
		*/

		/* AnotherLane credit */
	} elseif ( isset( $_REQUEST['SiteId'] ) && isset( $_REQUEST['TransactionId'] ) && isset( $_REQUEST['Result'] ) ) {
		$data = array();
		foreach ( $_REQUEST as $key => $value ) {
			$data[ $key ] = $value;
		}

		$acting_opts = $usces->options['acting_settings']['anotherlane'];
		if ( ! isset( $_REQUEST['KickType'] ) && isset( $_REQUEST['TransactionId'] ) ) {
			$permalink_structure = get_option( 'permalink_structure' );
			$delim               = ( ! $usces->use_ssl && $permalink_structure ) ? '?' : '&';
			if ( $acting_opts['siteid'] == wp_unslash( $_REQUEST['SiteId'] ) && 'NG' == wp_unslash( $_REQUEST['Result'] ) ) {
				$log = array(
					'acting' => 'anotherlane_card',
					'key'    => wp_unslash( $_REQUEST['TransactionId'] ),
					'result' => wp_unslash( $_REQUEST['Result'] ),
					'data'   => wp_unslash( $_REQUEST ),
				);
				usces_save_order_acting_error( $log );
				header( 'location: ' . USCES_CART_URL . $delim . 'confirm=1' );
				exit;
			} elseif ( $acting_opts['siteid'] == wp_unslash( $_REQUEST['SiteId'] ) && 'OK' == wp_unslash( $_REQUEST['Result'] ) ) {
				header( 'location: ' . USCES_CART_URL . $delim . 'acting=anotherlane_card&acting_return=1' );
				exit;
			}
		} elseif ( isset( $_REQUEST['KickType'] ) && $acting_opts['siteid'] == wp_unslash( $_GET['SiteId'] ) && 'OK' == wp_unslash( $_GET['Result'] ) ) {
			$table_meta_name = $wpdb->prefix . 'usces_order_meta';
			$query           = $wpdb->prepare( "SELECT order_id FROM $table_meta_name WHERE meta_key = %s AND meta_value = %s", 'TransactionId', $data['TransactionId'] );
			$order_id        = $wpdb->get_var( $query );
			if ( ! $order_id ) {
				$res = $usces->order_processing();
				if ( 'ordercompletion' == $res ) {
					$usces->set_order_meta_value( 'wc_trans_id', $data['TransactionId'], $order_id );
					usces_log( 'AnotherLane [OK] transaction : ' . $data['TransactionId'], 'acting_transaction.log' );
				} else {
					$log = array(
						'acting' => 'anotherlane_card',
						'key'    => $data['TransactionId'],
						'result' => 'ORDER DATA REGISTERED ERROR',
						'data'   => wp_unslash( $_GET ),
					);
					usces_save_order_acting_error( $log );
					usces_log( 'AnotherLane order processing error : ' . print_r( $data, true ), 'acting_transaction.log' );
				}
			}
		} else {
			$log = array(
				'acting' => 'anotherlane_card',
				'key'    => $data['TransactionId'],
				'result' => $data['Result'],
				'data'   => wp_unslash( $_GET ),
			);
			usces_save_order_acting_error( $log );
		}
		exit;
	}
}
