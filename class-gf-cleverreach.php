<?php

defined( 'ABSPATH' ) or die();

// Load Feed Add-On Framework.
GFForms::include_feed_addon_framework();

/**
 * CleverReach integration using the Add-On Framework.
 *
 * @see GFFeedAddOn
 */
class GFCleverReach extends GFFeedAddOn {

	/**
	 * Defines the version of the CleverReach Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_version Contains the version, defined in cleverreach.php
	 */
	protected $_version = GF_CLEVERREACH_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '1.9.14.26';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformscleverreach';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformscleverreach/cleverreach.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string
	 */
	protected $_url = 'http://www.gravityforms.com';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_title The title of the Add-On.
	 */
	protected $_title = 'Gravity Forms CleverReach Add-On';

	/**
	 * Defines the short title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_title The short title of the Add-On.
	 */
	protected $_short_title = 'CleverReach';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 * @var    object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_cleverreach';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_cleverreach';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_cleverreach_uninstall';

	/**
	 * Defines the capabilities to add to roles by the Members plugin.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities Capabilities to add to roles by the Members plugin.
	 */
	protected $_capabilities = array( 'gravityforms_cleverreach', 'gravityforms_cleverreach_uninstall' );

	/**
	 * Contains an instance of the CleverReach API library.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    GF_CleverReach_API $api If available, contains an instance of the CleverReach API library.
	 */
	protected $api = null;

	/**
	 * Defines the CleverReach API key.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $api_key The CleverReach API key.
	 */
	protected $api_key = null;

	/**
	 * Defines the base path to the CleverReach API.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $api_url The base path to the CleverReach API.
	 */
	protected $api_url = 'http://api.cleverreach.com/soap/interface_v5.1.php?wsdl';

	/**
	 * Get instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 * @static
	 *
	 * @return GFCleverReach
	 */
	public static function get_instance() {

		if ( self::$_instance == null ) {
			self::$_instance = new self;
		}

		return self::$_instance;

	}

	/**
	 * Register needed plugin hooks and PayPal delayed payment support.
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @uses   GFFeedAddOn::add_delayed_payment_support()
	 */
	public function init() {

		parent::init();

		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Subscribe contact to CleverReach only when payment is received.', 'gravityformscleverreach' ),
			)
		);

	}

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @since 1.7
	 *
	 * @return string
	 */
	public function get_menu_icon() {

		return file_get_contents( $this->get_base_path() . '/images/menu-icon.svg' );

	}





	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Prepare settings to be rendered on plugin settings tab.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses   GFCleverReach::initialize_api()
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {

		return array(
			array(
				'description' => sprintf(
					'<p>%s</p>',
					sprintf(
						esc_html__( 'CleverReach makes it easy to send email newsletters to your customers, manage your subscriber lists, and track campaign performance. Use Gravity Forms to collect customer information and automatically add it to your CleverReach group. If you don\'t have a CleverReach account, you can %1$ssign up for one here.%2$s', 'gravityformscleverreach' ),
						'<a href="http://www.cleverreach.com/" target="_blank">', '</a>'
					)
				),
				'fields'      => array(
					array(
						'name'          => 'apiToken',
						'type'          => 'hidden',
						'save_callback' => array( $this, 'save_api_token' ),
					),
					array(
						'name'              => 'clientId',
						'label'             => esc_html__( 'Customer ID', 'gravityformscleverreach' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'api_feedback_callback' ),
					),
					array(
						'name'              => 'username',
						'label'             => esc_html__( 'Username', 'gravityformscleverreach' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'api_feedback_callback' ),
					),
					array(
						'name'              => 'password',
						'label'             => esc_html__( 'Password', 'gravityformscleverreach' ),
						'type'              => 'text',
						'input_type'        => 'password',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'api_feedback_callback' ),
					),
					array(
						'type'     => 'save',
						'messages' => array(
							'success' => esc_html__( 'CleverReach settings have been updated.', 'gravityformscleverreach' ),
						),
					),
				),
			),
		);

	}

	/**
	 * Get API token upon saving plugin settings.
	 *
	 * @since  1.4
	 * @access public
	 *
	 * @param array  $field       Field properties.
	 * @param string $field_value Field value.
	 *
	 * @return bool|string
	 */
	public function save_api_token( $field = array(), $field_value = '' ) {

		// Get previous and posted settings.
		$previous = $this->get_previous_settings();
		$settings = $this->get_posted_settings();

		// If settings did not change, do not update API token.
		if ( rgar( $previous, 'clientId' ) === rgar( $settings, 'clientId' ) && rgar( $previous, 'username' ) === rgar( $settings, 'username' ) && rgar( $previous, 'password' ) === rgar( $settings, 'password' ) ) {
			return $field_value;
		}

		// Load API library.
		if ( ! class_exists( 'GF_CleverReach_API' ) ) {
			require_once 'includes/class-gf-cleverreach-api.php';
		}

		// Get token.
		$token = GF_CleverReach_API::authenticate( $settings['clientId'], $settings['username'], $settings['password'] );

		// Reset API object.
		$this->api = null;

		if ( is_wp_error( $token ) ) {
			$this->log_error( __METHOD__ . '(): API credentials are invalid; ' . $token->get_error_message() );

			return 'failed';
		}

		return $token;

	}

	/**
	 * Provide validation state for API settings.
	 *
	 * @since  1.4
	 * @access public
	 *
	 * @uses   GFAddOn::get_plugin_setting()
	 * @uses   GFCleverReach::initialize_api()
	 *
	 * @return bool|null
	 */
	public function api_feedback_callback() {

		// Get API token.
		$api_token = $this->get_plugin_setting( 'apiToken' );

		if ( 'failed' === $api_token ) {
			return false;
		} else if ( rgblank( $api_token ) ) {
			return null;
		} else {
			return $this->initialize_api();
		}

	}





	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Prepare settings to be rendered on feed settings tab.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses   GFAddOn::add_field_after()
	 * @uses   GFCleverReach::get_custom_fields_field_map()
	 * @uses   GFCleverReach::get_forms_for_feed_setting()
	 * @uses   GFCleverReach::get_groups_for_feed_setting()
	 *
	 * @return array
	 */
	public function feed_settings_fields() {

		$groups = $this->get_groups_for_feed_setting();

		// Prepare settings fields.
		$fields = array(
			array(
				'fields' => array(
					array(
						'name'          => 'feed_name',
						'label'         => esc_html__( 'Feed Name', 'gravityformscleverreach' ),
						'type'          => 'text',
						'class'         => 'medium',
						'required'      => true,
						'default_value' => $this->get_default_feed_name(),
						'tooltip'       => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Name', 'gravityformscleverreach' ),
							esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformscleverreach' )
						),
					),
					array(
						'name'       => 'group',
						'label'      => esc_html__( 'CleverReach Group', 'gravityformscleverreach' ),
						'type'       => 'select',
						'required'   => true,
						'choices'    => $groups,
						'no_choices' => empty( $groups ) ? esc_html__( 'Unable to retrieve Groups from CleverReach.', 'gravityformscleverreach' ) : '',
						'onchange'   => "jQuery(this).parents('form').submit();",
						'tooltip'    => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'CleverReach Group', 'gravityformscleverreach' ),
							esc_html__( 'Select which CleverReach group this feed will add contacts to.', 'gravityformscleverreach' )
						),
					),
					array(
						'name'       => 'email',
						'label'      => esc_html__( 'Email Field', 'gravityformscleverreach' ),
						'type'       => 'field_select',
						'required'   => true,
						'dependency' => 'group',
						'args'       => array( 'input_types' => array( 'email' ) ),
						'tooltip'    => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Email Field', 'gravityformscleverreach' ),
							esc_html__( 'Select which Gravity Form field will be used as the subscriber email.', 'gravityformscleverreach' )
						),
					),
					array(
						'name'          => 'custom_fields',
						'label'         => esc_html__( 'Custom Fields', 'gravityformscleverreach' ),
						'type'          => 'dynamic_field_map',
						'dependency'    => 'group',
						'field_map'     => $this->get_custom_fields_field_map(),
						'save_callback' => array( $this, 'create_new_custom_fields' ),
						'tooltip'       => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Custom Fields', 'gravityformscleverreach' ),
							esc_html__( 'Select or create a new CleverReach custom field to pair with Gravity Forms fields.', 'gravityformscleverreach' )
						),
					),
					array(
						'name'           => 'feed_condition',
						'label'          => esc_html__( 'Opt-In Condition', 'gravityformscleverreach' ),
						'type'           => 'feed_condition',
						'dependency'     => 'group',
						'checkbox_label' => esc_html__( 'Enable', 'gravityformscleverreach' ),
						'instructions'   => esc_html__( 'Export to CleverReach if', 'gravityformscleverreach' ),
						'tooltip'        => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Opt-In Condition', 'gravityformscleverreach' ),
							esc_html__( 'When the opt-in condition is enabled, form submissions will only be exported to CleverReach when the condition is met. When disabled, all form submissions will be exported.', 'gravityformscleverreach' )
						),
					),
				),
			),
		);

		// Get available Double Opt-In forms.
		$forms = $this->get_forms_for_feed_setting();

		if ( empty( $forms ) ) {
			return $fields;
		}

		// Prepare Double Opt-In field.
		$optin_field = array(
			'name'       => 'double_optin_form',
			'label'      => esc_html__( 'Double Opt-In Form', 'gravityformscleverreach' ),
			'type'       => 'select',
			'dependency' => 'group',
			'choices'    => $forms,
			'tooltip'    => sprintf(
				'<h6>%s</h6>%s',
				esc_html__( 'Double Opt-In Form', 'gravityformscleverreach' ),
				esc_html__( 'Select which CleverReach form will be used when exporting to CleverReach to send the opt-in email.', 'gravityformscleverreach' )
			),
		);

		// Add Double Opt-In field.
		$fields = $this->add_field_after( 'custom_fields', $optin_field, $fields );

		return $fields;

	}

	/**
	 * Prepare CleverReach forms for feed settings field.
	 *
	 * @since  1.1
	 * @access public
	 *
	 * @uses   GFAddOn::get_setting()
	 * @uses   GFAddOn::log_error()
	 * @uses   GFCleverReach::initialize_api()
	 *
	 * @return array
	 */
	public function get_forms_for_feed_setting() {

		// Initialize choices array.
		$choices = array();

		// If API isn't initialized, return.
		if ( ! $this->initialize_api() ) {
			return $choices;
		}

		// Get the current group ID.
		$group_id = $this->get_setting( 'group' );

		// If group ID is empty, return.
		if ( rgblank( $group_id ) ) {
			return $choices;
		}

		// Get available CleverReach forms.
		$forms = $this->api->get_group_forms( $group_id );

		if ( is_wp_error( $forms ) ) {
			$this->log_error( __METHOD__ . '(): Unable to retrieve forms for group; ' . $forms->get_error_message() );

			return $choices;
		}

		// If no forms were found, return.
		if ( empty( $forms ) || ! is_array( $forms ) ) {
			return $choices;
		}

		$choices[] = array(
			'label' => __( 'Choose a Double Opt-In Form', 'gravityformscleverreach' ),
			'value' => '',
		);

		// Loop through the forms.
		foreach ( $forms as $form ) {

			// Add form as choice.
			$choices[] = array(
				'label' => esc_html( $form['name'] ),
				'value' => esc_html( $form['id'] ),
			);

		}

		return $choices;

	}

	/**
	 * Prepare CleverReach groups for feed settings field.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses   GFAddOn::log_error()
	 * @uses   GFCleverReach::initialize_api()
	 *
	 * @return array
	 */
	public function get_groups_for_feed_setting() {

		// Initialize choices array.
		$choices = array();

		// If API isn't initialized, return.
		if ( ! $this->initialize_api() ) {
			return $choices;
		}

		// Get the CleverReach groups.
		$groups = $this->api->get_groups();

		if ( is_wp_error( $groups ) ) {
			$this->log_error( __METHOD__ . '(): Unable to retrieve groups; ' . $groups->get_error_message() );

			return $choices;
		}

		if ( empty( $groups ) || ! is_array( $groups ) ) {
			return $choices;
		}

		$choices[] = array(
			'label' => __( 'Choose a CleverReach Group', 'gravityformscleverreach' ),
			'value' => '',
		);

		// Loop through groups.
		foreach ( $groups as $group ) {

			// Add group as choice.
			$choices[] = array(
				'label' => esc_html( $group['name'] ),
				'value' => esc_attr( $group['id'] ),
			);

		}

		return $choices;

	}

	/**
	 * Prepare CleverReach custom fields for field map feed settings field.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses   Exception::getMessage()
	 * @uses   GFAddOn::get_setting()
	 * @uses   GFAddOn::log_error()
	 * @uses   GFCleverReach::initialize_api()
	 * @uses   GF_CleverReach_API::get_attributes()
	 *
	 * @return array
	 */
	public function get_custom_fields_field_map() {

		// Get current group ID.
		$group_id = $this->get_setting( 'group' );

		// If API is not initialized or no group is selected, return. */
		if ( ! $this->initialize_api() || rgblank( $group_id ) ) {
			return array();
		}

		// Get global attributes.
		$global_attributes = $this->api->get_attributes();

		if ( is_wp_error( $global_attributes ) ) {
			$this->log_error( __METHOD__ . '(): Unable to retrieve global attributes; ' . $global_attributes->get_error_message() );

			return array();
		}

		// Get group attributes.
		$group_attributes = $this->api->get_attributes( $group_id );

		if ( is_wp_error( $group_attributes ) ) {
			$this->log_error( __METHOD__ . '(): Unable to retrieve group attributes; ' . $group_attributes->get_error_message() );

			return array();
		}

		// Merge global and group attributes.
		$attributes = array_merge( ( is_array( $global_attributes ) ? $global_attributes : array() ), ( is_array( $group_attributes ) ? $group_attributes : array() ) );

		// If no attributes were found, return.
		if ( empty( $attributes ) ) {
			return array();
		}

		// Initialize choices array.
		$choices = array(
			array(
				'label' => esc_html__( 'Select a Custom Field', 'gravityformscleverreach' ),
				'value' => '',
			),
		);

		// Loop through attributes.
		foreach ( $attributes as $attribute ) {

			// Add attribute as choice.
			$choices[] = array(
				'label' => esc_html( $attribute['description'] ),
				'value' => esc_attr( $attribute['name'] ),
			);

		}

		// Add "Add Custom Field" option as choice.
		$choices[] = array(
			'label' => esc_html__( 'Add Custom Field', 'gravityformscleverreach' ),
			'value' => 'gf_custom',
		);

		return $choices;

	}

	/**
	 * Create new CleverReach custom fields when feed settings are saved.
	 *
	 * @since  1.7
	 * @access public
	 *
	 * @param array|\Rocketgenius\Gravity_Forms\Settings\Fields\Base $field       Field object.
	 * @param array                                                  $field_value Posted field value.
	 *
	 * @return array
	 */
	public function create_new_custom_fields( $field = array(), $field_value = array() ) {

		global $_gaddon_posted_settings;

		// If no custom fields are set or if the API credentials are invalid, return.
		if ( empty( $field_value ) || ! $this->initialize_api() ) {
			return $field_value;
		}

		// Get group ID.
		$group_id = $this->get_setting( 'group' );

		// Loop through custom fields.
		foreach ( $field_value as $index => &$field ) {

			// If custom key is not set, skip.
			if ( rgblank( $field['custom_key'] ) ) {
				continue;
			}

			// Add new field.
			$new_field = $this->api->create_attribute( $field['custom_key'], 'text', $group_id );

			if ( is_wp_error( $new_field ) ) {
				$this->log_error( __METHOD__ . '(): Unable to create custom field; ' . $new_field->get_error_message() );
				continue;
			}

			// Set custom field key.
			$field['key']        = $new_field['name'];
			$field['custom_key'] = '';

			// Update POST field to ensure front-end display is up-to-date.
			$_gaddon_posted_settings['custom_fields'][ $index ]['key']        = $new_field['name'];
			$_gaddon_posted_settings['custom_fields'][ $index ]['custom_key'] = '';

			// Log that field was created.
			$this->log_debug( __METHOD__ . "(): New field '{$new_field['name']}' created." );

		}

		return $field_value;

	}

	/**
	 * Renders and initializes a dynamic field map field based on the $field array whose choices are populated by the fields to be mapped.
	 * (Forked to force reload of field map options.)
	 *
	 * @since  1.5
	 * @access public
	 *
	 * @param array $field Field array containing the configuration options of this field.
	 * @param bool  $echo  Determines if field contents should automatically be displayed. Defaults to true.
	 *
	 * @uses   GFAddOn::is_postback()
	 * @uses   GFCleverReach::get_custom_fields_field_map()
	 *
	 * @return string
	 */
	public function settings_dynamic_field_map( $field, $echo = true ) {

		// Refresh field map.
		if ( 'custom_fields' === $field['name'] && $this->is_postback() ) {
			$field['field_map'] = $this->get_custom_fields_field_map();
		}

		return parent::settings_dynamic_field_map( $field, $echo );

	}

	/**
	 * Set feed creation control.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses   GFCleverReach::initialize_api()
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		return $this->initialize_api();

	}

	/**
	 * Enable feed duplication.
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @param int|array $id The ID of the feed to be duplicated or the feed object when duplicating a form.
	 *
	 * @return bool
	 */
	public function can_duplicate_feed( $id ) {

		return true;

	}





	// # FEED LIST -----------------------------------------------------------------------------------------------------

	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function feed_list_columns() {

		return array(
			'feed_name' => esc_html__( 'Name', 'gravityformscleverreach' ),
			'group'     => esc_html__( 'CleverReach Group', 'gravityformscleverreach' ),
		);

	}

	/**
	 * Returns the value to be displayed in the group name column.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $feed The current Feed object.
	 *
	 * @uses   GFAddOn::log_error()
	 * @uses   GFCleverReach::initialize_api()
	 * @uses   GF_CleverReach_API::get_group()
	 *
	 * @return string
	 */
	public function get_column_value_group( $feed ) {

		// If CleverReach instance is not initialized, return group ID.
		if ( ! $this->initialize_api() ) {
			return esc_html( $feed['meta']['group'] );
		}

		// Get group.
		$group = $this->api->get_group( rgars( $feed, 'meta/group' ) );

		if ( is_wp_error( $group ) ) {
			$this->log_error( __METHOD__ . '(): Unable to retrieve group for feed; ' . $group->get_error_message() );

			return esc_html__( 'Group not found', 'gravityformscleverreach' );
		}

		return esc_html( rgar( $group, 'name' ) );

	}





	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Processes the feed, subscribe the user to the list.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $feed  The current Feed object.
	 * @param array $entry The current Entry object.
	 * @param array $form  The current Form object.
	 *
	 * @uses   GFAddOn::get_field_value()
	 * @uses   GFAddOn::log_debug()
	 * @uses   GFCleverReach::double_optin_contact()
	 * @uses   GFCleverReach::initialize_api()
	 * @uses   GF_CleverReach_API::get_attributes()
	 * @uses   GF_CleverReach_API::get_group_receiver()
	 * @uses   GF_CleverReach_API::send_form()
	 * @uses   GF_CleverReach_API::upsert_group_receiver()
	 * @uses   GFCommon::is_invalid_or_empty_email()
	 * @uses   GFFeedAddOn::add_feed_error()
	 *
	 * @return array
	 */
	public function process_feed( $feed, $entry, $form ) {

		// If API instance is not initialized, exit.
		if ( ! $this->initialize_api() ) {
			$this->add_feed_error( esc_html__( 'Unable to process feed because API was not initialized.', 'gravityformscleverreach' ), $feed, $entry, $form );
			return $entry;
		}

		// Initialize contact object.
		$contact = array(
			'email'             => $this->get_field_value( $form, $entry, $feed['meta']['email'] ),
			'source'            => esc_html__( 'Gravity Forms CleverReach Add-On', 'gravityformscleverreach' ),
			'attributes'        => array(),
			'global_attributes' => array(),
		);

		// If email is invalid, exit.
		if ( GFCommon::is_invalid_or_empty_email( $contact['email'] ) ) {
			$this->add_feed_error( esc_html__( 'Unable to process feed because an invalid email address was provided.', 'gravityformscleverreach' ), $feed, $entry, $form );
			return $entry;
		}

		// Get global attributes.
		$global_attributes = $this->api->get_attributes();

		if ( is_wp_error( $global_attributes ) ) {
			// Log that group could not be retrieved.
			$this->add_feed_error( 'Unable to retrieve global attributes, ignoring custom fields; ' . $global_attributes->get_error_message(), $feed, $entry, $form );
			$global_attributes = array();
		} else {
			// Extract global attribute names.
			$global_attributes = is_array( $global_attributes ) ? wp_list_pluck( $global_attributes, 'name' ) : array();
		}

		// Add custom fields to contact.
		if ( ! empty( $feed['meta']['custom_fields'] ) && ! empty( $global_attributes ) ) {

			// Loop through custom fields.
			foreach ( $feed['meta']['custom_fields'] as $field ) {

				// If no field is mapped, skip.
				if ( rgblank( $field['value'] ) || $field['key'] == 'gf_custom' ) {
					continue;
				}

				// Get field value.
				$field_value = $this->get_field_value( $form, $entry, $field['value'] );

				// If field value is empty, skip.
				if ( rgblank( $field_value ) ) {
					continue;
				}

				// Add custom field to contact object.
				if ( in_array( $field['key'], $global_attributes ) ) {
					$contact['global_attributes'][ $field['key'] ] = $field_value;
				} else {
					$contact['attributes'][ $field['key'] ] = $field_value;
				}

			}

		}

		// Get existing contact.
		$existing_contact = $this->api->get_group_receiver( $feed['meta']['group'], $contact['email'] );

		if ( is_wp_error( $existing_contact ) && $existing_contact->get_error_code() !== 404 ) {
			$this->add_feed_error( esc_html__( 'Unable to determine if contact exists.', 'gravityformscleverreach' ), $feed, $entry, $form );

			return $entry;
		}


		// If contact exists, merge data.
		if ( ! is_wp_error( $existing_contact ) && is_array( $existing_contact ) ) {

			// Merge contact data.
			$contact = array_merge( $existing_contact, $contact );

			// Set activation time.
			if ( rgars( $feed, 'meta/double_optin_form' ) ) {
				$contact['activated'] = 0;
			}

		} else {

			// Set registered time.
			$contact['registered'] = time();

			// Set activation time.
			$contact['activated'] = rgars( $feed, 'meta/double_optin_form' ) ? 0 : time();

		}

		$this->log_debug( __METHOD__ . '(): Contact:' . print_r( $contact, true ) );

		// Upserting contact.
		$new_contact = $this->api->upsert_group_receiver( rgars( $feed, 'meta/group' ), $contact );

		if ( is_wp_error( $new_contact ) ) {
			$this->add_feed_error( sprintf( esc_html__( 'Unable to add or update contact; %s (%d)', 'gravityformscleverreach' ), $new_contact->get_error_message(), $new_contact->get_error_code() ), $feed, $entry, $form );

			return $entry;
		}

		$this->log_debug( __METHOD__ . '(): Contact added/updated; ' . print_r( $new_contact, true ) );

		// If we are not sending a double opt-in email, return.
		if ( ! rgars( $feed, 'meta/double_optin_form' ) ) {
			return $entry;
		}

		// Prepare double opt-in data.
		$optin_data = array(
			'user_ip'    => $entry['ip'],
			'user_agent' => $entry['user_agent'],
			'referer'    => $entry['source_url'],
		);

		// When personal data settings are set to not save the submitter IP, pass a descriptive dummy value as CleverReach won't accept an empty string.
		if ( rgars( $form, 'personalData/preventIP' ) == true ) {
			$optin_data['user_ip'] = 'IP not saved due to personal data configuration.';
			$this->log_debug( __METHOD__ . '(): IP not sent with double opt-in data due to personal data configuration.' );
		}

		// Send double opt-in email.
		$opted_in = $this->api->send_form( rgars( $feed, 'meta/double_optin_form' ), 'activate', $new_contact['email'], $optin_data );

		if ( is_wp_error( $opted_in ) ) {
			$this->add_feed_error( $contact['email'] . ' was not sent a double opt-in email; ' . $opted_in->get_error_message(), $feed, $entry, $form );
		}

		$this->log_debug( __METHOD__ . '(): Double opt-in email sent; ' . print_r( $opted_in, true ) );

		return $entry;

	}




	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Initializes CleverReach API if credentials are valid.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses   GFAddOn::get_plugin_setting()
	 * @uses   GFAddOn::log_debug()
	 * @uses   GFAddOn::log_error()
	 *
	 * @return bool|null
	 */
	public function initialize_api() {

		// If the API is already initialized, return.
		if ( ! is_null( $this->api ) ) {
			return true;
		}

		// Get the API token.
		$api_token = $this->get_plugin_setting( 'apiToken' );

		// If API token isempty, return.
		if ( ! $api_token ) {
			return null;
		}

		// Load API library.
		if ( ! class_exists( 'GF_CleverReach_API' ) ) {
			require_once 'includes/class-gf-cleverreach-api.php';
		}

		// Log validation step.
		$this->log_debug( __METHOD__ . "(): Validating API info." );

		// Initialize new API object.
		$api = new GF_CleverReach_API( $api_token );

		// Run authentication test.
		$result = $api->get_groups();

		if ( is_wp_error( $result ) ) {
			$this->log_error( __METHOD__ . '(): API credentials are invalid; ' . $result->get_error_message() );

			return false;
		}

		// Assign API object to instance.
		$this->api = $api;

		return true;

	}

}
