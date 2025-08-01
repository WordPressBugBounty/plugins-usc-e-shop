<?php
/**
 * Settlement Class.
 * PayPal
 *
 * @package  Welcart
 * @author   Collne Inc.
 * @version  1.1.0
 * @since    2.1.0
 */

/**
 * PayPal Commerce Platform
 */
class PAYPAL_CP_SETTLEMENT {

	const API_BN_CODE_PCP  = 'Welcart_Cart_PCP_JP';
	const API_BN_CODE_ACDC = 'Collne_Cart_ACDC_JP';
	const API_FRAUDNET_S   = 'Collne_T7SS625X3B32Q_RT';
	const API_URL          = 'https://api-m.paypal.com';
	const API_SANDBOX_URL  = 'https://api-m.sandbox.paypal.com';
	const API_TIMEOUT      = 30;

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * 決済代行会社ID
	 *
	 * @var string
	 */
	protected $paymod_id;

	/**
	 * 決済種別
	 *
	 * @var string
	 */
	protected $pay_method;

	/**
	 * 決済代行会社略称
	 *
	 * @var string
	 */
	protected $acting_name;

	/**
	 * 決済代行会社正式名称
	 *
	 * @var string
	 */
	protected $acting_formal_name;

	/**
	 * 決済代行会社URL
	 *
	 * @var string
	 */
	protected $acting_company_url;

	/**
	 * 併用不可決済モジュール
	 *
	 * @var array
	 */
	protected $unavailable_method;

	/**
	 * エラーメッセージ
	 *
	 * @var string
	 */
	protected $error_mes;

	/**
	 * 自動継続課金処理結果メール
	 *
	 * @var array
	 */
	protected $continuation_charging_mail;

	/**
	 * Construct.
	 */
	public function __construct() {

		$this->paymod_id          = 'paypal_cp';
		$this->pay_method         = array(
			'acting_paypal_cp',
			'acting_paypal_card',
		);
		$this->acting_name        = 'PayPal(CP)';
		$this->acting_formal_name = 'PayPal Commerce Platform';

		$this->initialize_data();

		add_action( 'init', array( $this, 'init' ), 20 );
		add_filter( 'http_headers_useragent', array( $this, 'http_headers_useragent' ), 10, 2 );

		if ( is_admin() ) {
			add_action( 'wp_ajax_onboarded', array( $this, 'onboarded' ) );
			add_action( 'usces_action_admin_ajax', array( $this, 'admin_ajax' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'admin_scripts' ) );
			add_action( 'usces_action_admin_settlement_update', array( $this, 'settlement_update' ) );
			add_action( 'usces_action_settlement_tab_title', array( $this, 'settlement_tab_title' ) );
			add_action( 'usces_action_settlement_tab_body', array( $this, 'settlement_tab_body' ) );
		}

		if ( $this->is_validity_acting() ) {
			add_action( 'wp_ajax_create_billing_agreement', array( $this, 'create_billing_agreement' ) );
			add_action( 'wp_ajax_nopriv_create_billing_agreement', array( $this, 'create_billing_agreement' ) );
			add_action( 'wp_ajax_create_order', array( $this, 'create_order' ) );
			add_action( 'wp_ajax_nopriv_create_order', array( $this, 'create_order' ) );
			if ( $this->is_validity_acting( 'card' ) ) {
				add_action( 'wp_ajax_create_order_card', array( $this, 'create_order_card' ) );
				add_action( 'wp_ajax_nopriv_create_order_card', array( $this, 'create_order_card' ) );
				add_action( 'wp_ajax_create_order_member', array( $this, 'create_order_member' ) );
				add_action( 'wp_ajax_nopriv_create_order_member', array( $this, 'create_order_member' ) );
				add_action( 'wp_ajax_show_order_details_card', array( $this, 'show_order_details_card' ) );
				add_action( 'wp_ajax_nopriv_show_order_details_card', array( $this, 'show_order_details_card' ) );
				add_filter( 'usces_filter_payments_str', array( $this, 'payments_str' ), 10, 2 );
				add_filter( 'usces_filter_payments_arr', array( $this, 'payments_arr' ), 10, 2 );
				add_filter( 'usces_filter_delivery_secure_form_loop', array( $this, 'delivery_secure_form_loop' ), 10, 2 );
				add_filter( 'usces_filter_delivery_check', array( $this, 'delivery_check' ), 15 );
				add_action( 'usces_action_confirm_page_point_inform', array( $this, 'e_point_inform' ), 10, 5 );
				add_filter( 'usces_filter_confirm_point_inform', array( $this, 'point_inform' ), 10, 5 );
				if ( defined( 'WCEX_COUPON' ) ) {
					add_filter( 'wccp_filter_coupon_inform', array( $this, 'point_inform' ), 10, 5 );
				}
				add_filter( 'usces_filter_get_error_settlement', array( $this, 'error_page_message' ) );
				add_filter( 'usces_filter_template_redirect', array( $this, 'member_update_settlement' ), 1 );
				add_action( 'usces_action_member_submenu_list', array( $this, 'e_update_settlement' ) );
				add_filter( 'usces_filter_member_submenu_list', array( $this, 'update_settlement' ), 10, 2 );
				add_action( 'usces_action_pre_delete_memberdata', array( $this, 'delete_member' ) );
			}
			add_filter( 'usces_filter_confirm_inform', array( $this, 'confirm_inform' ), 10, 5 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'wp_print_footer_scripts', array( $this, 'footer_scripts' ) );
			add_action( 'usces_action_acting_processing', array( $this, 'acting_processing' ), 10, 2 );
			add_filter( 'usces_filter_check_acting_return_results', array( $this, 'acting_return' ) );
			add_action( 'usces_action_reg_orderdata', array( $this, 'register_orderdata' ) );
			add_filter( 'usces_filter_is_complete_settlement', array( $this, 'is_complete_settlement' ), 10, 3 );
			add_filter( 'usces_filter_delete_member_check', array( $this, 'delete_member_check' ), 10, 2 );
			if ( is_admin() ) {
				add_filter( 'usces_filter_settle_info_field_meta_keys', array( $this, 'settlement_info_field_meta_keys' ) );
				add_filter( 'usces_filter_settle_info_field_keys', array( $this, 'settlement_info_field_keys' ), 10, 2 );
				add_filter( 'usces_filter_settle_info_field_value', array( $this, 'settlement_info_field_value' ), 10, 3 );
				add_filter( 'usces_filter_orderlist_detail_value', array( $this, 'orderlist_settlement_status' ), 10, 4 );
				add_action( 'usces_action_order_edit_form_status_block_middle', array( $this, 'settlement_status' ), 10, 3 );
				add_action( 'usces_action_order_edit_form_settle_info', array( $this, 'settlement_information' ), 10, 2 );
				add_action( 'usces_action_endof_order_edit_form', array( $this, 'settlement_dialog' ), 10, 2 );
				add_action( 'usces_action_admin_member_info', array( $this, 'admin_member_info' ), 10, 3 );
				add_action( 'usces_action_post_update_memberdata', array( $this, 'admin_update_memberdata' ), 10, 2 );
			}

			/* WCEX DL Seller */
			if ( defined( 'WCEX_DLSELLER' ) ) {
				add_filter( 'usces_filter_the_continue_payment_method', array( $this, 'continuation_payment_method' ) );
				add_filter( 'dlseller_action_reg_continuationdata', array( $this, 'register_continuationdata' ) );
				add_filter( 'dlseller_filter_first_charging', array( $this, 'first_charging_date' ), 9, 5 );
				add_filter( 'dlseller_filter_the_payment_method_restriction', array( $this, 'payment_method_restriction' ), 10, 2 );
				add_filter( 'dlseller_filter_continue_memberlist_column', array( $this, 'continue_memberlist_column' ), 10, 2 );
				add_filter( 'dlseller_filter_continue_memberlist_sql_jointable', array( $this, 'continue_memberlist_sql_jointable' ), 10, 2 );
				add_filter( 'dlseller_filter_continue_memberlist_sql_select', array( $this, 'continue_memberlist_sql_select' ), 10, 3 );
				add_filter( 'dlseller_filter_continue_member_list_condition', array( $this, 'continue_member_list_condition' ), 10, 4 );
				add_action( 'dlseller_action_continue_member_list_page', array( $this, 'continue_member_list_page' ) );
				add_action( 'dlseller_action_do_continuation_charging', array( $this, 'auto_continuation_charging' ), 10, 4 );
				add_action( 'dlseller_action_do_continuation', array( $this, 'do_auto_continuation' ), 10, 2 );
				add_filter( 'dlseller_filter_reminder_mail_body', array( $this, 'reminder_mail_body' ), 10, 3 );
				add_filter( 'dlseller_filter_contract_renewal_mail_body', array( $this, 'contract_renewal_mail_body' ), 10, 3 );
				add_filter( 'usces_filter_order_confirm_mail_payment', array( $this, 'continue_order_mail_payment' ), 11, 4 );
				add_filter( 'usces_filter_send_order_mail_payment', array( $this, 'continue_order_mail_payment' ), 11, 4 );
			}

			/* WCEX Auto Delivery */
			if ( defined( 'WCEX_AUTO_DELIVERY' ) ) {
				add_filter( 'wcad_action_reg_regulardata', array( $this, 'register_regulardata' ) );
				add_filter( 'wcad_filter_shippinglist_acting', array( $this, 'set_shippinglist_acting' ) );
				add_filter( 'wcad_filter_available_regular_payment_method', array( $this, 'available_regular_payment_method' ) );
				add_filter( 'wcad_filter_the_payment_method_restriction', array( $this, 'payment_method_restriction' ), 10, 2 );
				add_action( 'wcad_action_reg_auto_orderdata', array( $this, 'register_auto_orderdata' ) );
				add_filter( 'wcad_filter_send_settlement_error_mail_message_head', array( $this, 'settlement_error_mail_message_header' ), 10, 2 );
				if ( version_compare( WCEX_AUTO_DELIVERY_VERSION, '1.2.5', '>=' ) ) {
					add_filter( 'wcad_filter_send_settlement_error_mail_message', array( $this, 'settlement_error_mail_message' ), 10, 3 );
				}
				add_action( 'wcad_action_regular_information_edit_form', array( $this, 'regular_information_edit_form' ) );
				add_filter( 'usces_filter_order_confirm_mail_payment', array( $this, 'regular_order_mail_payment' ), 11, 4 );
				add_filter( 'usces_filter_send_order_mail_payment', array( $this, 'regular_order_mail_payment' ), 11, 4 );
				add_filter( 'wcad_filter_order_confirm_mail_payment', array( $this, 'regular_order_mail_payment' ), 11, 4 );
			}
		}
	}

	/**
	 * Return an instance of this class.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize.
	 */
	public function initialize_data() {
		$options = get_option( 'usces', array() );
		$options['acting_settings']['paypal_cp']['activate']           = ( isset( $options['acting_settings']['paypal_cp']['activate'] ) ) ? $options['acting_settings']['paypal_cp']['activate'] : 'off';
		$options['acting_settings']['paypal_cp']['cp_activate']        = ( isset( $options['acting_settings']['paypal_cp']['cp_activate'] ) ) ? $options['acting_settings']['paypal_cp']['cp_activate'] : 'off';
		$options['acting_settings']['paypal_cp']['card_activate']      = ( isset( $options['acting_settings']['paypal_cp']['card_activate'] ) ) ? $options['acting_settings']['paypal_cp']['card_activate'] : 'off';
		$options['acting_settings']['paypal_cp']['card_vault']         = ( isset( $options['acting_settings']['paypal_cp']['card_vault'] ) ) ? $options['acting_settings']['paypal_cp']['card_vault'] : 'off';
		$options['acting_settings']['paypal_cp']['card_prefix']        = ( isset( $options['acting_settings']['paypal_cp']['card_prefix'] ) ) ? $options['acting_settings']['paypal_cp']['card_prefix'] : '';
		$options['acting_settings']['paypal_cp']['card_3ds']           = ( isset( $options['acting_settings']['paypal_cp']['card_3ds'] ) ) ? $options['acting_settings']['paypal_cp']['card_3ds'] : 'off';
		$options['acting_settings']['paypal_cp']['environment']        = ( isset( $options['acting_settings']['paypal_cp']['environment'] ) ) ? $options['acting_settings']['paypal_cp']['environment'] : 'live';
		$options['acting_settings']['paypal_cp']['bncode']             = ( isset( $options['acting_settings']['paypal_cp']['bncode'] ) ) ? $options['acting_settings']['paypal_cp']['bncode'] : '';
		$options['acting_settings']['paypal_cp']['client_id']          = ( isset( $options['acting_settings']['paypal_cp']['client_id'] ) ) ? $options['acting_settings']['paypal_cp']['client_id'] : '';
		$options['acting_settings']['paypal_cp']['secret']             = ( isset( $options['acting_settings']['paypal_cp']['secret'] ) ) ? $options['acting_settings']['paypal_cp']['secret'] : '';
		$options['acting_settings']['paypal_cp']['intent']             = ( isset( $options['acting_settings']['paypal_cp']['intent'] ) ) ? $options['acting_settings']['paypal_cp']['intent'] : 'CAPTURE';
		$options['acting_settings']['paypal_cp']['autobilling_intent'] = ( isset( $options['acting_settings']['paypal_cp']['autobilling_intent'] ) ) ? $options['acting_settings']['paypal_cp']['autobilling_intent'] : 'CAPTURE';
		$options['acting_settings']['paypal_cp']['autobilling_email']  = ( isset( $options['acting_settings']['paypal_cp']['autobilling_email'] ) ) ? $options['acting_settings']['paypal_cp']['autobilling_email'] : 'off';
		$options['acting_settings']['paypal_cp']['button_layout']      = ( isset( $options['acting_settings']['paypal_cp']['button_layout'] ) ) ? $options['acting_settings']['paypal_cp']['button_layout'] : 'vertical';
		$options['acting_settings']['paypal_cp']['button_color']       = ( isset( $options['acting_settings']['paypal_cp']['button_color'] ) ) ? $options['acting_settings']['paypal_cp']['button_color'] : 'gold';
		$options['acting_settings']['paypal_cp']['button_shape']       = ( isset( $options['acting_settings']['paypal_cp']['button_shape'] ) ) ? $options['acting_settings']['paypal_cp']['button_shape'] : 'rect';
		$options['acting_settings']['paypal_cp']['button_label']       = ( isset( $options['acting_settings']['paypal_cp']['button_label'] ) ) ? $options['acting_settings']['paypal_cp']['button_label'] : 'paypal';
		$options['acting_settings']['paypal_cp']['agree']              = ( isset( $options['acting_settings']['paypal_cp']['agree'] ) ) ? $options['acting_settings']['paypal_cp']['agree'] : '';
		update_option( 'usces', $options );

		$available_settlement = get_option( 'usces_available_settlement', array() );
		if ( ! in_array( $this->paymod_id, $available_settlement ) ) {
			$available_settlement[ $this->paymod_id ] = $this->acting_formal_name;
			update_option( 'usces_available_settlement', $available_settlement );
		}

		$this->unavailable_method = array( 'acting_paypal_ec', 'acting_paypal_wpp' );
	}

	/**
	 * 決済有効判定
	 * 支払方法で使用している場合に true
	 *
	 * @param  string $type Module type.
	 * @return boolean
	 */
	public function is_validity_acting( $type = '' ) {
		$acting_opts = $this->get_acting_settings();
		if ( empty( $acting_opts ) ) {
			return false;
		}

		$payment_method = usces_get_system_option( 'usces_payment_method', 'sort' );
		$method         = false;

		switch ( $type ) {
			case 'cp':
				foreach ( $payment_method as $payment ) {
					if ( 'acting_paypal_cp' === $payment['settlement'] && 'activate' === $payment['use'] ) {
						$method = true;
						break;
					}
				}
				if ( $method && $this->is_activate_paypal_cp() ) {
					return true;
				} else {
					return false;
				}
			case 'card':
				foreach ( $payment_method as $payment ) {
					if ( 'acting_paypal_card' === $payment['settlement'] && 'activate' === $payment['use'] ) {
						$method = true;
						break;
					}
				}
				if ( $method && $this->is_activate_paypal_card() ) {
					return true;
				} else {
					return false;
				}
				break;
			default:
				if ( 'on' === $acting_opts['activate'] ) {
					return true;
				} else {
					return false;
				}
		}
	}

	/**
	 * 決済利用判定
	 * PayPal CP を「利用する」のとき true
	 *
	 * @return boolean
	 */
	public function is_activate_paypal_cp() {
		$acting_opts = $this->get_acting_settings();
		if ( ( isset( $acting_opts['activate'] ) && 'on' === $acting_opts['activate'] ) &&
			( isset( $acting_opts['cp_activate'] ) && 'on' === $acting_opts['cp_activate'] ) ) {
			$res = true;
		} else {
			$res = false;
		}
		return $res;
	}

	/**
	 * 決済利用判定
	 * PayPal カード決済を「利用する」のとき true
	 *
	 * @return boolean
	 */
	public function is_activate_paypal_card() {
		$acting_opts = $this->get_acting_settings();
		if ( ( isset( $acting_opts['activate'] ) && 'on' === $acting_opts['activate'] ) &&
			( isset( $acting_opts['card_activate'] ) && 'on' === $acting_opts['card_activate'] ) ) {
			$res = true;
		} else {
			$res = false;
		}
		return $res;
	}

	/**
	 * Resolve conflicts.
	 * init
	 */
	public function init() {
		global $usces;

		$payment_method = usces_get_system_option( 'usces_payment_method', 'sort' );
		foreach ( $payment_method as $payment ) {
			if ( 'acting_paypal_ec' === $payment['settlement'] && 'activate' === $payment['use'] ) {
				$payment['use'] = 'deactivate';
				usces_update_system_option( 'usces_payment_method', $payment['id'], $payment );
			} elseif ( 'acting_paypal_wpp' === $payment['settlement'] && 'activate' === $payment['use'] ) {
				$payment['use'] = 'deactivate';
				usces_update_system_option( 'usces_payment_method', $payment['id'], $payment );
			}
		}
	}

	/**
	 * Filters the user agent value sent with an HTTP request.
	 * http_headers_useragent
	 *
	 * @param string $user_agent WordPress user agent string.
	 * @param string $url        The request URL.
	 */
	public function http_headers_useragent( $user_agent, $url ) {
		if ( false !== strpos( $url, self::API_URL ) || false !== strpos( $url, self::API_SANDBOX_URL ) ) {
			$user_agent = 'Welcart/' . USCES_VERSION . '; ' . get_bloginfo( 'url' );
		}
		return $user_agent;
	}

	/**
	 * 管理画面スクリプト
	 * admin_print_footer_scripts
	 */
	public function admin_scripts() {
		global $usces, $usces_settings;

		$admin_page = ( isset( $_GET['page'] ) ) ? wp_unslash( $_GET['page'] ) : '';
		switch ( $admin_page ) :
			/* クレジット決済設定画面 */
			case 'usces_settlement':
				$settlement_selected = get_option( 'usces_settlement_selected', array() );
				if ( in_array( $this->paymod_id, (array) $settlement_selected ) ) :
					$acting_opts    = $this->get_acting_settings();
					$cp_activate    = ( isset( $acting_opts['cp_activate'] ) ) ? $acting_opts['cp_activate'] : 'off';
					$card_activate  = ( isset( $acting_opts['card_activate'] ) ) ? $acting_opts['card_activate'] : 'off';
					$cp_environment = ( isset( $acting_opts['environment'] ) ) ? $acting_opts['environment'] : 'live';
					?>
<script src="https://www.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js" id="paypal-js"></script>
<script type="text/javascript">
jQuery( document ).ready( function( $ ) {
	var card_activate = "<?php echo esc_js( $card_activate ); ?>";
	if ( "on" == card_activate ) {
		$( ".paypal_card_form" ).css( "display", "" );
	} else {
		$( ".paypal_card_form" ).css( "display", "none" );
	}
	$( document ).on( "change", ".activate_paypal_card", function() {
		if ( "on" == $( this ).val() ) {
			$( ".paypal_card_form" ).css( "display", "" );
		} else {
			$( ".paypal_card_form" ).css( "display", "none" );
		}
	});

	var environment_cp = "<?php echo esc_js( $cp_environment ); ?>";
	if ( "live" == environment_cp ) {
		$( "#upfront_onboarding_paypal_cp" ).prop( "disabled", false );
	} else {
		$( "#upfront_onboarding_paypal_cp" ).prop( "disabled", true );
	}
	$( document ).on( "change", ".cp_environment", function() {
		if ( "live" == $( this ).val() ) {
			$( "#upfront_onboarding_paypal_cp" ).prop( "disabled", false );
		} else {
			$( "#upfront_onboarding_paypal_cp" ).prop( "disabled", true );
		}
	});

	$( document ).on( "change", ".button_color_paypal_cp", function() {
		if ( "gold" == $( this ).val() ) {
			$( "#button_preview_paypal_cp" ).addClass( "color-gold" );
			$( "#button_preview_paypal_cp" ).removeClass( "color-blue" );
			$( "#button_preview_paypal_cp" ).removeClass( "color-silver" );
			$( "#button_preview_paypal_cp" ).removeClass( "color-white" );
			$( "#button_preview_paypal_cp" ).removeClass( "color-black" );
		} else if ( "blue" == $( this ).val() ) {
			$( "#button_preview_paypal_cp" ).removeClass( "color-gold" );
			$( "#button_preview_paypal_cp" ).addClass( "color-blue" );
			$( "#button_preview_paypal_cp" ).removeClass( "color-silver" );
			$( "#button_preview_paypal_cp" ).removeClass( "color-white" );
			$( "#button_preview_paypal_cp" ).removeClass( "color-black" );
		} else if ( "silver" == $( this ).val() ) {
			$( "#button_preview_paypal_cp" ).removeClass( "color-gold" );
			$( "#button_preview_paypal_cp" ).removeClass( "color-blue" );
			$( "#button_preview_paypal_cp" ).addClass( "color-silver" );
			$( "#button_preview_paypal_cp" ).removeClass( "color-white" );
			$( "#button_preview_paypal_cp" ).removeClass( "color-black" );
		} else if ( "white" == $( this ).val() ) {
			$( "#button_preview_paypal_cp" ).removeClass( "color-gold" );
			$( "#button_preview_paypal_cp" ).removeClass( "color-blue" );
			$( "#button_preview_paypal_cp" ).removeClass( "color-silver" );
			$( "#button_preview_paypal_cp" ).addClass( "color-white" );
			$( "#button_preview_paypal_cp" ).removeClass( "color-black" );
		} else if ( "black" == $( this ).val() ) {
			$( "#button_preview_paypal_cp" ).removeClass( "color-gold" );
			$( "#button_preview_paypal_cp" ).removeClass( "color-blue" );
			$( "#button_preview_paypal_cp" ).removeClass( "color-silver" );
			$( "#button_preview_paypal_cp" ).removeClass( "color-white" );
			$( "#button_preview_paypal_cp" ).addClass( "color-black" );
		}
	});

	$( document ).on( "change", ".button_shape_paypal_cp", function() {
		if ( "rect" == $( this ).val() ) {
			$( "#button_preview_paypal_cp" ).addClass( "shape-rect" );
			$( "#button_preview_paypal_cp" ).removeClass( "shape-pill" );
		} else if ( "pill" == $( this ).val() ) {
			$( "#button_preview_paypal_cp" ).removeClass( "shape-rect" );
			$( "#button_preview_paypal_cp" ).addClass( "shape-pill" );
		}
	});

	$( document ).on( "change", ".button_label_paypal_cp", function() {
		if ( "paypal" == $( this ).val() ) {
			$( "#button_preview_paypal_cp" ).addClass( "label-paypal" );
			$( "#button_preview_paypal_cp" ).removeClass( "label-checkout" );
			$( "#button_preview_paypal_cp" ).removeClass( "label-buynow" );
			$( "#button_preview_paypal_cp" ).removeClass( "label-pay" );
		} else if ( "checkout" == $( this ).val() ) {
			$( "#button_preview_paypal_cp" ).removeClass( "label-paypal" );
			$( "#button_preview_paypal_cp" ).addClass( "label-checkout" );
			$( "#button_preview_paypal_cp" ).removeClass( "label-buynow" );
			$( "#button_preview_paypal_cp" ).removeClass( "label-pay" );
		} else if ( "buynow" == $( this ).val() ) {
			$( "#button_preview_paypal_cp" ).removeClass( "label-paypal" );
			$( "#button_preview_paypal_cp" ).removeClass( "label-checkout" );
			$( "#button_preview_paypal_cp" ).addClass( "label-buynow" );
			$( "#button_preview_paypal_cp" ).removeClass( "label-pay" );
		} else if ( "pay" == $( this ).val() ) {
			$( "#button_preview_paypal_cp" ).removeClass( "label-paypal" );
			$( "#button_preview_paypal_cp" ).removeClass( "label-checkout" );
			$( "#button_preview_paypal_cp" ).removeClass( "label-buynow" );
			$( "#button_preview_paypal_cp" ).addClass( "label-pay" );
		}
	});

	$( document ).on( "click", "#upfront_onboarding_paypal_cp", function() {
		$.ajax({
			url: ajaxurl,
			type: "POST",
			cache: false,
			dataType: "json",
			data: {
				action: "usces_admin_ajax",
				mode: "upfront_onboarding",
				wc_nonce: $( "#wc_nonce" ).val()
			}
		}).done( function( retVal, dataType ) {
			var action_url = retVal.action_url;
			$( "#seller_nonce" ).val( retVal.seller_nonce );
			$( '#onboarding' ).attr( 'href', action_url + '&displayMode=minibrowser' );
			$( '#onboarding' )[0].click();
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			console.log( textStatus );
			console.log( jqXHR.status );
			console.log( errorThrown.message );
		});
		return false;
	});
});
</script>
					<?php
				endif;
				break;

			/* 受注編集画面・継続課金会員詳細画面 */
			case 'usces_orderlist':
			case 'usces_continue':
				$order_id        = '';
				$acting_flg      = '';
				$order_action    = ( isset( $_GET['order_action'] ) ) ? wp_unslash( $_GET['order_action'] ) : '';
				$continue_action = ( isset( $_GET['continue_action'] ) ) ? wp_unslash( $_GET['continue_action'] ) : '';
				if ( ( 'usces_orderlist' === $admin_page && ( 'edit' === $order_action || 'editpost' === $order_action || 'newpost' === $order_action ) ) ||
					( 'usces_continue' === $admin_page && 'settlement_paypal_cp' === $continue_action ) ) {
					$order_id = ( isset( $_REQUEST['order_id'] ) ) ? wp_unslash( $_REQUEST['order_id'] ) : '';
					if ( ! empty( $order_id ) ) {
						$acting_flg = $this->get_order_acting_flg( $order_id );
					}
				}
				if ( in_array( $acting_flg, $this->pay_method ) ) :
					$args = compact( 'order_id', 'acting_flg', 'admin_page' );
					$cr   = $usces->options['system']['currency'];
					if ( isset( $usces_settings['currency'][ $cr ] ) ) {
						list( $crcode, $decimal, $point, $seperator, $symbol ) = $usces_settings['currency'][ $cr ];
						if ( 'JPY' === $crcode ) {
							$currency = __( $crcode, 'usces' );
						} else {
							$currency = ( usces_is_entity( $symbol ) ) ? mb_convert_encoding( $symbol, 'UTF-8', 'HTML-ENTITIES' ) : $symbol;
						}
					} else {
						$currency = '';
					}
					if ( defined( 'WCEX_AUTO_DELIVERY' ) && 'acting_paypal_cp' === $acting_flg ) {
						$reg_id = $this->get_regular_id( $order_id );
					} else {
						$reg_id = '';
					}
					?>
<script type="text/javascript">
jQuery( document ).ready( function( $ ) {
	adminOrderEdit = {
		getPayPalCP : function() {
			$( "#settlement-response" ).html( "" );
			$( "#settlement-response-loading" ).html( '<img src="' + uscesL10n.USCES_PLUGIN_URL + '/images/loading.gif" />' );
			var order_num = $( "#order_num" ).val();
			$.ajax({
				url: ajaxurl,
				type: "POST",
				cache: false,
				dataType: "json",
				data: {
					action: "usces_admin_ajax",
					mode: "get_paypal_cp",
					order_id: $( "#order_id" ).val(),
					order_num: order_num,
					tracking_id: $( "#tracking_id" ).val(),
					member_id: $( "#member_id" ).val(),
					<?php if ( 'usces_continue' === $admin_page && 'acting_paypal_cp' === $acting_flg ) : ?>
					con_id: $( "#con_id" ).val(),
					<?php endif; ?>
					<?php if ( ! empty( $reg_id ) ) : ?>
					reg_id: $( "#reg_id" ).val(),
					<?php endif; ?>
					acting: $( "#acting" ).val(),
					wc_nonce: $( "#wc_nonce" ).val()
				}
			}).done( function( retVal, dataType ) {
				if ( retVal.result ) {
					$( "#settlement-response" ).html( retVal.result );
				}
				$( "#settlement-response-loading" ).html( "" );
			}).fail( function( jqXHR, textStatus, errorThrown ) {
				console.log( textStatus );
				console.log( jqXHR.status );
				console.log( errorThrown.message );
				$( "#settlement-response-loading" ).html( "" );
			});
			return false;
		},
		capturePayPalCP : function( amount ) {
			$( "#settlement-response" ).html( "" );
			$( "#settlement-response-loading" ).html( '<img src="' + uscesL10n.USCES_PLUGIN_URL + '/images/loading.gif" />' );
			var order_num = $( "#order_num" ).val();
			$.ajax({
				url: ajaxurl,
				type: "POST",
				cache: false,
				dataType: "json",
				data: {
					action: "usces_admin_ajax",
					mode: "capture_paypal_cp",
					order_id: $( "#order_id" ).val(),
					order_num: order_num,
					tracking_id: $( "#tracking_id" ).val(),
					member_id: $( "#member_id" ).val(),
					amount: amount,
					<?php if ( 'usces_continue' === $admin_page ) : ?>
					con_id: $( "#con_id" ).val(),
					<?php endif; ?>
					acting: $( "#acting" ).val(),
					wc_nonce: $( "#wc_nonce" ).val()
				}
			}).done( function( retVal, dataType ) {
				if ( retVal.result ) {
					$( "#settlement-response" ).html( retVal.result );
					$( "#settlement-status-" + order_num ).html( retVal.acting_status );
				}
				$( "#settlement-response-loading" ).html( "" );
			}).fail( function( jqXHR, textStatus, errorThrown ) {
				console.log( textStatus );
				console.log( jqXHR.status );
				console.log( errorThrown.message );
				$( "#settlement-response-loading" ).html( "" );
			});
			return false;
		},
		voidPayPalCP : function() {
			$( "#settlement-response" ).html( "" );
			$( "#settlement-response-loading" ).html( '<img src="' + uscesL10n.USCES_PLUGIN_URL + '/images/loading.gif" />' );
			var order_num = $( "#order_num" ).val();
			$.ajax({
				url: ajaxurl,
				type: "POST",
				cache: false,
				dataType: "json",
				data: {
					action: "usces_admin_ajax",
					mode: "void_paypal_cp",
					order_id: $( "#order_id" ).val(),
					order_num: order_num,
					tracking_id: $( "#tracking_id" ).val(),
					member_id: $( "#member_id" ).val(),
					<?php if ( 'usces_continue' === $admin_page ) : ?>
					con_id: $( "#con_id" ).val(),
					<?php endif; ?>
					acting: $( "#acting" ).val(),
					wc_nonce: $( "#wc_nonce" ).val()
				}
			}).done( function( retVal, dataType ) {
				if ( retVal.result ) {
					$( "#settlement-response" ).html( retVal.result );
					$( "#settlement-status-" + order_num ).html( retVal.acting_status );
				}
				$( "#settlement-response-loading" ).html( "" );
			}).fail( function( jqXHR, textStatus, errorThrown ) {
				console.log( textStatus );
				console.log( jqXHR.status );
				console.log( errorThrown.message );
				$( "#settlement-response-loading" ).html( "" );
			});
			return false;
		},
		refundPayPalCP : function( amount ) {
			$( "#settlement-response" ).html( "" );
			$( "#settlement-response-loading" ).html( '<img src="' + uscesL10n.USCES_PLUGIN_URL + '/images/loading.gif" />' );
			var order_num = $( "#order_num" ).val();
			$.ajax({
				url: ajaxurl,
				type: "POST",
				cache: false,
				dataType: "json",
				data: {
					action: "usces_admin_ajax",
					mode: "refund_paypal_cp",
					order_id: $( "#order_id" ).val(),
					order_num: order_num,
					tracking_id: $( "#tracking_id" ).val(),
					member_id: $( "#member_id" ).val(),
					amount: amount,
					<?php if ( 'usces_continue' === $admin_page ) : ?>
					con_id: $( "#con_id" ).val(),
					<?php endif; ?>
					acting: $( "#acting" ).val(),
					wc_nonce: $( "#wc_nonce" ).val()
				}
			}).done( function( retVal, dataType ) {
				if ( retVal.result ) {
					$( "#settlement-response" ).html( retVal.result );
					$( "#settlement-status-" + order_num ).html( retVal.acting_status );
				}
				$( "#settlement-response-loading" ).html( "" );
			}).fail( function( jqXHR, textStatus, errorThrown ) {
				console.log( textStatus );
				console.log( jqXHR.status );
				console.log( errorThrown.message );
				$( "#settlement-response-loading" ).html( "" );
			});
			return false;
		},
		reSettlementPayPalCP : function( amount, intent ) {
			$( "#settlement-response" ).html( "" );
			$( "#settlement-response-loading" ).html( '<img src="' + uscesL10n.USCES_PLUGIN_URL + '/images/loading.gif" />' );
			var order_num = $( "#order_num" ).val();
			$.ajax({
				url: ajaxurl,
				type: "POST",
				cache: false,
				dataType: "json",
				data: {
					action: "usces_admin_ajax",
					mode: "re_settlement_paypal_cp",
					order_id: $( "#order_id" ).val(),
					order_num: order_num,
					tracking_id: $( "#tracking_id" ).val(),
					member_id: $( "#member_id" ).val(),
					amount: amount,
					intent: intent,
					<?php if ( 'usces_continue' === $admin_page ) : ?>
					con_id: $( "#con_id" ).val(),
					<?php endif; ?>
					<?php if ( ! empty( $reg_id ) ) : ?>
					reg_id: $( "#reg_id" ).val(),
					<?php endif; ?>
					acting: $( "#acting" ).val(),
					wc_nonce: $( "#wc_nonce" ).val()
				}
			}).done( function( retVal, dataType ) {
				if ( retVal.result ) {
					$( "#settlement-response" ).html( retVal.result );
					$( "#settlement-status-" + order_num ).html( retVal.acting_status );
				}
				$( "#settlement-response-loading" ).html( "" );
			}).fail( function( jqXHR, textStatus, errorThrown ) {
				console.log( textStatus );
				console.log( jqXHR.status );
				console.log( errorThrown.message );
				$( "#settlement-response-loading" ).html( "" );
			});
			return false;
		},
	};

	$( "#settlement_dialog" ).dialog({
		dialogClass: "admin-paypal-dialog",
		bgiframe: true,
		autoOpen: false,
		height: "auto",
		width: 800,
		resizable: true,
		modal: true,
		buttons: {
			"<?php esc_html_e( 'Close' ); ?>": function() {
				$( this ).dialog( "close" );
			}
		},
		open: function() {
			adminOrderEdit.getPayPalCP();
		},
		close: function() {
					<?php do_action( 'usces_action_paypalcp_settlement_dialog_close', $args ); ?>
		}
	});

	$( document ).on( "click", ".settlement-information", function() {
		var tracking_id = $( this ).attr( "data-tracking_id" );
		var order_num = $( this ).attr( "data-num" );
		$( "#tracking_id" ).val( tracking_id );
		$( "#order_num" ).val( order_num );
		$( "#settlement_dialog" ).dialog( "option", "title", "<?php echo esc_js( __( 'PayPal Commerce Platform', 'usces' ) ); ?>" );
		$( "#settlement_dialog" ).dialog( "open" );
	});

	$( document ).on( "click", "#capture-settlement", function() {
		var amount_original = parseFloat( $( "#amount_original" ).val() ) || 0;
		var amount_refund = parseFloat( $( "#amount_refund" ).val() ) || 0;
		var amount_capture = amount_original - amount_refund;
		if ( 0 == amount_capture ) {
			return;
		}
		if ( amount_capture == amount_original ) {
			if ( ! confirm( "<?php esc_html_e( 'Execute the capture payment process. Are you sure?', 'usces' ); ?>" ) ) {
				return;
			}
		} else {
			if ( ! confirm( <?php printf( __( "'Execute the refund payment process for %s' + amount_refund + '. Are you sure?'", 'usces' ), $currency ); ?> ) ) {
				return;
			}
		}
		adminOrderEdit.capturePayPalCP( amount_capture );
	});

	$( document ).on( "click", "#void-settlement", function() {
		if ( ! confirm( "<?php esc_html_e( 'Execute the void payment process. Are you sure?', 'usces' ); ?>" ) ) {
			return;
		}
		adminOrderEdit.voidPayPalCP();
	});

	$( document ).on( "click", "#refund-settlement", function() {
		var amount_original = parseFloat( $( "#amount_original" ).val() ) || 0;
		var amount_refund = parseFloat( $( "#amount_refund" ).val() ) || 0;
		if ( 0 == amount_refund ) {
			return;
		}
		if ( amount_refund > amount_original ) {
			alert( "<?php esc_html_e( 'Amounts in excess of the transaction amount are not refundable.', 'usces' ); ?>" );
			return;
		}
		if ( amount_refund == amount_original ) {
			if ( ! confirm( "<?php esc_html_e( 'Execute the refund payment process. Are you sure?', 'usces' ); ?>" ) ) {
				return;
			}
		} else {
			if ( ! confirm( <?php printf( __( "'Execute the refund payment process for %s' + amount_refund + '. Are you sure?'", 'usces' ), $currency ); ?> ) ) {
				return;
			}
		}
		adminOrderEdit.refundPayPalCP( amount_refund );
	});

	$( document ).on( "click", "#re-authorize-settlement", function() {
		var amount_authorize = parseFloat( $( "#amount_resettlement" ).val() ) || 0;
		if ( 0 == amount_authorize ) {
			return;
		}
		if ( ! confirm( <?php printf( __( "'Execute the void payment process for %s' + amount_authorize + '. Are you sure?'", 'usces' ), $currency ); ?> ) ) {
			return;
		}
		adminOrderEdit.reSettlementPayPalCP( amount_authorize, 'AUTHORIZE' );
	});

	$( document ).on( "click", "#re-capture-settlement", function() {
		var amount_capture = parseFloat( $( "#amount_resettlement" ).val() ) || 0.0;
		if ( 0 == amount_capture ) {
			return;
		}
		if ( ! confirm( <?php printf( __( "'Execute the capture payment process for %s' + amount_capture + '. Are you sure?'", 'usces' ), $currency ); ?> ) ) {
			return;
		}
		adminOrderEdit.reSettlementPayPalCP( amount_capture, 'CAPTURE' );
	});

	$( document ).on( "keydown", ".amount", function( e ) {
		var halfVal = $( this ).val().replace( /[！-～]/g,
			function( tmpStr ) {
				return String.fromCharCode( tmpStr.charCodeAt(0) - 0xFEE0 );
			}
		);
		$( this ).val( halfVal.replace( /[^0-9]/g, '' ) );
	});
	$( document ).on( "keyup", ".amount", function() {
		this.value = this.value.replace( /[^0-9]+/i, '' );
		this.value = Number( this.value ) || 0;
	});
	$( document ).on( "blur", ".amount", function() {
		this.value = this.value.replace( /[^0-9]+/i, '' );
	});
					<?php if ( 'usces_continue' === $admin_page ) : ?>
	adminContinuation = {
		update : function() {
			$.ajax({
				url: ajaxurl,
				type: "POST",
				cache: false,
				dataType: 'json',
				data: {
					action: "usces_admin_ajax",
					mode: "continuation_update",
					member_id: $( "#member_id" ).val(),
					order_id: $( "#order_id" ).val(),
					contracted_year: $( "#contracted-year option:selected" ).val(),
					contracted_month: $( "#contracted-month option:selected" ).val(),
					contracted_day: $( "#contracted-day option:selected" ).val(),
					charged_year: $( "#charged-year option:selected" ).val(),
					charged_month: $( "#charged-month option:selected" ).val(),
					charged_day: $( "#charged-day option:selected" ).val(),
					price: $( "#price" ).val(),
					status: $( "#dlseller-status" ).val(),
					wc_nonce: $( "#wc_nonce" ).val()
				}
			}).done( function( retVal, dataType ) {
				if ( "OK" == retVal.status ) {
					adminOperation.setActionStatus( "success", "<?php esc_html_e( 'The update was completed.', 'usces' ); ?>" );
				} else {
					var message = ( retVal.message != "" ) ? retVal.message : "<?php esc_html_e( 'failure in update', 'usces' ); ?>";
					adminOperation.setActionStatus( "error", message );
				}
			}).fail( function( jqXHR, textStatus, errorThrown ) {
				console.log( textStatus );
				console.log( jqXHR.status );
				console.log( errorThrown.message );
				adminOperation.setActionStatus( "error", "<?php esc_html_e( 'failure in update', 'usces' ); ?>" );
			});
			return false;
		}
	};

	$( document ).on( "click", "#continuation-update", function() {
		var status = $( "#dlseller-status option:selected" ).val();
		if ( "continuation" == status ) {
			var year = $( "#charged-year option:selected" ).val();
			var month = $( "#charged-month option:selected" ).val();
			var day = $( "#charged-day option:selected" ).val();
			if ( 0 == year || 0 == month || 0 == day ) {
				alert( "<?php esc_html_e( 'Data have deficiency.', 'usces' ); ?>" );
				$( "#charged-year" ).focus();
				return;
			}
			if ( "" == $( "#price" ).val() || 0 == parseFloat( $( "#price" ).val() ) ) {
				alert( "<?php printf( __( 'Input the %s', 'usces' ), esc_html__( 'Amount', 'dlseller' ) ); ?>" );
				$( "#price" ).focus();
				return;
			}
		}
		if ( ! confirm( "<?php esc_html_e( 'Are you sure you want to update the settings?', 'usces' ); ?>" ) ) {
			return;
		}
		adminContinuation.update();
	});
					<?php endif; ?>
});
</script>
					<?php
				endif;
				break;
		endswitch;
	}

	/**
	 * 決済オプション登録・更新
	 * usces_action_admin_settlement_update
	 */
	public function settlement_update() {
		global $usces;

		if ( $this->paymod_id != wp_unslash( $_POST['acting'] ) ) {
			return;
		}

		$this->error_mes = '';
		$options         = get_option( 'usces', array() );
		$payment_method  = usces_get_system_option( 'usces_payment_method', 'settlement' );
		$post_data       = wp_unslash( $_POST );

		unset( $options['acting_settings']['paypal_cp'] );
		$options['acting_settings']['paypal_cp']['cp_activate']        = ( isset( $post_data['cp_activate'] ) ) ? $post_data['cp_activate'] : 'off';
		$options['acting_settings']['paypal_cp']['card_activate']      = ( isset( $post_data['card_activate'] ) ) ? $post_data['card_activate'] : 'off';
		$options['acting_settings']['paypal_cp']['card_vault']         = ( isset( $post_data['card_vault'] ) ) ? $post_data['card_vault'] : 'off';
		$options['acting_settings']['paypal_cp']['card_prefix']        = ( isset( $post_data['card_prefix'] ) ) ? $post_data['card_prefix'] : '';
		$options['acting_settings']['paypal_cp']['card_3ds']           = ( isset( $post_data['card_3ds'] ) ) ? $post_data['card_3ds'] : 'off';
		$options['acting_settings']['paypal_cp']['environment']        = ( isset( $post_data['cp_environment'] ) ) ? $post_data['cp_environment'] : 'live';
		$options['acting_settings']['paypal_cp']['client_id']          = ( isset( $post_data['cp_client_id'] ) ) ? trim( $post_data['cp_client_id'] ) : '';
		$options['acting_settings']['paypal_cp']['secret']             = ( isset( $post_data['cp_secret'] ) ) ? trim( $post_data['cp_secret'] ) : '';
		$options['acting_settings']['paypal_cp']['intent']             = ( isset( $post_data['cp_intent'] ) ) ? $post_data['cp_intent'] : 'CAPTURE';
		$options['acting_settings']['paypal_cp']['autobilling_intent'] = ( isset( $post_data['cp_autobilling_intent'] ) ) ? $post_data['cp_autobilling_intent'] : 'CAPTURE';
		$options['acting_settings']['paypal_cp']['autobilling_email']  = ( isset( $post_data['cp_autobilling_email'] ) ) ? $post_data['cp_autobilling_email'] : 'off';
		$options['acting_settings']['paypal_cp']['button_layout']      = ( isset( $post_data['cp_button_layout'] ) ) ? $post_data['cp_button_layout'] : 'vertical';
		$options['acting_settings']['paypal_cp']['button_color']       = ( isset( $post_data['cp_button_color'] ) ) ? $post_data['cp_button_color'] : 'gold';
		$options['acting_settings']['paypal_cp']['button_shape']       = ( isset( $post_data['cp_button_shape'] ) ) ? $post_data['cp_button_shape'] : 'rect';
		$options['acting_settings']['paypal_cp']['button_label']       = ( isset( $post_data['cp_button_label'] ) ) ? $post_data['cp_button_label'] : 'paypal';
		$options['acting_settings']['paypal_cp']['agree']              = ( isset( $post_data['cp_agree'] ) ) ? $post_data['cp_agree'] : '';

		if ( 'on' === $options['acting_settings']['paypal_cp']['cp_activate'] || 'on' === $options['acting_settings']['paypal_cp']['card_activate'] ) {
			$unavailable_activate = false;
			foreach ( $payment_method as $settlement => $payment ) {
				if ( in_array( $settlement, $this->unavailable_method ) && 'deactivate' !== $payment['use'] ) {
					$unavailable_activate = true;
					break;
				}
			}

			if ( $unavailable_activate ) {
				$this->error_mes .= __( '* Settlement that can not be used together is activated.', 'usces' ) . '<br />';
			} else {
				if ( WCUtils::is_blank( $post_data['cp_client_id'] ) ) {
					$this->error_mes .= __( '* Enter Client ID.', 'usces' ) . '<br />';
				}
				if ( WCUtils::is_blank( $post_data['cp_secret'] ) ) {
					$this->error_mes .= __( '* Enter Secret.', 'usces' ) . '<br />';
				}
				if ( 'on' === $options['acting_settings']['paypal_cp']['card_activate'] && 'on' === $options['acting_settings']['paypal_cp']['card_vault'] ) {
					if ( ! $this->check_prefix( $options['acting_settings']['paypal_cp']['card_prefix'] ) ) {
						$this->error_mes .= __( '* The prefix must be at least one and no more than four single-byte uppercase alphabetic characters.', 'usces' ) . '<br />';
					}
				}
				if ( empty( $options['acting_settings']['paypal_cp']['agree'] ) ) {
					$this->error_mes .= __( '* There was no agreement on terms of use.', 'usces' ) . '<br />';
				}
			}
		}

		if ( '' === $this->error_mes ) {
			$usces->action_status  = 'success';
			$usces->action_message = __( 'Options are updated.', 'usces' );
			if ( 'on' === $options['acting_settings']['paypal_cp']['cp_activate'] || 'on' === $options['acting_settings']['paypal_cp']['card_activate'] ) {
				$options['acting_settings']['paypal_cp']['activate'] = 'on';
				if ( 'live' === $options['acting_settings']['paypal_cp']['environment'] ) {
					$options['acting_settings']['paypal_cp']['api_request_url'] = self::API_URL;
				} else {
					$options['acting_settings']['paypal_cp']['api_request_url'] = self::API_SANDBOX_URL;
				}
				$toactive = array();
				if ( 'on' === $options['acting_settings']['paypal_cp']['cp_activate'] ) {
					$usces->payment_structure['acting_paypal_cp'] = $this->acting_name;
					foreach ( $payment_method as $settlement => $payment ) {
						if ( 'acting_paypal_cp' === $settlement && 'activate' !== $payment['use'] ) {
							$toactive[] = $payment['name'];
						}
					}
				}
				if ( 'on' === $options['acting_settings']['paypal_cp']['card_activate'] ) {
					$usces->payment_structure['acting_paypal_card'] = __( 'PayPal(CreditCard)', 'usces' );
					foreach ( $payment_method as $settlement => $payment ) {
						if ( 'acting_paypal_card' === $settlement && 'activate' !== $payment['use'] ) {
							$toactive[] = $payment['name'];
						}
					}
				}
				if ( 0 < count( $toactive ) ) {
					$usces->action_message .= __( 'Please update the payment method to "Activate". <a href="admin.php?page=usces_initial#payment_method_setting">General Setting > Payment Methods</a>', 'usces' );
				}
				usces_admin_orderlist_show_wc_trans_id();
				$this->setting_update_notification( $options );
			} else {
				$options['acting_settings']['paypal_cp']['activate'] = 'off';
				unset( $usces->payment_structure['acting_paypal_cp'] );
				unset( $usces->payment_structure['acting_paypal_card'] );
				$deactivate = array();
				foreach ( $payment_method as $settlement => $payment ) {
					if ( ! array_key_exists( $settlement, $usces->payment_structure ) ) {
						if ( 'deactivate' !== $payment['use'] ) {
							$payment['use'] = 'deactivate';
							$deactivate[]   = $payment['name'];
							usces_update_system_option( 'usces_payment_method', $payment['id'], $payment );
						}
					}
				}
				if ( 0 < count( $deactivate ) ) {
					$deactivate_message     = sprintf( __( '"Deactivate" %s of payment method.', 'usces' ), implode( ',', $deactivate ) );
					$usces->action_message .= $deactivate_message;
				}
			}
		} else {
			$usces->action_status  = 'error';
			$usces->action_message = __( 'Data have deficiency.', 'usces' );
			$options               = get_option( 'usces', array() );
			$options['acting_settings']['paypal_cp']['activate'] = 'off';
			$options['acting_settings']['paypal_cp']['agree']    = '';
			unset( $usces->payment_structure['acting_paypal_cp'] );
			unset( $usces->payment_structure['acting_paypal_card'] );
			$deactivate = array();
			foreach ( $payment_method as $settlement => $payment ) {
				if ( in_array( $settlement, $this->pay_method ) ) {
					if ( 'deactivate' !== $payment['use'] ) {
						$payment['use'] = 'deactivate';
						$deactivate[]   = $payment['name'];
						usces_update_system_option( 'usces_payment_method', $payment['id'], $payment );
					}
				}
			}
			if ( 0 < count( $deactivate ) ) {
				$deactivate_message     = sprintf( __( '"Deactivate" %s of payment method.', 'usces' ), implode( ',', $deactivate ) );
				$usces->action_message .= $deactivate_message . __( 'Please complete the setup and update the payment method to "Activate".', 'usces' );
			}
		}
		ksort( $usces->payment_structure );
		update_option( 'usces', $options );
		update_option( 'usces_payment_structure', $usces->payment_structure );
	}

	/**
	 * 決済設定通知
	 *
	 * @param array $options Setting values.
	 */
	private function setting_update_notification( $options ) {
		$message         = 'サイト名/屋号：' . get_option( 'blogname' ) . "\n" .
			'サイトURL：' . esc_url( home_url( '/' ) ) . "\n" .
			'Eメールアドレス：' . get_option( 'admin_email' ) . "\n" .
			'動作環境：' . $options['acting_settings']['paypal_cp']['environment'] . "\n";
		$sendmail_params = array(
			'to_name'      => 'PayPal',
			'to_address'   => 'welcart-setting-update@paypal.com',
			'from_name'    => get_option( 'blogname' ),
			'from_address' => get_option( 'admin_email' ),
			'reply_name'   => get_option( 'blogname' ),
			'reply_to'     => usces_get_first_order_mail(),
			'return_path'  => get_option( 'admin_email' ),
			'subject'      => '[Welcart]PayPal決済設定通知',
			'message'      => $message,
		);
		usces_send_mail( $sendmail_params );
	}

	/**
	 * クレジット決済設定画面タブ
	 * usces_action_settlement_tab_title
	 */
	public function settlement_tab_title() {
		$settlement_selected = get_option( 'usces_settlement_selected', array() );
		if ( in_array( $this->paymod_id, (array) $settlement_selected ) ) {
			echo '<li><a href="#uscestabs_' . esc_html( $this->paymod_id ) . '">' . esc_html( $this->acting_name ) . '</a></li>';
		}
	}

	/**
	 * クレジット決済設定画面フォーム
	 * usces_action_settlement_tab_body
	 */
	public function settlement_tab_body() {
		global $usces;

		$acting_opts         = $this->get_acting_settings();
		$settlement_selected = get_option( 'usces_settlement_selected', array() );
		if ( in_array( $this->paymod_id, (array) $settlement_selected ) ) :
			$cp_activate    = ( isset( $acting_opts['cp_activate'] ) && 'on' === $acting_opts['cp_activate'] ) ? 'on' : 'off';
			$card_activate  = ( isset( $acting_opts['card_activate'] ) && 'on' === $acting_opts['card_activate'] ) ? 'on' : 'off';
			$card_vault     = ( isset( $acting_opts['card_vault'] ) && 'on' === $acting_opts['card_vault'] ) ? 'on' : 'off';
			$card_prefix    = ( isset( $acting_opts['card_prefix'] ) ) ? $acting_opts['card_prefix'] : '';
			$card_3ds       = ( isset( $acting_opts['card_3ds'] ) && 'on' === $acting_opts['card_3ds'] ) ? 'on' : 'off';
			$cp_environment = ( isset( $acting_opts['environment'] ) && 'live' === $acting_opts['environment'] ) ? 'live' : 'sandbox';
			$cp_client_id   = ( isset( $acting_opts['client_id'] ) ) ? $acting_opts['client_id'] : '';
			$cp_secret      = ( isset( $acting_opts['secret'] ) ) ? $acting_opts['secret'] : '';
			$cp_intent      = ( isset( $acting_opts['intent'] ) ) ? $acting_opts['intent'] : '';
			if ( defined( 'WCEX_DLSELLER' ) ) {
				$cp_autobilling_intent = ( isset( $acting_opts['autobilling_intent'] ) ) ? $acting_opts['autobilling_intent'] : '';
				$cp_autobilling_email  = ( isset( $acting_opts['autobilling_email'] ) && 'on' === $acting_opts['autobilling_email'] ) ? 'on' : 'off';
			}
			$cp_button_layout       = ( isset( $acting_opts['button_layout'] ) && 'horizontal' === $acting_opts['button_layout'] ) ? 'horizontal' : 'vertical';
			$class_button_color     = '';
			$cp_button_color_gold   = '';
			$cp_button_color_blue   = '';
			$cp_button_color_silver = '';
			$cp_button_color_white  = '';
			$cp_button_color_black  = '';
			if ( isset( $acting_opts['button_color'] ) && 'blue' === $acting_opts['button_color'] ) {
				$cp_button_color_blue = ' checked="checked"';
				$class_button_color   = ' color-blue';
			} elseif ( isset( $acting_opts['button_color'] ) && 'silver' === $acting_opts['button_color'] ) {
				$cp_button_color_silver = ' checked="checked"';
				$class_button_color     = ' color-silver';
			} elseif ( isset( $acting_opts['button_color'] ) && 'white' === $acting_opts['button_color'] ) {
				$cp_button_color_white = ' checked="checked"';
				$class_button_color    = ' color-white';
			} elseif ( isset( $acting_opts['button_color'] ) && 'black' === $acting_opts['button_color'] ) {
				$cp_button_color_black = ' checked="checked"';
				$class_button_color    = ' color-black';
			} else {
				$cp_button_color_gold = ' checked="checked"'; /* default */
				$class_button_color   = ' color-gold';
			}
			$class_button_shape   = '';
			$cp_button_shape_rect = '';
			$cp_button_shape_pill = '';
			if ( isset( $acting_opts['button_shape'] ) && 'pill' === $acting_opts['button_shape'] ) {
				$cp_button_shape_pill = ' checked="checked"';
				$class_button_shape   = ' shape-pill';
			} else {
				$cp_button_shape_rect = ' checked="checked"'; /* default */
				$class_button_shape   = ' shape-rect';
			}
			$class_button_label       = '';
			$cp_button_label_paypal   = '';
			$cp_button_label_checkout = '';
			$cp_button_label_buynow   = '';
			$cp_button_label_pay      = '';
			if ( isset( $acting_opts['button_label'] ) && 'checkout' === $acting_opts['button_label'] ) {
				$cp_button_label_checkout = ' checked="checked"';
				$class_button_label       = ' label-checkout';
			} elseif ( isset( $acting_opts['button_label'] ) && 'buynow' === $acting_opts['button_label'] ) {
				$cp_button_label_buynow = ' checked="checked"';
				$class_button_label     = ' label-buynow';
			} elseif ( isset( $acting_opts['button_label'] ) && 'pay' === $acting_opts['button_label'] ) {
				$cp_button_label_pay = ' checked="checked"';
				$class_button_label  = ' label-pay';
			} else {
				$cp_button_label_paypal = ' checked="checked"'; /* default */
				$class_button_label     = ' label-paypal';
			}
			$cp_agree = ( isset( $acting_opts['agree'] ) && 'agree' === $acting_opts['agree'] ) ? 'agree' : '';
			?>
	<div id="uscestabs_paypal_cp">
	<div class="settlement_service"><span class="service_title"><?php esc_html_e( 'PayPal Commerce Platform', 'usces' ); ?></span></div>
			<?php
			if ( isset( $_POST['acting'] ) && $this->paymod_id === wp_unslash( $_POST['acting'] ) ) :
				if ( ! empty( $this->error_mes ) ) :
					?>
		<div class="error_message"><?php wel_esc_script_e( $this->error_mes ); ?></div>
					<?php
				elseif ( isset( $acting_opts['activate'] ) && 'on' === $acting_opts['activate'] ) :
					?>
		<div class="message"><?php esc_html_e( 'Test thoroughly before use.', 'usces' ); ?></div>
					<?php
				endif;
			endif;
			?>
	<form action="" method="post" name="paypal_cp_form" id="paypal_cp_form">
		<table class="settle_table">
			<tr>
				<th><?php esc_html_e( 'PayPal Commerce Platform', 'usces' ); ?></th>
				<td><label><input type="radio" class="activate_paypal_cp" name="cp_activate" value="on"<?php checked( $cp_activate, 'on' ); ?>/><span><?php esc_html_e( 'Use', 'usces' ); ?></span></label><br />
					<label><input type="radio" class="activate_paypal_cp" name="cp_activate" value="off"<?php checked( $cp_activate, 'off' ); ?>/><span><?php esc_html_e( 'Do not Use', 'usces' ); ?></span></label>
				</td>
			</tr>
			<tr>
				<th><a class="explanation-label" id="label_ex_card_activate_paypal_cp"><?php esc_html_e( 'Credit card settlement', 'usces' ); ?></a></th>
				<td><label><input type="radio" class="activate_paypal_card" name="card_activate" value="on"<?php checked( $card_activate, 'on' ); ?>/><span><?php esc_html_e( 'Use', 'usces' ); ?></span></label><br />
					<label><input type="radio" class="activate_paypal_card" name="card_activate" value="off"<?php checked( $card_activate, 'off' ); ?>/><span><?php esc_html_e( 'Do not Use', 'usces' ); ?></span></label>
				</td>
			</tr>
			<tr id="ex_card_activate_paypal_cp" class="explanation"><td colspan="2"><?php esc_html_e( 'Advanced credit and debit cards requires that your business account be evaluated and approved by PayPal.', 'usces' ); ?></td></tr>
			<tr class="paypal_card_form">
				<th><a class="explanation-label" id="label_ex_card_vault_paypal_cp"><?php esc_html_e( 'Save credit card information', 'usces' ); ?></a></th>
				<td><label><input type="radio" name="card_vault" value="on"<?php checked( $card_vault, 'on' ); ?>/><span><?php esc_html_e( 'Save', 'usces' ); ?></span></label>
					<label><input type="text" name="card_prefix" value="<?php echo esc_attr( $card_prefix ); ?>" class="small-text"><span><?php esc_html_e( '* Prefix', 'usces' ); ?></span></label><br />
					<label><input type="radio" name="card_vault" value="off"<?php checked( $card_vault, 'off' ); ?>/><span><?php esc_html_e( 'Not save', 'usces' ); ?></span></label>
				</td>
			</tr>
			<tr id="ex_card_vault_paypal_cp" class="explanation paypal_card_form"><td colspan="2"><?php esc_html_e( 'To use this feature, you need to apply for a Client ID with PayPal.', 'usces' ); ?><br /><?php esc_html_e( 'Also need to use the Welcart membership system.', 'usces' ); ?></td></tr>
			<tr class="paypal_card_form">
				<th><?php esc_html_e( '3D Secure', 'usces' ); ?></th>
				<td><label><input type="radio" name="card_3ds" value="on"<?php checked( $card_3ds, 'on' ); ?>/><span><?php esc_html_e( 'Use', 'usces' ); ?></span></label><br />
					<label><input type="radio" name="card_3ds" value="off"<?php checked( $card_3ds, 'off' ); ?>/><span><?php esc_html_e( 'Do not Use', 'usces' ); ?></span></label>
				</td>
			</tr>
			<tr class="paypal_cp_form">
				<th><a class="explanation-label" id="label_ex_environment_paypal_cp"><?php esc_html_e( 'Operation Environment', 'usces' ); ?></a></th>
				<td><div><label><input type="radio" class="cp_environment" name="cp_environment" value="live"<?php checked( $cp_environment, 'live' ); ?>/><span><?php esc_html_e( 'Live environment', 'usces' ); ?></span></label>
						<script>
							function onboardedCallback( authCode, sharedId ) {
								let params = new URLSearchParams();
								params.append( "action", "onboarded" );
								params.append( "authCode", authCode );
								params.append( "sharedId", sharedId );
								params.append( "seller_nonce", document.getElementById( "seller_nonce" ).value );
								return fetch( ajaxurl, {
									method: 'POST',
									body: params
								}).then( function( res ) {
									return res.json();
								}).then( function( data ) {
									if ( data.client_id ) {
										document.getElementById( "cp_client_id" ).value = data.client_id;
									}
									if ( data.client_secret ) {
										document.getElementById( "cp_secret" ).value = data.client_secret;
									}
								});
							}
						</script>
						<a target="_blank" data-paypal-onboard-complete="onboardedCallback" href="" data-paypal-button="true" id="onboarding"></a>
						<input type="hidden" id="seller_nonce">
						<button type="button" id="upfront_onboarding_paypal_cp" value="upfront_onboarding" class="button"><?php esc_html_e( 'Sign up for PayPal', 'usces' ); ?></button>
					</div>
					<label><input type="radio" class="cp_environment" name="cp_environment" value="sandbox"<?php checked( $cp_environment, 'sandbox' ); ?>/><span><?php esc_html_e( 'Test environment (Sandbox)', 'usces' ); ?></span></label>
				</td>
			</tr>
			<tr id="ex_environment_paypal_cp" class="explanation paypal_cp_form"><td colspan="2"><?php esc_html_e( 'Choose if to use PayPal Commerce Platform.', 'usces' ); ?></td></tr>
			<tr class="paypal_cp_form">
				<th><?php esc_html_e( 'Client ID', 'usces' ); ?></th>
				<td><textarea name="cp_client_id" id="cp_client_id" class="regular-text code"><?php echo esc_attr( $cp_client_id ); ?></textarea></td>
			</tr>
			<tr class="paypal_cp_form">
				<th><?php esc_html_e( 'Secret', 'usces' ); ?></th>
				<td><textarea name="cp_secret" id="cp_secret" class="regular-text code"><?php echo esc_attr( $cp_secret ); ?></textarea></td>
			</tr>
			<tr class="paypal_cp_form">
				<th><a class="explanation-label" id="label_ex_intent_paypal_cp"><?php esc_html_e( 'Intent', 'usces' ); ?></a></th>
				<td><label><input type="radio" name="cp_intent" value="CAPTURE"<?php checked( $cp_intent, 'CAPTURE' ); ?>/><span><?php esc_html_e( 'Capture', 'usces' ); ?></span></label><br />
					<label><input type="radio" name="cp_intent" value="AUTHORIZE"<?php checked( $cp_intent, 'AUTHORIZE' ); ?>/><span><?php esc_html_e( 'Authorize', 'usces' ); ?></span></label>
				</td>
			</tr>
			<tr id="ex_intent_paypal_cp" class="explanation paypal_cp_form"><td colspan="2"><?php esc_html_e( 'The intent to either capture payment immediately or authorize a payment for an order after order creation.', 'usces' ); ?></td></tr>
			<?php if ( defined( 'WCEX_DLSELLER' ) ) : ?>
			<tr class="paypal_cp_form">
				<th><a class="explanation-label" id="label_ex_autobilling_intent_cp"><?php esc_html_e( 'Automatic recurring billing intent', 'usces' ); ?></a></th>
				<td><label><input type="radio" name="cp_autobilling_intent" value="CAPTURE"<?php checked( $cp_autobilling_intent, 'CAPTURE' ); ?>/><span><?php esc_html_e( 'Capture', 'usces' ); ?></span></label><br />
					<label><input type="radio" name="cp_autobilling_intent" value="AUTHORIZE"<?php checked( $cp_autobilling_intent, 'AUTHORIZE' ); ?>/><span><?php esc_html_e( 'Authorize', 'usces' ); ?></span></label>
				</td>
			</tr>
			<tr id="ex_autobilling_intent_cp" class="explanation paypal_cp_form"><td colspan="2"><?php esc_html_e( 'Processing classification when automatic continuing charging (required WCEX DLSeller).', 'usces' ); ?></td></tr>
			<tr class="paypal_cp_form">
				<th><a class="explanation-label" id="label_ex_autobilling_email_paypal_cp"><?php esc_html_e( 'Automatic Continuing Charging Completion Mail', 'usces' ); ?></a></th>
				<td><label><input type="radio" name="cp_autobilling_email" value="on"<?php checked( $cp_autobilling_email, 'on' ); ?>/><span><?php esc_html_e( 'Send', 'usces' ); ?></span></label><br />
					<label><input type="radio" name="cp_autobilling_email" value="off"<?php checked( $cp_autobilling_email, 'off' ); ?>/><span><?php esc_html_e( "Don't send", 'usces' ); ?></span></label>
				</td>
			</tr>
			<tr id="ex_autobilling_email_paypal_cp" class="explanation paypal_cp_form"><td colspan="2"><?php esc_html_e( 'Send billing completion mail to the member on which automatic continuing charging processing (required WCEX DLSeller) is executed.', 'usces' ); ?></td></tr>
			<?php endif; ?>
		</table>
		<table class="settle_table paypal_cp_form">
			<tr>
				<th rowspan="4"><?php esc_html_e( 'Customize the PayPal Buttons', 'usces' ); ?>
				<div id="button_preview_paypal_cp" class="<?php echo esc_attr( $class_button_color ); ?><?php echo esc_attr( $class_button_shape ); ?><?php echo esc_attr( $class_button_label ); ?>"><span></span></div>
				</th>
				<td><span><?php esc_html_e( 'Layout', 'usces' ); ?></span><br />
					<label><input type="radio" name="cp_button_layout" value="vertical"<?php checked( $cp_button_layout, 'vertical' ); ?>/><span><?php esc_html_e( 'Arrange the buttons vertically.', 'usces' ); ?></span></label><br />
					<label><input type="radio" name="cp_button_layout" value="horizontal"<?php checked( $cp_button_layout, 'horizontal' ); ?>/><span><?php esc_html_e( 'Arrange the buttons horizontally.', 'usces' ); ?></span></label>
				</td>
			</tr>
			<tr>
				<td><span><?php esc_html_e( 'Color', 'usces' ); ?></span><br />
					<label><input type="radio" name="cp_button_color" class="button_color_paypal_cp" value="gold"<?php echo esc_attr( $cp_button_color_gold ); ?>/><span><?php esc_html_e( 'Gold', 'usces' ); ?></span></label><br />
					<label><input type="radio" name="cp_button_color" class="button_color_paypal_cp" value="blue"<?php echo esc_attr( $cp_button_color_blue ); ?>/><span><?php esc_html_e( 'Blue', 'usces' ); ?></span></label><br />
					<label><input type="radio" name="cp_button_color" class="button_color_paypal_cp" value="silver"<?php echo esc_attr( $cp_button_color_silver ); ?>/><span><?php esc_html_e( 'Silver', 'usces' ); ?></span></label><br />
					<label><input type="radio" name="cp_button_color" class="button_color_paypal_cp" value="white"<?php echo esc_attr( $cp_button_color_white ); ?>/><span><?php esc_html_e( 'White', 'usces' ); ?></span></label><br />
					<label><input type="radio" name="cp_button_color" class="button_color_paypal_cp" value="black"<?php echo esc_attr( $cp_button_color_black ); ?>/><span><?php esc_html_e( 'Black', 'usces' ); ?></span></label>
				</td>
			</tr>
			<tr>
				<td><span><?php esc_html_e( 'Shape', 'usces' ); ?></span><br />
					<label><input type="radio" name="cp_button_shape" class="button_shape_paypal_cp" value="rect"<?php echo esc_attr( $cp_button_shape_rect ); ?>/><span><?php esc_html_e( 'Rect', 'usces' ); ?></span></label><br />
					<label><input type="radio" name="cp_button_shape" class="button_shape_paypal_cp" value="pill"<?php echo esc_attr( $cp_button_shape_pill ); ?>/><span><?php esc_html_e( 'Pill', 'usces' ); ?></span></label>
				</td>
			</tr>
			<tr>
				<td><span><?php esc_html_e( 'Label', 'usces' ); ?></span><br />
					<label><input type="radio" name="cp_button_label" class="button_label_paypal_cp" value="paypal"<?php echo esc_attr( $cp_button_label_paypal ); ?>/><span><?php esc_html_e( 'Display the PayPal logo.', 'usces' ); ?></span></label><br />
					<label><input type="radio" name="cp_button_label" class="button_label_paypal_cp" value="checkout"<?php echo esc_attr( $cp_button_label_checkout ); ?>/><span><?php esc_html_e( 'Display the PayPal Checkout button.', 'usces' ); ?></span></label><br />
					<label><input type="radio" name="cp_button_label" class="button_label_paypal_cp" value="buynow"<?php echo esc_attr( $cp_button_label_buynow ); ?>/><span><?php esc_html_e( 'Display the PayPal Buy Now button.', 'usces' ); ?></span></label><br />
					<label><input type="radio" name="cp_button_label" class="button_label_paypal_cp" value="pay"<?php echo esc_attr( $cp_button_label_pay ); ?>/><span><?php esc_html_e( 'Display the Pay With PayPal button.', 'usces' ); ?></span></label>
				</td>
			</tr>
		</table>
		<input name="acting" type="hidden" value="paypal_cp" />
		<input name="usces_option_update" id="paypal_cp" type="submit" class="button button-primary" value="<?php esc_html_e( 'Update the PayPal Commerce Platform settings', 'usces' ); ?>" />
		<span class="paypal_cp_form_agree"><label><input type="checkbox" name="cp_agree" value="agree"<?php checked( $cp_agree, 'agree' ); ?> /><span><?php esc_html_e( 'I agree to the following terms and conditions of use.', 'usces' ); ?></span></label></span>
		<p class="paypal_cp_form_agree"><?php esc_html_e( 'You agree that the information you submit during the application process will be provided to our partner company PayPal Pte. Ltd. and will be used by PayPal to evaluate, improve and enhance its services and for marketing purposes and PayPal Pte. Ltd. may send you information for marketing and promotional purposes (including sending e-mails, etc.).', 'usces' ); ?></p>
			<?php wp_nonce_field( 'admin_settlement', 'wc_nonce' ); ?>
	</form>
	<div class="settle_exp">
		<p><strong><?php esc_html_e( 'PayPal Commerce Platform', 'usces' ); ?></strong></p>
		<p>問い合わせ先<br />
新規ビジネス・サービス導入・お申込専用ライン<br />
Tel：03-6739-7135 9:30～17:30（土日祝休）※通話料がかかります</p>
<p>すでにペイパルアカウントをお持ちの方（カスタマーサービス）<br />
Tel：0120-271-888 または 03-6739-7360（携帯電話と海外からはこちら）※通話料がかかります<br />
9:00～18:00（土日休）</p>
		<p><a href="https://www.paypal.com/jp/webapps/mpp/corporate/contact">WEBでのお問い合わせ</a>
			<?php
			$wcex    = '';
			$billing = '';
			if ( defined( 'WCEX_DLSELLER' ) ) {
				$wcex    = 'WCEX DLSeller での自動継続課金';
				$billing = '自動継続課金';
			} elseif ( defined( 'WCEX_AUTO_DELIVERY' ) ) {
				$wcex    = 'WCEX Auto Delivery での定期購入';
				$billing = '定期購入';
			}
			if ( '' !== $wcex ) :
				?>
		<p><?php echo esc_html( $wcex ); ?>を行う場合は、ペイパルへの「従量課金」の利用申請・審査が必要となります。<br />
利用申請については、<a href="https://www.paypal.com/jp/smarthelp/contact-us">こちら</a>からお問い合わせください。<br />
※上記の問い合わせページで「その他のお問い合わせ方法」の下にある「メッセージを送る」を選択ください。<br />
※メッセージボックスに、「Welcart にて Reference Transaction を利用するための審査・設定をしてほしい」旨をご記入ください。</p>
				<?php
			endif;
			?>
<p>クレジットカード決済のご利用には別途ペイパルへのお申込みと審査が必要です。<br />
クレジットカード決済の申込みは<a href="https://www.paypal.com/jp/webapps/mpp/contact-sales">こちら</a>から行えます。</p>
	</div>
	</div><!--uscestabs_paypal_cp-->
			<?php
		endif;
	}

	/**
	 * 支払方法 JavaScript 用決済名追加
	 * usces_filter_payments_str
	 *
	 * @param  string $payments_str Payments.
	 * @param  array  $payment Selected payment.
	 * @return string
	 */
	public function payments_str( $payments_str, $payment ) {
		if ( 'acting_paypal_card' === $payment['settlement'] && 'activate' === $payment['use'] ) {
			$payments_str .= "'" . $payment['name'] . "': 'paypal_card_form', ";
		}
		return $payments_str;
	}

	/**
	 * 支払方法 JavaScript 用決済追加
	 * usces_filter_payments_arr
	 *
	 * @param  array $payments_arr Payments.
	 * @param  array $payment Selected payment.
	 * @return array
	 */
	public function payments_arr( $payments_arr, $payment ) {
		if ( 'acting_paypal_card' === $payment['settlement'] && 'activate' === $payment['use'] ) {
			$payments_arr[] = 'paypal_card_form';
		}
		return $payments_arr;
	}

	/**
	 * 支払方法ページ用入力フォーム
	 * usces_filter_delivery_secure_form_loop
	 *
	 * @param  string $nouse Empty.
	 * @param  array  $payment Payment data.
	 * @return string
	 */
	public function delivery_secure_form_loop( $nouse, $payment ) {
		global $usces;

		$form = '';
		if ( isset( $payment['settlement'] ) && 'acting_paypal_card' === $payment['settlement'] && 'activate' === $payment['use'] ) {
			$acting_opts               = $this->get_acting_settings();
			$card_vault                = 'off';
			$payment_token_id          = '';
			$payment_token_last_digits = '';
			$payment_token_brand       = '';
			if ( usces_is_member_system() && usces_is_login() ) {
				$card_vault = ( isset( $acting_opts['card_vault'] ) ) ? $acting_opts['card_vault'] : 'off';
				if ( 'on' === $card_vault ) {
					$member = $usces->get_member();

					/* Get Access Token */
					$access_token = $this->get_access_token();

					$payment_tokens = $this->api_get_payment_tokens( $access_token, $member['ID'] );
					if ( ! empty( $payment_tokens['payment_tokens'] ) && is_array( $payment_tokens['payment_tokens'] ) ) {
						$tokens_count = count( $payment_tokens['payment_tokens'] );
						if ( 1 === $tokens_count ) {
							$payment_token             = current( $payment_tokens['payment_tokens'] );
							$payment_token_id          = $payment_token['id'];
							$payment_token_last_digits = $payment_token['source']['card']['last_digits'];
							$payment_token_brand       = $payment_token['source']['card']['brand'];
						} else {
							foreach ( $payment_tokens['payment_tokens'] as $payment_token ) {
								$delete_status = $this->api_delete_payment_tokens( $access_token, $payment_token['id'] );
							}
						}
					}
				}
			}
			$tracking_id = usces_acting_key();

			ob_start();
			?>
	<table class="customer_form" id="paypal_card_form">
		<tbody><tr>
			<th scope="row"><em><?php esc_html_e( '*', 'usces' ); ?></em><?php esc_html_e( 'Credit card information', 'usces' ); ?></th>
			<td>
			<?php if ( 'on' === $card_vault && ! empty( $payment_token_id ) ) : ?>
			<div class="card_selection"><input type="radio" id="paypal_payment_card_vault" name="paypal_payment_card" value="vault" checked /><label for="paypal_payment_card_vault"><?php esc_html_e( 'Pay with vaulted card', 'usces' ); ?></label>
				<p class="card_last_digits"><?php echo esc_html( $payment_token_brand ); ?> <?php esc_html_e( 'Lower 4 digits', 'usces' ); ?>:<?php echo esc_html( $payment_token_last_digits ); ?></p>
			</div>
			<div class="card_selection"><input type="radio" id="paypal_payment_card_another" name="paypal_payment_card" value="another"><label for="paypal_payment_card_another"><?php esc_html_e( 'Pay with another card', 'usces' ); ?></label></div>
			<?php endif; ?>
			<div class="card_container">
				<div>
					<label for="card-number"><?php esc_html_e( 'Card Number', 'usces' ); ?></label>
					<div id="card-number" class="card_field"></div>
				</div>
				<div>
					<label for="expiration-date"><?php esc_html_e( 'Expiration Date', 'usces' ); ?></label>
					<div id="expiration-date" class="card_field"></div>
				</div>
				<div>
					<label for="cvv"><?php esc_html_e( 'CVV', 'usces' ); ?></label>
					<div id="cvv" class="card_field"></div>
				</div>
			<?php if ( 'on' === $card_vault ) : ?>
				<div>
					<input type="checkbox" id="vault" name="paypal_vault" value="vault">
					<label for="vault"><?php esc_html_e( 'Save your card', 'usces' ); ?></label>
				</div>
			<?php else : ?>
				<input type="hidden" id="vault" value="">
			<?php endif; ?>
			</div>
			<input type="hidden" id="paypal_tracking_id" name="paypal_tracking_id" value="<?php echo esc_attr( $tracking_id ); ?>" />
			<input type="hidden" id="paypal_resource_id" name="paypal_resource_id" value="" />
			<input type="hidden" id="paypal_payment_token_id " name="paypal_payment_token_id" value="<?php echo esc_attr( $payment_token_id ); ?>" />
			<input type="hidden" id="paypal_f" name="paypal_f" value="" />
		</td></tr></tbody>
	</table>
			<?php
			$form = ob_get_contents();
			ob_end_clean();
		}
		return $form;
	}

	/**
	 * 支払方法チェック
	 * usces_filter_delivery_check
	 *
	 * @param  string $mes Validation check message.
	 * @return string
	 */
	public function delivery_check( $mes ) {
		if ( ! isset( $_POST['offer']['payment_name'] ) ) {
			return $mes;
		}

		if ( ! empty( $mes ) ) {
			return $mes;
		}

		$payment = usces_get_payments_by_name( wp_unslash( $_POST['offer']['payment_name'] ) );
		if ( isset( $payment['settlement'] ) && 'acting_paypal_card' === $payment['settlement'] ) {
			if ( isset( $_POST['paypal_payment_card'] ) && 'vault' === wp_unslash( $_POST['paypal_payment_card'] ) ) {
			} else {
				if ( isset( $_POST['paypal_resource_id'] ) && empty( $_POST['paypal_resource_id'] ) ) {
					$mes .= __( 'Please enter the card information correctly.', 'usces' ) . '<br />';
				} else {
					if ( ! wel_check_credit_security() ) {
						$mes .= __( 'Update has been locked. Please contact the store administrator.', 'usces' ) . '<br />';
					}
				}
			}
		}
		return $mes;
	}

	/**
	 * 内容確認ページ ポイントフォーム
	 * usces_action_confirm_page_point_inform
	 */
	public function e_point_inform() {
		$html = $this->point_inform( '' );
		wel_esc_script_e( $html );
	}

	/**
	 * 内容確認ページ ポイントフォーム
	 * usces_filter_confirm_point_inform
	 *
	 * @param  string $html Input point form.
	 * @return string
	 */
	public function point_inform( $html ) {
		global $usces;

		$entry   = $usces->cart->get_entry();
		$payment = usces_get_payments_by_name( $entry['order']['payment_name'] );
		if ( isset( $payment['settlement'] ) && 'acting_paypal_card' === $payment['settlement'] ) {
			if ( isset( $_POST['paypal_tracking_id'] ) ) {
				$html .= '<input type="hidden" name="paypal_tracking_id" value="' . esc_attr( sanitize_text_field( wp_unslash( $_POST['paypal_tracking_id'] ) ) ) . '">';
			}
			if ( isset( $_POST['paypal_resource_id'] ) ) {
				$html .= '<input type="hidden" name="paypal_resource_id" value="' . esc_attr( sanitize_text_field( wp_unslash( $_POST['paypal_resource_id'] ) ) ) . '">';
			}
			if ( isset( $_POST['paypal_payment_card'] ) ) {
				$html .= '<input type="hidden" name="paypal_payment_card" value="' . esc_attr( sanitize_text_field( wp_unslash( $_POST['paypal_payment_card'] ) ) ) . '">';
			}
			if ( isset( $_POST['paypal_payment_token_id'] ) ) {
				$html .= '<input type="hidden" name="paypal_payment_token_id" value="' . esc_attr( sanitize_text_field( wp_unslash( $_POST['paypal_payment_token_id'] ) ) ) . '">';
			}
			if ( isset( $_POST['paypal_vault'] ) ) {
				$html .= '<input type="hidden" name="paypal_vault" value="' . esc_attr( sanitize_text_field( wp_unslash( $_POST['paypal_vault'] ) ) ) . '">';
			}
			if ( isset( $_POST['paypal_f'] ) ) {
				$html .= '<input type="hidden" name="paypal_f" value="' . esc_attr( sanitize_text_field( wp_unslash( $_POST['paypal_f'] ) ) ) . '">';
			}
			if ( isset( $_POST['paypal_liability_shift'] ) ) {
				$html .= '<input type="hidden" name="paypal_liability_shift" value="' . esc_attr( sanitize_text_field( wp_unslash( $_POST['paypal_liability_shift'] ) ) ) . '">';
			}
			if ( isset( $_POST['paypal_enrollment_status'] ) ) {
				$html .= '<input type="hidden" name="paypal_enrollment_status" value="' . esc_attr( sanitize_text_field( wp_unslash( $_POST['paypal_enrollment_status'] ) ) ) . '">';
			}
			if ( isset( $_POST['paypal_authentication_status'] ) ) {
				$html .= '<input type="hidden" name="paypal_authentication_status" value="' . esc_attr( sanitize_text_field( wp_unslash( $_POST['paypal_authentication_status'] ) ) ) . '">';
			}
		}
		return $html;
	}

	/**
	 * 内容確認ページ [注文する] ボタン
	 * usces_filter_confirm_inform
	 *
	 * @param  string $form Purchase post form.
	 * @param  array  $payment Payment method info.
	 * @param  string $acting_flg Payment type.
	 * @param  string $rand Welcart transaction key.
	 * @param  string $purchase_disabled Disable purchase button.
	 * @return string
	 */
	public function confirm_inform( $form, $payment, $acting_flg, $rand, $purchase_disabled ) {
		global $usces;

		$entry = $usces->cart->get_entry();
		$cart  = $usces->cart->get_cart();
		if ( empty( $entry ) || empty( $cart ) ) {
			return $form;
		}
		if ( empty( $entry['order']['total_full_price'] ) ) {
			return $form;
		}

		if ( 'acting_paypal_cp' === $acting_flg ) {
			usces_save_order_acting_data( $rand );
			$form = '<form name="purchase_form" action="' . USCES_CART_URL . '" method="post" onKeyDown="if(event.keyCode == 13){return false;}">
					<input type="hidden" name="purchase" value="' . $acting_flg . '">
					<input type="hidden" name="tracking_id" value="' . $rand . '">
					<input type="hidden" name="resource_id">
					<input type="hidden" name="billing_token">
					<input type="hidden" name="_nonce" value="' . wp_create_nonce( $acting_flg ) . '">
					<div class="send paypal-cp-send"><div id="checkout_paypal_cp"></div></div>
				</form>
				<form action="' . USCES_CART_URL . '" method="post" onKeyDown="if(event.keyCode == 13){return false;}">
					<div class="send">
						' . apply_filters( 'usces_filter_confirm_before_backbutton', '', $payment, $acting_flg, $rand ) . '
						<input name="backDelivery" type="submit" id="back_button" class="back_to_delivery_button" value="' . __( 'Back', 'usces' ) . '"' . apply_filters( 'usces_filter_confirm_prebutton', '' ) . ' />
					</div>';

		} elseif ( 'acting_paypal_card' === $acting_flg ) {
			$tracking_id           = ( isset( $_POST['paypal_tracking_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['paypal_tracking_id'] ) ) : '';
			$resource_id           = ( isset( $_POST['paypal_resource_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['paypal_resource_id'] ) ) : '';
			$payment_card          = ( isset( $_POST['paypal_payment_card'] ) ) ? sanitize_text_field( wp_unslash( $_POST['paypal_payment_card'] ) ) : '';
			$payment_token_id      = ( isset( $_POST['paypal_payment_token_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['paypal_payment_token_id'] ) ) : '';
			$vault                 = ( isset( $_POST['paypal_vault'] ) ) ? sanitize_text_field( wp_unslash( $_POST['paypal_vault'] ) ) : '';
			$correlation_id        = ( isset( $_POST['paypal_f'] ) ) ? sanitize_text_field( wp_unslash( $_POST['paypal_f'] ) ) : '';
			$liability_shift       = ( isset( $_POST['paypal_liability_shift'] ) ) ? sanitize_text_field( wp_unslash( $_POST['paypal_liability_shift'] ) ) : '';
			$enrollment_status     = ( isset( $_POST['paypal_enrollment_status'] ) ) ? sanitize_text_field( wp_unslash( $_POST['paypal_enrollment_status'] ) ) : '';
			$authentication_status = ( isset( $_POST['paypal_authentication_status'] ) ) ? sanitize_text_field( wp_unslash( $_POST['paypal_authentication_status'] ) ) : '';
			usces_save_order_acting_data( $tracking_id );
			$form = '<form name="purchase_form" action="' . USCES_CART_URL . '" method="post" onKeyDown="if(event.keyCode == 13){return false;}">
						<input type="hidden" name="purchase" value="' . $acting_flg . '">
						<input type="hidden" name="tracking_id" value="' . esc_attr( $tracking_id ) . '">
						<input type="hidden" name="resource_id" value="' . esc_attr( $resource_id ) . '">
						<input type="hidden" name="payment_card" value="' . esc_attr( $payment_card ) . '">
						<input type="hidden" name="payment_token_id" value="' . esc_attr( $payment_token_id ) . '">
						<input type="hidden" name="vault" value="' . esc_attr( $vault ) . '">
						<input type="hidden" name="correlation_id" value="' . esc_attr( $correlation_id ) . '">
						<input type="hidden" name="liability_shift" value="' . esc_attr( $liability_shift ) . '">
						<input type="hidden" name="enrollment_status" value="' . esc_attr( $enrollment_status ) . '">
						<input type="hidden" name="authentication_status" value="' . esc_attr( $authentication_status ) . '">
						<input type="hidden" name="_nonce" value="' . wp_create_nonce( $acting_flg ) . '">
					<div class="send">' . apply_filters( 'usces_filter_confirm_before_backbutton', null, $payment, $acting_flg, $tracking_id ) . '
						<input name="backDelivery" type="submit" id="back_button" class="back_to_delivery_button" value="' . __( 'Back', 'usces' ) . '"' . apply_filters( 'usces_filter_confirm_prebutton', null ) . ' />
						<input name="purchase" type="submit" id="purchase_button" class="checkout_button" value="' . apply_filters( 'usces_filter_confirm_checkout_button_value', __( 'Checkout', 'usces' ) ) . '"' . apply_filters( 'usces_filter_confirm_nextbutton', null ) . $purchase_disabled . ' />
					</div>';
		}
		return $form;
	}

	/**
	 * 決済エラーメッセージ
	 * usces_filter_get_error_settlement
	 *
	 * @param  string $form Payment error message.
	 * @return string
	 */
	public function error_page_message( $form ) {
		$acting        = ( isset( $_GET['acting'] ) ) ? wp_unslash( $_GET['acting'] ) : '';
		$acting_return = ( isset( $_GET['acting_return'] ) ) ? wp_unslash( $_GET['acting_return'] ) : '';
		if ( 'paypal_card' === $acting && '0' === $acting_return ) {
			if ( isset( $_GET['retry'] ) ) {
				$retry_url = add_query_arg(
					array(
						'backDelivery' => 'paypal_card',
						're-enter'     => 1,
					),
					USCES_CUSTOMER_URL
				);
				$form     .= '<div class="support_box">' . __( 'Your credit card information is incorrect.', 'usces' ) . '<br />' . sprintf( __( 'Click <a href="%s">here</a> to re-enter your credit card number.', 'usces' ), $retry_url ) . '</div>';
			}
		}
		return $form;
	}

	/**
	 * フロントスクリプト
	 * wp_enqueue_scripts
	 */
	public function enqueue_scripts() {
		global $usces;

		if ( ! is_admin() && $this->is_validity_acting( 'card' ) ) {
			$usces_page = ( isset( $_GET['usces_page'] ) ) ? wp_unslash( $_GET['usces_page'] ) : '';
			if ( ( $usces->is_cart_page( $_SERVER['REQUEST_URI'] ) && 'delivery' === $usces->page ) ||
				( $usces->is_member_page( $_SERVER['REQUEST_URI'] ) && ( 'member_register_settlement' === $usces_page || 'member_update_settlement' === $usces_page ) ) ) {
				$paypal_style_url = apply_filters( 'usces_filter_paypalcp_card_style', USCES_FRONT_PLUGIN_URL . '/css/paypal_style.css' );
				wp_enqueue_style( 'usces-paypal-style', $paypal_style_url, array() );
				wp_enqueue_style( 'usces-loading', USCES_FRONT_PLUGIN_URL . '/css/loading.css', array() );
			}
		}
	}

	/**
	 * フロントスクリプト
	 * wp_print_footer_scripts
	 */
	public function footer_scripts() {
		global $usces;

		if ( 'delivery' === $usces->page && $this->is_validity_acting( 'card' ) ) :
			$entry = $usces->cart->get_entry();
			$cart  = $usces->cart->get_cart();
			if ( empty( $cart ) || empty( $entry ) ) {
				return;
			}

			$continue = ( defined( 'WCEX_DLSELLER' ) ) ? usces_have_continue_charge( $cart ) : false;
			$regular  = ( defined( 'WCEX_AUTO_DELIVERY' ) ) ? usces_have_regular_order() : false;
			if ( $continue || $regular ) {
				return;
			}

			$acting_opts    = $this->get_acting_settings();
			$cp_client_id   = ( isset( $acting_opts['client_id'] ) ) ? $acting_opts['client_id'] : '';
			$cp_intent      = ( isset( $acting_opts['intent'] ) ) ? $acting_opts['intent'] : '';
			$cp_environment = ( isset( $acting_opts['environment'] ) ) ? $acting_opts['environment'] : 'live';
			$card_vault     = ( isset( $acting_opts['card_vault'] ) ) ? $acting_opts['card_vault'] : 'off';
			$card_3ds       = ( isset( $acting_opts['card_3ds'] ) ) ? $acting_opts['card_3ds'] : 'off';

			$customer_id = '';
			if ( usces_is_login() && 'on' === $card_vault ) {
				$member      = $usces->get_member();
				$customer_id = $member['ID'];
			}

			$paypal_card_name = '';
			$payment_method   = usces_get_system_option( 'usces_payment_method', 'settlement' );
			if ( isset( $payment_method['acting_paypal_card'] ) ) {
				$paypal_card_name = $payment_method['acting_paypal_card']['name'];
			}

			$currency_code       = $usces->get_currency_code();
			$query               = array();
			$query['components'] = 'hosted-fields';
			$query['client-id']  = $cp_client_id;
			$query['currency']   = $currency_code;
			$query['locale']     = $this->get_locale();
			if ( 'AUTHORIZE' === $cp_intent ) {
				$query['intent'] = 'authorize';
			}
			$sdk_query    = http_build_query( $query );
			$client_token = $this->get_client_token( $customer_id );
			if ( usces_is_login() && 'on' === $card_vault ) :
				$fraudnet_session_id = $this->get_fraudnet_session_id();
				?>
<script type="application/json" fncls="fnparams-dede7cc5-15fd-4c75-a9f4-36c430ee3a99">
{
	"f":"<?php echo esc_attr( $fraudnet_session_id ); ?>",
	"s":"<?php echo self::API_FRAUDNET_S; ?>"<?php if ( 'live' !== $cp_environment ) : ?>,
	"sandbox":true
<?php endif; ?>}
</script>
<script type="text/javascript" src="https://c.paypal.com/da/r/fb.js"></script>
<script type="text/javascript">
document.getElementById( "paypal_f" ).value = "<?php echo esc_attr( $fraudnet_session_id ); ?>";
</script><?php endif; ?>
<div id="welcart-overlay" style="display:none"><div id="welcart-loading"></div><div id="welcart-loading-text"><?php esc_html_e( 'Processing...', 'usces' ); ?></div></div>
<script src="https://www.paypal.com/sdk/js?<?php echo $sdk_query; // no escape due to fixed format. ?>" data-client-token="<?php echo $client_token; // no escape due to fixed format. ?>"></script>
<script type="text/javascript">
jQuery(document).ready( function($) {
	$( 'body input[type="submit"]' ).each( function( i, elem ) {
		if ( "confirm" == $( this ).attr( "name" ) ) {
			$( this ).parents( "form" ).attr( "id", "delivery-form" );
			$( this ).parents( "form" ).attr( "name", "delivery_form" );
			$( this ).attr( "id", "confirm" );
		}
	});
});
</script>
<script type="text/javascript">
	document.getElementById( "welcart-overlay" ).style.display = 'none';
	let paypal_card_name = "<?php echo esc_js( $paypal_card_name ); ?>";
	if ( paypal.HostedFields.isEligible() ) {
		paypal.HostedFields.render({
			createOrder: function() {
				let params = new URLSearchParams();
				params.append( "action", "create_order_card" );
				params.append( "tracking_id", document.getElementById( "paypal_tracking_id" ).value );
				return fetch( uscesL10n.ajaxurl+"?uscesid="+uscesL10n.uscesid, {
					method: "POST",
					body: params
				}).then( function( res ) {
					return res.json();
				}).then( function( data ) {
					if ( data.message ) {
						alert( data.message );
						return false;
					}
					if ( data.stock && 'error' == data.stock ) {
						location.href = "<?php echo esc_js( USCES_CART_URL ); ?>";
					}
					document.getElementById( "paypal_resource_id" ).value = data.id;
					return data.id;
				});
			},
			fields: {
				number: {
					selector: "#card-number",
					placeholder: "4111 1111 1111 1111"
				},
				expirationDate: {
					selector: "#expiration-date",
					placeholder: "MM/YY"
				},
				cvv: {
					selector: "#cvv",
					placeholder: "123"
				}
			},
			styles: {
				'.valid': {
					'color': 'green'
				},
				'.invalid': {
					'color': 'red'
				}
			}
		}).then( function( hostedFieldsInstance ) {
			document.querySelector( "#confirm" ).onclick = function( event ) {
				var payment_name = document.getElementsByName( "offer[payment_name]" );
				for ( var select_payment = "", i = payment_name.length; i--; ) {
					if ( payment_name[i].checked ) {
						var select_payment = payment_name[i].value;
						break;
					}
				}
				<?php if ( 'on' === $card_vault ) : ?>
				var payment_card = document.getElementsByName( "paypal_payment_card" );
				for ( var select_vault = "", i = payment_card.length; i--; ) {
					if ( payment_card[i].checked ) {
						var select_vault = payment_card[i].value;
						break;
					}
				}
				<?php else : ?>
				var select_vault = "";
				<?php endif; ?>
				if ( paypal_card_name == select_payment && "vault" != select_vault ) {
				<?php if ( 'on' !== $card_3ds ) : ?>
					document.getElementById( "welcart-overlay" ).style.display = "block";
				<?php endif; ?>
					event.preventDefault();
					hostedFieldsInstance.submit(<?php if ( 'on' === $card_vault || 'on' === $card_3ds ) : ?>{
						<?php if ( 'on' === $card_vault ) : ?>
						vault: document.querySelector( '#vault' ).checked<?php endif; ?><?php if ( 'on' === $card_vault && 'on' === $card_3ds ) : ?>,
						<?php endif; ?>
						<?php if ( 'on' === $card_3ds ) : ?>
						contingencies: ['SCA_ALWAYS']
						<?php endif; ?>
					}<?php endif; ?>).then( function( payload ) {
					<?php if ( 'on' === $card_3ds ) : ?>
						if ( "POSSIBLE" == payload.liabilityShift ) {
							let params3ds = new URLSearchParams();
							params3ds.append( "action", "show_order_details_card" );
							params3ds.append( "tracking_id", document.getElementById( "paypal_tracking_id" ).value );
							params3ds.append( "resource_id", payload.orderId );
							return fetch( uscesL10n.ajaxurl + "?uscesid=" + uscesL10n.uscesid, {
								method: "POST",
								body: params3ds
							}).then( function( res ) {
								return res.json();
							}).then( function( res3ds ) {
								document.getElementById( "paypal_resource_id" ).value = payload.orderId;
								var elem_liability_shift = document.createElement( "input" );
								elem_liability_shift.setAttribute( "type", "hidden" );
								elem_liability_shift.setAttribute( "name", "paypal_liability_shift" );
								elem_liability_shift.setAttribute( "value", res3ds.payment_source.card.authentication_result.liability_shift );
								document.delivery_form.appendChild( elem_liability_shift );
								var elem_enrollment_status = document.createElement( "input" );
								elem_enrollment_status.setAttribute( "type", "hidden" );
								elem_enrollment_status.setAttribute( "name", "paypal_enrollment_status" );
								elem_enrollment_status.setAttribute( "value", res3ds.payment_source.card.authentication_result.three_d_secure.enrollment_status );
								document.delivery_form.appendChild( elem_enrollment_status );
								var elem_authentication_status = document.createElement( "input" );
								elem_authentication_status.setAttribute( "type", "hidden" );
								elem_authentication_status.setAttribute( "name", "paypal_authentication_status" );
								elem_authentication_status.setAttribute( "value", res3ds.payment_source.card.authentication_result.three_d_secure.authentication_status );
								document.delivery_form.appendChild( elem_authentication_status );
								var elem_confirm = document.createElement( "input" );
								elem_confirm.setAttribute( "type", "hidden" );
								elem_confirm.setAttribute( "name", "confirm" );
								elem_confirm.setAttribute( "value", "confirm" );
								document.delivery_form.appendChild( elem_confirm );
								document.delivery_form.submit();
							});
						} else {
							alert( "3D Secure authorization failed. Please try another card number." );
							return false;
						}
					<?php else : ?>
						document.getElementById( "paypal_resource_id" ).value = payload.orderId;
						var elem_confirm = document.createElement( "input" );
						elem_confirm.setAttribute( "type", "hidden" );
						elem_confirm.setAttribute( "name", "confirm" );
						elem_confirm.setAttribute( "value", "confirm" );
						document.delivery_form.appendChild( elem_confirm );
						document.delivery_form.submit();
					<?php endif; ?>
					}).catch(( reason ) => {
						document.getElementById( "welcart-overlay" ).style.display = 'none';
						alert( 'Payment could not be captured! ' + reason.name + ' ' + reason.message );
						return false;
					});
				}
			}
		}).catch( function( err ) {
			if ( err ) {
				alert( 'Payment could not be captured! ' + JSON.stringify( err ) );
				return false;
			}
		});
	} else {
		document.querySelector( "#paypal_card_form" ).style = 'display: none';
	}
</script>
			<?php
		elseif ( 'confirm' === $usces->page ) :
			$entry = $usces->cart->get_entry();
			$cart  = $usces->cart->get_cart();
			if ( empty( $cart ) || empty( $entry['order']['total_full_price'] ) ) {
				return;
			}

			$payment = usces_get_payments_by_name( $entry['order']['payment_name'] );

			/* PayPal CP */
			if ( 'acting_paypal_cp' === $payment['settlement'] && 'activate' === $payment['use'] ) :
				$continue = ( defined( 'WCEX_DLSELLER' ) ) ? usces_have_continue_charge( $cart ) : false;
				$regular  = ( defined( 'WCEX_AUTO_DELIVERY' ) ) ? usces_have_regular_order() : false;

				$acting_opts      = $this->get_acting_settings();
				$cp_client_id     = ( isset( $acting_opts['client_id'] ) ) ? $acting_opts['client_id'] : '';
				$cp_intent        = ( isset( $acting_opts['intent'] ) ) ? $acting_opts['intent'] : '';
				$cp_button_layout = ( isset( $acting_opts['button_layout'] ) ) ? $acting_opts['button_layout'] : 'vertical';
				$cp_button_color  = ( isset( $acting_opts['button_color'] ) ) ? $acting_opts['button_color'] : 'gold';
				$cp_button_shape  = ( isset( $acting_opts['button_shape'] ) ) ? $acting_opts['button_shape'] : 'rect';
				$cp_button_label  = ( isset( $acting_opts['button_label'] ) ) ? $acting_opts['button_label'] : 'paypal';

				$currency_code      = $usces->get_currency_code();
				$query              = array();
				$query['client-id'] = $cp_client_id;
				$query['currency']  = $currency_code;
				$query['locale']    = $this->get_locale();
				if ( $continue || $regular ) {
					$query['vault']  = 'true';
					$query['intent'] = 'tokenize';
					$billing         = ( $continue ) ? 'continue' : 'regular';
				} else {
					if ( 'AUTHORIZE' === $cp_intent ) {
						$query['intent'] = 'authorize';
					}
				}
				$sdk_query = http_build_query( $query );
				?>
<script src="https://www.paypal.com/sdk/js?<?php echo $sdk_query; // no escape due to fixed format. ?>"></script>
<script>
	paypal.Buttons({
		style: {
			layout: "<?php echo esc_html( $cp_button_layout ); ?>",
			color: "<?php echo esc_html( $cp_button_color ); ?>",
			shape: "<?php echo esc_html( $cp_button_shape ); ?>",
			label: "<?php echo esc_html( $cp_button_label ); ?>"
		},<?php if ( $continue || $regular ) : ?>
		createBillingAgreement: function() {
			let params = new URLSearchParams();
			params.append( "action", "create_billing_agreement" );
			params.append( "tracking_id", document.getElementsByName( "tracking_id" )[0].value );
			params.append( "billing", "<?php echo esc_html( $billing ); ?>" );
			return fetch( uscesL10n.ajaxurl + "?uscesid=" + uscesL10n.uscesid, {
				method: "POST",
				body: params
			}).then( function( res ) {
				return res.json();
			}).then( function( data ) {
				if ( data.stock && 'error' == data.stock ) {
					location.href = "<?php echo esc_js( USCES_CART_URL ); ?>";
				}
				return data.token_id;
			});
		},
		onApprove: function( data ) {
			document.getElementById( "checkout_paypal_cp" ).style.pointerEvents = "none";
			var purchase_form = document.forms.purchase_form;
			purchase_form.resource_id.value = data.orderID;
			purchase_form.billing_token.value = data.billingToken;
			purchase_form.submit();
		},<?php else : ?>
		createOrder: function() {
			let params = new URLSearchParams();
			params.append( "action", "create_order" );
			params.append( "tracking_id", document.getElementsByName( "tracking_id" )[0].value );
			return fetch( uscesL10n.ajaxurl + "?uscesid=" + uscesL10n.uscesid, {
				method: "POST",
				body: params
			}).then( function( res ) {
				return res.json();
			}).then( function( data ) {
				if ( data.stock && 'error' == data.stock ) {
					location.href = "<?php echo esc_js( USCES_CART_URL ); ?>";
				}
				return data.id;
			});
		},
		onApprove: function( data ) {
			document.getElementById( "checkout_paypal_cp" ).style.pointerEvents = "none";
			var purchase_form = document.forms.purchase_form;
			purchase_form.resource_id.value = data.orderID;
			purchase_form.submit();
		},<?php endif; ?>
		onCancel: function( data ) {
		},
		onError: function( err ) {
			alert( err );
		}
	}).render( "#checkout_paypal_cp" );
</script>
				<?php
			endif;
		elseif ( $usces->is_member_page( $_SERVER['REQUEST_URI'] ) && ( $this->is_validity_acting( 'cp' ) || $this->is_validity_acting( 'card' ) ) ) :
			$member = $usces->get_member();
			if ( usces_have_member_continue_order( $member['ID'] ) || usces_have_member_regular_order( $member['ID'] ) ) :
				?>
<script type="text/javascript">
jQuery( document ).ready( function( $ ) {
	$( "input[name='deletemember']" ).css( "display", "none" );
});
</script>
				<?php
			endif;
			$usces_page = ( isset( $_GET['usces_page'] ) ) ? wp_unslash( $_GET['usces_page'] ) : '';
			if ( $this->is_validity_acting( 'card' ) && ( 'member_update_settlement' === $usces_page || 'member_register_settlement' === $usces_page ) ) :
				$acting_opts  = $this->get_acting_settings();
				$cp_client_id = ( isset( $acting_opts['client_id'] ) ) ? $acting_opts['client_id'] : '';
				$card_vault   = ( isset( $acting_opts['card_vault'] ) ) ? $acting_opts['card_vault'] : 'off';
				if ( 'on' !== $card_vault ) {
					return;
				}

				$member        = $usces->get_member();
				$currency_code = $usces->get_currency_code();

				$query               = array();
				$query['components'] = 'hosted-fields';
				$query['client-id']  = $cp_client_id;
				$query['currency']   = $currency_code;
				$query['locale']     = $this->get_locale();
				$query['intent']     = 'authorize';
				$sdk_query           = http_build_query( $query );
				$client_token        = $this->get_client_token( $member['ID'] );
				?>
<div id="welcart-overlay" style="display:none"><div id="welcart-loading"></div><div id="welcart-loading-text"><?php esc_html_e( 'Processing...', 'usces' ); ?></div></div>
<script src="https://www.paypal.com/sdk/js?<?php echo $sdk_query; // no escape due to fixed format. ?>" data-client-token="<?php echo $client_token; // no escape due to fixed format. ?>"></script>
<script>
	document.getElementById( "welcart-overlay" ).style.display = 'none';
	if ( paypal.HostedFields.isEligible() ) {
		paypal.HostedFields.render({
			createOrder: function() {
				let params = new URLSearchParams();
				params.append( "action", "create_order_member" );
				params.append( "tracking_id", document.getElementById( "paypal_tracking_id" ).value );
				return fetch( uscesL10n.ajaxurl + "?uscesid=" + uscesL10n.uscesid, {
					method: "POST",
					body: params
				}).then( function( res ) {
					return res.json();
				}).then( function( data ) {
					if ( data.message ) {
						alert( data.message );
						return false;
					}
					document.getElementById( "paypal_resource_id" ).value = data.id;
					return data.id;
				});
			},
			fields: {
				number: {
					selector: "#card-number",
					placeholder: "4111 1111 1111 1111"
				},
				expirationDate: {
					selector: "#expiration-date",
					placeholder: "MM/YY"
				},
				cvv: {
					selector: "#cvv",
					placeholder: "123"
				}
			},
			styles: {
				'.valid': {
					'color': 'green'
				},
				'.invalid': {
					'color': 'red'
				}
			}
		}).then( function( hostedFieldsInstance ) {
			document.querySelector( "#card-update" ).onclick = function( event ) {
				document.getElementById( "welcart-overlay" ).style.display = 'block';
				event.preventDefault();
				hostedFieldsInstance.submit({
					vault: true
				}).then( function( payload ) {
					document.getElementById( "paypal_resource_id" ).value = payload.orderId;
					document.member_card_update.submit();
				}).catch(( reason ) => {
					document.getElementById( "welcart-overlay" ).style.display = 'none';
					alert( 'Credit card could not be renewed! ' + reason.name + ' ' + reason.message );
				});
			}
		}).catch( function( err ) {
			if ( err ) {
				alert( 'Credit card could not be renewed! ' + JSON.stringify( err ) );
			}
		});
	} else {
		document.querySelector( "#paypal_card_form" ).style = 'display: none';
	}
</script>
				<?php
				print_google_recaptcha_response( $usces->page, 'member-card-update', 'member_card_update' );
			endif;
		endif;
	}

	/**
	 * Get FraudNet Session Identifier.
	 */
	private function get_fraudnet_session_id() {
		$session_id = substr( base_convert( hash( 'sha256', uniqid() ), 16, 36 ), 0, 32 );
		return $session_id;
	}

	/**
	 * Get seller nonce.
	 * S256 - The code verifier must be high-entropy cryptographic random string with a byte length of 43-128 range.
	 */
	private function get_seller_nonce() {
		$seller_nonce = substr( base_convert( hash( 'sha256', uniqid() ), 16, 36 ), 0, 48 );
		return $seller_nonce;
	}

	/**
	 * Get Access Token.
	 */
	private function get_access_token() {
		$acting_opts     = $this->get_acting_settings();
		$cp_client_id    = ( isset( $acting_opts['client_id'] ) ) ? $acting_opts['client_id'] : '';
		$cp_secret       = ( isset( $acting_opts['secret'] ) ) ? $acting_opts['secret'] : '';
		$api_request_url = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';
		$headers         = array(
			'Content-Type: application/x-www-form-urlencoded;application/json',
			'Accept: application/json',
		);
		$ch              = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $api_request_url . '/v1/oauth2/token' );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_USERPWD, $cp_client_id . ':' . $cp_secret );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials' );

		$result = curl_exec( $ch );
		if ( ! $result ) {
			$error = curl_error( $ch );
			unset( $ch );
			return json_decode( $error, true );
		}
		unset( $ch );
		return json_decode( $result, true );
	}

	/**
	 * Get Client Token.
	 *
	 * @param  string $customer_id Member ID.
	 * @return string
	 */
	private function get_client_token( $customer_id = '' ) {
		$acting_opts     = $this->get_acting_settings();
		$api_request_url = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';
		$card_prefix     = ( isset( $acting_opts['card_prefix'] ) ) ? $acting_opts['card_prefix'] : '';

		/* Get Access Token */
		$access_token = $this->get_access_token();

		$params = array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type'  => 'application/json;charset=utf-8',
				'Authorization' => 'Bearer ' . $access_token['access_token'],
			),
			'timeout' => self::API_TIMEOUT,
		);
		if ( ! empty( $customer_id ) ) {
			$body                = array();
			$body['customer_id'] = $card_prefix . $customer_id;
			$params['body']      = wp_json_encode( $body );
		}
		$response      = wp_remote_post( $api_request_url . '/v1/identity/generate-token', $params );
		$response_data = json_decode( wp_remote_retrieve_body( $response ) );
		return $response_data->client_token;
	}

	/**
	 * Onboarded.
	 * Get seller REST API credentials.
	 */
	public function onboarded() {
		$acting_opts     = $this->get_acting_settings();
		$api_request_url = self::API_URL;

		$seller_nonce = $this->get_seller_nonce();
		$auth_code    = $_POST['authCode'];
		$shared_id    = $_POST['sharedId'];
		$seller_nonce = $_POST['seller_nonce'];

		$headers = array(
			'Content-Type: application/x-www-form-urlencoded;application/json',
			'Accept: application/json',
		);
		$ch      = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $api_request_url . '/v1/oauth2/token' );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_USERPWD, $shared_id . ':' );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, 'grant_type=authorization_code&code=' . $auth_code . '&code_verifier=' . $seller_nonce );

		$result = curl_exec( $ch );
		unset( $ch );
		$seller_access_token = json_decode( $result, true );
		if ( isset( $seller_access_token['access_token'] ) ) {
			$partner_merchant_id = 'T7SS625X3B32Q';
			$params              = array(
				'method'  => 'GET',
				'headers' => array(
					'Content-Type'  => 'application/json;charset=utf-8',
					'Authorization' => 'Bearer ' . $seller_access_token['access_token'],
				),
				'timeout' => self::API_TIMEOUT,
			);
			$response            = wp_remote_get( $api_request_url . '/v1/customer/partners/' . $partner_merchant_id . '/merchant-integrations/credentials/', $params );
			$response_data       = json_decode( wp_remote_retrieve_body( $response ), true );
			wp_send_json( $response_data );
		} else {
			wp_send_json( $seller_access_token );
		}
	}

	/**
	 * Create billing agreement.
	 */
	public function create_billing_agreement() {
		global $usces;

		$entry = $usces->cart->get_entry();
		$cart  = $usces->cart->get_cart();

		$usces->error_message = $usces->zaiko_check();
		if ( ! empty( $usces->error_message ) || 0 === (int) $usces->cart->num_row() ) {
			wp_send_json( array( 'stock' => 'error' ) );
		}

		$tracking_id = wp_unslash( $_POST['tracking_id'] );
		$billing     = wp_unslash( $_POST['billing'] ); /* continue|regular */
		$shipping    = usces_have_shipped( $cart );

		$acting_opts     = $this->get_acting_settings();
		$api_request_url = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';

		/* Get Access Token */
		$access_token = $this->get_access_token();

		if ( 'continue' === $billing ) {
			$description = __( '[ Automatic recurring billing ]', 'usces' );
		} elseif ( 'regular' === $billing ) {
			$description = __( '[ Regular Purchase ]', 'usces' );
		} else {
			$description = 'Billing Agreement.';
		}

		$body                            = array();
		$body['description']             = $description;
		$body['payer']['payment_method'] = 'PAYPAL';
		$body['plan']['type']            = 'MERCHANT_INITIATED_BILLING';
		$body['plan']['merchant_preferences']['return_url']         = USCES_CART_URL;
		$body['plan']['merchant_preferences']['cancel_url']         = USCES_CART_URL;
		$body['plan']['merchant_preferences']['notify_url']         = USCES_CART_URL;
		$body['plan']['merchant_preferences']['accepted_pymt_type'] = 'INSTANT';
		if ( $shipping ) {
			$name                                       = usces_localized_name( trim( $entry['delivery']['name1'] ), trim( $entry['delivery']['name2'] ), 'return' );
			$country                                    = ( ! empty( $entry['delivery']['country'] ) ) ? $entry['delivery']['country'] : usces_get_base_country();
			$country_code                               = apply_filters( 'usces_filter_paypalcp_shipping_country_code', $country );
			$shipping_address                           = array();
			$body['shipping_address']['line2']          = trim( $entry['delivery']['address3'] );
			$body['shipping_address']['line1']          = trim( $entry['delivery']['address2'] );
			$body['shipping_address']['city']           = trim( $entry['delivery']['address1'] );
			$body['shipping_address']['state']          = trim( $entry['delivery']['pref'] );
			$body['shipping_address']['postal_code']    = trim( $entry['delivery']['zipcode'] );
			$body['shipping_address']['country_code']   = $country_code;
			$body['shipping_address']['recipient_name'] = $name;
			$body['plan']['merchant_preferences']['skip_shipping_address']      = false;
			$body['plan']['merchant_preferences']['immutable_shipping_address'] = true;
		} else {
			$body['plan']['merchant_preferences']['skip_shipping_address']      = true;
			$body['plan']['merchant_preferences']['immutable_shipping_address'] = false;
		}

		$params        = array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type'                  => 'application/json;charset=utf-8',
				'Authorization'                 => 'Bearer ' . $access_token['access_token'],
				'PayPal-Partner-Attribution-Id' => self::API_BN_CODE_PCP,
				'PayPal-Request-Id'             => $tracking_id,
			),
			'timeout' => self::API_TIMEOUT,
			'body'    => wp_json_encode( $body ),
		);
		$response      = wp_remote_post( $api_request_url . '/v1/billing-agreements/agreement-tokens', $params );
		$response_data = json_decode( wp_remote_retrieve_body( $response ) );
		wp_send_json( $response_data );
	}

	/**
	 * Create order.
	 */
	public function create_order() {
		global $usces;

		$entry = $usces->cart->get_entry();
		$cart  = $usces->cart->get_cart();

		$usces->error_message = $usces->zaiko_check();
		if ( ! empty( $usces->error_message ) || 0 === (int) $usces->cart->num_row() ) {
			wp_send_json( array( 'stock' => 'error' ) );
		}

		$bn_code     = self::API_BN_CODE_PCP;
		$tracking_id = wp_unslash( $_POST['tracking_id'] );

		/* Get Access Token */
		$access_token = $this->get_access_token();

		/* Create order */
		$response_data = $this->api_create_order( $access_token, $bn_code, '', $tracking_id, $entry, $cart, false );
		wp_send_json( $response_data );
	}

	/**
	 * Create order.
	 */
	public function create_order_card() {
		global $usces;

		$entry = $usces->cart->get_entry();
		$cart  = $usces->cart->get_cart();

		$usces->error_message = $usces->zaiko_check();
		if ( ! empty( $usces->error_message ) || 0 === (int) $usces->cart->num_row() ) {
			wp_send_json( array( 'stock' => 'error' ) );
		}

		$bn_code                             = self::API_BN_CODE_ACDC;
		$tracking_id                         = wp_unslash( $_POST['tracking_id'] );
		$entry['order']['total_items_price'] = $usces->get_total_price( $cart );
		$entry['pre_card_order']             = true;

		/* Get Access Token */
		$access_token = $this->get_access_token();

		/* Create order */
		$response_data = $this->api_create_order( $access_token, $bn_code, '', $tracking_id, $entry, $cart, false );
		wp_send_json( $response_data );
	}

	/**
	 * Create order.
	 */
	public function create_order_member() {
		global $usces;

		$acting_opts = $this->get_acting_settings();
		$tracking_id = wp_unslash( $_POST['tracking_id'] );

		/* Get Access Token */
		$access_token = $this->get_access_token();

		/* Create order */
		$api_request_url = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';
		$currency_code   = $usces->get_currency_code();

		$body                                      = array();
		$body['intent']                            = 'AUTHORIZE';
		$purchase_units                            = array();
		$purchase_units['reference_id']            = $tracking_id;
		$purchase_units['amount']['currency_code'] = $currency_code;
		$purchase_units['amount']['value']         = 10;
		$body['purchase_units']                    = array( $purchase_units );

		$member                      = $usces->get_member();
		$payer                       = array();
		$phone                       = ( ! empty( $member['tel'] ) ) ? str_replace( array( '-', '+', ' ' ), '', mb_convert_kana( $member['tel'], 'a', 'UTF-8' ) ) : '';
		$country                     = ( ! empty( $member['country'] ) ) ? $member['country'] : usces_get_base_country();
		$country_code                = apply_filters( 'usces_filter_paypalcp_customer_country_code', $country );
		$payer['name']['given_name'] = trim( $member['name2'] );
		$payer['name']['surname']    = trim( $member['name1'] );
		$payer['email_address']      = trim( $member['mailaddress1'] );
		if ( ! empty( $phone ) ) {
			$payer['phone']['phone_number']['national_number'] = ltrim( $phone, '0' );
		}
		if ( ! empty( $member['address1'] ) ) {
			$payer['address']['address_line_2'] = trim( $member['address3'] );
			$payer['address']['address_line_1'] = trim( $member['address2'] );
			$payer['address']['admin_area_2']   = trim( $member['address1'] );
		}
		if ( ! empty( $member['pref'] ) ) {
			$payer['address']['admin_area_1'] = trim( $member['pref'] );
		}
		if ( ! empty( $member['zipcode'] ) ) {
			$payer['address']['postal_code'] = trim( mb_convert_kana( $member['zipcode'], 'a', 'UTF-8' ) );
		}
		$payer['address']['country_code'] = $country_code;
		$body['payer']                    = $payer;

		$params   = array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type'                  => 'application/json;charset=utf-8',
				'Authorization'                 => 'Bearer ' . $access_token['access_token'],
				'PayPal-Partner-Attribution-Id' => self::API_BN_CODE_ACDC,
				'PayPal-Request-Id'             => $tracking_id,
			),
			'timeout' => self::API_TIMEOUT,
			'body'    => wp_json_encode( $body ),
		);
		$response = wp_remote_post( $api_request_url . '/v2/checkout/orders', $params );
		if ( isset( $response['response']['code'] ) && ( '200' === (string) $response['response']['code'] || '201' === (string) $response['response']['code'] ) ) {
			$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
		} else {
			$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $response_data ) && ! isset( $response_data['status'] ) && ! isset( $response_data['name'] ) ) {
				$response_data['name'] = 'CREATE ORDER ERROR';
			}
		}
		wp_send_json( $response_data );
	}

	/**
	 * Show Order Details.
	 */
	public function show_order_details_card() {
		$bn_code     = self::API_BN_CODE_ACDC;
		$tracking_id = ( isset( $_POST['tracking_id'] ) ) ? wp_unslash( $_POST['tracking_id'] ) : ''; /* rand|reference_id */
		$resource_id = ( isset( $_POST['resource_id'] ) ) ? wp_unslash( $_POST['resource_id'] ) : '';

		/* Get Access Token */
		$access_token = $this->get_access_token();

		/* Get order */
		$response_data = $this->api_get_order( $access_token, $bn_code, $tracking_id, $resource_id );
		wp_send_json( $response_data );
	}

	/**
	 * 決済処理
	 * usces_action_acting_processing
	 *
	 * @param string $acting_flg Payment type.
	 * @param array  $post_query Post data.
	 */
	public function acting_processing( $acting_flg, $post_query ) {
		global $usces;

		if ( 'acting_paypal_cp' === $acting_flg ) {
			parse_str( $post_query, $post_data );
			$tracking_id   = ( isset( $post_data['tracking_id'] ) ) ? $post_data['tracking_id'] : ''; /* rand|reference_id */
			$resource_id   = ( isset( $post_data['resource_id'] ) ) ? $post_data['resource_id'] : '';
			$billing_token = ( isset( $post_data['billing_token'] ) ) ? $post_data['billing_token'] : '';

			if ( empty( $tracking_id ) ) {
				$log = array(
					'acting' => $this->paymod_id,
					'key'    => '(empty)',
					'result' => 'ON APPROVE ERROR',
					'data'   => $post_data,
				);
				usces_save_order_acting_error( $log );
				wp_redirect(
					add_query_arg(
						array(
							'acting'        => $this->paymod_id,
							'acting_return' => 0,
							'result'        => 0,
						),
						USCES_CART_URL
					)
				);
				exit();
			}

			$acting_opts     = $this->get_acting_settings();
			$cp_intent       = ( isset( $acting_opts['intent'] ) ) ? $acting_opts['intent'] : '';
			$api_request_url = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';
			$bn_code         = self::API_BN_CODE_PCP;
			$correlation_id  = '';
			$payment_source  = array();

			/* Get Access Token */
			$access_token = $this->get_access_token();

			if ( $billing_token ) {
				$params_billing_agreements        = array(
					'method'  => 'POST',
					'headers' => array(
						'Content-Type'                  => 'application/json;charset=utf-8',
						'Authorization'                 => 'Bearer ' . $access_token['access_token'],
						'PayPal-Partner-Attribution-Id' => $bn_code,
						'PayPal-Request-Id'             => $tracking_id,
					),
					'timeout' => self::API_TIMEOUT,
				);
				$response_billing_agreements      = wp_remote_post( $api_request_url . '/v1/billing-agreements/' . $billing_token . '/agreements', $params_billing_agreements );
				$response_billing_agreements_data = json_decode( wp_remote_retrieve_body( $response_billing_agreements ), true );
				if ( isset( $response_billing_agreements['response']['code'] ) && '201' === (string) $response_billing_agreements['response']['code'] && isset( $response_billing_agreements_data['id'] ) ) {
					$ba_id                   = $response_billing_agreements_data['id'];
					$payment_source['token'] = array(
						'id'   => $ba_id,
						'type' => 'BILLING_AGREEMENT',
					);
					$entry                   = $usces->cart->get_entry();
					$cart                    = $usces->cart->get_cart();
					$continue                = ( defined( 'WCEX_DLSELLER' ) ) ? usces_have_continue_charge( $cart ) : false;
					$regular                 = ( defined( 'WCEX_AUTO_DELIVERY' ) ) ? usces_have_regular_order() : false;

					if ( $continue ) {
						$chargingday = $usces->getItemChargingDay( $cart[0]['post_id'] );
						/* 受注日課金 */
						if ( 99 !== (int) $chargingday ) {
							$cp_intent = 'AUTHORIZE';
						}
					}

					/* Create order */
					$response_order_data = $this->api_create_order( $access_token, $bn_code, $cp_intent, $tracking_id, $entry, $cart );
					if ( isset( $response_order_data['id'] ) && isset( $response_order_data['status'] ) && 'CREATED' === $response_order_data['status'] ) {
						$resource_id = $response_order_data['id'];
					} else {
						$result                     = 'CREATE ORDER ERROR';
						$params_cancel_agreements   = array(
							'method'  => 'POST',
							'headers' => array(
								'Content-Type'      => 'application/json;charset=utf-8',
								'Authorization'     => 'Bearer ' . $access_token['access_token'],
								'PayPal-Partner-Attribution-Id' => $bn_code,
								'PayPal-Request-Id' => $tracking_id,
							),
							'timeout' => self::API_TIMEOUT,
						);
						$response_cancel_agreements = wp_remote_post( $api_request_url . '/v1/billing-agreements/agreements/' . $ba_id . '/cancel', $params_cancel_agreements );
						if ( isset( $response_cancel_agreements['response']['code'] ) && '200' === (string) $response_cancel_agreements['response']['code'] ) {
							$result .= ' (BILLING AGREEMENT CANCEL COMPLETED)';
						}
						$log = array(
							'acting' => $this->paymod_id,
							'key'    => $tracking_id,
							'result' => $result,
							'data'   => $response_order_data,
						);
						usces_save_order_acting_error( $log );
						wp_redirect(
							add_query_arg(
								array(
									'acting'        => $this->paymod_id,
									'acting_return' => 0,
									'result'        => 0,
								),
								USCES_CART_URL
							)
						);
						exit();
					}
				} else {
					$log = array(
						'acting' => $this->paymod_id,
						'key'    => $tracking_id,
						'result' => 'BILLING AGREEMENT ERROR',
						'data'   => $response_billing_agreements_data,
					);
					usces_save_order_acting_error( $log );
					wp_redirect(
						add_query_arg(
							array(
								'acting'        => $this->paymod_id,
								'acting_return' => 0,
								'result'        => 0,
							),
							USCES_CART_URL
						)
					);
					exit();
				}
			} else {
				/* Get Order */
				$response_order_data = $this->api_get_order( $access_token, $bn_code, $tracking_id, $resource_id );
				if ( isset( $response_order_data['intent'] ) && $cp_intent === $response_order_data['intent'] && isset( $response_order_data['status'] ) && 'APPROVED' === $response_order_data['status'] ) {
				} else {
					/**
					 * GET ORDER ERROR frequent, avoid because not required.
					if ( empty( $response_order_data ) ) {
						$response_order_data = $post_data;
					}
					$log = array(
						'acting' => $this->paymod_id,
						'key'    => $tracking_id,
						'result' => 'GET ORDER ERROR',
						'data'   => $response_order_data,
					);
					usces_save_order_acting_error( $log );
					wp_redirect(
						add_query_arg(
							array(
								'acting'        => $this->paymod_id,
								'acting_return' => 0,
								'result'        => 0,
							),
							USCES_CART_URL
						)
					);
					exit();
					 */
					usces_log( '[' . $this->paymod_id . '] GET ORDER ERROR : ' . print_r( $response_order_data, true ), 'acting_transaction.log' );
				}
			}

			$usces->error_message = $usces->zaiko_check();
			if ( ! empty( $usces->error_message ) || 0 === (int) $usces->cart->num_row() ) {
				wp_redirect( USCES_CART_URL );
				exit();
			}

			if ( 'CAPTURE' === $cp_intent ) {
				/* Capture */
				$response_data = $this->api_capture_order( $access_token, $bn_code, $tracking_id, $resource_id, $correlation_id, $payment_source );
			} elseif ( 'AUTHORIZE' === $cp_intent ) {
				/* Authorize */
				$response_data = $this->api_authorize_order( $access_token, $bn_code, $tracking_id, $resource_id, $correlation_id, $payment_source );
			}
			if ( isset( $response_data['id'] ) && isset( $response_data['status'] ) && 'COMPLETED' === $response_data['status'] ) {
				if ( ! empty( $ba_id ) ) {
					$response_data['ba_id'] = $ba_id;
				}

				/* Welcart order data registration */
				$res = $usces->order_processing( $response_data );
				if ( 'ordercompletion' === $res ) {
					wp_redirect(
						add_query_arg(
							array(
								'acting'        => $this->paymod_id,
								'acting_return' => 1,
								'result'        => 1,
								'_nonce'        => $post_data['_nonce'],
							),
							USCES_CART_URL
						)
					);
				} else {
					$log = array(
						'acting' => $this->paymod_id,
						'key'    => $tracking_id,
						'result' => 'ORDER DATA REGISTRATION ERROR',
						'data'   => $response_data,
					);
					usces_save_order_acting_error( $log );
					wp_redirect(
						add_query_arg(
							array(
								'acting'        => $this->paymod_id,
								'acting_return' => 0,
								'result'        => 0,
							),
							USCES_CART_URL
						)
					);
				}
				exit();
			} else {
				$log = array(
					'acting' => $this->paymod_id,
					'key'    => $tracking_id,
					'result' => $cp_intent . ' ERROR',
					'data'   => $response_data,
				);
				usces_save_order_acting_error( $log );
				wp_redirect(
					add_query_arg(
						array(
							'acting'        => $this->paymod_id,
							'acting_return' => 0,
							'result'        => 0,
						),
						USCES_CART_URL
					)
				);
				exit();
			}
		} elseif ( 'acting_paypal_card' === $acting_flg ) {
			$entry = $usces->cart->get_entry();
			$cart  = $usces->cart->get_cart();
			if ( empty( $cart ) || empty( $entry ) ) {
				return;
			}

			parse_str( $post_query, $post_data );
			$tracking_id      = ( isset( $post_data['tracking_id'] ) ) ? $post_data['tracking_id'] : ''; /* rand|reference_id */
			$resource_id      = ( isset( $post_data['resource_id'] ) ) ? $post_data['resource_id'] : '';
			$payment_card     = ( isset( $post_data['payment_card'] ) ) ? $post_data['payment_card'] : '';
			$payment_token_id = ( isset( $post_data['payment_token_id'] ) ) ? $post_data['payment_token_id'] : '';
			$vault            = ( isset( $post_data['vault'] ) ) ? $post_data['vault'] : '';

			if ( 'vault' === $payment_card && ! empty( $payment_token_id ) ) {
				if ( empty( $tracking_id ) ) {
					$log = array(
						'acting' => 'paypal_card',
						'key'    => '(empty)',
						'result' => 'VAULT ORDER ERROR',
						'data'   => $post_data,
					);
					usces_save_order_acting_error( $log );
					wp_redirect(
						add_query_arg(
							array(
								'acting'        => 'paypal_card',
								'acting_return' => 0,
								'result'        => 0,
							),
							USCES_CART_URL
						)
					);
					exit();
				}
			} else {
				if ( empty( $tracking_id ) || empty( $resource_id ) ) {
					$log = array(
						'acting' => 'paypal_card',
						'key'    => '(empty)',
						'result' => 'CREATE ORDER ERROR',
						'data'   => $post_data,
					);
					usces_save_order_acting_error( $log );
					wp_redirect(
						add_query_arg(
							array(
								'acting'        => 'paypal_card',
								'acting_return' => 0,
								'result'        => 0,
							),
							USCES_CART_URL
						)
					);
					exit();
				}
			}

			$acting_opts     = $this->get_acting_settings();
			$cp_intent       = ( isset( $acting_opts['intent'] ) ) ? $acting_opts['intent'] : '';
			$api_request_url = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';
			$bn_code         = self::API_BN_CODE_ACDC;

			/* Get Access Token */
			$access_token = $this->get_access_token();

			$correlation_id = '';
			$payment_source = array();

			if ( 'vault' === $payment_card && ! empty( $payment_token_id ) ) {
				$correlation_id          = ( isset( $post_data['correlation_id'] ) ) ? $post_data['correlation_id'] : '';
				$payment_source['token'] = array(
					'id'   => $payment_token_id,
					'type' => 'PAYMENT_METHOD_TOKEN',
				);
				$entry['correlation_id'] = $correlation_id;

				/* Create order */
				$response_order_data = $this->api_create_order( $access_token, $bn_code, $cp_intent, $tracking_id, $entry, $cart );
				if ( isset( $response_order_data['id'] ) && isset( $response_order_data['status'] ) && 'CREATED' === $response_order_data['status'] ) {
					$resource_id = $response_order_data['id'];
				} else {
					$log = array(
						'acting' => 'paypal_card',
						'key'    => $tracking_id,
						'result' => 'CREATE ORDER ERROR',
						'data'   => $response_order_data,
					);
					usces_save_order_acting_error( $log );
					wp_redirect(
						add_query_arg(
							array(
								'acting'        => 'paypal_card',
								'acting_return' => 0,
								'result'        => 0,
							),
							USCES_CART_URL
						)
					);
					exit();
				}
			} else {
				$currency_code     = $usces->get_currency_code();
				$cart_count        = ( $cart && is_array( $cart ) ) ? count( $cart ) : 0;
				$item_total        = (float) $entry['order']['total_items_price'];
				$shipping          = usces_have_shipped( $cart );
				$shipping_charge   = ( $shipping && isset( $entry['order']['shipping_charge'] ) && 0.0 !== (float) $entry['order']['shipping_charge'] ) ? (float) $entry['order']['shipping_charge'] : 0;
				$multiple_shipping = ( defined( 'WCEX_MSA' ) && isset( $entry['delivery']['delivery_flag'] ) && 2 === (int) $entry['delivery']['delivery_flag'] ) ? true : false;
				$fee               = ( isset( $entry['order']['cod_fee'] ) && 0.0 !== (float) $entry['order']['cod_fee'] ) ? (float) $entry['order']['cod_fee'] : 0;
				$tax               = ( isset( $entry['order']['tax'] ) && 'exclude' === usces_get_tax_mode() ) ? (float) $entry['order']['tax'] : 0;
				$discount          = ( isset( $entry['order']['discount'] ) && 0.0 !== (float) $entry['order']['discount'] ) ? ( (float) $entry['order']['discount'] * -1 ) : 0;
				if ( usces_is_member_system() && usces_is_member_system_point() && isset( $entry['order']['usedpoint'] ) && 0 !== (int) $entry['order']['usedpoint'] ) {
					$discount += (float) $entry['order']['usedpoint'];
				}

				$body            = array();
				$body[0]['op']   = 'replace';
				$body[0]['path'] = "/purchase_units/@reference_id=='" . $tracking_id . "'";

				$purchase_units['reference_id']            = $tracking_id;
				$purchase_units['amount']['currency_code'] = $currency_code;
				$purchase_units['amount']['value']         = usces_crform( $entry['order']['total_full_price'], false, false, 'return', false );
				if ( 0 < $item_total ) {
					$breakdown                                = array();
					$breakdown['item_total']['currency_code'] = $currency_code;
					$breakdown['item_total']['value']         = usces_crform( $item_total, false, false, 'return', false );
					if ( $shipping && 0 < $shipping_charge ) {
						$breakdown['shipping']['currency_code'] = $currency_code;
						$breakdown['shipping']['value']         = usces_crform( $shipping_charge, false, false, 'return', false );
					}
					if ( 0 < $fee ) {
						$breakdown['handling']['currency_code'] = $currency_code;
						$breakdown['handling']['value']         = usces_crform( $fee, false, false, 'return', false );
					}
					if ( 0 < $tax ) {
						$breakdown['tax_total']['currency_code'] = $currency_code;
						$breakdown['tax_total']['value']         = usces_crform( $tax, false, false, 'return', false );
					}
					if ( 0 < $discount ) {
						$breakdown['discount']['currency_code'] = $currency_code;
						$breakdown['discount']['value']         = usces_crform( $discount, false, false, 'return', false );
					}
					$purchase_units['amount']['breakdown'] = $breakdown;
					$items                                 = array();
					for ( $i = 0; $i < $cart_count; $i++ ) {
						$cart_row  = $cart[ $i ];
						$item_name = $usces->getItemName( $cart_row['post_id'] );
						if ( 60 < mb_strlen( $item_name, 'UTF-8' ) ) {
							$item_name = mb_substr( $item_name, 0, 60, 'UTF-8' ) . '...';
						}
						$items[ $i ]['name']                         = $item_name;
						$items[ $i ]['unit_amount']['currency_code'] = $currency_code;
						$items[ $i ]['unit_amount']['value']         = usces_crform( $cart_row['price'], false, false, 'return', false );
						$items[ $i ]['quantity']                     = $cart_row['quantity'];
						$options                                     = ( isset( $cart_row['options'] ) && is_array( $cart_row['options'] ) ) ? $cart_row['options'] : array();
						if ( 0 < count( $options ) ) {
							$description = '';
							foreach ( $options as $key => $value ) {
								if ( ! empty( $key ) ) {
									$key   = urldecode( $key );
									$value = maybe_unserialize( $value );
									if ( is_array( $value ) ) {
										$c            = '';
										$description .= $key . ' : ';
										foreach ( $value as $v ) {
											$description .= $c . urldecode( $v );
											$c            = ', ';
										}
										$description .= "\r\n";
									} else {
										$description .= $key . ' : ' . urldecode( $value ) . "\r\n";
									}
								}
							}
							if ( '' !== $description ) {
								if ( 60 < mb_strlen( $description, 'UTF-8' ) ) {
									$description = mb_substr( $description, 0, 60, 'UTF-8' ) . '...';
								}
								$items[ $i ]['description'] = $description;
							}
						}
						$items[ $i ]['sku'] = $usces->getItemCode( $cart_row['post_id'] ) . ' ' . urldecode( $cart_row['sku'] );
					}
					$purchase_units['items'] = $items;
				}

				$application_context = array();
				if ( $multiple_shipping && false === $array ) {
					$msacart = ( isset( $_SESSION['msa_cart'] ) ) ? current( $_SESSION['msa_cart'] ) : array();
					if ( isset( $msacart['delivery']['destination_id'] ) ) {
						$member      = $usces->get_member();
						$msadelivery = msa_get_destination( $member['ID'], $msacart['delivery']['destination_id'] );
						$name        = usces_localized_name( trim( $msadelivery['msa_name'] ), trim( $msadelivery['msa_name2'] ), 'return' );
						$purchase_units['shipping']['name']['full_name']         = $name;
						$purchase_units['shipping']['address']['address_line_2'] = trim( $msadelivery['msa_address3'] );
						$purchase_units['shipping']['address']['address_line_1'] = trim( $msadelivery['msa_address2'] );
						$purchase_units['shipping']['address']['admin_area_2']   = trim( $msadelivery['msa_address1'] );
						$purchase_units['shipping']['address']['admin_area_1']   = trim( $msadelivery['msa_pref'] );
						$purchase_units['shipping']['address']['postal_code']    = trim( mb_convert_kana( $msadelivery['msa_zip'], 'a', 'UTF-8' ) );
						$purchase_units['shipping']['address']['country_code']   = 'JP';
						$application_context['shipping_preference']              = 'SET_PROVIDED_ADDRESS';
					} else {
						$application_context['shipping_preference'] = 'NO_SHIPPING';
					}
				} elseif ( $shipping ) {
					$name         = usces_localized_name( trim( $entry['delivery']['name1'] ), trim( $entry['delivery']['name2'] ), 'return' );
					$country      = ( ! empty( $entry['delivery']['country'] ) ) ? $entry['delivery']['country'] : usces_get_base_country();
					$country_code = apply_filters( 'usces_filter_paypalcp_shipping_country_code', $country );
					$purchase_units['shipping']['name']['full_name']         = $name;
					$purchase_units['shipping']['address']['address_line_2'] = trim( $entry['delivery']['address3'] );
					$purchase_units['shipping']['address']['address_line_1'] = trim( $entry['delivery']['address2'] );
					$purchase_units['shipping']['address']['admin_area_2']   = trim( $entry['delivery']['address1'] );
					$purchase_units['shipping']['address']['admin_area_1']   = trim( $entry['delivery']['pref'] );
					$purchase_units['shipping']['address']['postal_code']    = trim( mb_convert_kana( $entry['delivery']['zipcode'], 'a', 'UTF-8' ) );
					$purchase_units['shipping']['address']['country_code']   = $country_code;
					$application_context['shipping_preference']              = 'SET_PROVIDED_ADDRESS';
				} else {
					$application_context['shipping_preference'] = 'NO_SHIPPING';
				}
				// $body['application_context'] = $application_context;

				$body[0]['value'] = $purchase_units;

				/* Update Order */
				$params   = array(
					'method'  => 'PATCH',
					'headers' => array(
						'Content-Type'                  => 'application/json',
						'Authorization'                 => 'Bearer ' . $access_token['access_token'],
						'PayPal-Partner-Attribution-Id' => $bn_code,
						'PayPal-Request-Id'             => $tracking_id,
					),
					'timeout' => self::API_TIMEOUT,
					'body'    => wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
				);
				$response = wp_remote_request( $api_request_url . '/v2/checkout/orders/' . $resource_id, $params );
				if ( isset( $response['response']['code'] ) && '204' === (string) $response['response']['code'] ) {
					/* A successful request returns the HTTP 204 No Content status code with an empty object in the JSON response body. */
				} else {
					$log = array(
						'acting' => 'paypal_card',
						'key'    => $tracking_id,
						'result' => 'UPDATE ORDER ERROR',
						'data'   => $response,
					);
					usces_save_order_acting_error( $log );
					wp_redirect(
						add_query_arg(
							array(
								'acting'        => 'paypal_card',
								'acting_return' => 0,
								'result'        => 0,
							),
							USCES_CART_URL
						)
					);
					exit();
				}
			}

			$usces->error_message = $usces->zaiko_check();
			if ( ! empty( $usces->error_message ) || 0 === (int) $usces->cart->num_row() ) {
				wp_redirect( USCES_CART_URL );
				exit();
			}

			if ( 'CAPTURE' === $cp_intent ) {
				/* Capture */
				$response_data = $this->api_capture_order( $access_token, $bn_code, $tracking_id, $resource_id, $correlation_id, $payment_source );
			} elseif ( 'AUTHORIZE' === $cp_intent ) {
				/* Authorize */
				$response_data = $this->api_authorize_order( $access_token, $bn_code, $tracking_id, $resource_id, $correlation_id, $payment_source );
			}
			if ( isset( $response_data['status'] ) ) {
				$status = $response_data['status'];
			} else {
				$status = $cp_intent . ' ERROR';
			}
			if ( isset( $response_data['purchase_units'] ) ) {
				$purchase_units = $response_data['purchase_units'][0];
				if ( isset( $purchase_units['payments']['captures'] ) ) {
					$payments = $purchase_units['payments']['captures'][0];
					if ( isset( $payments['status'] ) ) {
						if ( 'COMPLETED' !== $payments['status'] && 'PENDING' !== $payments['status'] ) {
							$status = $payments['status'];
						}
					}
				} elseif ( isset( $purchase_units['payments']['authorizations'] ) ) {
					$payments = $purchase_units['payments']['authorizations'][0];
					if ( isset( $payments['status'] ) ) {
						if ( 'CREATED' !== $payments['status'] && 'PENDING' !== $payments['status'] ) {
							$status = $payments['status'];
						}
					}
				}
			}
			if ( isset( $response_data['id'] ) && 'COMPLETED' === $status ) {
				if ( 'vault' === $vault && 'another' === $payment_card && ! empty( $payment_token_id ) ) {
					$delete_status = $this->api_delete_payment_tokens( $access_token, $payment_token_id );
				}
				if ( isset( $acting_opts['card_3ds'] ) && 'on' === $acting_opts['card_3ds'] ) {
					$response_data['card_3ds']['liability_shift']       = ( isset( $post_data['liability_shift'] ) ) ? $post_data['liability_shift'] : '';
					$response_data['card_3ds']['enrollment_status']     = ( isset( $post_data['enrollment_status'] ) ) ? $post_data['enrollment_status'] : '';
					$response_data['card_3ds']['authentication_status'] = ( isset( $post_data['authentication_status'] ) ) ? $post_data['authentication_status'] : '';
				}

				/* Welcart order data registration */
				$res = $usces->order_processing( $response_data );
				if ( 'ordercompletion' === $res ) {
					wp_redirect(
						add_query_arg(
							array(
								'acting'        => 'paypal_card',
								'acting_return' => 1,
								'result'        => 1,
								'_nonce'        => $post_data['_nonce'],
							),
							USCES_CART_URL
						)
					);
				} else {
					$log = array(
						'acting' => 'paypal_card',
						'key'    => $tracking_id,
						'result' => 'ORDER DATA REGISTRATION ERROR',
						'data'   => $response_data,
					);
					usces_save_order_acting_error( $log );
					wp_redirect(
						add_query_arg(
							array(
								'acting'        => 'paypal_card',
								'acting_return' => 0,
								'result'        => 0,
							),
							USCES_CART_URL
						)
					);
				}
				exit();
			} else {
				$log = array(
					'acting' => 'paypal_card',
					'key'    => $tracking_id,
					'result' => $cp_intent . ' ERROR',
					'data'   => $response_data,
				);
				usces_save_order_acting_error( $log );
				wp_redirect(
					add_query_arg(
						array(
							'acting'        => 'paypal_card',
							'acting_return' => 0,
							'retry'         => 1,
						),
						USCES_CART_URL
					)
				);
				exit();
			}
		}
	}

	/**
	 * Orders API.
	 * /v2/checkout/orders
	 *
	 * @param  array   $access_token API Access Token.
	 * @param  string  $bn_code BN Code.
	 * @param  string  $intent AUTHORIZE|CAPTURE.
	 * @param  string  $tracking_id Tracking ID.
	 * @param  array   $entry Entry data.
	 * @param  array   $cart Cart data.
	 * @param  boolean $array Associative true|false.
	 * @return array|object
	 */
	public function api_create_order( $access_token, $bn_code, $intent, $tracking_id, $entry, $cart, $array = true ) {
		global $usces;

		$correlation_id = ( ! empty( $entry['correlation_id'] ) ) ? $entry['correlation_id'] : ''; // Purchase using a saved card.
		$pre_card_order = ( isset( $entry['pre_card_order'] ) && '' === $correlation_id ) ? true : false; // Pre-Order. Orders will be updated later.

		$acting_opts = $this->get_acting_settings();
		if ( empty( $intent ) ) {
			$intent = ( isset( $acting_opts['intent'] ) ) ? $acting_opts['intent'] : '';
		}
		$api_request_url = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';
		$currency_code   = $usces->get_currency_code();
		$cart_count      = ( $cart && is_array( $cart ) ) ? count( $cart ) : 0;
		if ( 0 < $cart_count && isset( $entry['order']['total_items_price'] ) ) {
			$item_total        = (float) $entry['order']['total_items_price'];
			$shipping          = usces_have_shipped( $cart );
			$shipping_charge   = ( $shipping && isset( $entry['order']['shipping_charge'] ) && 0.0 !== (float) $entry['order']['shipping_charge'] ) ? (float) $entry['order']['shipping_charge'] : 0;
			$multiple_shipping = ( defined( 'WCEX_MSA' ) && isset( $entry['delivery']['delivery_flag'] ) && 2 === (int) $entry['delivery']['delivery_flag'] ) ? true : false;
			$fee               = ( isset( $entry['order']['cod_fee'] ) && 0.0 !== (float) $entry['order']['cod_fee'] ) ? (float) $entry['order']['cod_fee'] : 0;
			$tax               = ( isset( $entry['order']['tax'] ) && 'exclude' === usces_get_tax_mode() ) ? (float) $entry['order']['tax'] : 0;
			$discount          = ( isset( $entry['order']['discount'] ) && 0.0 !== (float) $entry['order']['discount'] ) ? ( (float) $entry['order']['discount'] * -1 ) : 0;
			if ( usces_is_member_system() && usces_is_member_system_point() && isset( $entry['order']['usedpoint'] ) && 0 !== (int) $entry['order']['usedpoint'] ) {
				$discount += (float) $entry['order']['usedpoint'];
			}
		} else {
			$item_total        = 0;
			$shipping          = false;
			$multiple_shipping = false;
		}

		$body                           = array();
		$body['intent']                 = $intent;
		$purchase_units                 = array();
		$purchase_units['reference_id'] = $tracking_id;
		if ( $pre_card_order ) {
			$purchase_units['amount']['currency_code'] = $currency_code;
			$purchase_units['amount']['value']         = $item_total;
		} else {
			$purchase_units['amount']['currency_code'] = $currency_code;
			$purchase_units['amount']['value']         = usces_crform( $entry['order']['total_full_price'], false, false, 'return', false );
			if ( 0 < $item_total ) {
				$breakdown                                = array();
				$breakdown['item_total']['currency_code'] = $currency_code;
				$breakdown['item_total']['value']         = usces_crform( $item_total, false, false, 'return', false );
				if ( $shipping && 0 < $shipping_charge ) {
					$breakdown['shipping']['currency_code'] = $currency_code;
					$breakdown['shipping']['value']         = usces_crform( $shipping_charge, false, false, 'return', false );
				}
				if ( 0 < $fee ) {
					$breakdown['handling']['currency_code'] = $currency_code;
					$breakdown['handling']['value']         = usces_crform( $fee, false, false, 'return', false );
				}
				if ( 0 < $tax ) {
					$breakdown['tax_total']['currency_code'] = $currency_code;
					$breakdown['tax_total']['value']         = usces_crform( $tax, false, false, 'return', false );
				}
				if ( 0 < $discount ) {
					$breakdown['discount']['currency_code'] = $currency_code;
					$breakdown['discount']['value']         = usces_crform( $discount, false, false, 'return', false );
				}
				$purchase_units['amount']['breakdown'] = $breakdown;
				$items                                 = array();
				for ( $i = 0; $i < $cart_count; $i++ ) {
					$cart_row  = $cart[ $i ];
					$item_name = $usces->getItemName( $cart_row['post_id'] );
					if ( 60 < mb_strlen( $item_name, 'UTF-8' ) ) {
						$item_name = mb_substr( $item_name, 0, 60, 'UTF-8' ) . '...';
					}
					$items[ $i ]['name']                         = $item_name;
					$items[ $i ]['unit_amount']['currency_code'] = $currency_code;
					$items[ $i ]['unit_amount']['value']         = usces_crform( $cart_row['price'], false, false, 'return', false );
					$items[ $i ]['quantity']                     = $cart_row['quantity'];
					$options                                     = ( isset( $cart_row['options'] ) && is_array( $cart_row['options'] ) ) ? $cart_row['options'] : array();
					if ( 0 < count( $options ) ) {
						$description = '';
						foreach ( $options as $key => $value ) {
							if ( ! empty( $key ) ) {
								$key   = urldecode( $key );
								$value = maybe_unserialize( $value );
								if ( is_array( $value ) ) {
									$c            = '';
									$description .= $key . ' : ';
									foreach ( $value as $v ) {
										$description .= $c . urldecode( $v );
										$c            = ', ';
									}
									$description .= "\r\n";
								} else {
									$description .= $key . ' : ' . urldecode( $value ) . "\r\n";
								}
							}
						}
						if ( '' !== $description ) {
							if ( 60 < mb_strlen( $description, 'UTF-8' ) ) {
								$description = mb_substr( $description, 0, 60, 'UTF-8' ) . '...';
							}
							$items[ $i ]['description'] = $description;
						}
					}
					$items[ $i ]['sku'] = $usces->getItemCode( $cart_row['post_id'] ) . ' ' . urldecode( $cart_row['sku'] );
				}
				$purchase_units['items'] = $items;
			}
		}
		if ( false === $array ) {
			$payer                       = array();
			$name                        = usces_localized_name( trim( $entry['customer']['name1'] ), trim( $entry['customer']['name2'] ), 'return' );
			$phone                       = str_replace( array( '-', '+', ' ' ), '', mb_convert_kana( $entry['customer']['tel'], 'a', 'UTF-8' ) );
			$country                     = ( ! empty( $entry['customer']['country'] ) ) ? $entry['customer']['country'] : usces_get_base_country();
			$country_code                = apply_filters( 'usces_filter_paypalcp_customer_country_code', $country );
			$payer['name']['given_name'] = trim( $entry['customer']['name2'] );
			$payer['name']['surname']    = trim( $entry['customer']['name1'] );
			$payer['email_address']      = trim( $entry['customer']['mailaddress1'] );
			if ( ! empty( $phone ) ) {
				$payer['phone']['phone_number']['national_number'] = ltrim( $phone, '0' );
			}
			if ( ! empty( $entry['customer']['address1'] ) ) {
				$payer['address']['address_line_2'] = trim( $entry['customer']['address3'] );
				$payer['address']['address_line_1'] = trim( $entry['customer']['address2'] );
				$payer['address']['admin_area_2']   = trim( $entry['customer']['address1'] );
			}
			if ( ! empty( $entry['customer']['pref'] ) ) {
				$payer['address']['admin_area_1'] = trim( $entry['customer']['pref'] );
			}
			if ( ! empty( $entry['customer']['zipcode'] ) ) {
				$payer['address']['postal_code'] = trim( mb_convert_kana( $entry['customer']['zipcode'], 'a', 'UTF-8' ) );
			}
			$payer['address']['country_code'] = $country_code;
			$body['payer']                    = $payer;
		}
		if ( ! $pre_card_order ) {
			$application_context = array();
			if ( $multiple_shipping && false === $array ) {
				$msacart = ( isset( $_SESSION['msa_cart'] ) ) ? current( $_SESSION['msa_cart'] ) : array();
				if ( isset( $msacart['delivery']['destination_id'] ) ) {
					$member      = $usces->get_member();
					$msadelivery = msa_get_destination( $member['ID'], $msacart['delivery']['destination_id'] );
					$name        = usces_localized_name( trim( $msadelivery['msa_name'] ), trim( $msadelivery['msa_name2'] ), 'return' );
					$purchase_units['shipping']['name']['full_name']         = $name;
					$purchase_units['shipping']['address']['address_line_2'] = trim( $msadelivery['msa_address3'] );
					$purchase_units['shipping']['address']['address_line_1'] = trim( $msadelivery['msa_address2'] );
					$purchase_units['shipping']['address']['admin_area_2']   = trim( $msadelivery['msa_address1'] );
					$purchase_units['shipping']['address']['admin_area_1']   = trim( $msadelivery['msa_pref'] );
					$purchase_units['shipping']['address']['postal_code']    = trim( mb_convert_kana( $msadelivery['msa_zip'], 'a', 'UTF-8' ) );
					$purchase_units['shipping']['address']['country_code']   = 'JP';
					$application_context['shipping_preference']              = 'SET_PROVIDED_ADDRESS';
				} else {
					$application_context['shipping_preference'] = 'NO_SHIPPING';
				}
			} elseif ( $shipping ) {
				$name         = usces_localized_name( trim( $entry['delivery']['name1'] ), trim( $entry['delivery']['name2'] ), 'return' );
				$country      = ( ! empty( $entry['delivery']['country'] ) ) ? $entry['delivery']['country'] : usces_get_base_country();
				$country_code = apply_filters( 'usces_filter_paypalcp_shipping_country_code', $country );
				$purchase_units['shipping']['name']['full_name']         = $name;
				$purchase_units['shipping']['address']['address_line_2'] = trim( $entry['delivery']['address3'] );
				$purchase_units['shipping']['address']['address_line_1'] = trim( $entry['delivery']['address2'] );
				$purchase_units['shipping']['address']['admin_area_2']   = trim( $entry['delivery']['address1'] );
				$purchase_units['shipping']['address']['admin_area_1']   = trim( $entry['delivery']['pref'] );
				$purchase_units['shipping']['address']['postal_code']    = trim( mb_convert_kana( $entry['delivery']['zipcode'], 'a', 'UTF-8' ) );
				$purchase_units['shipping']['address']['country_code']   = $country_code;
				$application_context['shipping_preference']              = 'SET_PROVIDED_ADDRESS';
			} else {
				$application_context['shipping_preference'] = 'NO_SHIPPING';
			}
		}
		$body['purchase_units'] = array( $purchase_units );
		if ( ! $pre_card_order ) {
			$body['application_context'] = $application_context;
		}

		$params = array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type'                  => 'application/json;charset=utf-8',
				'Authorization'                 => 'Bearer ' . $access_token['access_token'],
				'PayPal-Partner-Attribution-Id' => $bn_code,
				'PayPal-Request-Id'             => $tracking_id,
			),
			'timeout' => self::API_TIMEOUT,
			'body'    => wp_json_encode( $body ),
		);
		if ( ! empty( $correlation_id ) ) {
			$params['headers']['PayPal-Client-Metadata-Id'] = $correlation_id;
		}
		$response = wp_remote_post( $api_request_url . '/v2/checkout/orders', $params );
		if ( isset( $response['response']['code'] ) && ( '200' === (string) $response['response']['code'] || '201' === (string) $response['response']['code'] ) ) {
			$response_data = json_decode( wp_remote_retrieve_body( $response ), $array );
		} else {
			$response_data = json_decode( wp_remote_retrieve_body( $response ), $array );
			if ( is_array( $response_data ) && ! isset( $response_data['status'] ) && ! isset( $response_data['name'] ) ) {
				$response_data['name'] = 'CREATE ORDER ERROR';
			}
		}
		return $response_data;
	}

	/**
	 * Orders API.
	 * /v2/checkout/orders/{id}
	 *
	 * @param  array   $access_token API Access Token.
	 * @param  string  $bn_code BN Code.
	 * @param  string  $tracking_id Tracking ID.
	 * @param  string  $resource_id order ID.
	 * @param  boolean $array Associative true|false.
	 * @return array
	 */
	public function api_get_order( $access_token, $bn_code, $tracking_id, $resource_id, $array = true ) {
		$acting_opts     = $this->get_acting_settings();
		$api_request_url = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';

		$params   = array(
			'method'  => 'GET',
			'headers' => array(
				'Content-Type'                  => 'application/json;charset=utf-8',
				'Authorization'                 => 'Bearer ' . $access_token['access_token'],
				'PayPal-Partner-Attribution-Id' => $bn_code,
				'PayPal-Request-Id'             => $tracking_id,
			),
			'timeout' => self::API_TIMEOUT,
		);
		$response = wp_remote_get( $api_request_url . '/v2/checkout/orders/' . $resource_id, $params );
		if ( is_wp_error( $response ) ) {
			$response_data = array();
		} else {
			$response_data = json_decode( wp_remote_retrieve_body( $response ), $array );
		}
		return $response_data;
	}

	/**
	 * Orders API.
	 * /v2/checkout/orders/{id}/capture
	 *
	 * @param  array   $access_token API Access Token.
	 * @param  string  $bn_code BN Code.
	 * @param  string  $tracking_id Tracking ID.
	 * @param  string  $resource_id order ID.
	 * @param  string  $correlation_id Correlation ID (CMID, Session Identifier f).
	 * @param  array   $payment_source Billing Agreement ID.
	 * @param  boolean $array Associative true|false.
	 * @return array
	 */
	public function api_capture_order( $access_token, $bn_code, $tracking_id, $resource_id, $correlation_id, $payment_source, $array = true ) {
		$acting_opts     = $this->get_acting_settings();
		$api_request_url = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';

		$params = array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type'                  => 'application/json;charset=utf-8',
				'Authorization'                 => 'Bearer ' . $access_token['access_token'],
				'PayPal-Partner-Attribution-Id' => $bn_code,
				'PayPal-Request-Id'             => $tracking_id,
			),
			'timeout' => self::API_TIMEOUT,
		);
		if ( ! empty( $correlation_id ) ) {
			$params['headers']['PayPal-Client-Metadata-Id'] = $correlation_id;
		}
		if ( ! empty( $payment_source ) ) {
			$body                   = array();
			$body['payment_source'] = $payment_source;
			$params['body']         = wp_json_encode( $body );
		}
		$response = wp_remote_get( $api_request_url . '/v2/checkout/orders/' . $resource_id . '/capture', $params );
		if ( is_wp_error( $response ) ) {
			$response_data = array();
		} else {
			if ( isset( $response['response']['code'] ) && ( '200' === (string) $response['response']['code'] || '201' === (string) $response['response']['code'] ) ) {
				$response_data = json_decode( wp_remote_retrieve_body( $response ), $array );
			} else {
				$response_data = json_decode( wp_remote_retrieve_body( $response ), $array );
				if ( ! isset( $response_data['status'] ) && ! isset( $response_data['name'] ) ) {
					$response_data['name'] = 'CAPTURE ERROR';
				}
			}
		}
		return $response_data;
	}

	/**
	 * Orders API.
	 * /v2/checkout/orders/{id}/authorize
	 *
	 * @param  array   $access_token API Access Token.
	 * @param  string  $bn_code BN Code.
	 * @param  string  $tracking_id Tracking ID.
	 * @param  string  $resource_id order ID.
	 * @param  string  $correlation_id Correlation ID (CMID, Session Identifier f).
	 * @param  array   $payment_source Billing Agreement ID.
	 * @param  boolean $array Associative true|false.
	 * @return array
	 */
	public function api_authorize_order( $access_token, $bn_code, $tracking_id, $resource_id, $correlation_id, $payment_source, $array = true ) {
		$acting_opts     = $this->get_acting_settings();
		$api_request_url = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';

		$params = array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type'                  => 'application/json;charset=utf-8',
				'Authorization'                 => 'Bearer ' . $access_token['access_token'],
				'PayPal-Partner-Attribution-Id' => $bn_code,
				'PayPal-Request-Id'             => $tracking_id,
			),
			'timeout' => self::API_TIMEOUT,
		);
		if ( ! empty( $correlation_id ) ) {
			$params['headers']['PayPal-Client-Metadata-Id'] = $correlation_id;
		}
		if ( ! empty( $payment_source ) ) {
			$body                   = array();
			$body['payment_source'] = $payment_source;
			$params['body']         = wp_json_encode( $body );
		}
		$response = wp_remote_get( $api_request_url . '/v2/checkout/orders/' . $resource_id . '/authorize', $params );
		if ( is_wp_error( $response ) ) {
			$response_data = array();
		} else {
			if ( isset( $response['response']['code'] ) && ( '200' === (string) $response['response']['code'] || '201' === (string) $response['response']['code'] ) ) {
				$response_data = json_decode( wp_remote_retrieve_body( $response ), $array );
			} else {
				$response_data = json_decode( wp_remote_retrieve_body( $response ), $array );
				if ( ! isset( $response_data['status'] ) && ! isset( $response_data['name'] ) ) {
					$response_data['name'] = 'AUTHORIZE ERROR';
				}
			}
		}
		return $response_data;
	}

	/**
	 * Orders API.
	 * /v2/payments/authorizations/{id}/void
	 *
	 * @param  array  $access_token API Access Token.
	 * @param  string $bn_code BN Code.
	 * @param  string $tracking_id Tracking ID.
	 * @param  string $id ID.
	 * @return string
	 */
	public function api_authorize_void( $access_token, $bn_code, $tracking_id, $id ) {
		$acting_opts     = $this->get_acting_settings();
		$api_request_url = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';
		$void_status     = '';

		$params   = array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type'                  => 'application/json;charset=utf-8',
				'Authorization'                 => 'Bearer ' . $access_token['access_token'],
				'PayPal-Partner-Attribution-Id' => $bn_code,
				'PayPal-Request-Id'             => $tracking_id,
			),
			'timeout' => self::API_TIMEOUT,
		);
		$response = wp_remote_get( $api_request_url . '/v2/payments/authorizations/' . $id . '/void', $params );
		if ( is_wp_error( $response ) ) {
			$void_status = 'VOID ERROR';
		} elseif ( isset( $response['response']['code'] ) && '204' === (string) $response['response']['code'] ) {
			$void_status = 'COMPLETED';
		} else {
			$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( isset( $response_data['status'] ) ) {
				$void_status = $response_data['status'];
			} elseif ( isset( $response_data['name'] ) ) {
				$void_status = $response_data['name'];
			} else {
				$void_status = 'VOID ERROR';
			}
		}
		return $void_status;
	}

	/**
	 * Payment Method Tokens API.
	 * /v2/vault/payment-tokens/
	 * /v2/vault/payment-tokens/{id}
	 *
	 * @param  array  $access_token API Access Token.
	 * @param  string $customer_id Member ID.
	 * @param  string $payment_token_id Payment Token ID.
	 * @return array
	 */
	public function api_get_payment_tokens( $access_token, $customer_id, $payment_token_id = '' ) {
		$acting_opts     = $this->get_acting_settings();
		$api_request_url = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';
		$card_prefix     = ( isset( $acting_opts['card_prefix'] ) ) ? $acting_opts['card_prefix'] : '';

		$params = array(
			'method'  => 'GET',
			'headers' => array(
				'Content-Type'                  => 'application/json;charset=utf-8',
				'Authorization'                 => 'Bearer ' . $access_token['access_token'],
				'PayPal-Partner-Attribution-Id' => self::API_BN_CODE_ACDC,
			),
			'timeout' => self::API_TIMEOUT,
		);
		if ( ! empty( $payment_token_id ) ) {
			$response = wp_remote_get( $api_request_url . '/v2/vault/payment-tokens/' . $payment_token_id, $params );
		} else {
			$response = wp_remote_get( $api_request_url . '/v2/vault/payment-tokens?customer_id=' . $card_prefix . $customer_id . '&total_required=true', $params );
		}
		if ( is_wp_error( $response ) ) {
			$response_data = array();
		} else {
			$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
		}
		return $response_data;
	}

	/**
	 * Payment Method Tokens API.
	 * /v2/vault/payment-tokens/{id}
	 *
	 * @param  array  $access_token API Access Token.
	 * @param  string $payment_token_id Payment Token ID.
	 * @return string
	 */
	public function api_delete_payment_tokens( $access_token, $payment_token_id ) {
		$acting_opts     = $this->get_acting_settings();
		$api_request_url = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';
		$delete_status   = '';

		$params   = array(
			'method'  => 'DELETE',
			'headers' => array(
				'Content-Type'                  => 'application/json;charset=utf-8',
				'Authorization'                 => 'Bearer ' . $access_token['access_token'],
				'PayPal-Partner-Attribution-Id' => self::API_BN_CODE_ACDC,
			),
			'timeout' => self::API_TIMEOUT,
		);
		$response = wp_remote_request( $api_request_url . '/v2/vault/payment-tokens/' . $payment_token_id, $params );
		if ( is_wp_error( $response ) ) {
			$delete_status = 'DELETE ERROR';
		} elseif ( isset( $response['response']['code'] ) && '204' === (string) $response['response']['code'] ) {
			$delete_status = 'COMPLETED';
		} else {
			$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( isset( $response_data['status'] ) ) {
				$delete_status = $response_data['status'];
			} elseif ( isset( $response_data['name'] ) ) {
				$delete_status = $response_data['name'];
			} else {
				$delete_status = 'DELETE ERROR';
			}
		}
		return $delete_status;
	}

	/**
	 * 決済完了ページ制御
	 * usces_filter_check_acting_return_results
	 *
	 * @param  array $results Results data.
	 * @return array
	 */
	public function acting_return( $results ) {
		$acting_flg = ( isset( $results['acting'] ) ) ? 'acting_' . $results['acting'] : '';
		if ( ! in_array( $acting_flg, $this->pay_method ) ) {
			return $results;
		}
		if ( isset( $results['acting_return'] ) && 1 !== (int) $results['acting_return'] ) {
			return $results;
		}
		if ( ! isset( $results['_nonce'] ) || ! wp_verify_nonce( $results['_nonce'], $acting_flg ) ) {
			wp_redirect( home_url() );
			exit();
		}
		$results['reg_order'] = false;
		return $results;
	}

	/**
	 * 受注データ登録
	 * Called by usces_reg_orderdata() and usces_new_orderdata().
	 * usces_action_reg_orderdata
	 *
	 * @param  array $args Compact array( $cart, $entry, $order_id, $member_id, $payments, $charging_type, $results ).
	 */
	public function register_orderdata( $args ) {
		global $usces;
		extract( $args );

		$acting_flg = $payments['settlement'];
		if ( ! in_array( $acting_flg, $this->pay_method ) ) {
			return;
		}
		if ( ! $entry['order']['total_full_price'] ) {
			return;
		}

		if ( isset( $results['id'] ) && isset( $results['purchase_units'] ) ) {
			$acting = substr( $acting_flg, 7 );
			$usces->set_order_meta_value( 'resource_id', $results['id'], $order_id );
			$purchase_units = $results['purchase_units'][0];
			if ( isset( $purchase_units['reference_id'] ) ) {
				$tracking_id = $purchase_units['reference_id']; /* rand */
				$usces->set_order_meta_value( 'reference_id', $tracking_id, $order_id );
			}
			if ( isset( $purchase_units['payments'] ) ) {
				if ( isset( $purchase_units['payments']['captures'] ) ) {
					$acting_status = 'CAPTURE';
					$payments      = $purchase_units['payments']['captures'][0];
				} elseif ( isset( $purchase_units['payments']['authorizations'] ) ) {
					$acting_status = 'AUTHORIZE';
					$payments      = $purchase_units['payments']['authorizations'][0];
				}
				if ( ! empty( $payments['id'] ) ) {
					$usces->set_order_meta_value( 'wc_trans_id', $payments['id'], $order_id ); /* 決済ID */
					$usces->set_order_meta_value( 'trans_id', $payments['id'], $order_id ); /* 取引ID */
				}
				if ( isset( $results['status'] ) && ! empty( $tracking_id ) ) {
					$results = apply_filters( 'usces_filter_paypal_cp_register_orderdata_log', $results, $args );
					$this->save_acting_log( $results, $acting, $acting_status, $results['status'], $entry['order']['total_full_price'], $order_id, $tracking_id );
				}
			}
			$results['acting'] = $acting;
			$usces->set_order_meta_value( $acting_flg, usces_serialize( $results ), $order_id );
			if ( isset( $results['card_3ds'] ) ) {
				$usces->set_order_meta_value( 'liability_shift', $results['card_3ds']['liability_shift'], $order_id );
				$usces->set_order_meta_value( 'enrollment_status', $results['card_3ds']['enrollment_status'], $order_id );
				$usces->set_order_meta_value( 'authentication_status', $results['card_3ds']['authentication_status'], $order_id );
			}
		}
	}

	/**
	 * ポイント即時付与
	 * usces_filter_is_complete_settlement
	 *
	 * @param  boolean $complete Always give points immediately.
	 * @param  string  $payment_name Payment name.
	 * @param  string  $status Payment status.
	 * @return boolean
	 */
	public function is_complete_settlement( $complete, $payment_name, $status ) {
		$payment = usces_get_payments_by_name( $payment_name );
		if ( isset( $payment['settlement'] ) && in_array( $payment['settlement'], $this->pay_method ) ) {
			$complete = true;
		}
		return $complete;
	}

	/**
	 * 会員データ削除チェック
	 * usces_filter_delete_member_check
	 *
	 * @param  boolean $del Removable|unavailable.
	 * @param  int     $member_id Member ID.
	 * @return boolean
	 */
	public function delete_member_check( $del, $member_id ) {
		if ( usces_have_member_continue_order( $member_id ) || usces_have_member_regular_order( $member_id ) ) {
			$del = false;
		}
		return $del;
	}

	/**
	 * 会員データ削除
	 * usces_action_pre_delete_memberdata
	 *
	 * @param  string $member_id Member ID.
	 */
	public function delete_member( $member_id ) {

		/* Get Access Token */
		$access_token = $this->get_access_token();

		$payment_tokens = $this->api_get_payment_tokens( $access_token, $member_id );
		if ( ! empty( $payment_tokens['payment_tokens'] ) && is_array( $payment_tokens['payment_tokens'] ) ) {
			foreach ( $payment_tokens['payment_tokens'] as $payment_token ) {
				$delete_status = $this->api_delete_payment_tokens( $access_token, $payment_token['id'] );
			}
		}
	}

	/**
	 * クレジットカード登録・変更ページ表示
	 * usces_filter_template_redirect
	 */
	public function member_update_settlement() {
		global $usces;

		if ( $usces->is_member_page( $_SERVER['REQUEST_URI'] ) ) {
			if ( ! usces_is_membersystem_state() || ! usces_is_login() ) {
				return;
			}
			$acting_opts = $this->get_acting_settings();
			if ( 'on' !== $acting_opts['card_vault'] ) {
				return;
			}
			$usces_page = ( isset( $_REQUEST['usces_page'] ) ) ? wp_unslash( $_REQUEST['usces_page'] ) : '';
			if ( 'member_update_settlement' === $usces_page ) {
				add_filter( 'usces_filter_states_form_js', array( $this, 'states_form_js' ) );
				$usces->page = 'member_update_settlement';
				$this->member_update_settlement_form();
				exit();
			} elseif ( 'member_register_settlement' === $usces_page ) {
				add_filter( 'usces_filter_states_form_js', array( $this, 'states_form_js' ) );
				$usces->page = 'member_register_settlement';
				$this->member_update_settlement_form();
				exit();
			}
		}
		// return false;
	}

	/**
	 * クレジットカード登録・変更ページ表示
	 * usces_filter_states_form_js
	 *
	 * @param  string $js Scripts.
	 * @return string
	 */
	public function states_form_js( $js ) {
		return '';
	}

	/**
	 * クレジットカード登録・変更ページリンク
	 * usces_action_member_submenu_list
	 */
	public function e_update_settlement() {
		global $usces;

		$member = $usces->get_member();
		$form   = $this->update_settlement( '', $member );
		echo $form; // no escape.
	}

	/**
	 * クレジットカード登録・変更ページリンク
	 * usces_filter_member_submenu_list
	 *
	 * @param  string $form Submenu area of the member page.
	 * @param  array  $member Member information.
	 * @return string
	 */
	public function update_settlement( $form, $member ) {
		$acting_opts = $this->get_acting_settings();
		if ( isset( $acting_opts['card_activate'] ) && 'off' !== $acting_opts['card_activate'] && 'on' === $acting_opts['card_vault'] && ! empty( $member['ID'] ) ) {

			/* Get Access Token */
			$access_token = $this->get_access_token();

			$payment_tokens = $this->api_get_payment_tokens( $access_token, $member['ID'] );
			if ( ! empty( $payment_tokens['payment_tokens'] ) ) {
				$update_settlement_url = add_query_arg(
					array(
						'usces_page' => 'member_update_settlement',
						're-enter'   => 1,
					),
					USCES_MEMBER_URL
				);
				$form                 .= '<li class="settlement-update gotoedit"><a href="' . $update_settlement_url . '">' . __( 'Change the credit card is here >>', 'usces' ) . '</a></li>';
			} else {
				$register_settlement_url = add_query_arg(
					array(
						'usces_page' => 'member_register_settlement',
						're-enter'   => 1,
					),
					USCES_MEMBER_URL
				);
				$form                   .= '<li class="settlement-register gotoedit"><a href="' . $register_settlement_url . '">' . __( 'Credit card registration is here >>', 'usces' ) . '</a></li>';
			}
		}
		return $form;
	}

	/**
	 * クレジットカード登録・変更ページ
	 */
	public function member_update_settlement_form() {
		global $usces;

		$member      = $usces->get_member();
		$acting_opts = $this->get_acting_settings();

		$bn_code               = self::API_BN_CODE_ACDC;
		$script                = '';
		$done_message          = '';
		$update_settlement_url = add_query_arg(
			array(
				'usces_page' => $usces->page,
				'settlement' => 1,
				're-enter'   => 1,
			),
			USCES_MEMBER_URL
		);

		$update_mode = ( isset( $_POST['update_mode'] ) ) ? wp_unslash( $_POST['update_mode'] ) : '';
		if ( 'card-register' === $update_mode ) {
			check_admin_referer( 'member_update_settlement', 'wc_nonce' );
			$verify_action = wel_verify_update_settlement( $member['ID'] );
			if ( ! $verify_action ) {
				$usces->error_message .= '<p>' . __( 'Update has been locked. Please contact the store administrator.', 'usces' ) . '</p>';
			} else {
				$api_request_url = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';
				$tracking_id     = ( isset( $_POST['paypal_tracking_id'] ) ) ? wp_unslash( $_POST['paypal_tracking_id'] ) : '';
				$resource_id     = ( isset( $_POST['paypal_resource_id'] ) ) ? wp_unslash( $_POST['paypal_resource_id'] ) : '';

				/* Get Access Token */
				$access_token = $this->get_access_token();

				$correlation_id = '';
				$payment_source = array();

				/* Authorize */
				$response_data = $this->api_authorize_order( $access_token, $bn_code, $tracking_id, $resource_id, $correlation_id, $payment_source );
				if ( isset( $response_data['id'] ) && isset( $response_data['status'] ) && 'COMPLETED' === $response_data['status'] ) {
					$done_message   = __( 'Successfully registered.', 'usces' );
					$purchase_units = $response_data['purchase_units'][0];
					if ( isset( $purchase_units['payments']['authorizations'] ) ) {
						$payments = $purchase_units['payments']['authorizations'][0];
						if ( ! empty( $payments['id'] ) ) {
							$void_status = $this->api_authorize_void( $access_token, $bn_code, $tracking_id, $payments['id'] );
						}
					}
				} else {
					$done_message = __( 'Registration failed.', 'usces' );
				}
			}
		} elseif ( 'card-update' === $update_mode ) {
			check_admin_referer( 'member_update_settlement', 'wc_nonce' );
			$verify_action = wel_verify_update_settlement( $member['ID'] );
			if ( ! $verify_action ) {
				$usces->error_message .= '<p>' . __( 'Update has been locked. Please contact the store administrator.', 'usces' ) . '</p>';
			} else {
				$api_request_url  = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';
				$tracking_id      = ( isset( $_POST['paypal_tracking_id'] ) ) ? wp_unslash( $_POST['paypal_tracking_id'] ) : '';
				$resource_id      = ( isset( $_POST['paypal_resource_id'] ) ) ? wp_unslash( $_POST['paypal_resource_id'] ) : '';
				$payment_token_id = ( isset( $_POST['paypal_payment_token_id'] ) ) ? wp_unslash( $_POST['paypal_payment_token_id'] ) : '';
				if ( ! empty( $payment_token_id ) ) {

					/* Get Access Token */
					$access_token = $this->get_access_token();

					$delete_status = $this->api_delete_payment_tokens( $access_token, $payment_token_id );
					if ( 'COMPLETED' === $delete_status ) {
						$correlation_id = '';
						$payment_source = array();

						/* Authorize */
						$response_data = $this->api_authorize_order( $access_token, $bn_code, $tracking_id, $resource_id, $correlation_id, $payment_source );
						if ( isset( $response_data['id'] ) && isset( $response_data['status'] ) && 'COMPLETED' === $response_data['status'] ) {
							$this->send_update_settlement_mail();
							$done_message   = __( 'Successfully updated.', 'usces' );
							$purchase_units = $response_data['purchase_units'][0];
							if ( isset( $purchase_units['payments']['authorizations'] ) ) {
								$payments = $purchase_units['payments']['authorizations'][0];
								if ( ! empty( $payments['id'] ) ) {
									$void_status = $this->api_authorize_void( $access_token, $bn_code, $tracking_id, $payments['id'] );
								}
							}
						} else {
							$done_message = __( 'Update failed.', 'usces' );
						}
					} else {
						$done_message = __( 'Update failed.', 'usces' );
					}
				} else {
					$done_message = __( 'Update failed.', 'usces' );
				}
			}
		} elseif ( 'card-delete' === $update_mode ) {
			check_admin_referer( 'member_update_settlement', 'wc_nonce' );
			$payment_token_id = ( isset( $_POST['paypal_payment_token_id'] ) ) ? wp_unslash( $_POST['paypal_payment_token_id'] ) : '';
			if ( ! empty( $payment_token_id ) ) {

				/* Get Access Token */
				$access_token = $this->get_access_token();

				$delete_status = $this->api_delete_payment_tokens( $access_token, $payment_token_id );
				if ( 'COMPLETED' === $delete_status ) {
					$done_message = __( 'Successfully deleted.', 'usces' );
				} else {
					$done_message = __( 'Deletion failed.', 'usces' );
				}
			} else {
				$done_message = __( 'Deletion failed.', 'usces' );
			}
		}

		if ( '' !== $done_message ) {
			$script .= '
<script type="text/javascript">
jQuery.event.add( window, "load", function() {
	alert( "' . $done_message . '" );
});
</script>';
		}
		$error_message = apply_filters( 'usces_filter_member_update_settlement_error_message', $usces->error_message );

		$payment_token_id          = '';
		$payment_token_last_digits = '';
		$payment_token_brand       = '';

		/* Get Access Token */
		$access_token = $this->get_access_token();

		$payment_tokens = $this->api_get_payment_tokens( $access_token, $member['ID'] );
		if ( ! empty( $payment_tokens['payment_tokens'] ) && is_array( $payment_tokens['payment_tokens'] ) ) {
			$tokens_count = count( $payment_tokens['payment_tokens'] );
			if ( 1 === $tokens_count ) {
				$payment_token             = current( $payment_tokens['payment_tokens'] );
				$payment_token_id          = $payment_token['id'];
				$payment_token_last_digits = $payment_token['source']['card']['last_digits'];
				$payment_token_brand       = $payment_token['source']['card']['brand'];
				$register                  = false;
			} else {
				foreach ( $payment_tokens['payment_tokens'] as $payment_token ) {
					$delete_status = $this->api_delete_payment_tokens( $access_token, $payment_token['id'] );
				}
				$register = true;
			}
		} else {
			$register = true;
		}

		$tracking_id = usces_acting_key();

		ob_start();
		get_header();
		if ( '' !== $script ) {
			echo $script; // no escape due to script.
		}
		?>
<div id="content" class="two-column">
	<div class="catbox">
		<?php
		if ( have_posts() ) :
			usces_remove_filter();
			?>
		<div class="post" id="wc_member_update_settlement">
			<?php if ( $register ) : ?>
			<h1 class="member_page_title"><?php esc_html_e( 'Credit card registration', 'usces' ); ?></h1>
			<?php else : ?>
			<h1 class="member_page_title"><?php esc_html_e( 'Credit card update', 'usces' ); ?></h1>
			<?php endif; ?>
			<div class="entry">
				<div id="memberpages">
					<div class="whitebox">
						<div id="memberinfo">
							<div class="header_explanation"></div>
							<div class="error_message"><?php wel_esc_script_e( $error_message ); ?></div>
							<form id="member-card-update" name="member_card_update" action="<?php echo esc_url( $update_settlement_url ); ?>" method="post" onKeyDown="if(event.keyCode == 13) {return false;}">
								<table class="customer_form" id="paypal_card_form">
									<tbody><tr><td>
									<?php if ( ! $register ) : ?>
										<div class="card_container">
											<p><?php esc_html_e( 'Your registered credit card', 'usces' ); ?></p>
											<p><?php echo esc_html( $payment_token_brand ); ?> <?php esc_html_e( 'Lower 4 digits', 'usces' ); ?>:<?php echo esc_html( $payment_token_last_digits ); ?></p>
										</div>
										</td></tr><tr><td>
									<?php endif; ?>
										<div class="card_container">
											<div>
												<label for="card-number"><?php esc_html_e( 'Card Number', 'usces' ); ?></label>
												<div id="card-number" class="card_field"></div>
											</div>
											<div>
												<label for="expiration-date"><?php esc_html_e( 'Expiration Date', 'usces' ); ?></label>
												<div id="expiration-date" class="card_field"></div>
											</div>
											<div>
												<label for="cvv"><?php esc_html_e( 'CVV', 'usces' ); ?></label>
												<div id="cvv" class="card_field"></div>
											</div>
										</div>
									</td></tr></tbody>
								</table>
								<input type="hidden" id="paypal_tracking_id" name="paypal_tracking_id" value="<?php echo esc_attr( $tracking_id ); ?>" />
								<input type="hidden" id="paypal_resource_id" name="paypal_resource_id" value="" />
								<input type="hidden" id="paypal_payment_token_id " name="paypal_payment_token_id" value="<?php echo esc_attr( $payment_token_id ); ?>" />
								<input type="hidden" id="paypal_f" name="paypal_f" value="" />
								<div class="send">
									<?php if ( $register ) : ?>
									<input type="submit" id="card-update" class="card-update" value="<?php esc_attr_e( 'Register', 'usces' ); ?>" />
									<input type="hidden" id="update-mode" name="update_mode" value="card-register" />
									<?php else : ?>
									<input type="submit" id="card-update" class="card-update" value="<?php esc_attr_e( 'Update', 'usces' ); ?>" />
									<input type="submit" id="card-delete" class="card-update" value="<?php esc_attr_e( 'Delete', 'usces' ); ?>" />
									<input type="hidden" id="update-mode" name="update_mode" value="card-update" />
									<?php endif; ?>
									<input type="button" name="back" value="<?php esc_attr_e( 'Back to the member page.', 'usces' ); ?>" onclick="location.href='<?php echo esc_url( USCES_MEMBER_URL ); ?>'" />
									<input type="button" name="top" value="<?php esc_attr_e( 'Back to the top page.', 'usces' ); ?>" onclick="location.href='<?php echo esc_url( home_url() ); ?>'" />
								</div>
								<?php wp_nonce_field( 'member_update_settlement', 'wc_nonce' ); ?>
							</form>
							<div class="footer_explanation"></div>
						</div><!-- end of memberinfo -->
					</div><!-- end of whitebox -->
				</div><!-- end of memberpages -->
			</div><!-- end of entry -->
		</div><!-- end of post -->
		<?php else : ?>
		<p><?php esc_html_e( 'Sorry, no posts matched your criteria.', 'usces' ); ?></p>
		<?php endif; ?>
	</div><!-- end of catbox -->
</div><!-- end of content -->
		<?php
		$sidebar = apply_filters( 'usces_filter_member_update_settlement_page_sidebar', 'cartmember' );
		if ( ! empty( $sidebar ) ) {
			get_sidebar( $sidebar );
		}
		get_footer();
		$contents = ob_get_contents();
		ob_end_clean();
		echo $contents; // no escape.
	}

	/**
	 * クレジットカード変更メール
	 */
	public function send_update_settlement_mail() {
		global $usces;

		$member = $usces->get_member();

		$subject     = apply_filters( 'usces_filter_send_update_settlement_mail_subject', __( 'Confirmation of credit card update', 'usces' ), $member );
		$mail_header = __( 'Your credit card information has been updated on the membership page.', 'usces' ) . "\r\n\r\n";
		$mail_footer = get_option( 'blogname' ) . "\r\n";
		$name        = usces_localized_name( $member['name1'], $member['name2'], 'return' );

		$message  = '--------------------------------' . "\r\n";
		$message .= __( 'Member ID', 'usces' ) . ' : ' . $member['ID'] . "\r\n";
		$message .= __( 'Name', 'usces' ) . ' : ' . sprintf( _x( '%s', 'honorific', 'usces' ), $name ) . "\r\n";
		$message .= __( 'e-mail adress', 'usces' ) . ' : ' . $member['mailaddress1'] . "\r\n";
		$message .= '--------------------------------' . "\r\n\r\n";
		$message .= __( 'If you have not requested this email, sorry to trouble you, but please contact us.', 'usces' ) . "\r\n\r\n";
		$message  = apply_filters( 'usces_filter_send_update_settlement_mail_message', $message, $member );
		$message  = apply_filters( 'usces_filter_send_update_settlement_mail_message_head', $mail_header, $member ) . $message . apply_filters( 'usces_filter_send_update_settlement_mail_message_foot', $mail_footer, $member ) . "\r\n";
		$message  = sprintf( __( 'Dear %s', 'usces' ), $name ) . "\r\n\r\n" . $message;

		$send_para = array(
			'to_name'      => sprintf( _x( '%s', 'honorific', 'usces' ), $name ),
			'to_address'   => $member['mailaddress1'],
			'from_name'    => get_option( 'blogname' ),
			'from_address' => $usces->options['sender_mail'],
			'reply_name'   => get_option( 'blogname' ),
			'reply_to'     => usces_get_first_order_mail(),
			'return_path'  => $usces->options['sender_mail'],
			'subject'      => $subject,
			'message'      => do_shortcode( $message ),
		);
		usces_send_mail( $send_para );

		$admin_message  = $mail_header;
		$admin_message .= '--------------------------------' . "\r\n";
		$admin_message .= __( 'Member ID', 'usces' ) . ' : ' . $member['ID'] . "\r\n";
		$admin_message .= __( 'Name', 'usces' ) . ' : ' . sprintf( _x( '%s', 'honorific', 'usces' ), $name ) . "\r\n";
		$admin_message .= __( 'e-mail adress', 'usces' ) . ' : ' . $member['mailaddress1'] . "\r\n";
		$admin_message .= '--------------------------------' . "\r\n\r\n";
		$admin_message .=
			"\r\n----------------------------------------------------\r\n" .
			'REMOTE_ADDR : ' . wp_unslash( $_SERVER['REMOTE_ADDR'] ) .
			"\r\n----------------------------------------------------\r\n";

		$admin_para = array(
			'to_name'      => apply_filters( 'usces_filter_bccmail_to_admin_name', 'Shop Admin' ),
			'to_address'   => $usces->options['order_mail'],
			'from_name'    => apply_filters( 'usces_filter_bccmail_from_admin_name', 'Welcart Auto BCC' ),
			'from_address' => $usces->options['sender_mail'],
			'reply_name'   => get_option( 'blogname' ),
			'reply_to'     => usces_get_first_order_mail(),
			'return_path'  => $usces->options['sender_mail'],
			'subject'      => $subject . '( ' . sprintf( _x( '%s', 'honorific', 'usces' ), $name ) . ' )',
			'message'      => do_shortcode( $admin_message ),
		);
		usces_send_mail( $admin_para );
	}

	/**
	 * 管理画面決済処理
	 * usces_action_admin_ajax
	 */
	public function admin_ajax() {
		global $usces;

		$mode = wp_unslash( $_POST['mode'] );
		$data = array();

		switch ( $mode ) {
			/* Upfront Onboarding */
			case 'upfront_onboarding':
				check_admin_referer( 'admin_settlement', 'wc_nonce' );

				/* Get Access Token */
				$cp_client_id    = 'AYbgr2xC4qFsOLqWriUhmUrTLVKQZl_LsO4lLRQ1hkWYaqalZ-DY9n6L4edGt-T0Gnwem9_jKjSgbKc8';
				$cp_secret       = 'EE_9-oMggvZjksDF2zpdmAafq_v7LZutPWgE1W6yu_KKU4sbeFIswcVjoDUeyA3Szywjs3xQ3qRdTDoj';
				$api_request_url = self::API_URL;

				$headers = array(
					'Content-Type: application/x-www-form-urlencoded;application/json',
					'Accept: application/json',
				);
				$ch      = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $api_request_url . '/v1/oauth2/token' );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
				curl_setopt( $ch, CURLOPT_USERPWD, $cp_client_id . ':' . $cp_secret );
				curl_setopt( $ch, CURLOPT_POST, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials' );

				$result = curl_exec( $ch );
				if ( ! $result ) {
					$error = curl_error( $ch );
					wp_send_json( json_decode( $error, true ) );
				}
				unset( $ch );
				$access_token = json_decode( $result, true );

				$seller_nonce            = $this->get_seller_nonce();
				$features                = array( 'PAYMENT', 'REFUND', 'VAULT' );
				$operations              = array();
				$operations['operation'] = 'API_INTEGRATION';
				$operations['api_integration_preference']['rest_api_integration']['integration_method']                  = 'PAYPAL';
				$operations['api_integration_preference']['rest_api_integration']['integration_type']                    = 'FIRST_PARTY';
				$operations['api_integration_preference']['rest_api_integration']['first_party_details']['features']     = $features;
				$operations['api_integration_preference']['rest_api_integration']['first_party_details']['seller_nonce'] = $seller_nonce;
				$products                  = array( 'EXPRESS_CHECKOUT' );
				$legal_consents            = array();
				$legal_consents['type']    = 'SHARE_DATA_CONSENT';
				$legal_consents['granted'] = true;

				$body = array();
				if ( 'ja' === usces_get_local_language() ) {
					$body['preferred_language_code'] = 'ja-JP';
					$addresses                       = array( 'country_code' => 'JP' );
					$body['business_entity']         = array( $addresses );
				}
				$body['operations']     = array( $operations );
				$body['products']       = $products;
				$body['legal_consents'] = array( $legal_consents );

				$params        = array(
					'method'  => 'POST',
					'headers' => array(
						'Content-Type'  => 'application/json;charset=utf-8',
						'Authorization' => 'Bearer ' . $access_token['access_token'],
					),
					'timeout' => self::API_TIMEOUT,
					'body'    => wp_json_encode( $body ),
				);
				$response      = wp_remote_post( $api_request_url . '/v2/customer/partner-referrals', $params );
				$response_data = json_decode( wp_remote_retrieve_body( $response ), true );

				$action_url = '';
				$res        = '';
				if ( isset( $response_data['links'] ) ) {
					foreach ( (array) $response_data['links'] as $links ) {
						if ( isset( $links['rel'] ) && 'action_url' === $links['rel'] && isset( $links['href'] ) ) {
							$action_url = $links['href'];
						}
					}
				}
				if ( '' === $action_url && isset( $response_data['name'] ) ) {
					$res = $response_data['name'];
				}
				wp_send_json(
					array(
						'action_url'   => $action_url,
						'res'          => $res,
						'seller_nonce' => $seller_nonce,
					)
				);
				break;

			/* 参照 */
			case 'get_paypal_cp':
				check_admin_referer( 'order_edit', 'wc_nonce' );
				$order_id    = ( isset( $_POST['order_id'] ) ) ? wp_unslash( $_POST['order_id'] ) : '';
				$order_num   = ( isset( $_POST['order_num'] ) ) ? wp_unslash( $_POST['order_num'] ) : 1;
				$tracking_id = ( isset( $_POST['tracking_id'] ) ) ? wp_unslash( $_POST['tracking_id'] ) : ''; /* rand|reference_id */
				$member_id   = ( isset( $_POST['member_id'] ) ) ? wp_unslash( $_POST['member_id'] ) : 0;
				$con_id      = ( defined( 'WCEX_DLSELLER' ) && isset( $_POST['con_id'] ) ) ? wp_unslash( $_POST['con_id'] ) : 0;
				$reg_id      = ( defined( 'WCEX_AUTO_DELIVERY' ) && isset( $_POST['reg_id'] ) ) ? wp_unslash( $_POST['reg_id'] ) : 0;
				$acting      = ( isset( $_POST['acting'] ) ) ? wp_unslash( $_POST['acting'] ) : 'paypal_cp';
				if ( empty( $order_id ) || empty( $tracking_id ) ) {
					wp_send_json( $data );
					break;
				}

				if ( 1 === (int) $order_num ) {
					$resource_id = $usces->get_order_meta_value( 'resource_id', $order_id );
				} else {
					$resource_id = dlseller_get_continuation_meta_value( 'resource_' . $tracking_id, $con_id );
				}

				$bn_code = ( 'paypal_card' === $acting ) ? self::API_BN_CODE_ACDC : self::API_BN_CODE_PCP;
				$result  = '';

				/* Get Access Token */
				$access_token = $this->get_access_token();

				/* Get Order */
				$response_data = $this->api_get_order( $access_token, $bn_code, $tracking_id, $resource_id );
				if ( $response_data ) {
					if ( isset( $response_data['name'] ) && isset( $response_data['message'] ) && isset( $response_data['debug_id'] ) ) {
						// $result .= '<div class="paypal-settlement-admin paypal-error">' . $response_data['name'] . '</div>';
						$acting_status = $this->get_acting_status( $order_id, $tracking_id );
						$class         = ' paypal-' . strtolower( $acting_status );
						$result       .= '<div class="paypal-settlement-admin' . $class . '">' . __( $acting_status, 'usces' ) . '</div>';
						$log           = $this->get_entry_log( $tracking_id );
						if ( isset( $log['entry'] ) && isset( $log['cart'] ) ) {
							$amount  = $this->get_latest_amount( $order_id, $tracking_id );
							$result .= '<table class="paypal-settlement-admin-table">
								<tr><th>' . __( 'Transaction amount', 'usces' ) . '</th>
									<td><input type="tel" class="settlement-amount" value="' . usces_crform( $amount, false, false, 'return', true ) . '" readonly />' . __( usces_crcode( 'return' ), 'usces' ) . '</td>
								</tr>';
							if ( empty( $amount ) ) {
								if ( defined( 'WCEX_DLSELLER' ) && ! empty( $con_id ) ) {
									$amount = $this->get_continuation_amount( $con_id );
								} elseif ( defined( 'WCEX_AUTO_DELIVERY' ) && ! empty( $reg_id ) ) {
									$amount = $this->get_order_amount( $order_id );
								}
							}
							$result .= '
								<tr><th>' . __( 'Settlement amount', 'usces' ) . '</th>
									<td><input type="tel" class="settlement-amount amount" id="amount_resettlement" value="' . usces_crform( $amount, false, false, 'return', false ) . '" />' . __( usces_crcode( 'return' ), 'usces' ) . '</td>
								</tr>
								</table>';
							if ( 'paypal_cp' === $acting ) {
								$result .= '<div class="paypal-settlement-admin-button">
									<input id="re-authorize-settlement" type="button" class="button" value="' . __( 'AUTHORIZE', 'usces' ) . '" />
									<input id="re-capture-settlement" type="button" class="button" value="' . __( 'CAPTURE', 'usces' ) . '" />
								</div>';
							}
						}
					} else {
						$pending = false;
						$amount  = $this->get_latest_amount( $order_id, $tracking_id );
						if ( isset( $response_data['intent'] ) && isset( $response_data['purchase_units'] ) ) {
							$acting_status  = $response_data['intent'];
							$purchase_units = $response_data['purchase_units'][0];
							if ( isset( $purchase_units['payments']['captures'] ) ) {
								$payments        = $purchase_units['payments']['captures'][0];
								$payments_status = ( isset( $payments['status'] ) ) ? $payments['status'] : '';
								if ( 'REFUNDED' === $payments_status ) {
									$acting_status = 'REFUNDED';
								} elseif ( 'COMPLETED' === $payments_status || 'PARTIALLY_REFUNDED' === $payments_status || 'PENDING' === $payments_status ) {
									$acting_status = 'CAPTURE';
									if ( 'PENDING' === $payments_status ) {
										$pending = true;
									}
								}
							} elseif ( isset( $purchase_units['payments']['authorizations'] ) ) {
								$payments        = $purchase_units['payments']['authorizations'][0];
								$payments_status = ( isset( $payments['status'] ) ) ? $payments['status'] : '';
								if ( 'VOIDED' === $payments_status ) {
									$acting_status = 'VOIDED';
									$amount        = 0;
								} elseif ( 'CREATED' === $payments_status || 'PENDING' === $payments_status ) {
									$acting_status = 'AUTHORIZE';
									if ( 'PENDING' === $payments_status ) {
										$pending = true;
									}
								}
							} else {
								$acting_status = $this->get_acting_status( $order_id, $tracking_id );
							}
						} else {
							$acting_status = $this->get_acting_status( $order_id, $tracking_id );
						}
						$class          = ' paypal-' . strtolower( $acting_status );
						$acting_pending = ( $pending ) ? ' ' . __( '[ PENDING ]', 'usces' ) : '';
						$result        .= '<div class="paypal-settlement-admin' . $class . '">' . __( $acting_status, 'usces' ) . $acting_pending . '</div>';
						if ( 0 < $amount ) {
							if ( 'AUTHORIZE' === $acting_status ) {
								$result .= '<table class="paypal-settlement-admin-table">
									<tr><th>' . __( 'Transaction amount', 'usces' ) . '</th>
										<td><input type="tel" class="settlement-amount" value="' . usces_crform( $amount, false, false, 'return', true ) . '" readonly />' . __( usces_crcode( 'return' ), 'usces' ) . '<input type="hidden" id="amount_original" value="' . usces_crform( $amount, false, false, 'return', false ) . '" /></td>
									</tr>
									<tr><th>' . __( 'Refund amount', 'usces' ) . '</th>
										<td><input type="tel" class="settlement-amount amount" id="amount_refund" value="" />' . __( usces_crcode( 'return' ), 'usces' ) . '</td>
									</tr>
									</table>';
								$result .= '<div class="paypal-settlement-admin-button">
										<input id="capture-settlement" type="button" class="button" value="' . __( 'CAPTURE', 'usces' ) . '" />
										<input id="void-settlement" type="button" class="button" value="' . __( 'VOID', 'usces' ) . '" />
									</div>';
							} elseif ( 'CAPTURE' === $acting_status ) {
								$result .= '<table class="paypal-settlement-admin-table">
									<tr><th>' . __( 'Transaction amount', 'usces' ) . '</th>
										<td><input type="tel" class="settlement-amount" value="' . usces_crform( $amount, false, false, 'return', true ) . '" readonly />' . __( usces_crcode( 'return' ), 'usces' ) . '<input type="hidden" id="amount_original" value="' . usces_crform( $amount, false, false, 'return', false ) . '" /></td>
									</tr>
									<tr><th>' . __( 'Refund amount', 'usces' ) . '</th>
										<td><input type="tel" class="settlement-amount amount" id="amount_refund" value="" />' . __( usces_crcode( 'return' ), 'usces' ) . '</td>
									</tr>
									</table>';
								$result .= '<div class="paypal-settlement-admin-button">
										<input id="refund-settlement" type="button" class="button" value="' . __( 'REFUND', 'usces' ) . '" />
									</div>';
							}
						} else {
							$result .= '<table class="paypal-settlement-admin-table">
								<tr><th>' . __( 'Transaction amount', 'usces' ) . '</th>
									<td><input type="tel" class="settlement-amount" value="' . usces_crform( $amount, false, false, 'return', true ) . '" readonly />' . __( usces_crcode( 'return' ), 'usces' ) . '</td>
								</tr></table>';
						}
					}
				} else {
					$acting_status = $this->get_acting_status( $order_id, $tracking_id );
					$class         = ' paypal-' . strtolower( $acting_status );
					$result       .= '<div class="paypal-settlement-admin' . $class . '">' . __( $acting_status, 'usces' ) . '</div>';
					$log           = $this->get_entry_log( $tracking_id );
					if ( isset( $log['entry'] ) && isset( $log['cart'] ) ) {
						$amount  = $this->get_latest_amount( $order_id, $tracking_id );
						$result .= '<table class="paypal-settlement-admin-table">
							<tr><th>' . __( 'Transaction amount', 'usces' ) . '</th>
								<td><input type="tel" class="settlement-amount" value="' . usces_crform( $amount, false, false, 'return', true ) . '" readonly />' . __( usces_crcode( 'return' ), 'usces' ) . '</td>
							</tr>';
						if ( empty( $amount ) ) {
							if ( defined( 'WCEX_DLSELLER' ) && ! empty( $con_id ) ) {
								$amount = $this->get_continuation_amount( $con_id );
							} elseif ( defined( 'WCEX_AUTO_DELIVERY' ) && ! empty( $reg_id ) ) {
								$amount = $this->get_order_amount( $order_id );
							}
						}
						$result .= '
							<tr><th>' . __( 'Settlement amount', 'usces' ) . '</th>
								<td><input type="tel" class="settlement-amount amount" id="amount_resettlement" value="' . usces_crform( $amount, false, false, 'return', false ) . '" />' . __( usces_crcode( 'return' ), 'usces' ) . '</td>
							</tr>
							</table>';
						if ( 'paypal_cp' === $acting ) {
							$result .= '<div class="paypal-settlement-admin-button">
								<input id="re-authorize-settlement" type="button" class="button" value="' . __( 'AUTHORIZE', 'usces' ) . '" />
								<input id="re-capture-settlement" type="button" class="button" value="' . __( 'CAPTURE', 'usces' ) . '" />
							</div>';
						}
					}
				}
				$result        .= $this->settlement_history( $order_id, $tracking_id );
				$data['result'] = $result;
				wp_send_json( $data );
				break;

			/* CAPTURE */
			case 'capture_paypal_cp':
				check_admin_referer( 'order_edit', 'wc_nonce' );
				$order_id       = ( isset( $_POST['order_id'] ) ) ? wp_unslash( $_POST['order_id'] ) : '';
				$order_num      = ( isset( $_POST['order_num'] ) ) ? wp_unslash( $_POST['order_num'] ) : 1;
				$tracking_id    = ( isset( $_POST['tracking_id'] ) ) ? wp_unslash( $_POST['tracking_id'] ) : '';
				$capture_amount = ( isset( $_POST['amount'] ) ) ? wp_unslash( $_POST['amount'] ) : 0;
				$con_id         = ( defined( 'WCEX_DLSELLER' ) && isset( $_POST['con_id'] ) ) ? wp_unslash( $_POST['con_id'] ) : 0;
				$acting         = ( isset( $_POST['acting'] ) ) ? wp_unslash( $_POST['acting'] ) : 'paypal_cp';
				if ( empty( $order_id ) || empty( $tracking_id ) ) {
					wp_send_json( $data );
					break;
				}

				$acting_status   = 'CAPTURE';
				$acting_opts     = $this->get_acting_settings();
				$api_request_url = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';
				$bn_code         = ( 'paypal_card' === $acting ) ? self::API_BN_CODE_ACDC : self::API_BN_CODE_PCP;
				if ( 1 === (int) $order_num ) {
					$trans_id = $usces->get_order_meta_value( 'trans_id', $order_id );
				} else {
					$trans_id = dlseller_get_continuation_meta_value( 'trans_' . $tracking_id, $con_id );
				}
				$amount = $this->get_latest_amount( $order_id, $tracking_id );
				$result = '';

				/* Get Access Token */
				$access_token = $this->get_access_token();

				$params = array(
					'method'  => 'POST',
					'headers' => array(
						'Content-Type'                  => 'application/json;charset=utf-8',
						'Authorization'                 => 'Bearer ' . $access_token['access_token'],
						'PayPal-Partner-Attribution-Id' => $bn_code,
						'PayPal-Request-Id'             => $tracking_id,
					),
					'timeout' => self::API_TIMEOUT,
				);
				if ( $amount > $capture_amount ) {
					$amount                          = $capture_amount;
					$body                            = array();
					$body['amount']['currency_code'] = $usces->get_currency_code();
					$body['amount']['value']         = $amount;
					$body['final_capture']           = true;
					$params['body']                  = wp_json_encode( $body );
				}
				$response      = wp_remote_get( $api_request_url . '/v2/payments/authorizations/' . $trans_id . '/capture', $params );
				$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( isset( $response['response']['code'] ) && '201' === (string) $response['response']['code'] ) {
					$status = 'COMPLETED';
				} elseif ( isset( $response['response']['code'] ) && '200' === (string) $response['response']['code'] ) {
					$status = 'RETRY';
				} else {
					if ( isset( $response_data['status'] ) ) {
						$status = $response_data['status'];
					} elseif ( isset( $response_data['name'] ) ) {
						$status = $response_data['name'];
					} else {
						$status = 'CAPTURE ERROR';
					}
				}
				$response_data = apply_filters( 'usces_filter_paypal_cp_capture_log', $response_data, $order_id, $status );
				if ( 'COMPLETED' === $status ) {
					$this->save_acting_log( $response_data, $acting, $acting_status, $status, $capture_amount, $order_id, $tracking_id );
					if ( ! empty( $response_data['id'] ) ) {
						if ( 1 === (int) $order_num ) {
							$usces->set_order_meta_value( 'wc_trans_id', $response_data['id'], $order_id ); /* 決済ID */
							$usces->set_order_meta_value( 'trans_id', $response_data['id'], $order_id ); /* 取引ID */
						} else {
							dlseller_set_continuation_meta_value( 'trans_' . $tracking_id, $response_data['id'], $con_id ); /* 取引ID */
						}
					}
				} else {
					$this->save_acting_log( $response_data, $acting, $acting_status, $status, 0, $order_id, $tracking_id );
					$acting_status = 'AUTHORIZE';
				}
				$class   = ' paypal-' . strtolower( $acting_status );
				$result .= '<div class="paypal-settlement-admin' . $class . '">' . __( $acting_status, 'usces' ) . '</div>';
				if ( 'AUTHORIZE' === $acting_status ) {
					$result .= '<table class="paypal-settlement-admin-table">
						<tr><th>' . __( 'Transaction amount', 'usces' ) . '</th>
							<td><input type="tel" class="settlement-amount" value="' . usces_crform( $amount, false, false, 'return', true ) . '" readonly />' . __( usces_crcode( 'return' ), 'usces' ) . '<input type="hidden" id="amount_original" value="' . usces_crform( $amount, false, false, 'return', false ) . '" /></td>
						</tr>
						<tr><th>' . __( 'Refund amount', 'usces' ) . '</th>
							<td><input type="tel" class="settlement-amount amount" id="amount_refund" value="" />' . __( usces_crcode( 'return' ), 'usces' ) . '</td>
						</tr>
						</table>';
					$result .= '<div class="paypal-settlement-admin-button">
							<input id="capture-settlement" type="button" class="button" value="' . __( 'CAPTURE', 'usces' ) . '" />
							<input id="void-settlement" type="button" class="button" value="' . __( 'VOID', 'usces' ) . '" />
						</div>';
				} elseif ( 'CAPTURE' === $acting_status ) {
					$result .= '<table class="paypal-settlement-admin-table">
						<tr><th>' . __( 'Transaction amount', 'usces' ) . '</th>
							<td><input type="tel" class="settlement-amount" value="' . usces_crform( $amount, false, false, 'return', true ) . '" readonly />' . __( usces_crcode( 'return' ), 'usces' ) . '<input type="hidden" id="amount_original" value="' . usces_crform( $amount, false, false, 'return', false ) . '" /></td>
						</tr>
						<tr><th>' . __( 'Refund amount', 'usces' ) . '</th>
							<td><input type="tel" class="settlement-amount amount" id="amount_refund" value="" />' . __( usces_crcode( 'return' ), 'usces' ) . '</td>
						</tr>
						</table>';
					$result .= '<div class="paypal-settlement-admin-button">
							<input id="refund-settlement" type="button" class="button" value="' . __( 'REFUND', 'usces' ) . '" />
						</div>';
				}
				$result .= '</div>';
				do_action( 'usces_action_admin_capture_paypal_cp', $response_data, $order_id, $status );
				$result               .= $this->settlement_history( $order_id, $tracking_id );
				$data['result']        = $result;
				$data['acting_status'] = '<span class="acting-status' . $class . '">' . __( $acting_status, 'usces' ) . '</span>';
				wp_send_json( $data );
				break;

			/* VOID */
			case 'void_paypal_cp':
				check_admin_referer( 'order_edit', 'wc_nonce' );
				$order_id    = ( isset( $_POST['order_id'] ) ) ? wp_unslash( $_POST['order_id'] ) : '';
				$order_num   = ( isset( $_POST['order_num'] ) ) ? wp_unslash( $_POST['order_num'] ) : 1;
				$tracking_id = ( isset( $_POST['tracking_id'] ) ) ? wp_unslash( $_POST['tracking_id'] ) : '';
				$con_id      = ( defined( 'WCEX_DLSELLER' ) && isset( $_POST['con_id'] ) ) ? wp_unslash( $_POST['con_id'] ) : 0;
				$acting      = ( isset( $_POST['acting'] ) ) ? wp_unslash( $_POST['acting'] ) : 'paypal_cp';
				if ( empty( $order_id ) || empty( $tracking_id ) ) {
					wp_send_json( $data );
					break;
				}

				$acting_status   = 'VOID';
				$acting_opts     = $this->get_acting_settings();
				$api_request_url = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';
				$bn_code         = ( 'paypal_card' === $acting ) ? self::API_BN_CODE_ACDC : self::API_BN_CODE_PCP;
				if ( 1 === (int) $order_num ) {
					$trans_id = $usces->get_order_meta_value( 'trans_id', $order_id );
				} else {
					$trans_id = dlseller_get_continuation_meta_value( 'trans_' . $tracking_id, $con_id );
				}
				$amount = $this->get_latest_amount( $order_id, $tracking_id );
				$result = '';

				/* Get Access Token */
				$access_token = $this->get_access_token();

				$params   = array(
					'method'  => 'POST',
					'headers' => array(
						'Content-Type'                  => 'application/json;charset=utf-8',
						'Authorization'                 => 'Bearer ' . $access_token['access_token'],
						'PayPal-Partner-Attribution-Id' => $bn_code,
						'PayPal-Request-Id'             => $tracking_id,
					),
					'timeout' => self::API_TIMEOUT,
				);
				$response = wp_remote_get( $api_request_url . '/v2/payments/authorizations/' . $trans_id . '/void', $params );
				if ( isset( $response['response']['code'] ) && '204' === (string) $response['response']['code'] ) {
					$status        = 'COMPLETED';
					$response_data = apply_filters( 'usces_filter_paypal_cp_void_log', $response['response'], $order_id, $status );
					$this->save_acting_log( $response_data, $acting, $acting_status, $status, $amount * -1, $order_id, $tracking_id );
					$acting_status = 'VOIDED';
					$amount        = 0;
					$class         = ' paypal-' . strtolower( $acting_status );
					$result       .= '<div class="paypal-settlement-admin' . $class . '">' . __( $acting_status, 'usces' ) . '</div>';
					$result       .= '<table class="paypal-settlement-admin-table">
						<tr><th>' . __( 'Transaction amount', 'usces' ) . '</th>
							<td><input type="tel" class="settlement-amount" value="' . usces_crform( $amount, false, false, 'return', true ) . '" readonly />' . __( usces_crcode( 'return' ), 'usces' ) . '</td>
						</tr></table>';
				} else {
					$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
					if ( isset( $response_data['status'] ) ) {
						$status = $response_data['status'];
					} elseif ( isset( $response_data['name'] ) ) {
						$status = $response_data['name'];
					} else {
						$status = 'VOID ERROR';
					}
					$this->save_acting_log( $response_data, $acting, $acting_status, $status, 0, $order_id, $tracking_id );
					$acting_status = 'AUTHORIZE';
					$class         = ' paypal-' . strtolower( $acting_status );
					$result       .= '<div class="paypal-settlement-admin' . $class . '">' . __( $acting_status, 'usces' ) . '</div>';
					$result       .= '<table class="paypal-settlement-admin-table">
						<tr><th>' . __( 'Transaction amount', 'usces' ) . '</th>
							<td><input type="tel" class="settlement-amount" value="' . usces_crform( $amount, false, false, 'return', true ) . '" readonly />' . __( usces_crcode( 'return' ), 'usces' ) . '<input type="hidden" id="amount_original" value="' . usces_crform( $amount, false, false, 'return', false ) . '" /></td>
						</tr>
						<tr><th>' . __( 'Refund amount', 'usces' ) . '</th>
							<td><input type="tel" class="settlement-amount amount" id="amount_refund" value="" />' . __( usces_crcode( 'return' ), 'usces' ) . '</td>
						</tr>
						</table>';
					$result       .= '<div class="paypal-settlement-admin-button">
							<input id="capture-settlement" type="button" class="button" value="' . __( 'CAPTURE', 'usces' ) . '" />
							<input id="void-settlement" type="button" class="button" value="' . __( 'VOID', 'usces' ) . '" />
						</div>';
				}
				do_action( 'usces_action_admin_void_paypal_cp', $response_data, $order_id, $status );
				$result               .= $this->settlement_history( $order_id, $tracking_id );
				$data['result']        = $result;
				$data['acting_status'] = '<span class="acting-status' . $class . '">' . __( $acting_status, 'usces' ) . '</span>';
				wp_send_json( $data );
				break;

			/* REFUND */
			case 'refund_paypal_cp':
				check_admin_referer( 'order_edit', 'wc_nonce' );
				$order_id      = ( isset( $_POST['order_id'] ) ) ? wp_unslash( $_POST['order_id'] ) : '';
				$order_num     = ( isset( $_POST['order_num'] ) ) ? wp_unslash( $_POST['order_num'] ) : 1;
				$tracking_id   = ( isset( $_POST['tracking_id'] ) ) ? wp_unslash( $_POST['tracking_id'] ) : '';
				$refund_amount = ( isset( $_POST['amount'] ) ) ? wp_unslash( $_POST['amount'] ) : 0;
				$con_id        = ( defined( 'WCEX_DLSELLER' ) && isset( $_POST['con_id'] ) ) ? wp_unslash( $_POST['con_id'] ) : 0;
				$acting        = ( isset( $_POST['acting'] ) ) ? wp_unslash( $_POST['acting'] ) : 'paypal_cp';
				if ( empty( $order_id ) || empty( $tracking_id ) || empty( $refund_amount ) ) {
					wp_send_json( $data );
					break;
				}

				$acting_status   = 'REFUND';
				$acting_opts     = $this->get_acting_settings();
				$api_request_url = ( isset( $acting_opts['api_request_url'] ) ) ? $acting_opts['api_request_url'] : '';
				$bn_code         = ( 'paypal_card' === $acting ) ? self::API_BN_CODE_ACDC : self::API_BN_CODE_PCP;
				if ( 1 === (int) $order_num ) {
					$trans_id = $usces->get_order_meta_value( 'trans_id', $order_id );
				} else {
					$trans_id = dlseller_get_continuation_meta_value( 'trans_' . $tracking_id, $con_id );
				}
				$currency_code = $usces->get_currency_code();
				$request_id    = usces_acting_key(); /* Duplication Prevention */
				$result        = '';

				/* Get Access Token */
				$access_token = $this->get_access_token();

				$body                            = array();
				$body['amount']['value']         = $refund_amount;
				$body['amount']['currency_code'] = $currency_code;
				$params                          = array(
					'method'  => 'POST',
					'headers' => array(
						'Content-Type'                  => 'application/json;charset=utf-8',
						'Authorization'                 => 'Bearer ' . $access_token['access_token'],
						'PayPal-Partner-Attribution-Id' => $bn_code,
						'PayPal-Request-Id'             => $request_id,
					),
					'timeout' => self::API_TIMEOUT,
					'body'    => wp_json_encode( $body ),
				);
				$response                        = wp_remote_get( $api_request_url . '/v2/payments/captures/' . $trans_id . '/refund', $params );
				$response_data                   = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( isset( $response['response']['code'] ) && '201' === (string) $response['response']['code'] ) {
					$status = 'COMPLETED';
				} elseif ( isset( $response['response']['code'] ) && '200' === (string) $response['response']['code'] ) {
					$status = 'RETRY';
				} else {
					if ( isset( $response_data['status'] ) ) {
						$status = $response_data['status'];
					} elseif ( isset( $response_data['name'] ) ) {
						$status = $response_data['name'];
					} else {
						$status = 'REFUND ERROR';
					}
				}
				$response_data = apply_filters( 'usces_filter_paypal_cp_refund_log', $response_data, $order_id, $status );
				if ( 'COMPLETED' === $status ) {
					$this->save_acting_log( $response_data, $acting, $acting_status, $status, $refund_amount * -1, $order_id, $tracking_id );
				} else {
					$this->save_acting_log( $response_data, $acting, $acting_status, $status, 0, $order_id, $tracking_id );
				}
				$amount = $this->get_latest_amount( $order_id, $tracking_id );
				if ( 0 < $amount ) {
					$acting_status = 'CAPTURE';
					$class         = ' paypal-' . strtolower( $acting_status );
					$result       .= '<div class="paypal-settlement-admin' . $class . '">' . __( $acting_status, 'usces' ) . '</div>';
					$result       .= '<table class="paypal-settlement-admin-table">
						<tr><th>' . __( 'Transaction amount', 'usces' ) . '</th>
							<td><input type="tel" class="settlement-amount" value="' . usces_crform( $amount, false, false, 'return', true ) . '" readonly />' . __( usces_crcode( 'return' ), 'usces' ) . '<input type="hidden" id="amount_original" value="' . usces_crform( $amount, false, false, 'return', false ) . '" /></td>
						</tr>
						<tr><th>' . __( 'Refund amount', 'usces' ) . '</th>
							<td><input type="tel" class="settlement-amount amount" id="amount_refund" value="" />' . __( usces_crcode( 'return' ), 'usces' ) . '</td>
						</tr>
						</table>';
					$result       .= '<div class="paypal-settlement-admin-button">
							<input id="refund-settlement" type="button" class="button" value="' . __( 'REFUND', 'usces' ) . '" />
						</div>';
				} else {
					$acting_status = 'REFUNDED';
					$class         = ' paypal-' . strtolower( $acting_status );
					$result       .= '<div class="paypal-settlement-admin' . $class . '">' . __( $acting_status, 'usces' ) . '</div>';
					$result       .= '<table class="paypal-settlement-admin-table">
						<tr><th>' . __( 'Transaction amount', 'usces' ) . '</th>
							<td><input type="tel" class="settlement-amount" value="' . usces_crform( $amount, false, false, 'return', true ) . '" readonly />' . __( usces_crcode( 'return' ), 'usces' ) . '</td>
						</tr></table>';
				}
				do_action( 'usces_action_admin_refund_paypal_cp', $response_data, $order_id, $status );
				$result               .= $this->settlement_history( $order_id, $tracking_id );
				$data['result']        = $result;
				$data['acting_status'] = '<span class="acting-status' . $class . '">' . __( $acting_status, 'usces' ) . '</span>';
				wp_send_json( $data );
				break;

			/* RE-SETTLEMENT */
			case 're_settlement_paypal_cp':
				check_admin_referer( 'order_edit', 'wc_nonce' );
				$order_id    = ( isset( $_POST['order_id'] ) ) ? wp_unslash( $_POST['order_id'] ) : '';
				$order_num   = ( isset( $_POST['order_num'] ) ) ? wp_unslash( $_POST['order_num'] ) : 1;
				$tracking_id = ( isset( $_POST['tracking_id'] ) ) ? wp_unslash( $_POST['tracking_id'] ) : '';
				$member_id   = ( isset( $_POST['member_id'] ) ) ? wp_unslash( $_POST['member_id'] ) : 0;
				$amount      = ( isset( $_POST['amount'] ) ) ? wp_unslash( $_POST['amount'] ) : 0;
				$cp_intent   = ( isset( $_POST['intent'] ) ) ? wp_unslash( $_POST['intent'] ) : '';
				$con_id      = ( defined( 'WCEX_DLSELLER' ) && isset( $_POST['con_id'] ) ) ? wp_unslash( $_POST['con_id'] ) : 0;
				$reg_id      = ( defined( 'WCEX_AUTO_DELIVERY' ) && isset( $_POST['reg_id'] ) ) ? wp_unslash( $_POST['reg_id'] ) : 0;
				$acting      = ( isset( $_POST['acting'] ) ) ? wp_unslash( $_POST['acting'] ) : 'paypal_cp';
				if ( empty( $order_id ) || empty( $tracking_id ) || empty( $amount ) || empty( $cp_intent ) ) {
					wp_send_json( $data );
					break;
				}

				$log = $this->get_entry_log( $tracking_id );
				if ( isset( $log['entry'] ) && isset( $log['cart'] ) ) {
					$entry                               = $log['entry'];
					$entry['order']['total_items_price'] = 0;
					$entry['order']['total_full_price']  = $amount;
					$cart                                = $log['cart'];
				} else {
					wp_send_json( $data );
					break;
				}

				$result  = '';
				$pending = false;
				if ( defined( 'WCEX_DLSELLER' ) && ! empty( $con_id ) ) {
					$ba_id = $this->get_continuation_ba_id( $member_id, $order_id );
				} elseif ( defined( 'WCEX_AUTO_DELIVERY' ) && ! empty( $reg_id ) ) {
					$ba_id = $this->get_regular_ba_id( $member_id, $reg_id );
				} else {
					$ba_id = '';
				}
				if ( ! empty( $ba_id ) ) {
					$bn_code    = ( 'paypal_card' === $acting ) ? self::API_BN_CODE_ACDC : self::API_BN_CODE_PCP;
					$request_id = usces_acting_key(); /* Duplication Prevention */

					/* Get Access Token */
					$access_token = $this->get_access_token();

					$shipping = usces_have_shipped( $cart );
					if ( $shipping ) {
						$order_data                    = $usces->get_order_data( $order_id, 'direct' );
						$delivery                      = unserialize( $order_data['order_delivery'] );
						$entry['delivery']['name1']    = $delivery['name1'];
						$entry['delivery']['name2']    = $delivery['name2'];
						$entry['delivery']['country']  = $delivery['country'];
						$entry['delivery']['zipcode']  = $delivery['zipcode'];
						$entry['delivery']['pref']     = $delivery['pref'];
						$entry['delivery']['address1'] = $delivery['address1'];
						$entry['delivery']['address2'] = $delivery['address2'];
						$entry['delivery']['address3'] = $delivery['address3'];
					}

					/* Create order */
					$response_order_data = $this->api_create_order( $access_token, $bn_code, $cp_intent, $request_id, $entry, $cart );
					if ( isset( $response_order_data['id'] ) && isset( $response_order_data['status'] ) && 'CREATED' === $response_order_data['status'] ) {
						$resource_id             = $response_order_data['id'];
						$correlation_id          = '';
						$payment_source          = array();
						$payment_source['token'] = array(
							'id'   => $ba_id,
							'type' => 'BILLING_AGREEMENT',
						);
						dlseller_set_continuation_meta_value( 'resource_' . $tracking_id, $resource_id, $con_id );
						if ( 'CAPTURE' === $cp_intent ) {
							/* Capture */
							$response_data = $this->api_capture_order( $access_token, $bn_code, $request_id, $resource_id, $correlation_id, $payment_source );
						} elseif ( 'AUTHORIZE' === $cp_intent ) {
							/* Authorize */
							$response_data = $this->api_authorize_order( $access_token, $bn_code, $request_id, $resource_id, $correlation_id, $payment_source );
						}
						if ( isset( $response_data['status'] ) ) {
							$status = $response_data['status'];
						} elseif ( isset( $response_data['name'] ) ) {
							$status = $response_data['name'];
						} else {
							$status = $cp_intent . ' ERROR';
						}
						if ( 'paypal_card' === $acting ) {
							if ( isset( $response_data['purchase_units'] ) ) {
								$purchase_units = $response_data['purchase_units'][0];
								if ( isset( $purchase_units['payments']['captures'] ) ) {
									$payments = $purchase_units['payments']['captures'][0];
								} elseif ( isset( $purchase_units['payments']['authorizations'] ) ) {
									$payments = $purchase_units['payments']['authorizations'][0];
								}
								if ( ! empty( $payments['status'] ) ) {
									if ( 'COMPLETED' !== $payments['status'] && 'PENDING' !== $payments['status'] ) {
										$status = $payments['status'];
									}
								}
							}
						}
						$response_data = apply_filters( 'usces_filter_paypal_cp_re_settlement_log', $response_data, $order_id, $status );
						if ( 'COMPLETED' === $status && isset( $response_data['purchase_units'] ) ) {
							$this->save_acting_log( $response_data, $acting, $cp_intent, $status, $amount, $order_id, $tracking_id );
							$purchase_units = $response_data['purchase_units'][0];
							if ( isset( $purchase_units['payments']['captures'] ) ) {
								$payments = $purchase_units['payments']['captures'][0];
							} elseif ( isset( $purchase_units['payments']['authorizations'] ) ) {
								$payments = $purchase_units['payments']['authorizations'][0];
							}
							if ( ! empty( $payments['status'] ) && 'PENDING' === $payments['status'] ) {
								$pending = true;
							}
							if ( $con_id ) {
								// dlseller_set_continuation_meta_value( 'resource_' . $tracking_id, $resource_id, $con_id );
								if ( ! empty( $payments['id'] ) ) {
									dlseller_set_continuation_meta_value( 'trans_' . $tracking_id, $payments['id'], $con_id ); /* 取引ID */
								}
							} elseif ( $reg_id ) {
								$usces->set_order_meta_value( 'resource_id', $resource_id, $order_id );
								if ( ! empty( $payments['id'] ) ) {
									$usces->set_order_meta_value( 'wc_trans_id', $payments['id'], $order_id ); /* 決済ID */
									$usces->set_order_meta_value( 'trans_id', $payments['id'], $order_id ); /* 取引ID */
								}
							}
							$this->del_entry_log( $tracking_id );
						} else {
							$this->save_acting_log( $response_data, $acting, 'ERROR', $status, 0, $order_id, $tracking_id );
						}
					} else {
						if ( isset( $response_order_data['status'] ) ) {
							$status = $response_order_data['status'];
						} elseif ( isset( $response_order_data['name'] ) ) {
							$status = $response_order_data['name'];
						} else {
							$status = 'CREATE ORDER ERROR';
						}
						$this->save_acting_log( $response_order_data, $acting, 'ERROR', $status, 0, $order_id, $tracking_id );
					}
				} else {
					$status = 'RE-SETTLEMENT ERROR';
					$this->save_acting_log( array(), $acting, 'ERROR', $status, 0, $order_id, $tracking_id );
				}
				if ( 'COMPLETED' === $status ) {
					$acting_status  = $cp_intent;
					$class          = ' paypal-' . strtolower( $acting_status );
					$acting_pending = ( $pending ) ? ' ' . __( '[ PENDING ]', 'usces' ) : '';
					$result        .= '<div class="paypal-settlement-admin' . $class . '">' . __( $acting_status, 'usces' ) . $acting_pending . '</div>';
					if ( 'AUTHORIZE' === $acting_status ) {
						$result .= '<table class="paypal-settlement-admin-table">
							<tr><th>' . __( 'Transaction amount', 'usces' ) . '</th>
								<td><input type="tel" class="settlement-amount" value="' . usces_crform( $amount, false, false, 'return', true ) . '" readonly />' . __( usces_crcode( 'return' ), 'usces' ) . '<input type="hidden" id="amount_original" value="' . usces_crform( $amount, false, false, 'return', false ) . '" /></td>
							</tr>
							<tr><th>' . __( 'Refund amount', 'usces' ) . '</th>
								<td><input type="tel" class="settlement-amount amount" id="amount_refund" value="" />' . __( usces_crcode( 'return' ), 'usces' ) . '</td>
							</tr>
							</table>';
						$result .= '<div class="paypal-settlement-admin-button">
								<input id="capture-settlement" type="button" class="button" value="' . __( 'CAPTURE', 'usces' ) . '" />
								<input id="void-settlement" type="button" class="button" value="' . __( 'VOID', 'usces' ) . '" />
							</div>';
					} elseif ( 'CAPTURE' === $acting_status ) {
						$result .= '<table class="paypal-settlement-admin-table">
							<tr><th>' . __( 'Transaction amount', 'usces' ) . '</th>
								<td><input type="tel" class="settlement-amount" value="' . usces_crform( $amount, false, false, 'return', true ) . '" readonly />' . __( usces_crcode( 'return' ), 'usces' ) . '<input type="hidden" id="amount_original" value="' . usces_crform( $amount, false, false, 'return', false ) . '" /></td>
							</tr>
							<tr><th>' . __( 'Refund amount', 'usces' ) . '</th>
								<td><input type="tel" class="settlement-amount amount" id="amount_refund" value="" />' . __( usces_crcode( 'return' ), 'usces' ) . '</td>
							</tr>
							</table>';
						$result .= '<div class="paypal-settlement-admin-button">
								<input id="refund-settlement" type="button" class="button" value="' . __( 'REFUND', 'usces' ) . '" />
							</div>';
					}
				} else {
					// $amount        = 0;
					$acting_status = 'ERROR';
					$class         = ' paypal-' . strtolower( $acting_status );
					$result       .= '<div class="paypal-settlement-admin paypal-error">' . __( $acting_status, 'usces' ) . '</div>';
					$result       .= '<table class="paypal-settlement-admin-table">
						<tr><th>' . __( 'Transaction amount', 'usces' ) . '</th>
							<td><input type="tel" class="settlement-amount" value="' . usces_crform( $amount, false, false, 'return', true ) . '" readonly />' . __( usces_crcode( 'return' ), 'usces' ) . '</td>
						</tr>
						<tr><th>' . __( 'Settlement amount', 'usces' ) . '</th>
							<td><input type="tel" class="settlement-amount amount" id="amount_resettlement" value="" />' . __( usces_crcode( 'return' ), 'usces' ) . '</td>
						</tr>
							</table>';
					if ( 'paypal_cp' === $acting ) {
						$result .= '<div class="paypal-settlement-admin-button">
							<input id="re-authorize-settlement" type="button" class="button" value="' . __( 'AUTHORIZE', 'usces' ) . '" />
							<input id="re-capture-settlement" type="button" class="button" value="' . __( 'CAPTURE', 'usces' ) . '" />
						</div>';
					}
				}
				if ( defined( 'WCEX_DLSELLER' ) && ! empty( $con_id ) ) {
					do_action( 'usces_action_admin_re_settlement_paypal_cp', $response_data, $order_id, $status, $con_id );
				} elseif ( defined( 'WCEX_AUTO_DELIVERY' ) && ! empty( $reg_id ) ) {
					do_action( 'usces_action_admin_re_settlement_paypal_cp', $response_data, $order_id, $status, $reg_id );
				} else {
					do_action( 'usces_action_admin_re_settlement_paypal_cp', $response_data, $order_id, $status );
				}
				$result               .= $this->settlement_history( $order_id, $tracking_id );
				$data['result']        = $result;
				$data['acting_status'] = '<span class="acting-status' . $class . '">' . __( $acting_status, 'usces' ) . '</span>';
				wp_send_json( $data );
				break;

			/* 継続課金情報更新 */
			case 'continuation_update':
				check_admin_referer( 'order_edit', 'wc_nonce' );
				$order_id         = ( isset( $_POST['order_id'] ) ) ? wp_unslash( $_POST['order_id'] ) : '';
				$member_id        = ( isset( $_POST['member_id'] ) ) ? wp_unslash( $_POST['member_id'] ) : '';
				$contracted_year  = ( isset( $_POST['contracted_year'] ) ) ? wp_unslash( $_POST['contracted_year'] ) : '';
				$contracted_month = ( isset( $_POST['contracted_month'] ) ) ? wp_unslash( $_POST['contracted_month'] ) : '';
				$contracted_day   = ( isset( $_POST['contracted_day'] ) ) ? wp_unslash( $_POST['contracted_day'] ) : '';
				$charged_year     = ( isset( $_POST['charged_year'] ) ) ? wp_unslash( $_POST['charged_year'] ) : '';
				$charged_month    = ( isset( $_POST['charged_month'] ) ) ? wp_unslash( $_POST['charged_month'] ) : '';
				$charged_day      = ( isset( $_POST['charged_day'] ) ) ? wp_unslash( $_POST['charged_day'] ) : '';
				$price            = ( isset( $_POST['price'] ) ) ? wp_unslash( $_POST['price'] ) : 0;
				$status           = ( isset( $_POST['status'] ) ) ? wp_unslash( $_POST['status'] ) : '';

				$continue_data = $this->get_continuation_data( $member_id, $order_id );
				if ( ! $continue_data ) {
					$data['status'] = 'NG';
					wp_send_json( $data );
					break;
				}

				/* 継続中→停止 */
				if ( 'continuation' === $continue_data['status'] && 'cancellation' === $status ) {
					$this->update_continuation_data( $member_id, $order_id, $continue_data, true );
				} else {
					if ( ! empty( $contracted_year ) && ! empty( $contracted_month ) && ! empty( $contracted_day ) ) {
						$contracted_date = ( empty( $continue_data['contractedday'] ) ) ? dlseller_next_contracting( $order_id ) : $continue_data['contractedday'];
						if ( $contracted_date ) {
							$new_contracted_date = $contracted_year . '-' . $contracted_month . '-' . $contracted_day;
							if ( ! $this->isdate( $new_contracted_date ) ) {
								$data['status']  = 'NG';
								$data['message'] = __( 'Next contract renewal date is incorrect.', 'dlseller' );
								wp_send_json( $data );
							}
						}
					} else {
						$new_contracted_date = '';
					}
					$new_charged_date = $charged_year . '-' . $charged_month . '-' . $charged_day;
					if ( ! $this->isdate( $new_charged_date ) ) {
						$data['status']  = 'NG';
						$data['message'] = __( 'Next settlement date is incorrect.', 'dlseller' );
						wp_send_json( $data );
					}
					$tomorrow = date_i18n( 'Y-m-d', strtotime( '+1 day' ) );
					if ( $new_charged_date < $tomorrow ) {
						$data['status']  = 'NG';
						$data['message'] = sprintf( __( 'The next settlement date must be after %s.', 'dlseller' ), $tomorrow );
						wp_send_json( $data );
					}
					$continue_data['contractedday'] = $new_contracted_date;
					$continue_data['chargedday']    = $new_charged_date;
					$continue_data['price']         = usces_crform( $price, false, false, 'return', false );
					$continue_data['status']        = $status;
					$this->update_continuation_data( $member_id, $order_id, $continue_data );
				}
				$data['status'] = 'OK';
				wp_send_json( $data );
				break;
		}
	}

	/**
	 * 受注データから取得する決済情報のキー
	 * usces_filter_settle_info_field_meta_keys
	 *
	 * @param  array $keys Settlement information keys.
	 * @return array
	 */
	public function settlement_info_field_meta_keys( $keys ) {
		$keys = array_merge( $keys, array( 'reference_id', 'trans_id', 'liability_shift', 'enrollment_status', 'authentication_status' ) );
		return $keys;
	}

	/**
	 * 受注編集画面に表示する決済情報のキー
	 * usces_filter_settle_info_field_keys
	 *
	 * @param  array $keys Settlement information keys.
	 * @param  array $fields Settlement information fields.
	 * @return array
	 */
	public function settlement_info_field_keys( $keys, $fields ) {
		if ( isset( $fields['acting'] ) && ( 'paypal_cp' === $fields['acting'] || 'paypal_card' === $fields['acting'] ) ) {
			$keys = array( 'acting', 'reference_id', 'trans_id', 'liability_shift', 'enrollment_status', 'authentication_status' );
		}
		return $keys;
	}

	/**
	 * 受注編集画面に表示する決済情報の値整形
	 * usces_filter_settle_info_field_value
	 *
	 * @param  string $value Value.
	 * @param  string $key Key.
	 * @param  string $acting Acting type.
	 * @return string
	 */
	public function settlement_info_field_value( $value, $key, $acting ) {
		if ( 'acting' === $key ) {
			if ( 'paypal_cp' === $value ) {
				$value = $this->acting_name;
			} elseif ( 'paypal_card' === $value ) {
				$value = __( 'PayPal(CreditCard)', 'usces' );
			}
		}
		return $value;
	}

	/**
	 * 決済状況
	 * usces_filter_orderlist_detail_value
	 *
	 * @param  string $detail HTML.
	 * @param  string $value Value.
	 * @param  string $key Key.
	 * @param  int    $order_id Order number.
	 * @return array
	 */
	public function orderlist_settlement_status( $detail, $value, $key, $order_id ) {
		global $usces;

		if ( 'wc_trans_id' !== $key || empty( $value ) ) {
			return $detail;
		}

		$acting_flg = $this->get_order_acting_flg( $order_id );
		if ( in_array( $acting_flg, $this->pay_method ) ) {
			$tracking_id   = $usces->get_order_meta_value( 'reference_id', $order_id );
			$acting_status = $this->get_acting_status( $order_id, $tracking_id );
			if ( ! empty( $acting_status ) ) {
				$class  = ' paypal-' . strtolower( $acting_status );
				$detail = '<td>' . $value . '<span class="acting-status' . $class . '">' . __( $acting_status, 'usces' ) . '</span></td>';
			}
		}
		return $detail;
	}

	/**
	 * 受注編集画面【ステータス】
	 * usces_action_order_edit_form_status_block_middle
	 *
	 * @param  array $data Order data.
	 * @param  array $cscs_meta Custom field data.
	 * @param  array $action_args Compact array( $order_action, $order_id, $cart ).
	 */
	public function settlement_status( $data, $cscs_meta, $action_args ) {
		global $usces;
		extract( $action_args );

		if ( 'new' !== $order_action && ! empty( $order_id ) ) {
			$payment    = usces_get_payments_by_name( $data['order_payment_name'] );
			$acting_flg = ( isset( $payment['settlement'] ) ) ? $payment['settlement'] : '';
			if ( in_array( $acting_flg, $this->pay_method ) ) {
				$tracking_id   = $usces->get_order_meta_value( 'reference_id', $order_id );
				$acting_status = $this->get_acting_status( $order_id, $tracking_id );
				if ( ! empty( $acting_status ) ) {
					$class = ' paypal-' . strtolower( $acting_status );
					if ( ! empty( $acting_status ) ) {
						echo '
						<tr>
							<td class="label status">' . esc_html__( 'Settlement status', 'usces' ) . '</td>
							<td class="col1 status"><span id="settlement-status-1"><span class="acting-status' . esc_attr( $class ) . '">' . esc_html__( $acting_status, 'usces' ) . '</span></span></td>
						</tr>';
					}
				}
			}
		}
	}

	/**
	 * 受注編集画面【支払情報】
	 * usces_action_order_edit_form_settle_info
	 *
	 * @param  array $data Order data.
	 * @param  array $action_args Compact array( $order_action, $order_id, $cart ).
	 */
	public function settlement_information( $data, $action_args ) {
		global $usces;
		extract( $action_args );

		if ( 'new' !== $order_action && ! empty( $order_id ) ) {
			$payment = usces_get_payments_by_name( $data['order_payment_name'] );
			if ( in_array( $payment['settlement'], $this->pay_method ) ) {
				$tracking_id = $usces->get_order_meta_value( 'reference_id', $order_id );
				echo '<input type="button" class="button settlement-information" id="settlement-information-' . esc_attr( $tracking_id ) . '" data-tracking_id="' . esc_attr( $tracking_id ) . '" data-num="1" value="' . esc_html__( 'Settlement info', 'usces' ) . '">';
			}
		}
	}

	/**
	 * 決済情報ダイアログ
	 * usces_action_endof_order_edit_form
	 *
	 * @param  array $data Order data.
	 * @param  array $action_args Compact array( $order_action, $order_id, $cart ).
	 */
	public function settlement_dialog( $data, $action_args ) {
		global $usces;
		extract( $action_args );

		if ( 'new' !== $order_action && ! empty( $order_id ) ) :
			$payment = usces_get_payments_by_name( $data['order_payment_name'] );
			if ( in_array( $payment['settlement'], $this->pay_method ) ) :
				$acting = substr( $payment['settlement'], 7 );
				if ( defined( 'WCEX_AUTO_DELIVERY' ) ) {
					$reg_id = $this->get_regular_id( $order_id );
				} else {
					$reg_id = '';
				}
				?>
<div id="settlement_dialog" title="">
	<div id="settlement-response-loading"></div>
	<fieldset>
	<div id="settlement-response"></div>
	<input type="hidden" id="order_num">
	<input type="hidden" id="tracking_id">
	<input type="hidden" id="acting" value="<?php echo esc_attr( $acting ); ?>">
				<?php if ( ! empty( $reg_id ) ) : ?>
	<input type="hidden" id="reg_id" value="<?php echo esc_attr( $reg_id ); ?>">
				<?php endif; ?>
	</fieldset>
</div>
				<?php
			endif;
		endif;
	}

	/**
	 * 会員データ編集画面
	 * usces_action_admin_member_info
	 *
	 * @param  array $member Member data.
	 * @param  array $member_metas Member meta data.
	 * @param  array $usces_member_history Member history data.
	 */
	public function admin_member_info( $member, $member_metas, $usces_member_history ) {
		if ( empty( $member['ID'] ) ) {
			return;
		}
		if ( 0 < count( $member_metas ) ) :
			if ( defined( 'WCEX_DLSELLER' ) ) :
				if ( dlseller_have_member_continue_order( $member['ID'] ) ) :
					?>
		<tr>
			<td class="label"><?php esc_html_e( '[ Automatic recurring billing ]', 'usces' ); ?></td>
			<td><div class="rod_left shortm"><?php esc_html_e( 'Registered', 'usces' ); ?></div></td>
		</tr>
					<?php
				endif;
			endif;
			if ( defined( 'WCEX_AUTO_DELIVERY' ) ) :
				if ( wcad_have_member_regular_order( $member['ID'] ) ) :
					?>
		<tr>
			<td class="label"><?php esc_html_e( '[ Regular Purchase ]', 'usces' ); ?></td>
			<td><div class="rod_left shortm"><?php esc_html_e( 'Registered', 'usces' ); ?></div></td>
		</tr>
					<?php
				endif;
			endif;
		endif;

		$acting_opts = $this->get_acting_settings();
		if ( $this->is_validity_acting( 'card' ) ) :
			/* Get Access Token */
			$access_token = $this->get_access_token();

			$payment_tokens = $this->api_get_payment_tokens( $access_token, $member['ID'] );
			if ( ! empty( $payment_tokens['payment_tokens'] ) && is_array( $payment_tokens['payment_tokens'] ) ) :
				$tokens_count = count( $payment_tokens['payment_tokens'] );
				if ( 1 === $tokens_count ) :
					$payment_token             = current( $payment_tokens['payment_tokens'] );
					$payment_token_id          = $payment_token['id'];
					$payment_token_last_digits = $payment_token['source']['card']['last_digits'];
					$payment_token_brand       = $payment_token['source']['card']['brand'];
					?>
		<tr>
			<td class="label"><?php esc_html_e( 'Last 4 digits of registered card', 'usces' ); ?></td>
			<td><div class="rod_left shortm"><?php echo esc_html( $payment_token_last_digits ); ?></div></td>
		</tr>
		<tr>
			<td class="label"><input type="hidden" name="paypal_payment_token_id" value="<?php echo esc_attr( $payment_token_id ); ?>" /></td>
			<td><input type="checkbox" name="paypal_card_delete" id="paypal_card_delete" value="delete"><label for="paypal_card_delete"><?php esc_html_e( 'Credit Card Deletion', 'usces' ); ?></label></td>
		</tr>
					<?php
				endif;
			endif;
		endif;
	}

	/**
	 * 会員データ編集画面 カード情報登録解除
	 * usces_action_post_update_memberdata
	 *
	 * @param int     $member_id Member ID.
	 * @param boolean $res Result.
	 */
	public function admin_update_memberdata( $member_id, $res ) {
		if ( ! $this->is_validity_acting( 'card' ) || false === $res ) {
			return;
		}

		if ( isset( $_POST['paypal_card_delete'] ) && 'delete' === wp_unslash( $_POST['paypal_card_delete'] ) && isset( $_POST['paypal_payment_token_id'] ) ) {
			/* Get Access Token */
			$access_token  = $this->get_access_token();
			$delete_status = $this->api_delete_payment_tokens( $access_token, wp_unslash( $_POST['paypal_payment_token_id'] ) );
		}
	}

	/**
	 * 利用可能な支払方法（継続課金・定期購入）
	 * dlseller_filter_the_payment_method_restriction
	 * wcad_filter_the_payment_method_restriction
	 *
	 * @param  array  $payments_restriction Payment method.
	 * @param  string $value Input value.
	 * @return array
	 */
	public function payment_method_restriction( $payments_restriction, $value ) {
		if ( ( usces_have_regular_order() || usces_have_continue_charge() ) && usces_is_login() ) {
			$paypal_cp = false;
			foreach ( (array) $payments_restriction as $key => $payment ) {
				if ( 'acting_paypal_cp' === $payment['settlement'] ) {
					$paypal_cp = true;
				}
			}
			if ( ! $paypal_cp ) {
				$payments               = usces_get_system_option( 'usces_payment_method', 'settlement' );
				$payments_restriction[] = $payments['acting_paypal_cp'];
			}
			$sort = array();
			foreach ( (array) $payments_restriction as $key => $payment ) {
				$sort[ $key ] = $payment['sort'];
			}
			array_multisort( $sort, SORT_ASC, $payments_restriction );
		}
		return $payments_restriction;
	}

	/**
	 * 利用可能な支払方法
	 * usces_filter_the_continue_payment_method
	 *
	 * @param  array $payment_method Payment method.
	 * @return array
	 */
	public function continuation_payment_method( $payment_method ) {
		if ( ! array_key_exists( 'acting_paypal_cp', $payment_method ) ) {
			$payment_method[] = 'acting_paypal_cp';
		}
		return $payment_method;
	}

	/**
	 * 自動継続課金データ登録
	 * dlseller_action_reg_continuationdata
	 *
	 * @param  array $args Compact array( $cart, $entry, $order_id, $member_id, $payments, $charging_type, $results, $con_id ).
	 */
	public function register_continuationdata( $args ) {
		global $usces;
		extract( $args );

		if ( ! empty( $order_id ) && ! empty( $member_id ) && ! empty( $results['ba_id'] ) ) {
			$this->set_continuation_ba_id( $member_id, $order_id, $results['ba_id'] );
		}
	}

	/**
	 * 「初回引落し日」
	 * dlseller_filter_first_charging
	 *
	 * @param  object $time Datetime.
	 * @param  int    $post_id Post ID.
	 * @param  array  $usces_item Item data.
	 * @param  int    $order_id Order number.
	 * @param  array  $continue_data Continuation data.
	 * @return object
	 */
	public function first_charging_date( $time, $post_id, $usces_item, $order_id, $continue_data ) {
		if ( 99 === (int) $usces_item['item_chargingday'] ) {
			if ( empty( $order_id ) ) {
				$today                      = date_i18n( 'Y-m-d', current_time( 'timestamp' ) );
				list( $year, $month, $day ) = explode( '-', $today );
				$time                       = mktime( 0, 0, 0, (int) $month, (int) $day, (int) $year );
			}
		}
		return $time;
	}

	/**
	 * 継続課金会員リスト「契約」
	 * dlseller_filter_continue_member_list_continue_status
	 *
	 * @param  string $status Continuation status.
	 * @param  int    $member_id Member ID.
	 * @param  int    $order_id Order number.
	 * @param  array  $meta_data Continuation data.
	 * @return string
	 */
	public function continue_member_list_continue_status( $status, $member_id, $order_id, $meta_data ) {
		return $status;
	}

	/**
	 * 継続課金会員リスト Column
	 * dlseller_filter_continue_memberlist_column
	 *
	 * @param  string $columns Continuation condition.
	 * @param  object $continuationlist Continuation list class.
	 * @return string
	 */
	public function continue_memberlist_column( $columns, $continuationlist ) {
		$columns['ba_id'] = __( 'Billing Agreement ID', 'usces' );
		return $columns;
	}

	/**
	 * 継続課金会員リスト Join table
	 * dlseller_filter_continue_memberlist_sql_jointable
	 *
	 * @param  string $join Join query.
	 * @param  object $continuationlist Continuation list class.
	 * @return string
	 */
	public function continue_memberlist_sql_jointable( $join, $continuationlist ) {
		global $wpdb;

		$join .= "LEFT JOIN {$wpdb->prefix}usces_member_meta AS `mm1` ON con_member_id = mm1.member_id AND mm1.meta_key = concat( 'paypal_continuation_', con_order_id ) ";
		return $join;
	}

	/**
	 * 継続課金会員リスト Select query
	 * dlseller_filter_continue_memberlist_sql_select
	 *
	 * @param  string $query Select query.
	 * @param  string $csod Custom query.
	 * @param  object $continuationlist Continuation list class.
	 * @return string
	 */
	public function continue_memberlist_sql_select( $query, $csod, $continuationlist ) {
		global $wpdb;

		$query = "SELECT 
			`con_id` AS `ID`, 
			`con_order_id` AS `order_id`, 
			om.meta_value AS `deco_id`, 
			`con_member_id` AS `mem_id`, 
			mem.mem_email AS `email`, 
			mem.mem_name1 AS `name1`, 
			mem.mem_name2 AS `name2`, 
			mem.mem_name3 AS `name3`, 
			mem.mem_name4 AS `name4`, 
			IFNULL( mm.meta_value, '' ) AS `limitofcard`, 
			`con_price` AS `price`, 
			`con_acting` AS `acting`, 
			ord.order_payment_name AS `payment_name`, 
			DATE_FORMAT( ord.order_date, '%Y-%m-%d' ) AS `orderdate`, 
			DATE_FORMAT( con_startdate, '%Y-%m-%d' ) AS `startdate`, 
			DATE_FORMAT( con_next_contracting, '%Y-%m-%d' ) AS `contractedday`, 
			DATE_FORMAT( con_next_charging, '%Y-%m-%d' ) AS `chargedday`, 
			`con_status` AS `status`, 
			`con_condition` AS `condition` 
			{$csod},
			IFNULL( mm1.meta_value, '' ) AS `ba_id` 
			FROM {$continuationlist->table} ";
		return $query;
	}

	/**
	 * 継続課金会員リスト「状態」
	 * dlseller_filter_continue_member_list_condition
	 *
	 * @param  string $condition Continuation condition.
	 * @param  int    $member_id Member ID.
	 * @param  int    $order_id Order number.
	 * @param  array  $continue_data Continuation data.
	 * @return string
	 */
	public function continue_member_list_condition( $condition, $member_id, $order_id, $continue_data ) {
		global $usces;

		$acting_flg = $this->get_order_acting_flg( $order_id );
		if ( 'acting_paypal_cp' === $acting_flg ) {
			$log_data = $this->get_acting_log( $order_id, 0, 'ALL' );
			if ( $log_data ) {
				$url       = admin_url( 'admin.php?page=usces_continue&continue_action=settlement_paypal_cp&member_id=' . esc_attr( $member_id ) . '&order_id=' . esc_attr( $order_id ) );
				$condition = '<a href="' . $url . '">' . __( 'Detail', 'usces' ) . '</a>';
				if ( 'continuation' === $continue_data['status'] ) {
					$latest_log = $this->get_acting_latest_log( $order_id, 0, 'ALL' );
					if ( isset( $latest_log['status'] ) && 'ERROR' === $latest_log['status'] ) {
						$condition .= '<div class="acting-status paypal-error">' . __( 'Settlement error', 'usces' ) . '</div>';
					}
				}
			}
		}
		return $condition;
	}

	/**
	 * 継続課金会員決済状況ページ表示
	 * dlseller_action_continue_member_list_page
	 *
	 * @param  string $continue_action Continuation action.
	 */
	public function continue_member_list_page( $continue_action ) {
		if ( 'settlement_paypal_cp' === $continue_action ) {
			$member_id = ( isset( $_GET['member_id'] ) ) ? wp_unslash( $_GET['member_id'] ) : '';
			$order_id  = ( isset( $_GET['order_id'] ) ) ? wp_unslash( $_GET['order_id'] ) : '';
			if ( ! empty( $member_id ) && ! empty( $order_id ) ) {
				$this->continue_member_settlement_info_page( $member_id, $order_id );
				exit();
			}
		}
	}

	/**
	 * 継続課金会員決済状況ページ
	 *
	 * @param  int $member_id Member ID.
	 * @param  int $order_id Order number.
	 */
	public function continue_member_settlement_info_page( $member_id, $order_id ) {
		global $usces;

		$order_data = $usces->get_order_data( $order_id, 'direct' );
		if ( ! $order_data ) {
			return;
		}

		$payment = $usces->getPayments( $order_data['order_payment_name'] );
		if ( 'acting_paypal_cp' !== $payment['settlement'] ) {
			return;
		}

		$continue_data = $this->get_continuation_data( $member_id, $order_id );
		$con_id        = $continue_data['con_id'];
		$curent_url    = esc_url( $_SERVER['REQUEST_URI'] );
		$navibutton    = '<a href="' . esc_url( $_SERVER['HTTP_REFERER'] ) . '" class="back-list"><span class="dashicons dashicons-list-view"></span>' . __( 'Back to the continue members list', 'dlseller' ) . '</a>';

		$member_info = $usces->get_member_info( $member_id );
		$name        = usces_localized_name( $member_info['mem_name1'], $member_info['mem_name2'], 'return' );

		$contracted_date = ( empty( $continue_data['contractedday'] ) ) ? dlseller_next_contracting( $order_id ) : $continue_data['contractedday'];
		if ( ! empty( $contracted_date ) ) {
			list( $contracted_year, $contracted_month, $contracted_day ) = explode( '-', $contracted_date );
		} else {
			$contracted_year  = 0;
			$contracted_month = 0;
			$contracted_day   = 0;
		}
		$charged_date = ( empty( $continue_data['chargedday'] ) ) ? dlseller_next_charging( $order_id ) : $continue_data['chargedday'];
		if ( ! empty( $charged_date ) ) {
			list( $charged_year, $charged_month, $charged_day ) = explode( '-', $charged_date );
		} else {
			$charged_year  = 0;
			$charged_month = 0;
			$charged_day   = 0;
		}
		$this_year = substr( date_i18n( 'Y', current_time( 'timestamp' ) ), 0, 4 );

		$log_data = $this->get_acting_log( $order_id, 0, 'ALL' );
		$num      = ( $log_data ) ? count( $log_data ) : 1;
		?>
<div class="wrap">
<div class="usces_admin">
<h1>Welcart Management <?php esc_html_e( 'Continuation charging member information', 'dlseller' ); ?></h1>
<p class="version_info">Version <?php echo esc_html( WCEX_DLSELLER_VERSION ); ?></p>
		<?php usces_admin_action_status(); ?>
<div class="edit_pagenav"><?php wel_esc_script_e( $navibutton ); ?></div>
<div id="datatable">
<div id="tablesearch" class="usces_tablesearch">
<div id="searchBox" style="display:block">
	<table class="search_table">
	<tr>
		<td class="label"><?php esc_html_e( 'Continuation charging information', 'dlseller' ); ?></td>
		<td>
			<table class="order_info">
			<tr>
				<th><?php esc_html_e( 'Member ID', 'dlseller' ); ?></th>
				<td><?php echo esc_html( $member_id ); ?></td>
				<th><?php esc_html_e( 'Contractor name', 'dlseller' ); ?></th>
				<td><?php echo esc_html( $name ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Order ID', 'dlseller' ); ?></th>
				<td><?php echo esc_html( $order_id ); ?></td>
				<th><?php esc_html_e( 'Application Date', 'dlseller' ); ?></th>
				<td><?php echo esc_html( $order_data['order_date'] ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Renewal Date', 'dlseller' ); ?></th>
				<td>
					<?php
					echo '<select id="contracted-year">';
					echo '<option value="0"' . selected( $contracted_year, 0, false ) . '></option>';
					for ( $i = 0; $i <= 10; $i++ ) {
						$year = (int) $this_year + $i;
						echo '<option value="' . esc_html( $year ) . '"' . selected( $contracted_year, $year, false ) . '>' . esc_html( $year ) . '</option>';
					}
					echo '</select>-<select id="contracted-month">';
					echo '<option value="0"' . selected( $contracted_month, 0, false ) . '></option>';
					for ( $i = 1; $i <= 12; $i++ ) {
						$month = sprintf( '%02d', $i );
						echo '<option value="' . esc_html( $month ) . '"' . selected( $contracted_month, $month, false ) . '>' . esc_html( $month ) . '</option>';
					}
					echo '</select>-<select id="contracted-day">';
					echo '<option value="0"' . selected( $contracted_day, 0, false ) . '></option>';
					for ( $i = 1; $i <= 31; $i++ ) {
						$day = sprintf( '%02d', $i );
						echo '<option value="' . esc_html( $day ) . '"' . selected( $contracted_day, $day, false ) . '>' . esc_html( $day ) . '</option>';
					}
					echo '</select>';
					?>
				</td>
				<th><?php esc_html_e( 'Next Withdrawal Date', 'dlseller' ); ?></th>
				<td>
					<?php
					echo '<select id="charged-year">';
					echo '<option value="0"' . selected( $charged_year, 0, false ) . '></option>';
					echo '<option value="' . esc_html( $this_year ) . '"' . selected( $charged_year, $this_year, false ) . '>' . esc_html( $this_year ) . '</option>';
					$next_year = (int) $this_year + 1;
					echo '<option value="' . esc_html( $next_year ) . '"' . selected( $charged_year, $next_year, false ) . '>' . esc_html( $next_year ) . '</option>';
					echo '</select>-<select id="charged-month">';
					echo '<option value="0"' . selected( $charged_month, 0, false ) . '></option>';
					for ( $i = 1; $i <= 12; $i++ ) {
						$month = sprintf( '%02d', $i );
						echo '<option value="' . esc_html( $month ) . '"' . selected( $charged_month, $month, false ) . '>' . esc_html( $month ) . '</option>';
					}
					echo '</select>-<select id="charged-day">';
					echo '<option value="0"' . selected( $charged_day, 0, false ) . '></option>';
					for ( $i = 1; $i <= 31; $i++ ) {
						$day = sprintf( '%02d', $i );
						echo '<option value="' . esc_html( $day ) . '"' . selected( $charged_day, $day, false ) . '>' . esc_html( $day ) . '</option>';
					}
					echo '</select>';
					?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Amount on order', 'usces' ); ?></th>
				<td><?php usces_crform( $continue_data['order_price'], false ); ?></td>
				<th><?php esc_html_e( 'Transaction amount', 'usces' ); ?></th>
				<td><input type="text" class="amount" id="price" style="text-align: right;" value="<?php usces_crform( $continue_data['price'], false, false, '', false ); ?>"><?php usces_crcode(); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Billing Agreement ID', 'usces' ); ?></th>
				<?php $ba_id = $this->get_continuation_ba_id( $member_id, $order_id ); ?>
				<td colspan="3"><?php echo esc_html( $ba_id ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Status', 'dlseller' ); ?></th>
				<td><select id="dlseller-status">
				<?php ob_start(); ?>
				<?php if ( 'continuation' === $continue_data['status'] ) : ?>
					<option value="continuation" selected><?php esc_html_e( 'Continuation', 'dlseller' ); ?></option>
					<option value="cancellation"><?php esc_html_e( 'Stop', 'dlseller' ); ?></option>
				<?php else : ?>
					<option value="cancellation" selected><?php esc_html_e( 'Cancellation', 'dlseller' ); ?></option>
					<option value="continuation"><?php esc_html_e( 'Resumption', 'dlseller' ); ?></option>
				<?php endif; ?>
				<?php
					$dlseller_status_options = ob_get_contents();
					ob_end_clean();
					$dlseller_status_options = apply_filters( 'usces_filter_continuation_charging_status_options', $dlseller_status_options, $continue_data );
					wel_esc_script_e( $dlseller_status_options );
				?>
				</select></td>
				<td colspan="2"><input id="continuation-update" type="button" class="button button-primary" value="<?php esc_attr_e( 'Update' ); ?>" /></td>
			</tr>
			</table>
			<?php do_action( 'usces_action_continuation_charging_information', $continue_data, $member_id, $order_id ); ?>
		</td>
	</tr>
	</table>
</div><!-- searchBox -->
</div><!-- tablesearch -->
<table id="mainDataTable" class="new-table order-new-table">
	<thead>
	<tr>
		<th scope="col">&nbsp;</th>
		<th scope="col"><?php esc_html_e( 'Processing date', 'usces' ); ?></th>
		<th scope="col"><?php esc_html_e( 'Trans ID', 'usces' ); ?></th>
		<th scope="col"><?php esc_html_e( 'Transaction amount', 'usces' ); ?></th>
		<th scope="col"><?php esc_html_e( 'Process', 'usces' ); ?></th>
		<th scope="col">&nbsp;</th>
	</tr>
	</thead>
		<?php
		foreach ( (array) $log_data as $data ) :
			$tracking_id = ( isset( $data['tracking_id'] ) ) ? $data['tracking_id'] : '';
			$latest_log  = $this->get_acting_latest_log( $order_id, $tracking_id, 'ALL' );
			if ( $latest_log ) :
				$acting_status = $this->get_acting_status( $order_id, $tracking_id );
				if ( 'COMPLETED' === $latest_log['result'] ) {
					$class  = ' paypal-' . strtolower( $acting_status );
					$amount = usces_crform( $this->get_latest_amount( $order_id, $tracking_id ), false, true, 'return', true );
				} else {
					$class  = ' paypal-error';
					$amount = '';
				}
				$log = usces_unserialize( $latest_log['log'] );
				$id  = ( isset( $log['id'] ) ) ? $log['id'] : '';
				if ( isset( $log['purchase_units'] ) ) {
					$purchase_units = $log['purchase_units'][0];
					if ( isset( $purchase_units['payments'] ) ) {
						if ( isset( $purchase_units['payments']['captures'] ) ) {
							$payments = $purchase_units['payments']['captures'][0];
						} elseif ( isset( $purchase_units['payments']['authorizations'] ) ) {
							$payments = $purchase_units['payments']['authorizations'][0];
						} elseif ( isset( $purchase_units['payments']['refunds'] ) ) {
							$payments = $purchase_units['payments']['refunds'][0];
						}
						if ( isset( $payments['id'] ) ) {
							$id = $payments['id'];
						}
					}
				}
				?>
	<tbody>
	<tr>
		<td><?php echo esc_html( $num ); ?></td>
		<td><?php echo esc_html( $data['datetime'] ); ?></td>
		<td><?php echo esc_html( $id ); ?></td>
		<td class="amount"><?php echo esc_html( $amount ); ?></td>
		<td><span id="settlement-status-<?php echo esc_attr( $num ); ?>"><span class="acting-status<?php echo esc_attr( $class ); ?>"><?php echo esc_html__( $acting_status, 'usces' ); ?></span></span></td>
		<td>
			<input type="button" class="button settlement-information" data-tracking_id="<?php echo esc_attr( $tracking_id ); ?>" data-num="<?php echo esc_attr( $num ); ?>" value="<?php esc_attr_e( 'Settlement info', 'usces' ); ?>">
		</td>
	</tr>
	</tbody>
				<?php
				$num--;
			endif;
		endforeach;
		?>
</table>
</div><!--datatable-->
<input name="member_id" type="hidden" id="member_id" value="<?php echo esc_attr( $member_id ); ?>" />
<input name="order_id" type="hidden" id="order_id" value="<?php echo esc_attr( $order_id ); ?>" />
<input name="con_id" type="hidden" id="con_id" value="<?php echo esc_attr( $con_id ); ?>" />
<input name="usces_referer" type="hidden" id="usces_referer" value="<?php echo urlencode( $curent_url ); ?>" />
		<?php wp_nonce_field( 'order_edit', 'wc_nonce' ); ?>
</div><!--usces_admin-->
</div><!--wrap-->
		<?php
		$order_action = 'edit';
		$cart         = array();
		$action_args  = compact( 'order_action', 'order_id', 'cart' );
		$this->settlement_dialog( $order_data, $action_args );
		include ABSPATH . 'wp-admin/admin-footer.php';
	}

	/**
	 * 自動継続課金処理
	 * dlseller_action_do_continuation_charging
	 *
	 * @param  string $today Today.
	 * @param  int    $member_id Member ID.
	 * @param  int    $order_id Order number.
	 * @param  array  $continue_data Continuation data.
	 */
	public function auto_continuation_charging( $today, $member_id, $order_id, $continue_data ) {
		global $usces;

		if ( ! usces_is_membersystem_state() ) {
			return;
		}

		if ( 0 >= $continue_data['price'] ) {
			return;
		}

		$order_data = $usces->get_order_data( $order_id, 'direct' );
		if ( ! $order_data || $usces->is_status( 'cancel', $order_data['order_status'] ) ) {
			return;
		}

		$payment = $usces->getPayments( $order_data['order_payment_name'] );
		if ( 'acting_paypal_cp' !== $payment['settlement'] || 'acting_paypal_cp' !== $continue_data['acting'] ) {
			return;
		}

		$log_data = $this->get_acting_log( $order_id, 0, 'ALL' );
		if ( ! $log_data ) {
			return;
		}

		$bn_code     = self::API_BN_CODE_PCP;
		$ba_id       = $this->get_continuation_ba_id( $member_id, $order_id );
		$tracking_id = usces_acting_key();
		if ( ! empty( $ba_id ) ) {
			$acting_opts = $this->get_acting_settings();
			$cp_intent   = ( isset( $acting_opts['autobilling_intent'] ) ) ? $acting_opts['autobilling_intent'] : '';

			/* Get Access Token */
			$access_token = $this->get_access_token();

			$entry                              = array();
			$entry['order']['total_full_price'] = $continue_data['price'];
			$cart                               = usces_get_ordercartdata( $order_id );
			$shipping                           = usces_have_shipped( $cart );
			if ( $shipping ) {
				$delivery                      = unserialize( $order_data['order_delivery'] );
				$entry['delivery']['name1']    = $delivery['name1'];
				$entry['delivery']['name2']    = $delivery['name2'];
				$entry['delivery']['country']  = $delivery['country'];
				$entry['delivery']['zipcode']  = $delivery['zipcode'];
				$entry['delivery']['pref']     = $delivery['pref'];
				$entry['delivery']['address1'] = $delivery['address1'];
				$entry['delivery']['address2'] = $delivery['address2'];
				$entry['delivery']['address3'] = $delivery['address3'];
			}

			/* Create order */
			$response_order_data = $this->api_create_order( $access_token, $bn_code, $cp_intent, $tracking_id, $entry, $cart );
			if ( isset( $response_order_data['id'] ) && isset( $response_order_data['status'] ) && 'CREATED' === $response_order_data['status'] ) {
				$resource_id             = $response_order_data['id'];
				$correlation_id          = '';
				$payment_source          = array();
				$payment_source['token'] = array(
					'id'   => $ba_id,
					'type' => 'BILLING_AGREEMENT',
				);
				dlseller_set_continuation_meta_value( 'resource_' . $tracking_id, $resource_id, $continue_data['con_id'] );
				if ( 'CAPTURE' === $cp_intent ) {
					/* Capture */
					$response_data = $this->api_capture_order( $access_token, $bn_code, $tracking_id, $resource_id, $correlation_id, $payment_source );
				} elseif ( 'AUTHORIZE' === $cp_intent ) {
					/* Authorize */
					$response_data = $this->api_authorize_order( $access_token, $bn_code, $tracking_id, $resource_id, $correlation_id, $payment_source );
				}
				if ( isset( $response_data['status'] ) ) {
					$status = $response_data['status'];
				} elseif ( isset( $response_data['name'] ) ) {
					$status = $response_data['name'];
				} else {
					$status = $cp_intent . ' ERROR';
				}
				$response_data = apply_filters( 'usces_filter_paypal_cp_auto_continuation_charging_log', $response_data, $order_id, $status );
				if ( 'COMPLETED' === $status && isset( $response_data['purchase_units'] ) ) {
					$this->save_acting_log( $response_data, 'paypal_cp', $cp_intent, $status, $continue_data['price'], $order_id, $tracking_id );
					// dlseller_set_continuation_meta_value( 'resource_' . $tracking_id, $resource_id, $continue_data['con_id'] );
					$purchase_units = $response_data['purchase_units'][0];
					if ( isset( $purchase_units['payments']['captures'] ) ) {
						$payments = $purchase_units['payments']['captures'][0];
					} elseif ( isset( $purchase_units['payments']['authorizations'] ) ) {
						$payments = $purchase_units['payments']['authorizations'][0];
					}
					if ( ! empty( $payments['id'] ) ) {
						dlseller_set_continuation_meta_value( 'trans_' . $tracking_id, $payments['id'], $continue_data['con_id'] ); /* 取引ID */
					}
					$response_data['acting'] = $this->paymod_id;
					$this->autobilling_email( $member_id, $order_id, $response_data, $continue_data );
				} else {
					$this->save_acting_log( $response_data, 'paypal_cp', 'ERROR', $status, 0, $order_id, $tracking_id );
					$this->save_entry_log( $tracking_id, $continue_data['price'], $cart, $entry );
					$log = array(
						'acting' => $this->paymod_id,
						'key'    => $tracking_id,
						'result' => $status,
						'data'   => $response_data,
					);
					usces_save_order_acting_error( $log );
					$this->autobilling_error_email( $member_id, $order_id, $response_data, $response_data );
				}
				do_action( 'usces_action_auto_continuation_charging', $member_id, $order_id, $continue_data, $response_data );
			} else {
				$this->save_entry_log( $tracking_id, $continue_data['price'], $cart, $entry );
				if ( isset( $response_order_data['status'] ) ) {
					$status = $response_order_data['status'];
				} elseif ( isset( $response_order_data['name'] ) ) {
					$status = $response_order_data['name'];
				} else {
					$status = 'CREATE ORDER ERROR';
				}
				$log = array(
					'acting' => $this->paymod_id,
					'key'    => $tracking_id,
					'result' => $status,
					'data'   => $response_order_data,
				);
				usces_save_order_acting_error( $log );
				$this->save_acting_log( $response_order_data, 'paypal_cp', 'ERROR', $status, 0, $order_id, $tracking_id );
				$this->autobilling_error_email( $member_id, $order_id, $response_order_data, $continue_data );
				do_action( 'usces_action_auto_continuation_charging', $member_id, $order_id, $continue_data, $response_order_data );
			}
		} else {
			$this->save_entry_log( $tracking_id, $continue_data['price'], $cart, $entry );
			$logdata = array();
			$log     = array(
				'acting' => $this->paymod_id,
				'key'    => $member_id,
				'result' => 'MEMBER ERROR',
				'data'   => $logdata,
			);
			usces_save_order_acting_error( $log );
			$this->save_acting_log( $logdata, 'paypal_cp', 'ERROR', 'MEMBER ERROR', 0, $order_id, $tracking_id );
			$this->autobilling_error_email( $member_id, $order_id, $logdata, $continue_data );
			do_action( 'usces_action_auto_continuation_charging', $member_id, $order_id, $continue_data, $logdata );
		}
	}

	/**
	 * 自動継続課金処理メール（正常）
	 *
	 * @param  int   $member_id Member ID.
	 * @param  int   $order_id Order number.
	 * @param  array $response_data Response data.
	 * @param  array $continue_data Continuation data.
	 */
	public function autobilling_email( $member_id, $order_id, $response_data, $continue_data ) {
		global $usces;

		$acting_opts = $this->get_acting_settings();
		$order_data  = $usces->get_order_data( $order_id, 'direct' );
		$mail_body   = $this->autobilling_message( $member_id, $order_id, $order_data, $response_data, $continue_data, false );

		if ( 'on' === $acting_opts['autobilling_email'] ) {
			$subject     = apply_filters( 'usces_filter_paypal_cp_autobilling_email_subject', __( 'Announcement of automatic continuing charging process', 'usces' ), $member_id, $order_id, $order_data, $response_data, $continue_data );
			$member_info = $usces->get_member_info( $member_id );
			$name        = usces_localized_name( $member_info['mem_name1'], $member_info['mem_name2'], 'return' );
			$mail_data   = usces_mail_data();
			$mail_header = '';
			if ( isset( $usces->options['put_customer_name'] ) && 1 === (int) $usces->options['put_customer_name'] ) {
				$mail_header .= sprintf( __( 'Dear %s', 'usces' ), $name ) . "\r\n\r\n";
			}
			$mail_header .= __( 'We will report automated accounting process was carried out as follows.', 'usces' ) . "\r\n\r\n";
			$mail_footer  = __( 'If you have any questions, please contact us.', 'usces' ) . "\r\n\r\n" . $mail_data['footer']['thankyou'];
			$message      = apply_filters( 'usces_filter_paypal_cp_autobilling_email_header', $mail_header, $member_id, $order_id, $order_data, $response_data, $continue_data ) .
				apply_filters( 'usces_filter_paypal_cp_autobilling_email_body', $mail_body, $member_id, $order_id, $order_data, $response_data, $continue_data ) .
				apply_filters( 'usces_filter_paypal_cp_autobilling_email_footer', $mail_footer, $member_id, $order_id, $order_data, $response_data, $continue_data );
			$headers      = apply_filters( 'usces_filter_paypal_cp_autobilling_email_headers', '' );
			$to_customer  = array(
				'to_name'      => sprintf( _x( '%s', 'honorific', 'usces' ), $name ),
				'to_address'   => $member_info['mem_email'],
				'from_name'    => get_option( 'blogname' ),
				'from_address' => $usces->options['sender_mail'],
				'reply_name'   => get_option( 'blogname' ),
				'reply_to'     => usces_get_first_order_mail(),
				'return_path'  => $usces->options['sender_mail'],
				'subject'      => $subject,
				'message'      => do_shortcode( $message ),
				'headers'      => $headers,
			);
			usces_send_mail( $to_customer );
		}

		$ok                                     = ( empty( $this->continuation_charging_mail['OK'] ) ) ? 0 : $this->continuation_charging_mail['OK'];
		$this->continuation_charging_mail['OK'] = $ok + 1;
		$this->continuation_charging_mail['mail'][] = $mail_body;
	}

	/**
	 * 自動継続課金処理メール（エラー）
	 *
	 * @param  int   $member_id Member ID.
	 * @param  int   $order_id Order number.
	 * @param  array $response_data Response data.
	 * @param  array $continue_data Continuation data.
	 */
	public function autobilling_error_email( $member_id, $order_id, $response_data, $continue_data ) {
		global $usces;

		$acting_opts = $this->get_acting_settings();
		$order_data  = $usces->get_order_data( $order_id, 'direct' );
		$mail_body   = $this->autobilling_message( $member_id, $order_id, $order_data, $response_data, $continue_data, false );

		if ( 'on' === $acting_opts['autobilling_email'] ) {
			$subject     = apply_filters( 'usces_filter_paypal_cp_autobilling_error_email_subject', __( 'Announcement of automatic continuing charging process', 'usces' ), $member_id, $order_id, $order_data, $response_data, $continue_data );
			$member_info = $usces->get_member_info( $member_id );
			$name        = usces_localized_name( $member_info['mem_name1'], $member_info['mem_name2'], 'return' );
			$mail_data   = usces_mail_data();
			$mail_header = '';
			if ( isset( $usces->options['put_customer_name'] ) && 1 === (int) $usces->options['put_customer_name'] ) {
				$mail_header .= sprintf( __( 'Dear %s', 'usces' ), $name ) . "\r\n\r\n";
			}
			$mail_header .= __( 'We will reported that an error occurred in automated accounting process.', 'usces' ) . "\r\n\r\n";
			$mail_footer  = __( 'If you have any questions, please contact us.', 'usces' ) . "\r\n\r\n" . $mail_data['footer']['thankyou'];
			$message      = apply_filters( 'usces_filter_paypal_cp_autobilling_error_email_header', $mail_header, $member_id, $order_id, $order_data, $response_data, $continue_data ) .
				apply_filters( 'usces_filter_paypal_cp_autobilling_error_email_body', $mail_body, $member_id, $order_id, $order_data, $response_data, $continue_data ) .
				apply_filters( 'usces_filter_paypal_cp_autobilling_error_email_footer', $mail_footer, $member_id, $order_id, $order_data, $response_data, $continue_data );
			$headers      = apply_filters( 'usces_filter_paypal_cp_autobilling_error_email_headers', '' );
			$to_customer  = array(
				'to_name'      => sprintf( _x( '%s', 'honorific', 'usces' ), $name ),
				'to_address'   => $member_info['mem_email'],
				'from_name'    => get_option( 'blogname' ),
				'from_address' => $usces->options['sender_mail'],
				'reply_name'   => get_option( 'blogname' ),
				'reply_to'     => usces_get_first_order_mail(),
				'return_path'  => $usces->options['sender_mail'],
				'subject'      => $subject,
				'message'      => do_shortcode( $message ),
				'headers'      => $headers,
			);
			usces_send_mail( $to_customer );
		}

		$error                                      = ( empty( $this->continuation_charging_mail['NG'] ) ) ? 0 : $this->continuation_charging_mail['NG'];
		$this->continuation_charging_mail['NG']     = $error + 1;
		$this->continuation_charging_mail['mail'][] = $mail_body;
	}

	/**
	 * 自動継続課金処理メール本文
	 *
	 * @param  int     $member_id Member ID.
	 * @param  int     $order_id Order number.
	 * @param  array   $order_data Order data.
	 * @param  array   $response_data Response data.
	 * @param  array   $continue_data Continuation data.
	 * @param  boolean $html true|false.
	 * @return string
	 */
	public function autobilling_message( $member_id, $order_id, $order_data, $response_data, $continue_data, $html = true ) {
		global $usces;

		if ( usces_is_html_mail() && $html ) {
			$message = $this->autobilling_message_htmlbody( $member_id, $order_id, $order_data, $response_data, $continue_data );
		} else {
			$member_info     = $usces->get_member_info( $member_id );
			$name            = usces_localized_name( $member_info['mem_name1'], $member_info['mem_name2'], 'return' );
			$contracted_date = ( isset( $continue_data['contractedday'] ) ) ? $continue_data['contractedday'] : '';
			$charged_date    = ( isset( $continue_data['chargedday'] ) ) ? $continue_data['chargedday'] : '';

			$message  = usces_mail_line( 2 ); // --------------------
			$message .= __( 'Order ID', 'dlseller' ) . ' : ' . $order_id . "\r\n";
			$message .= __( 'Application Date', 'dlseller' ) . ' : ' . $order_data['order_date'] . "\r\n";
			$message .= __( 'Member ID', 'dlseller' ) . ' : ' . $member_id . "\r\n";
			$message .= __( 'Contractor name', 'dlseller' ) . ' : ' . sprintf( _x( '%s', 'honorific', 'usces' ), $name ) . "\r\n";

			$cart      = usces_get_ordercartdata( $order_id );
			$cart_row  = current( $cart );
			$item_name = $usces->getCartItemName_byOrder( $cart_row );
			$options   = ( empty( $cart_row['options'] ) ) ? array() : $cart_row['options'];
			$message  .= __( 'Items', 'usces' ) . ' : ' . $item_name . "\r\n";
			if ( is_array( $options ) && 0 < count( $options ) ) {
				$optstr = '';
				foreach ( $options as $key => $value ) {
					if ( ! empty( $key ) ) {
						$key   = urldecode( $key );
						$value = maybe_unserialize( $value );
						if ( is_array( $value ) ) {
							$c       = '';
							$optstr .= '( ' . $key . ' : ';
							foreach ( $value as $v ) {
								$optstr .= $c . rawurldecode( $v );
								$c       = ', ';
							}
							$optstr .= " )\r\n";
						} else {
							$optstr .= '( ' . $key . ' : ' . rawurldecode( $value ) . " )\r\n";
						}
					}
				}
				$message .= $optstr;
			}

			$message .= __( 'Settlement amount', 'usces' ) . ' : ' . usces_crform( $continue_data['price'], true, false, 'return' ) . "\r\n";
			if ( isset( $response_data['reminder'] ) ) {
				if ( ! empty( $charged_date ) ) {
					$message .= __( 'Next Withdrawal Date', 'dlseller' ) . ' : ' . $charged_date . "\r\n";
				}
				if ( ! empty( $contracted_date ) ) {
					$message .= __( 'Renewal Date', 'dlseller' ) . ' : ' . $contracted_date . "\r\n";
				}
			} else {
				if ( ! empty( $charged_date ) ) {
					$message .= __( 'Next Withdrawal Date', 'dlseller' ) . ' : ' . $charged_date . "\r\n";
				}
				if ( ! empty( $contracted_date ) ) {
					$message .= __( 'Renewal Date', 'dlseller' ) . ' : ' . $contracted_date . "\r\n";
				}
				$message .= "\r\n";
				if ( isset( $response_data['status'] ) && 'COMPLETED' === $response_data['status'] ) {
					$message .= __( 'Result', 'usces' ) . ' : ' . __( 'Normal done', 'usces' ) . "\r\n";
				} elseif ( isset( $response_data['name'] ) ) {
					$message .= __( 'Result', 'usces' ) . ' : ' . $response_data['name'] . "\r\n";
				} else {
					$message .= __( 'Result', 'usces' ) . ' : ' . __( 'Error', 'usces' ) . "\r\n";
				}
			}

			$ba_id = $this->get_continuation_ba_id( $member_id, $order_id );
			if ( ! empty( $ba_id ) ) {
				$message .= "\r\n" . __( 'Billing Agreement ID', 'usces' ) . ' : ' . $ba_id . "\r\n\r\n";
			}

			$message .= usces_mail_line( 2 ) . "\r\n"; // --------------------
		}
		return $message;
	}

	/**
	 * Automatic renewal billing process email body html
	 *
	 * @param  int   $member_id Member ID.
	 * @param  int   $order_id Order number.
	 * @param  array $order_data Order data.
	 * @param  array $response_data Response data.
	 * @param  array $continue_data Continuation data.
	 * @return string
	 */
	public function autobilling_message_htmlbody( $member_id, $order_id, $order_data, $response_data, $continue_data ) {
		global $usces;

		$member_info     = $usces->get_member_info( $member_id );
		$name            = usces_localized_name( $member_info['mem_name1'], $member_info['mem_name2'], 'return' );
		$contracted_date = ( isset( $continue_data['contractedday'] ) ) ? $continue_data['contractedday'] : '';
		$charged_date    = ( isset( $continue_data['chargedday'] ) ) ? $continue_data['chargedday'] : '';

		$message  = '<table style="font-size: 14px; margin-bottom: 30px; width: 100%; border-collapse: collapse; border: 1px solid #ddd;"><tbody>';
		$message .= '<tr><td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">';
		$message .= __( 'Order ID', 'dlseller' );
		$message .= '</td><td style="padding: 12px; width: 67%; border: 1px solid #ddd;">';
		$message .= $order_id;
		$message .= '</td></tr>';

		$message .= '<tr><td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">';
		$message .= __( 'Application Date', 'dlseller' );
		$message .= '</td><td style="padding: 12px; width: 67%; border: 1px solid #ddd;">';
		$message .= $order_data['order_date'];
		$message .= '</td></tr>';

		$message .= '<tr><td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">';
		$message .= __( 'Member ID', 'dlseller' );
		$message .= '</td><td style="padding: 12px; width: 67%; border: 1px solid #ddd;">';
		$message .= $member_id;
		$message .= '</td></tr>';

		$message .= '<tr><td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">';
		$message .= __( 'Contractor name', 'dlseller' );
		$message .= '</td><td style="padding: 12px; width: 67%; border: 1px solid #ddd;">';
		$message .= sprintf( _x( '%s', 'honorific', 'usces' ), $name );
		$message .= '</td></tr>';

		$cart      = usces_get_ordercartdata( $order_id );
		$cart_row  = current( $cart );
		$item_name = $usces->getCartItemName_byOrder( $cart_row );
		$options   = ( empty( $cart_row['options'] ) ) ? array() : $cart_row['options'];
		$message  .= '<tr><td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">';
		$message  .= __( 'Items', 'usces' );
		$message  .= '</td><td style="padding: 12px; width: 67%; border: 1px solid #ddd;">';
		$message  .= $item_name;
		if ( is_array( $options ) && 0 < count( $options ) ) {
			$optstr = '';
			foreach ( $options as $key => $value ) {
				if ( ! empty( $key ) ) {
					$key   = urldecode( $key );
					$value = maybe_unserialize( $value );
					if ( is_array( $value ) ) {
						$c       = '';
						$optstr .= '( ' . $key . ' : ';
						foreach ( $value as $v ) {
							$optstr .= $c . rawurldecode( $v );
							$c       = ', ';
						}
						$optstr .= ' )<br>';
					} else {
						$optstr .= '( ' . $key . ' : ' . rawurldecode( $value ) . ' )<br>';
					}
				}
			}
			$message .= $optstr;
		}
		$message .= '</td></tr>';

		$message .= '<tr><td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">';
		$message .= __( 'Settlement amount', 'usces' );
		$message .= '</td><td style="padding: 12px; width: 67%; border: 1px solid #ddd;">';
		$message .= usces_crform( $continue_data['price'], true, false, 'return' );
		$message .= '</td></tr>';
		if ( isset( $response_data['reminder'] ) ) {
			if ( ! empty( $charged_date ) ) {
				$message .= '<tr><td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">';
				$message .= __( 'Next Withdrawal Date', 'dlseller' );
				$message .= '</td><td style="padding: 12px; width: 67%; border: 1px solid #ddd;">';
				$message .= $charged_date;
				$message .= '</td></tr>';
			}
			if ( ! empty( $contracted_date ) ) {
				$message .= '<tr><td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">';
				$message .= __( 'Renewal Date', 'dlseller' );
				$message .= '</td><td style="padding: 12px; width: 67%; border: 1px solid #ddd;">';
				$message .= $contracted_date;
				$message .= '</td></tr>';
			}
		} else {
			if ( ! empty( $charged_date ) ) {
				$message .= '<tr><td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">';
				$message .= __( 'Next Withdrawal Date', 'dlseller' );
				$message .= '</td><td style="padding: 12px; width: 67%; border: 1px solid #ddd;">';
				$message .= $charged_date;
				$message .= '</td></tr>';
			}
			if ( ! empty( $contracted_date ) ) {
				$message .= '<tr><td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">';
				$message .= __( 'Renewal Date', 'dlseller' );
				$message .= '</td><td style="padding: 12px; width: 67%; border: 1px solid #ddd;">';
				$message .= $contracted_date;
				$message .= '</td></tr>';
			}
			if ( isset( $response_data['status'] ) && 'COMPLETED' === $response_data['status'] ) {
				$message .= '<tr><td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">';
				$message .= __( 'Result', 'usces' );
				$message .= '</td><td style="padding: 12px; width: 67%; border: 1px solid #ddd;">';
				$message .= __( 'Normal done', 'usces' );
				$message .= '</td></tr>';
			} elseif ( isset( $response_data['name'] ) ) {
				$message .= '<tr><td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">';
				$message .= __( 'Result', 'usces' );
				$message .= '</td><td style="padding: 12px; width: 67%; border: 1px solid #ddd;">';
				$message .= $response_data['name'];
				$message .= '</td></tr>';
			} else {
				$message .= '<tr><td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">';
				$message .= __( 'Result', 'usces' );
				$message .= '</td><td style="padding: 12px; width: 67%; border: 1px solid #ddd;">';
				$message .= __( 'Error', 'usces' );
				$message .= '</td></tr>';
			}
		}

		$ba_id = $this->get_continuation_ba_id( $member_id, $order_id );
		if ( ! empty( $ba_id ) ) {
			$message .= '<tr><td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">';
			$message .= __( 'Billing Agreement ID', 'usces' );
			$message .= '</td><td style="padding: 12px; width: 67%; border: 1px solid #ddd;">';
			$message .= $ba_id;
			$message .= '</td></tr>';
		}
		$message .= '</tbody></table>';
		return $message;
	}

	/**
	 * 自動継続課金処理
	 * dlseller_action_do_continuation
	 *
	 * @param  string $today Today.
	 * @param  array  $todays_charging Charged data.
	 */
	public function do_auto_continuation( $today, $todays_charging ) {
		global $usces;

		if ( empty( $todays_charging ) ) {
			return;
		}

		$ok            = ( empty( $this->continuation_charging_mail['OK'] ) ) ? 0 : $this->continuation_charging_mail['OK'];
		$error         = ( empty( $this->continuation_charging_mail['NG'] ) ) ? 0 : $this->continuation_charging_mail['NG'];
		$admin_subject = apply_filters( 'usces_filter_paypal_cp_autobilling_email_admin_subject', __( 'Automatic Continuing Charging Process Result', 'usces' ) . ' ' . $today, $today );
		$admin_footer  = apply_filters( 'usces_filter_paypal_cp_autobilling_email_admin_mail_footer', __( 'For details, please check on the administration panel > Continuous charge member list > Continuous charge member information.', 'usces' ) );
		$admin_message = __( 'Report that automated accounting process has been completed.', 'usces' ) . "\r\n\r\n"
			. __( 'Processing date', 'usces' ) . ' : ' . date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) . "\r\n"
			. __( 'Normal done', 'usces' ) . ' : ' . $ok . "\r\n"
			. __( 'Abnormal done', 'usces' ) . ' : ' . $error . "\r\n\r\n";
		foreach ( (array) $this->continuation_charging_mail['mail'] as $mail ) {
			$admin_message .= $mail . "\r\n";
		}
		$admin_message .= $admin_footer . "\r\n";

		$to_admin = array(
			'to_name'      => apply_filters( 'usces_filter_bccmail_to_admin_name', 'Shop Admin' ),
			'to_address'   => $usces->options['order_mail'],
			'from_name'    => apply_filters( 'usces_filter_bccmail_from_admin_name', 'Welcart Auto BCC' ),
			'from_address' => $usces->options['sender_mail'],
			'reply_name'   => get_option( 'blogname' ),
			'reply_to'     => usces_get_first_order_mail(),
			'return_path'  => $usces->options['sender_mail'],
			'subject'      => $admin_subject,
			'message'      => do_shortcode( $admin_message ),
		);
		usces_send_mail( $to_admin );
		unset( $this->continuation_charging_mail );
	}

	/**
	 * 課金日通知メール
	 * dlseller_filter_reminder_mail_body
	 *
	 * @param  string $mail_body Message body.
	 * @param  int    $order_id Order number.
	 * @param  array  $continue_data Continuation data.
	 * @return string
	 */
	public function reminder_mail_body( $mail_body, $order_id, $continue_data ) {
		global $usces;

		$member_id     = $continue_data['member_id'];
		$order_id      = $continue_data['order_id'];
		$order_data    = $usces->get_order_data( $order_id, 'direct' );
		$response_data = array( 'reminder' => 'reminder' );
		$mail_body     = $this->autobilling_message( $member_id, $order_id, $order_data, $response_data, $continue_data );
		return $mail_body;
	}

	/**
	 * 契約更新日通知メール
	 * dlseller_filter_contract_renewal_mail_body
	 *
	 * @param  string $mail_body Message body.
	 * @param  int    $order_id Order number.
	 * @param  array  $continue_data Continuation data.
	 * @return string
	 */
	public function contract_renewal_mail_body( $mail_body, $order_id, $continue_data ) {
		global $usces;

		$member_id     = $continue_data['member_id'];
		$order_data    = $usces->get_order_data( $order_id, 'direct' );
		$response_data = array( 'reminder' => 'contract_renewal' );
		$mail_body     = $this->autobilling_message( $member_id, $order_id, $order_data, $response_data, $continue_data );
		return $mail_body;
	}

	/**
	 * メール支払方法
	 * usces_filter_order_confirm_mail_payment
	 * usces_filter_send_order_mail_payment
	 *
	 * @param  string $msg_payment Message body.
	 * @param  int    $order_id Order number.
	 * @param  array  $payment Payment data.
	 * @param  array  $cart Cart data.
	 * @return string
	 */
	public function continue_order_mail_payment( $msg_payment, $order_id, $payment, $cart ) {
		global $usces;

		$post_id       = $cart[0]['post_id'];
		$charging_type = $usces->getItemChargingType( $post_id );
		if ( 'continue' !== $charging_type ) {
			return $msg_payment;
		}

		$acting_flg = ( isset( $payment['settlement'] ) ) ? $payment['settlement'] : '';
		if ( 'acting_paypal_cp' !== $acting_flg ) {
			return $msg_payment;
		}

		$member_id = $this->get_member_id( $order_id );
		$ba_id     = $this->get_continuation_ba_id( $member_id, $order_id );
		if ( ! empty( $ba_id ) ) {
			$msg_payment .= __( 'Billing Agreement ID', 'usces' ) . ' : ' . $ba_id . "\r\n\r\n";
		}
		return $msg_payment;
	}

	/**
	 * 継続課金会員データ取得
	 *
	 * @param  int $member_id Member ID.
	 * @param  int $order_id Order number.
	 * @return array
	 */
	private function get_continuation_data( $member_id, $order_id ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT 
			`con_id` AS `con_id`, 
			`con_acting` AS `acting`, 
			`con_order_price` AS `order_price`, 
			`con_price` AS `price`, 
			`con_next_charging` AS `chargedday`, 
			`con_next_contracting` AS `contractedday`, 
			`con_startdate` AS `startdate`, 
			`con_status` AS `status` 
			FROM {$wpdb->prefix}usces_continuation 
			WHERE `con_order_id` = %d AND `con_member_id` = %d",
			$order_id,
			$member_id
		);
		$data  = $wpdb->get_row( $query, ARRAY_A );
		return $data;
	}

	/**
	 * 継続課金会員データ更新
	 *
	 * @param  int     $member_id Member ID.
	 * @param  int     $order_id Order number.
	 * @param  array   $data Continuation data.
	 * @param  boolean $stop Stop continuous billing.
	 * @return boolean
	 */
	private function update_continuation_data( $member_id, $order_id, $data, $stop = false ) {
		global $wpdb;

		if ( $stop ) {
			$query = $wpdb->prepare(
				"UPDATE {$wpdb->prefix}usces_continuation SET 
				`con_status` = 'cancellation' 
				WHERE `con_order_id` = %d AND `con_member_id` = %d",
				$order_id,
				$member_id
			);
		} else {
			$query = $wpdb->prepare(
				"UPDATE {$wpdb->prefix}usces_continuation SET 
				`con_price` = %f, 
				`con_next_charging` = %s, 
				`con_next_contracting` = %s, 
				`con_status` = %s 
				WHERE `con_order_id` = %d AND `con_member_id` = %d",
				$data['price'],
				$data['chargedday'],
				$data['contractedday'],
				$data['status'],
				$order_id,
				$member_id
			);
		}
		$res = $wpdb->query( $query );
		return $res;
	}

	/**
	 * 継続課金 ba_id 保存
	 *
	 * @param  int    $member_id Member ID.
	 * @param  int    $order_id Order number.
	 * @param  string $ba_id Billing Agreement ID.
	 */
	private function set_continuation_ba_id( $member_id, $order_id, $ba_id ) {
		global $usces;

		$usces->set_member_meta_value( 'paypal_continuation_' . $order_id, $ba_id, $member_id );
	}

	/**
	 * 継続課金 ba_id 取得
	 *
	 * @param  int $member_id Member ID.
	 * @param  int $order_id Order number.
	 * @return string
	 */
	private function get_continuation_ba_id( $member_id, $order_id = 0 ) {
		global $usces, $wpdb;

		if ( empty( $order_id ) ) {
			$member_meta_table = usces_get_tablename( 'usces_member_meta' );
			$query             = $wpdb->prepare( "SELECT COUNT(*) FROM {$member_meta_table} WHERE `member_id` = %d AND `meta_key` LIKE %s", $member_id, 'paypal_continuation_%' );
			$ba_id             = $wpdb->get_var( $query );
		} else {
			$ba_id = $usces->get_member_meta_value( 'paypal_continuation_' . $order_id, $member_id );
		}
		return $ba_id;
	}

	/**
	 * 継続課金決済金額取得
	 *
	 * @param  int $con_id Continuation ID.
	 * @return float
	 */
	private function get_continuation_amount( $con_id ) {
		global $wpdb;

		$amount = $wpdb->get_var( $wpdb->prepare( "SELECT `con_order_price` FROM {$wpdb->prefix}usces_continuation WHERE `con_id` = %d", $con_id ) );
		return $amount;
	}

	/**
	 * 定期購入データ登録
	 * wcad_action_reg_regulardata
	 *
	 * @param  array $args Compact array( $cart, $entry, $order_id, $member_id, $payments, $charging_type, $results、$reg_id ).
	 */
	public function register_regulardata( $args ) {
		global $usces;
		extract( $args );

		if ( ! empty( $reg_id ) && ! empty( $member_id ) && ! empty( $results['ba_id'] ) ) {
			$this->set_regular_ba_id( $member_id, $reg_id, $results['ba_id'] );
		}
	}

	/**
	 * 発送先リスト利用可能決済
	 * wcad_filter_shippinglist_acting
	 *
	 * @param  string $acting Payment method.
	 * @return string
	 */
	public function set_shippinglist_acting( $acting ) {
		$acting = 'acting_paypal_cp';
		return $acting;
	}

	/**
	 * 管理画面利用可能決済メッセージ
	 * wcad_filter_available_regular_payment_method
	 *
	 * @param  array $payment_method Payment method.
	 * @return array
	 */
	public function available_regular_payment_method( $payment_method ) {
		$payment_method[] = 'acting_paypal_cp';
		return $payment_method;
	}

	/**
	 * 定期購入決済処理
	 * wcad_action_reg_auto_orderdata
	 *
	 * @param  array $args Compact array( $cart, $entry, $order_id, $member_id, $payments, $charging_type, $total_amount, $reg_id, $order_date ).
	 */
	public function register_auto_orderdata( $args ) {
		global $usces;
		extract( $args );

		$acting_flg = $payments['settlement'];
		if ( 'acting_paypal_cp' !== $payments['settlement'] ) {
			return;
		}

		if ( ! usces_is_membersystem_state() ) {
			return;
		}

		if ( 0 >= $total_amount ) {
			return;
		}

		$bn_code     = self::API_BN_CODE_PCP;
		$ba_id       = $this->get_regular_ba_id( $member_id, $reg_id );
		$tracking_id = usces_acting_key();
		$usces->set_order_meta_value( 'reference_id', $tracking_id, $order_id );
		$settltment_errmsg = '';

		if ( ! empty( $ba_id ) ) {
			$acting_opts = $this->get_acting_settings();
			$cp_intent   = ( isset( $acting_opts['intent'] ) ) ? $acting_opts['intent'] : '';

			/* Get Access Token */
			$access_token = $this->get_access_token();

			$shipping = usces_have_shipped( $cart );
			if ( $shipping ) {
				$regular_data                  = $this->get_regular_data( $member_id, $reg_id );
				$delivery                      = (array) unserialize( $regular_data['reg_delivery'] );
				$entry['delivery']['name1']    = $delivery['name1'];
				$entry['delivery']['name2']    = $delivery['name2'];
				$entry['delivery']['country']  = $delivery['country'];
				$entry['delivery']['zipcode']  = $delivery['zipcode'];
				$entry['delivery']['pref']     = $delivery['pref'];
				$entry['delivery']['address1'] = $delivery['address1'];
				$entry['delivery']['address2'] = $delivery['address2'];
				$entry['delivery']['address3'] = $delivery['address3'];
			}
			$entry['order']['total_full_price'] = usces_crform( $total_amount, false, false, 'return', false );

			/* Create order */
			$response_order_data = $this->api_create_order( $access_token, $bn_code, $cp_intent, $tracking_id, $entry, $cart );
			if ( isset( $response_order_data['id'] ) && isset( $response_order_data['status'] ) && 'CREATED' === $response_order_data['status'] ) {
				$resource_id             = $response_order_data['id'];
				$correlation_id          = '';
				$payment_source          = array();
				$payment_source['token'] = array(
					'id'   => $ba_id,
					'type' => 'BILLING_AGREEMENT',
				);
				if ( 'CAPTURE' === $cp_intent ) {
					/* Capture */
					$response_data = $this->api_capture_order( $access_token, $bn_code, $tracking_id, $resource_id, $correlation_id, $payment_source );
				} elseif ( 'AUTHORIZE' === $cp_intent ) {
					/* Authorize */
					$response_data = $this->api_authorize_order( $access_token, $bn_code, $tracking_id, $resource_id, $correlation_id, $payment_source );
				}
				if ( isset( $response_data['status'] ) ) {
					$status = $response_data['status'];
				} elseif ( isset( $response_data['name'] ) ) {
					$status = $response_data['name'];
				} else {
					$status = $cp_intent . ' ERROR';
				}
				$response_data = apply_filters( 'usces_filter_paypal_cp_register_auto_orderdata_log', $response_data, $status, $args );
				if ( 'COMPLETED' === $status && isset( $response_data['purchase_units'] ) ) {
					$this->save_acting_log( $response_data, 'paypal_cp', $cp_intent, $status, $total_amount, $order_id, $tracking_id );
					$usces->set_order_meta_value( 'resource_id', $resource_id, $order_id );
					$purchase_units = $response_data['purchase_units'][0];
					if ( isset( $purchase_units['payments']['captures'] ) ) {
						$payments = $purchase_units['payments']['captures'][0];
					} elseif ( isset( $purchase_units['payments']['authorizations'] ) ) {
						$payments = $purchase_units['payments']['authorizations'][0];
					}
					if ( ! empty( $payments['id'] ) ) {
						$usces->set_order_meta_value( 'wc_trans_id', $payments['id'], $order_id ); /* 決済ID */
						$usces->set_order_meta_value( 'trans_id', $payments['id'], $order_id ); /* 取引ID */
					}
					$response_data['acting'] = $this->paymod_id;
					$usces->set_order_meta_value( $acting_flg, usces_serialize( $response_data ), $order_id );
				} else {
					$this->save_acting_log( $response_data, 'paypal_cp', 'ERROR', $status, 0, $order_id, $tracking_id );
					$this->save_entry_log( $tracking_id, $total_amount, $cart, $entry );
					$settltment_errmsg = __( '[Regular purchase] Settlement was not completed.', 'autodelivery' );
					$log               = array(
						'acting' => $this->paymod_id,
						'key'    => $tracking_id,
						'result' => $status,
						'data'   => $response_data,
					);
					usces_save_order_acting_error( $log );
				}
				do_action( 'usces_action_register_auto_orderdata', $args, $response_data );
			} else {
				$this->save_entry_log( $tracking_id, $total_amount, $cart, $entry );
				$settltment_errmsg = __( '[Regular purchase] Settlement was not completed.', 'autodelivery' );
				if ( isset( $response_order_data['status'] ) ) {
					$status = $response_order_data['status'];
				} elseif ( isset( $response_order_data['name'] ) ) {
					$status = $response_order_data['name'];
				} else {
					$status = 'CREATE ORDER ERROR';
				}
				$log = array(
					'acting' => $this->paymod_id,
					'key'    => $tracking_id,
					'result' => $status,
					'data'   => $response_order_data,
				);
				usces_save_order_acting_error( $log );
				$this->save_acting_log( $response_order_data, 'paypal_cp', 'ERROR', $status, 0, $order_id, $tracking_id );
				do_action( 'usces_action_register_auto_orderdata', $args, $response_order_data );
			}
		} else {
			$this->save_entry_log( $tracking_id, $total_amount, $cart, $entry );
			$settltment_errmsg = __( '[Regular purchase] Member information acquisition error.', 'autodelivery' );
			$logdata           = array( 'ba_id' => $ba_id );
			$log               = array(
				'acting' => $this->paymod_id,
				'key'    => $member_id,
				'result' => 'MEMBER ERROR',
				'data'   => $logdata,
			);
			usces_save_order_acting_error( $log );
			$this->save_acting_log( $logdata, 'paypal_cp', 'ERROR', 'MEMBER ERROR', 0, $order_id, $tracking_id );
			do_action( 'usces_action_register_auto_orderdata', $args, $log );
		}
		if ( '' !== $settltment_errmsg ) {
			$settlement = array(
				'settltment_status' => __( 'Failure', 'autodelivery' ),
				'settltment_errmsg' => $settltment_errmsg,
			);
			$usces->set_order_meta_value( $acting_flg, usces_serialize( $settlement ), $order_id );
			wcad_settlement_error_mail( $order_id, $settltment_errmsg );
		}
	}

	/**
	 * 自動受注決済エラーメールヘッダー
	 * wcad_filter_send_settlement_error_mail_message_head
	 *
	 * @param  string $mail_header Message header.
	 * @param  array  $order_data Order data.
	 * @return string
	 */
	public function settlement_error_mail_message_header( $mail_header, $order_data ) {
		$payment    = usces_get_payments_by_name( $order_data['order_payment_name'] );
		$acting_flg = ( isset( $payment['settlement'] ) ) ? $payment['settlement'] : '';
		if ( 'acting_paypal_cp' === $acting_flg ) {
			$mail_header = __( 'Settlement error has occurred in the regular purchase. Please check your PayPal account for any problems.', 'usces' ) . "\r\n\r\n";
		}
		return $mail_header;
	}

	/**
	 * 自動受注決済エラーメール本文
	 * wcad_filter_send_settlement_error_mail_message
	 *
	 * @param  string $mail_body Message body.
	 * @param  array  $order_data Order data.
	 * @param  string $errmsg Error message.
	 * @return string
	 */
	public function settlement_error_mail_message( $mail_body, $order_data, $errmsg ) {
		$payment    = usces_get_payments_by_name( $order_data['order_payment_name'] );
		$acting_flg = ( isset( $payment['settlement'] ) ) ? $payment['settlement'] : '';
		if ( 'acting_paypal_cp' === $acting_flg ) {
			$name         = usces_localized_name( $order_data['order_name1'], $order_data['order_name2'], 'return' );
			$order_number = usces_get_deco_order_id( $order_data['ID'] );
			if ( usces_is_html_mail() ) {
				$hook_args = compact( 'order_data', 'name', 'order_number', 'errmsg' );
				$mail_body = $this->settlement_error_mail_message_htmlbody( $hook_args );
			} else {
				$mail_body  = '--------------------------------' . "\r\n";
				$mail_body .= __( 'Member ID', 'usces' ) . ' : ' . $order_data['mem_id'] . "\r\n";
				$mail_body .= __( 'Name', 'usces' ) . ' : ' . sprintf( _x( '%s', 'honorific', 'usces' ), $name ) . "\r\n";
				$mail_body .= __( 'e-mail adress', 'usces' ) . ' : ' . $order_data['order_email'] . "\r\n";
				$mail_body .= __( 'Automatic order number', 'autodelivery' ) . ' : ' . $order_number . "\r\n";
				$mail_body .= $errmsg . "\r\n";
				$mail_body .= '--------------------------------' . "\r\n\r\n";
				$mail_body .= __( 'If you have not requested this email, sorry to trouble you, but please contact us.', 'usces' ) . "\r\n\r\n";
			}
		}
		return $mail_body;
	}

	/**
	 * Generate html for the body of settlement error mail
	 *
	 * @param array $hook_args : compact( 'order_data', 'name', 'order_number', 'errmsg' ).
	 *
	 * @return text : mail body
	 */
	public function settlement_error_mail_message_htmlbody( $hook_args ) {
		extract( $hook_args );

		$mail_body  = '<table style="font-size: 14px; margin-bottom: 40px; width: 100%; border-collapse: collapse; border: 1px solid #ddd;">';
		$mail_body .= '<caption style="background-color: #111; margin-bottom: 20px; padding: 15px; color: #fff; font-size: 15px; font-weight: 700; text-align: left;">';
		$mail_body .= remove_query_arg( 'uscesid', usces_url( 'login', 'return' ) );
		$mail_body .= '</caption><tbody>';
		$mail_body .= '<tr>';
		$mail_body .= '<td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">' . __( 'Member ID', 'usces' ) . '</td>';
		$mail_body .= '<td style="padding: 12px; width: 67%; border: 1px solid #ddd;">' . esc_attr( $order_data['mem_id'] ) . '</td>';
		$mail_body .= '</tr>';
		$mail_body .= '<tr>';
		$mail_body .= '<td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">' . __( 'Name', 'usces' ) . '</td>';
		$mail_body .= '<td style="padding: 12px; width: 67%; border: 1px solid #ddd;">' . sprintf( _x( '%s', 'honorific', 'usces' ), $name ) . '</td>';
		$mail_body .= '</tr>';
		$mail_body .= '<tr>';
		$mail_body .= '<td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">' . __( 'e-mail adress', 'usces' ) . '</td>';
		$mail_body .= '<td style="padding: 12px; width: 67%; border: 1px solid #ddd;">' . esc_attr( $order_data['order_email'] ) . '</td>';
		$mail_body .= '</tr>';
		$mail_body .= '<tr>';
		$mail_body .= '<td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">' . __( 'Automatic order number', 'autodelivery' ) . '</td>';
		$mail_body .= '<td style="padding: 12px; width: 67%; border: 1px solid #ddd;">' . esc_attr( $order_number ) . '</td>';
		$mail_body .= '</tr>';
		if ( ! empty( $errmsg ) ) {
			$mail_body .= '<tr>';
			$mail_body .= '<td style="background-color: #f9f9f9; padding: 12px; width: 33%; border: 1px solid #ddd; text-align: left;">' . __( 'Settlement error message', 'usces' ) . '</td>';
			$mail_body .= '<td style="padding: 12px; width: 67%; border: 1px solid #ddd;">' . esc_html( $errmsg ) . '</td>';
			$mail_body .= '</tr>';
		}
		$mail_body .= '</tbody></table>';

		$mail_body .= '<table style="font-size:15px; margin-bottom: 10px;" border="0" width="540" cellspacing="0" cellpadding="0" align="center" bgcolor="#ffffff">';
		$mail_body .= '<tbody><tr>';
		$mail_body .= '<td>' . __( 'If you have not requested this email, sorry to trouble you, but please contact us.', 'usces' ) . '</td>';
		$mail_body .= '</tr>';
		$mail_body .= '</tbody></table>';
		$mail_body .= '<hr style="margin: 50px 0 0; border-style: none; border-top: 3px solid #777;" />';

		return $mail_body;
	}

	/**
	 * 定期購入データ編集画面
	 * wcad_action_regular_information_edit_form
	 *
	 * @param array $regular_order Regular data.
	 */
	public function regular_information_edit_form( $regular_order ) {
		$payment    = usces_get_payments_by_name( $regular_order['reg_payment_name'] );
		$acting_flg = ( isset( $payment['settlement'] ) ) ? $payment['settlement'] : '';
		if ( 'acting_paypal_cp' !== $acting_flg ) {
			return;
		}

		$ba_id = $this->get_regular_ba_id( $regular_order['reg_mem_id'], $regular_order['reg_id'] );
		?>
	<div class="inside">
	<table class="mem_info">
		<tr><th class="label"><?php esc_html_e( 'Billing Agreement ID', 'usces' ); ?></th><td class="col1"><?php echo esc_attr( $ba_id ); ?></td></tr>
	</table>
	</div>
		<?php
	}

	/**
	 * メール支払方法
	 * usces_filter_order_confirm_mail_payment
	 * usces_filter_send_order_mail_payment
	 * wcad_filter_order_confirm_mail_payment
	 *
	 * @param  string $msg_payment Message body.
	 * @param  int    $order_id Order number.
	 * @param  array  $payment Payment data.
	 * @param  array  $cart Cart data.
	 * @return string
	 */
	public function regular_order_mail_payment( $msg_payment, $order_id, $payment, $cart ) {
		global $usces;

		if ( ! wcad_have_regular_order( $cart ) ) {
			return $msg_payment;
		}

		$acting_flg = ( isset( $payment['settlement'] ) ) ? $payment['settlement'] : '';
		if ( 'acting_paypal_cp' !== $acting_flg ) {
			return $msg_payment;
		}

		$member_id = $this->get_member_id( $order_id );
		$reg_id    = $this->get_regular_id( $order_id );
		if ( ! empty( $reg_id ) ) {
			$ba_id = $this->get_regular_ba_id( $member_id, $reg_id );
			if ( ! empty( $ba_id ) ) {
				$msg_payment .= __( 'Billing Agreement ID', 'usces' ) . ' : ' . $ba_id . "\r\n\r\n";
			}
		}
		return $msg_payment;
	}

	/**
	 * 定期購入ID 取得
	 *
	 * @param  int $order_id Order number.
	 * @return int
	 */
	private function get_regular_id( $order_id ) {
		global $wpdb, $usces;

		$query  = $wpdb->prepare( "SELECT `reg_id` FROM {$wpdb->prefix}usces_regular WHERE `reg_order_id` = %d ", $order_id );
		$reg_id = $wpdb->get_var( $query );
		if ( empty( $reg_id ) ) {
			$reg_id = $usces->get_order_meta_value( 'regular_id', $order_id );
		}
		return $reg_id;
	}

	/**
	 * 定期購入データ取得
	 *
	 * @param  int $member_id Member ID.
	 * @param  int $reg_id Regular ID.
	 * @return array
	 */
	private function get_regular_data( $member_id, $reg_id ) {
		global $wpdb;

		$query = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}usces_regular WHERE `reg_id` = %d AND `reg_mem_id` = %d", $reg_id, $member_id );
		$data  = $wpdb->get_row( $query, ARRAY_A );
		return $data;
	}

	/**
	 * 定期購入 ba_id 保存
	 *
	 * @param  int    $member_id Member ID.
	 * @param  int    $reg_id Regular ID.
	 * @param  string $ba_id Billing Agreement ID.
	 */
	private function set_regular_ba_id( $member_id, $reg_id, $ba_id ) {
		global $usces;

		$usces->set_member_meta_value( 'paypal_regular_' . $reg_id, $ba_id, $member_id );
	}

	/**
	 * 定期購入 ba_id 取得
	 *
	 * @param  int $member_id Member ID.
	 * @param  int $reg_id Regular ID.
	 * @return string
	 */
	private function get_regular_ba_id( $member_id, $reg_id = 0 ) {
		global $usces, $wpdb;

		if ( empty( $reg_id ) ) {
			$member_meta_table = usces_get_tablename( 'usces_member_meta' );
			$query             = $wpdb->prepare( "SELECT COUNT(*) FROM {$member_meta_table} WHERE `member_id` = %d AND `meta_key` LIKE %s", $member_id, 'paypal_regular_%' );
			$ba_id             = $wpdb->get_var( $query );
		} else {
			$ba_id = $usces->get_member_meta_value( 'paypal_regular_' . $reg_id, $member_id );
		}
		return $ba_id;
	}

	/**
	 * 定期購入受注金額取得
	 *
	 * @param  int $order_id Order ID.
	 * @return float
	 */
	private function get_order_amount( $order_id ) {
		global $wpdb;

		$amount = $wpdb->get_var( $wpdb->prepare( "SELECT ( `order_item_total_price` - `order_usedpoint` + `order_discount` + `order_shipping_charge` + `order_cod_fee` + `order_tax` ) AS `total_price` FROM {$wpdb->prefix}usces_order WHERE `ID` = %d", $order_id ) );
		return $amount;
	}

	/**
	 * 会員ID取得
	 *
	 * @param  int $order_id Order number.
	 * @return int
	 */
	private function get_member_id( $order_id ) {
		global $wpdb;

		$query     = $wpdb->prepare( "SELECT `mem_id` FROM {$wpdb->prefix}usces_order WHERE `ID` = %d", $order_id );
		$member_id = $wpdb->get_var( $query );
		return $member_id;
	}

	/**
	 * 決済ログ保存
	 *
	 * @param  string $log Log data.
	 * @param  string $acting Acting type.
	 * @param  string $status Status.
	 * @param  string $result Result.
	 * @param  float  $amount Amount.
	 * @param  int    $order_id Order number.
	 * @param  string $tracking_id Tracking ID.
	 * @return array
	 */
	private function save_acting_log( $log, $acting, $status, $result, $amount, $order_id, $tracking_id ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"INSERT INTO {$wpdb->prefix}usces_acting_log ( `datetime`, `log`, `acting`, `status`, `result`, `amount`, `order_id`, `tracking_id` ) VALUES ( %s, %s, %s, %s, %s, %f, %d, %s )",
			current_time( 'mysql' ),
			usces_serialize( $log ),
			$acting,
			$status,
			$result,
			$amount,
			$order_id,
			$tracking_id
		);
		$res   = $wpdb->query( $query );
		return $res;
	}

	/**
	 * 決済ログ取得
	 *
	 * @param  int    $order_id Order number.
	 * @param  string $tracking_id Tracking ID.
	 * @param  string $result Result.
	 * @return array
	 */
	public function get_acting_log( $order_id = 0, $tracking_id = 0, $result = 'COMPLETED' ) {
		global $wpdb;

		if ( empty( $order_id ) ) {
			if ( empty( $tracking_id ) ) {
				return array();
			}
			if ( 'COMPLETED' === $result ) {
				$query = $wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}usces_acting_log WHERE`tracking_id` = %s AND `result` IN ( 'COMPLETED', 'PENDING' ) ORDER BY `ID` DESC, datetime DESC",
					$tracking_id
				);
			} else {
				$query = $wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}usces_acting_log WHERE `tracking_id` = %s ORDER BY `ID` DESC, datetime DESC",
					$tracking_id
				);
			}
		} else {
			if ( empty( $tracking_id ) ) {
				if ( 'COMPLETED' === $result ) {
					$query = $wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}usces_acting_log WHERE `datetime` IN( SELECT MAX( `datetime` ) FROM {$wpdb->prefix}usces_acting_log WHERE `order_id` = %d GROUP BY `tracking_id` ) AND `order_id` = %d AND `result` IN ( 'COMPLETED', 'PENDING' ) ORDER BY `ID` DESC, datetime DESC",
						$order_id,
						$order_id
					);
				} else {
					$query = $wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}usces_acting_log WHERE `datetime` IN( SELECT MAX( `datetime` ) FROM {$wpdb->prefix}usces_acting_log WHERE `order_id` = %d GROUP BY `tracking_id` ) AND `order_id` = %d ORDER BY `ID` DESC, datetime DESC",
						$order_id,
						$order_id
					);
				}
			} else {
				if ( 'COMPLETED' === $result ) {
					$query = $wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}usces_acting_log WHERE `order_id` = %d AND `tracking_id` = %s AND `result` IN ( 'COMPLETED', 'PENDING' ) ORDER BY `ID` DESC, datetime DESC",
						$order_id,
						$tracking_id
					);
				} else {
					$query = $wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}usces_acting_log WHERE `order_id` = %d AND `tracking_id` = %s ORDER BY `ID` DESC, datetime DESC",
						$order_id,
						$tracking_id
					);
				}
			}
		}
		$log_data = $wpdb->get_results( $query, ARRAY_A );
		return $log_data;
	}

	/**
	 * 自動決済ログ保存
	 *
	 * @param  string $tracking_id Tracking ID.
	 * @param  float  $amount Amount.
	 * @param  array  $cart Cart data.
	 * @param  array  $entry Entry data.
	 * @return array
	 */
	private function save_entry_log( $tracking_id, $amount, $cart, $entry ) {
		global $wpdb;

		$log   = array(
			'cart'  => $cart,
			'entry' => $entry,
		);
		$query = $wpdb->prepare(
			"INSERT INTO {$wpdb->prefix}usces_acting_log ( `datetime`, `log`, `acting`, `result`, `amount`, `tracking_id` ) VALUES ( %s, %s, %s, %s, %f, %s )",
			current_time( 'mysql' ),
			usces_serialize( $log ),
			$this->paymod_id,
			'ENTRY',
			$amount,
			$tracking_id
		);
		$res   = $wpdb->query( $query );
		return $res;
	}

	/**
	 * 自動決済ログ取得
	 *
	 * @param  string $tracking_id Tracking ID.
	 * @return array
	 */
	public function get_entry_log( $tracking_id ) {
		global $wpdb;

		$query    = $wpdb->prepare(
			"SELECT `log` FROM {$wpdb->prefix}usces_acting_log WHERE `acting` = %s AND `result` = %s AND `tracking_id` = %s",
			$this->paymod_id,
			'ENTRY',
			$tracking_id
		);
		$log_data = $wpdb->get_var( $query );
		$log      = ( $log_data ) ? usces_unserialize( $log_data ) : array();
		return $log;
	}

	/**
	 * 自動決済ログ削除
	 *
	 * @param  string $tracking_id Tracking ID.
	 * @return array
	 */
	public function del_entry_log( $tracking_id ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}usces_acting_log WHERE `acting` = %s AND `result` = %s AND `tracking_id` = %s",
			$this->paymod_id,
			'ENTRY',
			$tracking_id
		);
		$res   = $wpdb->query( $query );
		return $res;
	}

	/**
	 * 最新処理取得
	 *
	 * @param  int    $order_id Order number.
	 * @param  string $tracking_id Tracking ID.
	 * @param  string $result Result.
	 * @return array
	 */
	public function get_acting_latest_log( $order_id, $tracking_id, $result = 'COMPLETED' ) {
		$log_data = $this->get_acting_log( $order_id, $tracking_id, $result );
		if ( $log_data ) {
			$data       = current( $log_data );
			$latest_log = array(
				'log'         => usces_unserialize( $data['log'] ),
				'acting'      => $data['acting'],
				'status'      => $data['status'],
				'result'      => $data['result'],
				'amount'      => $data['amount'],
				'order_id'    => $data['order_id'],
				'tracking_id' => $data['tracking_id'],
			);
		} else {
			$latest_log = array();
		}
		return $latest_log;
	}

	/**
	 * 最新決済金額取得
	 *
	 * @param  int    $order_id Order number.
	 * @param  string $tracking_id Tracking ID.
	 * @return int    $amount Amount.
	 */
	private function get_latest_amount( $order_id, $tracking_id ) {
		$amount   = 0;
		$refund   = 0;
		$log_data = $this->get_acting_log( $order_id, $tracking_id );
		if ( $log_data ) {
			foreach ( (array) $log_data as $data ) {
				if ( 'CAPTURE' === $data['status'] ) {
					$amount = $data['amount'];
				} elseif ( 'AUTHORIZE' === $data['status'] ) {
					if ( 0.0 === (float) $amount ) {
						$amount = $data['amount'];
					}
				} elseif ( 'REFUND' === $data['status'] || 'VOID' === $data['status'] ) {
					$refund += $data['amount']; /* minus value */
				}
			}
		}
		return $amount + $refund;
	}

	/**
	 * 決済処理取得
	 *
	 * @param  int    $order_id Order number.
	 * @param  string $tracking_id Tracking ID.
	 * @return string
	 */
	private function get_acting_status( $order_id, $tracking_id ) {
		global $wpdb;

		$acting_status = '';
		$latest_log    = $this->get_acting_latest_log( $order_id, $tracking_id, 'ALL' );
		if ( isset( $latest_log['status'] ) && isset( $latest_log['result'] ) ) {
			if ( 'COMPLETED' === $latest_log['result'] ) {
				$amount = $this->get_latest_amount( $order_id, $tracking_id );
				if ( 'VOID' === $latest_log['status'] ) {
					$acting_status = 'VOIDED';
				} elseif ( 'REFUND' === $latest_log['status'] && 0 < $amount ) {
					$acting_status = 'CAPTURE';
				} elseif ( 'REFUND' === $latest_log['status'] && 0.0 === (float) $amount ) {
					$acting_status = 'REFUNDED';
				} else {
					$acting_status = $latest_log['status'];
				}
			} else {
				$acting_status = $latest_log['status'];
			}
		}
		return $acting_status;
	}

	/**
	 * 決済履歴
	 *
	 * @param  int    $order_id Order number.
	 * @param  string $tracking_id Tracking ID.
	 * @return string
	 */
	private function settlement_history( $order_id, $tracking_id ) {
		$html     = '';
		$log_data = $this->get_acting_log( $order_id, $tracking_id, 'ALL' );
		if ( $log_data ) {
			$num  = count( $log_data );
			$html = '<table class="settlement-history">
				<thead class="settlement-history-head">
					<tr><th></th><th>' . __( 'Processing date', 'usces' ) . '</th><th>' . __( 'Trans ID', 'usces' ) . '</th><th>' . __( 'Process', 'usces' ) . '</th><th>' . __( 'Amount', 'usces' ) . '</th><th>' . __( 'Status', 'usces' ) . '</th></tr>
				</thead>
				<tbody class="settlement-history-body">';
			foreach ( (array) $log_data as $data ) {
				if ( 'COMPLETED' !== $data['result'] ) {
					$class  = ' error';
					$amount = '';
				} else {
					$class  = '';
					$amount = ( isset( $data['amount'] ) ) ? usces_crform( $data['amount'], false, true, 'return', true ) : '';
				}
				$log = usces_unserialize( $data['log'] );
				$id  = ( isset( $log['id'] ) ) ? $log['id'] : '';
				if ( isset( $log['purchase_units'] ) ) {
					$purchase_units = $log['purchase_units'][0];
					if ( isset( $purchase_units['payments'] ) ) {
						if ( isset( $purchase_units['payments']['captures'] ) ) {
							$payments = $purchase_units['payments']['captures'][0];
						} elseif ( isset( $purchase_units['payments']['authorizations'] ) ) {
							$payments = $purchase_units['payments']['authorizations'][0];
						} elseif ( isset( $purchase_units['payments']['refunds'] ) ) {
							$payments = $purchase_units['payments']['refunds'][0];
						}
						if ( isset( $payments['id'] ) ) {
							$id = $payments['id'];
						}
					}
				}
				$html .= '<tr>
					<td class="num">' . $num . '</td>
					<td class="datetime">' . $data['datetime'] . '</td>
					<td class="transactionid">' . $id . '</td>
					<td class="status">' . $data['status'] . '</td>
					<td class="amount">' . $amount . '</td>
					<td class="result' . $class . '">' . $data['result'] . '</td>
				</tr>';
				$num--;
			}
			$html .= '</tbody>
				</table>';
		}
		return $html;
	}

	/**
	 * 受注データ支払方法取得
	 *
	 * @param  int $order_id Order number.
	 * @return string
	 */
	private function get_order_acting_flg( $order_id ) {
		global $wpdb;

		$query              = $wpdb->prepare( "SELECT `order_payment_name` FROM {$wpdb->prefix}usces_order WHERE `ID` = %d", $order_id );
		$order_payment_name = $wpdb->get_var( $query );
		$payment            = usces_get_payments_by_name( $order_payment_name );
		$acting_flg         = ( isset( $payment['settlement'] ) ) ? $payment['settlement'] : '';
		return $acting_flg;
	}

	/**
	 * 決済オプション取得
	 *
	 * @return array
	 */
	public function get_acting_settings() {
		global $usces;

		$acting_settings = ( isset( $usces->options['acting_settings'][ $this->paymod_id ] ) ) ? $usces->options['acting_settings'][ $this->paymod_id ] : array();
		return $acting_settings;
	}

	/**
	 * Date validity check.
	 *
	 * @param  string $date Date to check.
	 * @return boolean
	 */
	private function isdate( $date ) {
		if ( empty( $date ) ) {
			return false;
		}
		try {
			new DateTime( $date );
			list( $year, $month, $day ) = explode( '-', $date );
			$res                        = checkdate( (int) $month, (int) $day, (int) $year );
			return $res;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Get PayPal locale.
	 *
	 * @return string
	 */
	private function get_locale() {
		global $usces;

		$front_lang = ( isset( $usces->options['system']['front_lang'] ) && ! empty( $usces->options['system']['front_lang'] ) ) ? $usces->options['system']['front_lang'] : usces_get_local_language();
		switch ( $front_lang ) {
			case 'en':
				$locale = 'en_US';
				break;
			case 'ja':
				$locale = 'ja_JP';
				break;
			case 'th':
				$locale = 'th_TH';
				break;
			default:
				$locale = get_locale();
		}
		return $locale;
	}

	/**
	 * Check prefix.
	 *
	 * @param string $prefix Prefix.
	 * @return bool
	 */
	private function check_prefix( $prefix ) {
		if ( 0 === strlen( $prefix ) ) {
			return true;
		}
		$check = false;
		if ( 1 > strlen( $prefix ) || 4 < strlen( $prefix ) ) {
			return $check;
		}
		if ( preg_match( '/[A-Z0-9]/', $prefix ) ) {
			$check = true;
		}
		return $check;
	}
}
