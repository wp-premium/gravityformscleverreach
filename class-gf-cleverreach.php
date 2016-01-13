<?php
	
GFForms::include_feed_addon_framework();

class GFCleverReach extends GFFeedAddOn {
	
	protected $_version = GF_CLEVERREACH_VERSION;
	protected $_min_gravityforms_version = '1.9.8';
	protected $_slug = 'gravityformscleverreach';
	protected $_path = 'gravityformscleverreach/cleverreach.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Gravity Forms CleverReach Add-On';
	protected $_short_title = 'CleverReach';

	// Members plugin integration
	protected $_capabilities = array( 'gravityforms_cleverreach', 'gravityforms_cleverreach_uninstall' );

	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_cleverreach';
	protected $_capabilities_form_settings = 'gravityforms_cleverreach';
	protected $_capabilities_uninstall = 'gravityforms_cleverreach_uninstall';
	protected $_enable_rg_autoupgrade = true;

	protected $api = null;
	protected $api_key = null;
	protected $api_url = 'http://api.cleverreach.com/soap/interface_v5.1.php?wsdl';
	protected $_new_custom_fields = array();
	private static $_instance = null;

	public static function get_instance() {
		
		if ( self::$_instance == null )
			self::$_instance = new GFCleverReach();

		return self::$_instance;
		
	}

	/* Setup plugin settings page */
	public function plugin_settings_page() {
		
		if ( extension_loaded( 'soap' ) )
			return parent::plugin_settings_page();
		
		$icon = $this->plugin_settings_icon();
		if ( empty( $icon ) )
			$icon = '<i class="fa fa-cogs"></i>';

		echo '<h3><span>'. $icon .' '. $this->plugin_settings_title() .'</span></h3>';
		echo '<p>' . __( 'Gravity Forms CleverReach Add-On requires the PHP Soap extension to be able to communicate with CleverReach.', 'gravityformscleverreach' ) . '</p>';
		echo '<p>' . __( 'To continue using this Add-On, please enable the Soap extension.', 'gravityformscleverreach' ) . '</p>';
		
	}

	/* Setup plugin settings fields */
	public function plugin_settings_fields() {
		
		return array(
			array(
				'title'       => '',
				'description' => $this->plugin_settings_description(),
				'fields'      => array(
					array(
						'name'              => 'api_key',
						'label'             => __( 'API Key', 'gravityformscleverreach' ),
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

	/* Prepare plugin settings description */
	public function plugin_settings_description() {
		
		$description  = '<p>';
		$description .= sprintf(
			__( 'CleverReach makes it easy to send email newsletters to your customers, manage your subscriber lists, and track campaign performance. Use Gravity Forms to collect customer information and automatically add them to your CleverReach group. If you don\'t have a CleverReach account, you can %1$s sign up for one here.%2$s', 'gravityformscleverreach' ),
			'<a href="http://www.cleverreach.com/" target="_blank">', '</a>'
		);
		$description .= '</p>';
		
		if ( ! $this->initialize_api() ) {
			
			$description .= '<p>';
			$description .= __( 'Gravity Forms CleverReach Add-On requires an API Key, with reading and writing authorization, which can be found on the API page under the Extras menu in your account settings.', 'gravityformscleverreach' );
			$description .= '</p>';
			
		}
				
		return $description;
		
	}
	
	/* Setup feed settings fields */
	public function feed_settings_fields() {
		
		$fields = array(
			array(	
				'title'  => '',
				'fields' => array(
					array(
						'name'           => 'feed_name',
						'label'          => __( 'Feed Name', 'gravityformscleverreach' ),
						'type'           => 'text',
						'required'       => true,
						'tooltip'        => '<h6>'. __( 'Name', 'gravityformscleverreach' ) .'</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'gravityformscleverreach' )
					),
					array(
						'name'           => 'group',
						'label'          => __( 'CleverReach Group', 'gravityformscleverreach' ),
						'type'           => 'select',
						'required'       => true,
						'choices'        => $this->groups_for_feed_setting(),
						'onchange'       => "jQuery(this).parents('form').submit();",
						'tooltip'        => '<h6>'. __( 'CleverReach Group', 'gravityformscleverreach' ) .'</h6>' . __( 'Select which CleverReach group this feed will add contacts to.', 'gravityformscleverreach' )
					),
					array(
						'name'           => 'email',
						'label'          => __( 'Email Field', 'gravityformscleverreach' ),
						'type'           => 'field_select',
						'required'       => true,
						'dependency'     => 'group',
						'tooltip'        => '<h6>'. __( 'Email Field', 'gravityformscleverreach' ) .'</h6>' . __( 'Select which Gravity Form field will be used as the subscriber email.', 'gravityformscleverreach' ),
						'args'           => array(
							'input_types'   => array( 'email' )
						)
					),
					array(
						'name'           => 'custom_fields',
						'label'          => __( 'Custom Fields', 'gravityformscleverreach' ),
						'type'           => 'dynamic_field_map',
						'dependency'     => 'group',
						'field_map'      => $this->custom_fields_for_feed_setting(),
						'tooltip'        => '<h6>'. __( 'Custom Fields', 'gravityformscleverreach' ) .'</h6>' . __( 'Select or create a new CleverReach custom field to pair with Gravity Forms fields.', 'gravityformscleverreach' )
					)
				)
			)
		);
		
		/* Add double opt-in form field if forms exist. */
		$forms = $this->forms_for_feed_setting();
		
		if ( count( $forms ) > 1 ) {
			
			$fields[0]['fields'][] = array(
				'name'           => 'double_optin_form',
				'label'          => __( 'Double Opt-In Form', 'gravityformscleverreach' ),
				'type'           => 'select',
				'dependency'     => 'group',
				'choices'        => $this->forms_for_feed_setting(),
				'tooltip'        => '<h6>'. __( 'Double Opt-In Form', 'gravityformscleverreach' ) .'</h6>' . __( 'Select which CleverReach form will be used when exporting to CleverReach to send the opt-in email.', 'gravityformscleverreach' )
			);
			
		}

			
		$fields[0]['fields'][] = array(
			'name'           => 'feed_condition',
			'label'          => __( 'Opt-In Condition', 'gravityformscleverreach' ),
			'type'           => 'feed_condition',
			'dependency'     => 'group',
			'checkbox_label' => __( 'Enable', 'gravityformscleverreach' ),
			'instructions'   => __( 'Export to CleverReach if', 'gravityformscleverreach' ),
			'tooltip'        => '<h6>'. __( 'Opt-In Condition', 'gravityformscleverreach' ) .'</h6>' . __( 'When the opt-in condition is enabled, form submissions will only be exported to CleverReach when the condition is met. When disabled, all form submissions will be exported.', 'gravityformscleverreach' )
		);
		
		return $fields;
		
	}

	/* Fork of maybe_save_feed_settings to create new CleverReach custom fields */
	public function maybe_save_feed_settings( $feed_id, $form_id ) {

		if ( ! rgpost( 'gform-settings-save' ) ) {
			return $feed_id;
		}

		// store a copy of the previous settings for cases where action would only happen if value has changed
		$feed = $this->get_feed( $feed_id );
		$this->set_previous_settings( $feed['meta'] );

		$settings = $this->get_posted_settings();
		$settings = $this->create_new_custom_fields( $settings );
		$sections = $this->get_feed_settings_fields();
		$settings = $this->trim_conditional_logic_vales( $settings, $form_id );

		$is_valid = $this->validate_settings( $sections, $settings );
		$result   = false;

		if ( $is_valid ) {
			$feed_id = $this->save_feed_settings( $feed_id, $form_id, $settings );
			if ( $feed_id ){
				GFCommon::add_message( $this->get_save_success_message( $sections ) );
			}
			else{
				GFCommon::add_error_message( $this->get_save_error_message( $sections ) );
			}
		}
		else{
			GFCommon::add_error_message( $this->get_save_error_message( $sections ) );
		}

		return $feed_id;
	}

	/* Prepare groups for feed setting */
	public function groups_for_feed_setting() {
		
		$groups = array(
			array(
				'label' => __( 'Choose a CleverReach Group', 'gravityformscleverreach' ),
				'value' => ''	
			)
		);
		
		/* If API isn't initialized, return the groups array. */
		if ( ! $this->initialize_api() )
			return $groups;
			
		/* Get the CleverReach groups. */
		$cr_groups = $this->api->groupGetList( $this->api_key );
		
		/* If request failed or request succeed but there are no groups, return the groups array. */
		if ( $cr_groups->statuscode == 1 || ( $cr_groups->statuscode == 0 && empty( $cr_groups->data ) ) )
			return $groups;
		
		foreach ( $cr_groups->data as $group ) {
			
			$groups[] = array(
				'label' => $group->name,
				'value' => $group->id	
			);
			
		}

		return $groups;
		
	}

	/* Prepare custom fields for feed setting */
	public function custom_fields_for_feed_setting() {
		
		/* Setup choices array. */
		$choices = array();
		
		/* If API isn't initialized, return the choices array. */
		if ( ! $this->initialize_api() )
			return $choices;		
		
		/* Get current group ID */
		$feed = $this->get_current_feed();
		$group_id = $feed ? $feed['meta']['group'] : rgpost( '_gaddon_setting_group' );

		/* Get the current group */
		$group = $this->api->groupGetDetails( $this->api_key, $group_id );
		
		/* If request failed, return the choices array. */
		if ( $group->statuscode == 1 )
			return $choices;
		
		/* Get the global and group attributes */
		$attributes = array_merge( $group->data->attributes, $group->data->globalAttributes );

		/* Push the attributes to the choices array. */
		if ( ! empty( $attributes ) ) {
			
			foreach ( $attributes as $attribute ) {
				
				$choices[] = array(
					'label' => $attribute->key,
					'value' => $attribute->key	
				);
				
			}
			
		}

		/* Add any newly created custom fields to the choices array. */
		if ( ! empty( $this->_new_custom_fields ) ) {
			
			foreach ( $this->_new_custom_fields as $new_field ) {
				
				$found_custom_field = false;
				foreach ( $choices as $choice ) {
					
					if ( $choice['value'] == $new_field )
						$found_custom_field = true;
					
				}
				
				if ( ! $found_custom_field )
					$choices[] = array(
						'label' => $new_field,
						'value' => $new_field	
					);
				
			}
			
		}

		/* Add "Add Custom Field" to array. */
		if ( count( $choices ) > 0 )
			$choices[] = array(
				'label' => __( 'Add Custom Field', 'gravityformscleverreach' ),
				'value' => 'gf_custom'	
			);		

		return $choices;
		
	}

	/* Create new CleverReach custom fields */
	public function create_new_custom_fields( $settings ) {

		global $_gaddon_posted_settings;

		/* If no custom fields are set or if the API credentials are invalid, return settings. */
		if ( empty( $settings['custom_fields'] ) || ! $this->initialize_api() )
			return $settings;
	
		/* Loop through each custom field. */
		foreach ( $settings['custom_fields'] as $index => &$field ) {
			
			/* If no custom key is set, move on. */
			if ( rgblank( $field['custom_key'] ) )
				continue;
				
			/* Add new field. */
			$new_field = $this->api->groupAttributeAdd( $this->api_key, 0, $field['custom_key'], 'text' );
			
			/* Replace key for field with new shortcut name and reset custom key. */
			if ( $new_field->statuscode == 0 ) {
							
				$field['key'] = $new_field->data;
				$field['custom_key'] = '';
				
				/* Update POST field to ensure front-end display is up-to-date. */
				$_gaddon_posted_settings['custom_fields'][ $index ]['key'] = $new_field->data;
				$_gaddon_posted_settings['custom_fields'][ $index ]['custom_key'] = '';
				
				/* Push to new custom fields array to update the UI. */			
				$this->_new_custom_fields[] = $new_field->data;

				$this->log_debug( __METHOD__ . "(): New field '{$new_field->data}' created." );
				
			}
			
		}
				
		return $settings;
		
	}

	/* Prepare CleverReach forms for feed field */
	public function forms_for_feed_setting() {
		
		$forms = array(
			array(
				'label' => __( 'Choose a Double Opt-In Form', 'gravityformscleverreach' ),
				'value' => ''
			)
		);

		/* If CleverReach API credentials are invalid, return the forms array. */
		if ( ! $this->initialize_api() )
			return $forms;
			
		/* Get list ID. */
		$current_feed = $this->get_current_feed();
		$group_id = rgpost( '_gaddon_setting_group' ) ? rgpost( '_gaddon_setting_group' ) : $current_feed['meta']['group'] ;
		
		/* Get available CleverReach forms. */
		$cr_forms = $this->api->formsGetList( $this->api_key, $group_id );
		
		/* Add CleverReach forms to array and return it. */
		if ( ! empty( $cr_forms->data ) ) {
			
			foreach ( $cr_forms->data as $form ) {
				
				$forms[] = array(
					'label' => $form->name,
					'value' => $form->id
				);
				
			}
			
		}
		
		return $forms;

		
	}

	/* Setup feed list columns */
	public function feed_list_columns() {
		
		return array(
			'feed_name' => __( 'Name', 'gravityformscleverreach' ),
			'group'     => __( 'CleverReach Group', 'gravityformscleverreach' )
		);
		
	}
	
	/* Change value of group feed column to group name */
	public function get_column_value_group( $feed ) {
			
		/* If CleverReach instance is not initialized, return group ID. */
		if ( ! $this->initialize_api() )
			return $feed['meta']['group'];
		
		/* Get group and return name */
		$group = $this->api->groupGetDetails( $this->api_key, $feed['meta']['group'] );
		return ( $group->statuscode == 0 ) ? $group->data->name : $feed['meta']['group'];
		
	}

	/* Hide "Add New" feed button if API credentials are invalid */		
	public function feed_list_title() {
		
		if ( $this->initialize_api() )
			return parent::feed_list_title();
			
		return sprintf( __( '%s Feeds', 'gravityforms' ), $this->get_short_title() );
		
	}

	/* Notify user to configure add-on before setting up feeds */
	public function feed_list_message() {

		$message = parent::feed_list_message();
		
		if ( $message !== false )
			return $message;

		if ( ! $this->initialize_api() )
			return $this->configure_addon_message();

		return false;
		
	}
	
	/* Feed list message for user to configure add-on */
	public function configure_addon_message() {
		
		$settings_label = sprintf( __( '%s Settings', 'gravityforms' ), $this->get_short_title() );
		$settings_link  = sprintf( '<a href="%s">%s</a>', esc_url( $this->get_plugin_settings_url() ), $settings_label );

		return sprintf( __( 'To get started, please configure your %s.', 'gravityformscleverreach' ), $settings_link );
		
	}

	/* Process feed */
	public function process_feed( $feed, $entry, $form ) {
		
		$this->log_debug( __METHOD__ . '(): Processing feed.' );
		
		/* If API instance is not initialized, exit. */
		if ( ! $this->initialize_api() ) {
			
			$this->log_error( __METHOD__ . '(): Failed to set up the API.' );
			return;
			
		}
		
		/* Setup contact array. */
		$contact = array(
			'email'      => $this->get_field_value( $form, $entry, $feed['meta']['email'] ),
			'attributes' => array(),
			'source'     => __( 'Gravity Forms CleverReach Add-On', 'gravityformscleverreach' )
		);
		
		/* Add the custom fields to the array. */
		if ( ! empty( $feed['meta']['custom_fields'] ) ) {
			
			foreach ( $feed['meta']['custom_fields'] as $field ) {
				
				if ( rgblank( $field['value'] ) || $field['key'] == 'gf_custom' )
					continue;
					
				$field_value = $this->get_field_value( $form, $entry, $field['value'] );
				
				if ( rgblank( $field_value ) )
					continue;
				
				$contact['attributes'][] = array(
					'key'   => $field['key'],
					'value' => $field_value
				);
				
			}
			
		}
		
		/* If the email address is empty, exit. */
		if ( rgblank( $contact['email'] ) ) {
			
			$this->log_error( __METHOD__ . '(): Email address not provided.' );
			return;			
		
		}
		
		/* Check if contact already exists. */
		$contact_exists = $this->api->receiverGetByEmail( $this->api_key, $feed['meta']['group'], $contact['email'] );
		
		/* If contact exists, update. Otherwise, create. */
		if ( $contact_exists->statuscode == 0 ) {
			
			/* Update the contact. */
			$update_contact = $this->api->receiverUpdate( $this->api_key, $feed['meta']['group'], $contact );
			
			/* Log success or failure based on response. */
			if ( $update_contact->statuscode == 0 ) {
				
				$this->log_debug( __METHOD__ . "(): {$contact['email']} has been updated." );
				return true;			

			} else {
				
				$this->log_error( __METHOD__ . "(): {$contact['email']} has not been updated; {$update_contact->message}" );
				return false;							
				
			}
			
		} else {
			
			/* Add additional needed information. */
			$contact['registered'] = time();
			if ( ! $feed['meta']['double_optin_form'] )
				$contact['activated'] = time();
			
			/* Add the contact. */
			$add_contact = $this->api->receiverAdd( $this->api_key, $feed['meta']['group'], $contact );
			
			/* Log success or failure based on response. */
			if ( $add_contact->statuscode == 0 ) {
				
				$this->log_debug( __METHOD__ . "(): {$contact['email']} has been created." );

			} else {
				
				$this->log_error( __METHOD__ . "(): {$contact['email']} has not been created; {$add_contact->message}" );
				return false;							
				
			}
			
			/* Send Double Opt-In email if set. */
			if ( $feed['meta']['double_optin_form'] ) {
				
				/* Prepare post data for Double Opt-In. */
				$postdata = 'email:' . $contact['email'] . ',';
				
				if ( ! empty( $contact['attributes'] ) ) {
					
					foreach ( $contact['attributes'] as $attribute ) {
						
						$postdata .= $attribute['key'] . ':' . $attribute['value'] . ',';
						
					} 
					
				}
				
				/* Prepare data for Double Opt-In. */
				$double_optin_data = array(
					'user_ip'    => $entry['ip'],
					'user_agent' => $entry['user_agent'],
					'referer'    => $entry['source_url'],
					'postdata'   => $postdata
				);
				
				/* Send Double Opt-In email. */
				$double_optin = $this->api->formsSendActivationMail( $this->api_key, $feed['meta']['double_optin_form'], $contact['email'], $double_optin_data );
				
				/* Log success or failure based on response. */
				if ( $double_optin->statuscode == 0 ) {
					
					$this->log_debug( __METHOD__ . "(): {$contact['email']} was sent a double opt-in email." );
					return true;
		
				} else {
					
					$this->log_error( __METHOD__ . "(): {$contact['email']} was not sent a double opt-in email; {$double_optin->message}" );
					return false;							
					
				}
				
			}
			
		}

	}

	/* Checks validity of CleverReach API credentials and initializes API if valid. */
	public function initialize_api() {

		if ( ! extension_loaded( 'soap' ) )
			return false;

		if ( ! is_null( $this->api ) )
			return true;
		
		/* Get the plugin settings */
		$settings = $this->get_plugin_settings();
		
		/* If the API Key is empty, return null. */
		if ( rgblank( $settings['api_key'] ) )
			return null;
			
		$this->log_debug( __METHOD__ . "(): Validating API info for {$settings['api_key']}." );
		
		/* Setup a new CleverReach API object. */
		$cleverreach = new SoapClient( $this->api_url );
		
		/* Run a test request. */
		$api_test = $cleverreach->clientGetDetails( $settings['api_key'] );
		
		if ( $api_test->statuscode == 0 ) {
			
			/* Assign API object to class. */
			$this->api = $cleverreach;
			
			/* Assign API Key to class. */
			$this->api_key = $settings['api_key'];
			
			/* Log that test passed. */
			$this->log_debug( __METHOD__ . '(): API credentials are valid.' );
						
			return true;
			
		} else {
			
			/* Log that test failed. */
			$this->log_error( __METHOD__ . '(): API credentials are invalid; '. $api_test->message );			

			return false;
			
		}
		
	}


}