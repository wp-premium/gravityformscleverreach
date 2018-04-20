<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

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
	 * Contains an instance of the CleverReach SoapClient.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    SoapClient $api If available, contains an instance of the CleverReach SoapClient.
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
	 * Defines the custom fields created upon saving a feed.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_new_custom_fields The custom fields created upon saving a feed.
	 */
	protected $_new_custom_fields = array();

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
	 * @uses GFFeedAddOn::add_delayed_payment_support()
	 */
	public function init() {

		parent::init();

		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Subscribe contact to CleverReach only when payment is received.', 'gravityformscleverreach' )
			)
		);

	}





	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Display warning message on plugin settings page when SOAP extension is not loaded.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::plugin_settings_icon()
	 * @uses GFAddOn::plugin_settings_page()
	 * @uses GFAddOn::plugin_settings_title()
	 */
	public function plugin_settings_page() {

		// If the Soap extension is loaded, display the normal settings page.
		if ( extension_loaded( 'soap' ) ) {
			return parent::plugin_settings_page();
		}

		// Get plugin settings icon.
		$icon = $this->plugin_settings_icon();

		// If no icon is defined, set it a default.
		if ( empty( $icon ) ) {
			$icon = '<i class="fa fa-cogs"></i>';
		}

		// Prepare message.
		echo sprintf(
			'<h3><span>%s %s</span></h3><p>%s</p><p>%s</p>',
			$icon,
			esc_html( $this->plugin_settings_title() ),
			sprintf(
				esc_html__( 'Gravity Forms CleverReach Add-On requires the %sPHP Soap extension%s to be able to communicate with CleverReach.' , 'gravityformscleverreach' ),
				'<a href="http://php.net/manual/en/book.soap.php">', '</a>'
			),
			esc_html__( 'To continue using this Add-On, please enable the Soap extension. For information on doing so, contact your hosting provider.', 'gravityformscleverreach' )
		);

	}

	/**
	 * Prepare settings to be rendered on plugin settings tab.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFCleverReach::plugin_settings_description()
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {

		return array(
			array(
				'description' => $this->plugin_settings_description(),
				'fields'      => array(
					array(
						'name'              => 'api_key',
						'label'             => esc_html__( 'API Key', 'gravityformscleverreach' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_api' )
					),
					array(
						'type'              => 'save',
						'messages'          => array(
							'success' => __( 'CleverReach settings have been updated.', 'gravityformscleverreach' )
						),
					),
				),
			),
		);

	}

	/**
	 * Prepare plugin settings description.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFCleverReach::initialize_api()
	 *
	 * @return string $description
	 */
	public function plugin_settings_description() {

		// Prepare base description.
		$description = sprintf(
			'<p>%s</p>',
			sprintf(
				esc_html__( 'CleverReach makes it easy to send email newsletters to your customers, manage your subscriber lists, and track campaign performance. Use Gravity Forms to collect customer information and automatically add it to your CleverReach group. If you don\'t have a CleverReach account, you can %1$ssign up for one here.%2$s', 'gravityformscleverreach' ),
				'<a href="http://www.cleverreach.com/" target="_blank">', '</a>'
			)
		);

		// If API is not initialized, add message to description about how to get API key.
		if ( ! $this->initialize_api() ) {

			$description .= sprintf(
				'<p>%s</p>',
				esc_html__( 'Gravity Forms CleverReach Add-On requires an API Key, with reading and writing authorization, which can be found on the API page under the Extras menu in your account settings.', 'gravityformscleverreach' )
			);

		}

		return $description;

	}





	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Prepare settings to be rendered on feed settings tab.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::add_field_after()
	 * @uses GFCleverReach::custom_fields_for_feed_setting()
	 * @uses GFCleverReach::forms_for_feed_setting()
	 * @uses GFCleverReach::groups_for_feed_setting()
	 *
	 * @return array
	 */
	public function feed_settings_fields() {

		// Prepare settings fields.
		$fields = array(
			array(
				'fields' => array(
					array(
						'name'       => 'feed_name',
						'label'      => esc_html__( 'Feed Name', 'gravityformscleverreach' ),
						'type'       => 'text',
						'required'   => true,
						'tooltip'    => sprintf(
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
						'choices'    => $this->groups_for_feed_setting(),
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
						'name'       => 'custom_fields',
						'label'      => esc_html__( 'Custom Fields', 'gravityformscleverreach' ),
						'type'       => 'dynamic_field_map',
						'dependency' => 'group',
						'field_map'  => $this->custom_fields_for_feed_setting(),
						'tooltip'    => sprintf(
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
		$forms = $this->forms_for_feed_setting();

		// If less than two forms were found, return.
		if ( count( $forms ) < 2 ) {
			return $fields;
		}

		// Prepare Double Opt-In field.
		$optin_field = array(
			'name'       => 'double_optin_form',
			'label'      => esc_html__( 'Double Opt-In Form', 'gravityformscleverreach' ),
			'type'       => 'select',
			'dependency' => 'group',
			'choices'    => $this->forms_for_feed_setting(),
			'tooltip'    => sprintf(
				'<h6>%s</h6>%s',
				esc_html__( 'Double Opt-In Form', 'gravityformscleverreach' ),
				esc_html__( 'Select which CleverReach form will be used when exporting to CleverReach to send the opt-in email.', 'gravityformscleverreach' )
			)
		);

		// Add Double Opt-In field.
		$fields = $this->add_field_after( 'custom_fields', $optin_field, $fields );

		return $fields;

	}

	/**
	 * Fork of maybe_save_feed_settings to create new CleverReach custom fields.
	 *
	 * @access public
	 * @param int $feed_id - The ID of the feed being edited
	 * @param int $form_id - The ID of the current form
	 * @return int $feed_id
	 */
	public function maybe_save_feed_settings( $feed_id, $form_id ) {

		// If feed was not saved, return.
		if ( ! rgpost( 'gform-settings-save' ) ) {
			return $feed_id;
		}

		// Store a copy of the previous settings for cases where action would only happen if value has changed.
		$feed = $this->get_feed( $feed_id );
		$this->set_previous_settings( $feed['meta'] );

		// Get posted sections.
		$settings = $this->get_posted_settings();

		// Create new custom fields.
		$settings = $this->create_new_custom_fields( $settings );
		$settings = $this->trim_conditional_logic_vales( $settings, $form_id );

		// Get feed settings.
		$sections = $this->get_feed_settings_fields();

		// Validate settings.
		$is_valid = $this->validate_settings( $sections, $settings );
		$result   = false;

		// If settings are valid, save.
		if ( $is_valid ) {

			// Save feed meta.
			$feed_id = $this->save_feed_settings( $feed_id, $form_id, $settings );

			if ( $feed_id ) {
				GFCommon::add_message( $this->get_save_success_message( $sections ) );
			} else {
				GFCommon::add_error_message( $this->get_save_error_message( $sections ) );
			}

		} else {

			GFCommon::add_error_message( $this->get_save_error_message( $sections ) );

		}

		return $feed_id;
	}

	/**
	 * Prepare CleverReach forms for feed settings field.
	 *
	 * @since  1.1
	 * @access public
	 *
	 * @uses GFAddOn::get_setting()
	 * @uses GFAddOn::log_error()
	 * @uses GFCleverReach::initialize_api()
	 *
	 * @return array
	 */
	public function forms_for_feed_setting() {

		// Initialize choices array.
		$choices = array(
			array(
				'label' => __( 'Choose a Double Opt-In Form', 'gravityformscleverreach' ),
				'value' => '',
			),
		);

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

		try {

			// Get available CleverReach forms.
			$forms = $this->api->formsGetList( $this->api_key, $group_id );

			// If no forms were found, return.
			if ( empty( $forms->data ) ) {
				return $choices;
			}

			// Loop through the forms.
			foreach ( $forms->data as $form ) {

				// Add form as choice.
				$choices[] = array(
					'label' => esc_html( $form->name ),
					'value' => esc_html( $form->id ),
				);

			}

		} catch ( Exception $e ) {

			// Log that we were unable to retrieve the forms.
			$this->log_error( __METHOD__ . '(): ' . $e->getMessage() );

		}

		return $choices;


	}

	/**
	 * Prepare CleverReach groups for feed settings field.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::log_error()
	 * @uses GFCleverReach::initialize_api()
	 *
	 * @return array
	 */
	public function groups_for_feed_setting() {

		// Initialize choices array.
		$choices = array(
			array(
				'label' => __( 'Choose a CleverReach Group', 'gravityformscleverreach' ),
				'value' => ''
			)
		);

		// If API isn't initialized, return.
		if ( ! $this->initialize_api() ) {
			return $choices;
		}

		try {

			// Get the CleverReach groups.
			$groups = $this->api->groupGetList( $this->api_key );

			// If request failed or no groups were found, return.
			if ( $groups->statuscode == 1 || ( $groups->statuscode == 0 && empty( $groups->data ) ) ) {
				return $groups;
			}

			// Loop through the groups.
			foreach ( $groups->data as $group ) {

				// Add group as choice.
				$choices[] = array(
					'label' => esc_html( $group->name ),
					'value' => esc_attr( $group->id )
				);

			}

		} catch ( Exception $e ) {

			// Log that we were unable to retrieve the groups.
			$this->log_error( __METHOD__ . '(): Unable to retrieve groups; ' . $e->getMessage() );

		}

		return $choices;

	}

	/**
	 * Prepare CleverReach custom fields for feed settings field.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array - An array of CleverReach custom fields formatted for a select settings field
	 */
	public function custom_fields_for_feed_setting() {

		// Get current group ID.
		$group_id = $this->get_setting( 'group' );

		// If API is not initialized or no group is selected, return. */
		if ( ! $this->initialize_api() || rgblank( $group_id ) ) {
			return array();
		}

		try {

			// Get the current group.
			$group = $this->api->groupGetDetails( $this->api_key, $group_id );

			// If request failed, return.
			if ( in_array( $group->statuscode, array( 1, 20 ) ) ) {

				// Log that group could not be retrieved.
				$this->log_error( __METHOD__ . '(): Unable to retrieve group.' );

				return array();

			}

		} catch ( Exception $e ) {

			// Log that group could not be retrieved.
			$this->log_error( __METHOD__ . '(): Unable to retrieve group; ' . $e->getMessage() );

			return array();

		}

		// Initialize choices array.
		$choices = array(
			array(
				'label' => esc_html__( 'Select a Custom Field', 'gravityformscleverreach' ),
				'value' => '',
			),
		);

		// Merge global and group attributes.
		$attributes = array_merge( $group->data->attributes, $group->data->globalAttributes );

		// Add attributes as choices.
		if ( ! empty( $attributes ) ) {

			// Loop through attributes.
			foreach ( $attributes as $attribute ) {

				// Add attribute as choice.
				$choices[] = array(
					'label' => esc_html( $attribute->key ),
					'value' => esc_attr( $attribute->key ),
				);

			}

		}

		// Add new custom fields.
		if ( ! empty( $this->_new_custom_fields ) ) {

			// Loop through new custom fields.
			foreach ( $this->_new_custom_fields as $new_field ) {

				// Loop through existing choices.
				foreach ( $choices as $choice ) {

					// If new field is a choice, break.
					if ( $choice['value'] == $new_field ) {
						continue 2;
					}

				}

				// Add new custom field as choice.
				$choices[] = array(
					'label' => esc_html( $new_field ),
					'value' => esc_attr( $new_field ),
				);

			}

		}

		// Add "Add Custom Field" option as choice.
		if ( count( $choices ) > 0 ) {
			$choices[] = array(
				'label' => esc_html__( 'Add Custom Field', 'gravityformscleverreach' ),
				'value' => 'gf_custom'
			);
		}

		return $choices;

	}

	/**
	 * Create new CleverReach custom fields when feed settings are saved.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $settings The posted Feed settings.
	 *
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 * @uses GFCleverReach::initialize_api()
	 *
	 * @return array
	 */
	public function create_new_custom_fields( $settings ) {

		global $_gaddon_posted_settings;

		// If no custom fields are set or if the API credentials are invalid, return.
		if ( empty( $settings['custom_fields'] ) || ! $this->initialize_api() ) {
			return $settings;
		}

		// Loop through custom fields.
		foreach ( $settings['custom_fields'] as $index => &$field ) {

			// If custom key is not set, skip.
			if ( rgblank( $field['custom_key'] ) ) {
				continue;
			}

			try {

				// Add new field.
				$new_field = $this->api->groupAttributeAdd( $this->api_key, 0, $field['custom_key'], 'text' );

			} catch ( Exception $e ) {

				// Log that we were unable to create the custom field.
				$this->log_error( __METHOD__ . '(): Unable to create custom field; ' . $e->getMessage() );

				continue;

			}

			// Replace key for field with new shortcut name and reset custom key.
			if ( $new_field->statuscode == 0 ) {

				// Set custom field key.
				$field['key']        = $new_field->data;
				$field['custom_key'] = '';

				// Update POST field to ensure front-end display is up-to-date.
				$_gaddon_posted_settings['custom_fields'][ $index ]['key']        = $new_field->data;
				$_gaddon_posted_settings['custom_fields'][ $index ]['custom_key'] = '';

				// Push to new custom fields array to update the UI.
				$this->_new_custom_fields[] = $new_field->data;

				// Log that field was created.
				$this->log_debug( __METHOD__ . "(): New field '{$new_field->data}' created." );

			} else {

				// Log that we were unable to create the custom field.
				$this->log_error( __METHOD__ . '(): Unable to create custom field.' );

				continue;

			}

		}

		return $settings;

	}

	/**
	 * Set feed creation control.
	 *
	 * @since  1.0
	 * @access public
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
	 * @uses GFCleverReach::initialize_api()
	 *
	 * @return string
	 */
	public function get_column_value_group( $feed ) {

		// If CleverReach instance is not initialized, return group ID.
		if ( ! $this->initialize_api() ) {
			return esc_html( $feed['meta']['group'] );
		}

		// Get group object.
		$group = $this->api->groupGetDetails( $this->api_key, $feed['meta']['group'] );

		return $group->statuscode == 0 ? esc_html( $group->data->name ) : esc_html( $feed['meta']['group'] );

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
	 * @uses GFAddOn::get_field_value()
	 * @uses GFCleverReach::double_optin_contact()
	 * @uses GFCleverReach::initialize_api()
	 * @uses GFCommon::is_invalid_or_empty_email()
	 * @uses GFFeedAddOn::add_feed_error()
	 */
	public function process_feed( $feed, $entry, $form ) {

		// If API instance is not initialized, exit.
		if ( ! $this->initialize_api() ) {
			$this->add_feed_error( esc_html__( 'Unable to process feed because API was not initialized.', 'gravityformscleverreach' ), $feed, $entry, $form );
			return;
		}

		// Prepare contact object.
		$contact = array(
			'email'      => $this->get_field_value( $form, $entry, $feed['meta']['email'] ),
			'attributes' => array(),
			'source'     => esc_html__( 'Gravity Forms CleverReach Add-On', 'gravityformscleverreach' ),
		);

		// If email is invalid, exit.
		if ( GFCommon::is_invalid_or_empty_email( $contact['email'] ) ) {
			$this->add_feed_error( esc_html__( 'Unable to process feed because an invalid email address was provided.', 'gravityformscleverreach' ), $feed, $entry, $form );
			return;
		}

		// Add custom fields to contact.
		if ( ! empty( $feed['meta']['custom_fields'] ) ) {

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
				$contact['attributes'][] = array(
					'key'   => $field['key'],
					'value' => $field_value
				);

			}

		}

		try {

			// Determine if contact already exists.
			$contact_exists = $this->api->receiverGetByEmail( $this->api_key, $feed['meta']['group'], $contact['email'] );

		} catch ( Exception $e ) {

			// Log that we could not determine if contact exists.
			$this->add_feed_error( esc_html__( 'Unable to determine if contact exists.', 'gravityformscleverreach' ), $feed, $entry, $form );

			return;

		}

		// If contact exists, update. Otherwise, create.
		if ( $contact_exists->statuscode == 0 ) {

			try {

				// Update contact.
				$update_contact = $this->api->receiverUpdate( $this->api_key, $feed['meta']['group'], $contact );

				// If contact could not be updated, exit.
				if ( $update_contact->statuscode != 0 ) {

					// Log that we could not update contact.
					$this->add_feed_error( "{$contact['email']} has not been updated; {$update_contact->message}", $feed, $entry, $form );

					return;

				}

				// Log that contact was updated.
				$this->log_debug( __METHOD__ . "(): {$contact['email']} has been updated." );

			} catch ( Exception $e ) {

				// Log that we could not update contact.
				$this->add_feed_error( $contact['email'] . ' has not been updated; ' . $e->getMessage(), $feed, $entry, $form );

				return;

			}

		} else {

			// Add registration time to contact object.
			$contact['registered'] = time();

			// Add activation time to contact object.
			if ( ! rgars( $feed, 'meta/double_optin_form' ) ) {
				$contact['activated'] = time();
			}


			try {

				// Add contact.
				$add_contact = $this->api->receiverAdd( $this->api_key, $feed['meta']['group'], $contact );

				// If contact could not be added, exit.
				if ( $update_contact->statuscode != 0 ) {

					// Log that we could not create contact.
					$this->add_feed_error( "{$contact['email']} has not been created; {$update_contact->message}", $feed, $entry, $form );

					return;

				}

				// Log that contact was created.
				$this->log_debug( __METHOD__ . "(): {$contact['email']} has been created." );

			} catch ( Exception $e ) {

				// Log that we could not create contact.
				$this->add_feed_error( $contact['email'] . ' has not been created; ' . $e->getMessage(), $feed, $entry, $form );

				return;

			}

		}

		// Send Double Opt-In email.
		if ( rgars( $feed, 'meta/double_optin_form' ) ) {
			$this->double_optin_contact( $contact, $feed, $entry, $form );
		}

	}

	/**
	 * Send Double Opt-In email to contact.
	 *
	 * @since 1.3.3
	 * @access public
	 *
	 * @param mixed $contact The Contact object.
	 * @param array $feed    The current Feed object.
	 * @param array $entry   The current Entry object.
	 * @param array $form  The current Form object.
	 *
	 * @uses GFAddOn::log_debug()
	 * @uses GFFeedAddOn::add_feed_error()
	 */
	public function double_optin_contact( $contact, $feed, $entry, $form ) {

		// Initialize POST data array.
		$post_data = array( 'email:' . $contact['email'] );
		
		// Add custom fields to POST data.
		if ( ! empty( $contact['attributes'] ) ) {
			
			// Loop through custom fields.
			foreach ( $contact['attributes'] as $attribute ) {
				
				// Add custom field to POST data.
				$post_data[] = $attribute['key'] . ':' . $attribute['value'];
				
			}
			
		}
		
		// Squash POST data array.
		$post_data = implode( ',', $post_data );

		// Prepare request arguments.
		$args = array(
			'user_ip'    => $entry['ip'],
			'user_agent' => $entry['user_agent'],
			'referer'    => $entry['source_url'],
			'postdata'   => $post_data
		);

		try {

			// Send Double Opt-In email.
			$double_optin = $this->api->formsSendActivationMail( $this->api_key, $feed['meta']['double_optin_form'], $contact['email'], $args );

			// Log response.
			if ( $double_optin->statuscode == 0 ) {
				$this->log_debug( __METHOD__ . "(): {$contact['email']} was sent a double opt-in email." );
			} else {
				$this->add_feed_error( $contact['email'] . ' was not sent a double opt-in email; ' . $double_optin->message, $feed, $entry, $form );
			}

		} catch ( Exception $e ) {
			
			// Log that Double Opt-In email could not be sent.
			$this->add_feed_error( $contact['email'] . ' was not sent a double opt-in email; ' . $e->getMessage(), $feed, $entry, $form );
			
		}

	}




	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Initializes CleverReach API if credentials are valid.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::get_plugin_settings()
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 *
	 * @return bool|null
	 */
	public function initialize_api() {

		// If the Soap client is not available, return.
		if ( ! extension_loaded( 'soap' ) ) {
			return false;
		}

		// If the API is already initialized, return.
		if ( ! is_null( $this->api ) ) {
			return true;
		}

		// Get the plugin settings.
		$settings = $this->get_plugin_settings();

		// If the API key is not set, return.
		if ( rgblank( $settings['api_key'] ) ) {
			return null;
		}

		// Log validation step.
		$this->log_debug( __METHOD__ . "(): Validating API info." );

		// Setup a new CleverReach API object.
		$cleverreach = new SoapClient( $this->api_url );

		try {

			// Run a test request.
			$api_test = $cleverreach->clientGetDetails( $settings['api_key'] );

			// If status code is successful, assign API object to instance.
			if ( $api_test->statuscode == 0 ) {

				// Assign API object to instance.
				$this->api = $cleverreach;

				// Assign API Key to instance.
				$this->api_key = $settings['api_key'];

				// Log that authentication test passed.
				$this->log_debug( __METHOD__ . '(): API credentials are valid.' );

				return true;

			} else {

				// Log that authentication test failed.
				$this->log_error( __METHOD__ . '(): API credentials are invalid; ' . $api_test->message );

				return false;

			}

		} catch ( Exception $e ) {

			// Log that authentication test failed.
			$this->log_error( __METHOD__ . '(): API credentials are invalid; ' . $e->getMessage() );

			return false;

		}

	}

}
