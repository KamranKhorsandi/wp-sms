<?php

namespace WP_SMS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * WP SMS newsletter class
 *
 * @category   class
 * @package    WP_SMS
 * @version    1.0
 */
class Newsletter {

	/**
	 * Wordpress Dates
	 *
	 * @var string
	 */
	public $date;

	/**
	 * Wordpress Database
	 *
	 * @var string
	 */
	protected $db;

	/**
	 * Wordpress Table prefix
	 *
	 * @var string
	 */
	protected $tb_prefix;

	/**
	 * Constructors
	 */
	public function __construct() {
		global $wpdb;

		$this->date      = WP_SMS_CURRENT_DATE;
		$this->db        = $wpdb;
		$this->tb_prefix = $wpdb->prefix;
		add_action( 'wp_enqueue_scripts', array( $this, 'load_script' ) );
	}

	/**
	 * Include front table
	 *
	 * @param  Not param
	 */
	public function load_script() {
		// jQuery will be included automatically
		wp_enqueue_script( 'ajax-script', WP_SMS_URL . 'assets/js/script.js', array( 'jquery' ), WP_SMS_VERSION );

		// Ajax params
		wp_localize_script( 'ajax-script', 'ajax_object', array(
			'ajaxurl' => get_rest_url( null, 'wpsms/v1/newsletter' )
		) );
	}

	/**
	 * Add Subscriber
	 *
	 * @param $name
	 * @param $mobile
	 * @param string $group_id
	 * @param string $status
	 * @param $key
	 *
	 * @return array
	 * @internal param param $Not
	 */
	public function add_subscriber( $name, $mobile, $group_id = '', $status = '1', $key = null ) {
		if ( $this->is_duplicate( $mobile, $group_id ) ) {
			return array(
				'result'  => 'error',
				'message' => __( 'The mobile numbers has been already duplicate.', 'wp-sms' )
			);
		}

		$result = $this->db->insert(
			$this->tb_prefix . "sms_subscribes",
			array(
				'date'         => $this->date,
				'name'         => $name,
				'mobile'       => $mobile,
				'status'       => $status,
				'activate_key' => $key,
				'group_ID'     => $group_id,
			)
		);

		if ( $result ) {
			/**
			 * Run hook after adding subscribe.
			 *
			 * @since 3.0
			 *
			 * @param string $name name.
			 * @param string $mobile mobile.
			 */
			do_action( 'wp_sms_add_subscriber', $name, $mobile );

			return array( 'result' => 'success', 'message' => __( 'Subscriber successfully added.', 'wp-sms' ) );
		}
	}

	/**
	 * @param $name
	 * @param $mobile
	 * @param string $group_id
	 * @param string $status
	 * @param null $key
	 *
	 * @return array
	 */
	public static function addSubscriber( $name, $mobile, $group_id = '', $status = '1', $key = null ) {
		global $wpdb;

		if ( self::isDuplicate( $mobile, $group_id ) ) {
			return array(
				'result'  => 'error',
				'message' => __( 'The mobile numbers has been already duplicate.', 'wp-sms' )
			);
		}

		$result = $wpdb->insert(
			$wpdb->prefix . "sms_subscribes",
			array(
				'date'         => WP_SMS_CURRENT_DATE,
				'name'         => $name,
				'mobile'       => $mobile,
				'status'       => $status,
				'activate_key' => $key,
				'group_ID'     => $group_id,
			)
		);

		if ( $result ) {
			/**
			 * Run hook after adding subscribe.
			 *
			 * @since 3.0
			 *
			 * @param string $name name.
			 * @param string $mobile mobile.
			 */
			do_action( 'wp_sms_add_subscriber', $name, $mobile );

			return array( 'result' => 'success', 'message' => __( 'Subscriber successfully added.', 'wp-sms' ) );
		}
	}

	/**
	 * Get Subscriber
	 *
	 * @param  Not param
	 *
	 * @return array|null|object|void
	 */
	public static function get_subscriber( $id ) {
		global $wpdb;
		$result = $wpdb->get_row( "SELECT * FROM `{$wpdb->prefix}sms_subscribes` WHERE ID = '" . $id . "'" );

		if ( $result ) {
			return $result;
		}
	}

	/**
	 * Delete Subscriber
	 *
	 * @param  Not param
	 *
	 * @return false|int|void
	 */
	public function delete_subscriber( $id ) {
		$result = $this->db->delete(
			$this->tb_prefix . "sms_subscribes",
			array(
				'ID' => $id,
			)
		);

		if ( $result ) {
			/**
			 * Run hook after deleting subscribe.
			 *
			 * @since 3.0
			 *
			 * @param string $result result query.
			 */
			do_action( 'wp_sms_delete_subscriber', $result );

			return $result;
		}
	}

	/**
	 * Delete subscribers by number
	 *
	 * @param $mobile
	 * @param null $group_id
	 *
	 * @return array
	 */
	public function delete_subscriber_by_number( $mobile, $group_id = null ) {
		$result = $this->db->delete(
			$this->tb_prefix . "sms_subscribes",
			array(
				'mobile'   => $mobile,
				'group_id' => $group_id,
			)
		);

		if ( ! $result ) {
			return array( 'result' => 'error', 'message' => __( 'The subscribe does not exist.', 'wp-sms' ) );
		}

		/**
		 * Run hook after deleting subscribe.
		 *
		 * @since 3.0
		 *
		 * @param string $result result query.
		 */
		do_action( 'wp_sms_delete_subscriber', $result );

		return array( 'result' => 'success', 'message' => __( 'Subscribe successfully removed.', 'wp-sms' ) );
	}

	/**
	 * @param $mobile
	 * @param null $group_id
	 *
	 * @return array
	 */
	public static function deleteSubscriberByNumber( $mobile, $group_id = null ) {
		global $wpdb;
		$result = $wpdb->delete(
			$wpdb->prefix . "sms_subscribes",
			array(
				'mobile'   => $mobile,
				'group_id' => $group_id,
			)
		);

		if ( ! $result ) {
			return array( 'result' => 'error', 'message' => __( 'The subscribe does not exist.', 'wp-sms' ) );
		}

		/**
		 * Run hook after deleting subscribe.
		 *
		 * @since 3.0
		 *
		 * @param string $result result query.
		 */
		do_action( 'wp_sms_delete_subscriber', $result );

		return array( 'result' => 'success', 'message' => __( 'Subscribe successfully removed.', 'wp-sms' ) );
	}

	/**
	 * Update Subscriber
	 *
	 * @param $id
	 * @param $name
	 * @param $mobile
	 * @param string $group_id
	 * @param string $status
	 *
	 * @return array|void
	 * @internal param param $Not
	 */
	public function update_subscriber( $id, $name, $mobile, $group_id = '', $status = '1' ) {
		if ( empty( $id ) or empty( $name ) or empty( $mobile ) ) {
			return;
		}

		if ( $this->is_duplicate( $mobile, $group_id, $id ) ) {
			return array(
				'result'  => 'error',
				'message' => __( 'The mobile numbers has been already duplicate.', 'wp-sms' )
			);
		}

		$result = $this->db->update(
			$this->tb_prefix . "sms_subscribes",
			array(
				'name'     => $name,
				'mobile'   => $mobile,
				'group_ID' => $group_id,
				'status'   => $status,
			),
			array(
				'ID' => $id
			)
		);

		if ( $result ) {

			/**
			 * Run hook after updating subscribe.
			 *
			 * @since 3.0
			 *
			 * @param string $result result query.
			 */
			do_action( 'wp_sms_update_subscriber', $result );

			return array( 'result' => 'success', 'message' => __( 'Subscriber successfully updated.', 'wp-sms' ) );
		}
	}

	/**
	 * Get Subscriber
	 *
	 * @param  Not param
	 *
	 * @return array|null|object
	 */
	public static function get_groups() {
		global $wpdb;
		$result = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}sms_subscribes_group`" );

		if ( $result ) {
			return $result;
		}
	}

	/**
	 * Get Group
	 *
	 * @param  Not param
	 *
	 * @return array|null|object|void
	 */
	public function get_group( $group_id ) {
		$result = $this->db->get_row( "SELECT * FROM `{$this->tb_prefix}sms_subscribes_group` WHERE ID = '" . $group_id . "'" );

		if ( $result ) {
			return $result;
		}
	}

	/**
	 * Delete Group
	 *
	 * @param  Not param
	 *
	 * @return false|int|void
	 */
	public function delete_group( $id ) {

		if ( empty( $id ) ) {
			return;
		}

		$result = $this->db->delete(
			$this->tb_prefix . "sms_subscribes_group",
			array(
				'ID' => $id,
			)
		);

		if ( $result ) {

			/**
			 * Run hook after deleting group.
			 *
			 * @since 3.0
			 *
			 * @param string $result result query.
			 */
			do_action( 'wp_sms_delete_group', $result );

			return $result;
		}
	}

	/**
	 * Add Group
	 *
	 * @param  Not param
	 *
	 * @return array
	 */
	public function add_group( $name ) {
		if ( empty( $name ) ) {
			return array( 'result' => 'error', 'message' => __( 'Name is empty!', 'wp-sms' ) );
		}

		$table   = $this->tb_prefix . 'sms_subscribes_group';
		$prepare = $this->db->prepare( "SELECT COUNT(ID) FROM {$table} WHERE `name` = %s", $name );
		$count   = $this->db->get_var( $prepare );
		if ( $count ) {
			return array(
				'result'  => 'error',
				'message' => sprintf( __( 'Group Name "%s" exists!', 'wp-sms' ), $name )
			);
		} else {

			$result = $this->db->insert(
				$this->tb_prefix . "sms_subscribes_group",
				array(
					'name' => $name,
				)
			);

			if ( $result ) {

				/**
				 * Run hook after adding group.
				 *
				 * @since 3.0
				 *
				 * @param string $result result query.
				 */
				do_action( 'wp_sms_add_group', $result );

				return array( 'result' => 'success', 'message' => __( 'Group successfully added.', 'wp-sms' ) );
			}
		}

	}

	/**
	 * Update Group
	 *
	 * @param $id
	 * @param $name
	 *
	 * @return array|void
	 * @internal param param $Not
	 */
	public function update_group( $id, $name ) {
		if ( empty( $id ) or empty( $name ) ) {
			return;
		}

		$table   = $this->tb_prefix . 'sms_subscribes_group';
		$prepare = $this->db->prepare( "SELECT COUNT(ID) FROM {$table} WHERE `name` = %s", $name );
		$count   = $this->db->get_var( $prepare );

		if ( $count ) {
			return array(
				'result'  => 'error',
				'message' => sprintf( __( 'Group Name "%s" exists!', 'wp-sms' ), $name )
			);
		} else {

			$result = $this->db->update(
				$this->tb_prefix . "sms_subscribes_group",
				array(
					'name' => $name,
				),
				array(
					'ID' => $id
				)
			);

			if ( $result ) {

				/**
				 * Run hook after updating group.
				 *
				 * @since 3.0
				 *
				 * @param string $result result query.
				 */
				do_action( 'wp_sms_update_group', $result );

				return array( 'result' => 'success', 'message' => __( 'Group successfully updated.', 'wp-sms' ) );
			} else {
				return array(
					'result'  => 'error',
					'message' => sprintf( __( 'Group Name "%s" exists!', 'wp-sms' ), $name )
				);
			}
		}


	}

	/**
	 * Check the mobile number is duplicate
	 *
	 * @param $mobile_number
	 * @param null $group_id
	 * @param null $id
	 *
	 * @return array|null|object|void
	 */
	private function is_duplicate( $mobile_number, $group_id = null, $id = null ) {
		$sql = "SELECT * FROM `{$this->tb_prefix}sms_subscribes` WHERE mobile = '" . $mobile_number . "'";

		if ( $group_id ) {
			$sql .= " AND group_id = '" . $group_id . "'";
		}

		if ( $id ) {
			$sql .= " AND id != '" . $id . "'";
		}

		$result = $this->db->get_row( $sql );

		return $result;
	}

	/**
	 * @param $mobile_number
	 * @param null $group_id
	 * @param null $id
	 *
	 * @return mixed
	 */
	public static function isDuplicate( $mobile_number, $group_id = null, $id = null ) {
		global $wpdb;
		$sql = "SELECT * FROM `{$wpdb->prefix}sms_subscribes` WHERE mobile = '" . $mobile_number . "'";

		if ( $group_id ) {
			$sql .= " AND group_id = '" . $group_id . "'";
		}

		if ( $id ) {
			$sql .= " AND id != '" . $id . "'";
		}

		$result = $wpdb->get_row( $sql );

		return $result;
	}


	/**
	 * @param string $group_id
	 *
	 * @return array
	 */
	public static function getSubscribers( $group_id = '' ) {
		global $wpdb;

		$where = '';

		if ( $group_id ) {
			$where = $wpdb->prepare( ' WHERE group_ID = %d', $group_id );
		}

		$result = $wpdb->get_col( "SELECT `mobile` FROM {$wpdb->prefix}sms_subscribes" . $where );

		return $result;

	}


	/**
	 * @param $date
	 * @param $name
	 * @param $mobile
	 * @param $status
	 * @param $group_id
	 *
	 * @return mixed
	 */
	public static function insertSubscriber( $date, $name, $mobile, $status, $group_id ) {
		global $wpdb;

		$result = $wpdb->insert( "{$wpdb->prefix}sms_subscribes",
			array(
				'date'     => $date,
				'name'     => $name,
				'mobile'   => $mobile,
				'status'   => $status,
				'group_ID' => $group_id
			)
		);

		return $result;
	}

	/**
	 * @param $group_id
	 *
	 * @return object|null
	 */
	public static function getGroup( $group_id ) {
		global $wpdb;

		$db_prepare = $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}sms_subscribes_group` WHERE `ID` = %d", $group_id );
		$result     = $wpdb->get_row( $db_prepare );

		if ( $result ) {
			return $result;
		}

		return null;
	}

	/**
	 * Load NewsLetter form for Shortcode or Widget usage
	 *
	 * @param null $widget_id
	 * @param null $instance
	 */
	public static function loadNewsLetter( $widget_id = null, $instance = null ) {
		global $wpdb;
		$get_group_result = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}sms_subscribes_group`" );

		include_once WP_SMS_DIR . "includes/templates/subscribe-form.php";
	}

}

new Newsletter();