<?php

/**
Plugin Name: Gravity Forms CleverReach Add-On
Plugin URI: https://gravityforms.com
Description: Integrates Gravity Forms with CleverReach, allowing form submissions to be automatically sent to your CleverReach account.
Version: 1.7
Author: Gravity Forms
Author URI: https://gravityforms.com
License: GPL-2.0+
Text Domain: gravityformscleverreach
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2009-2020 Rocketgenius

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

defined( 'ABSPATH' ) or die();

define( 'GF_CLEVERREACH_VERSION', '1.7' );

// If Gravity Forms is loaded, bootstrap the CleverReach Add-On.
add_action( 'gform_loaded', array( 'GF_CleverReach_Bootstrap', 'load' ), 5 );

/**
 * Class GF_CleverReach_Bootstrap
 *
 * Handles the loading of the CleverReach Add-On and registers with the Add-On framework.
 */
class GF_CleverReach_Bootstrap {

	/**
	 * If the Add-On Framework exists, CleverReach Add-On is loaded.
	 *
	 * @access public
	 * @static
	 */
	public static function load(){

		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}

		// Get Add-On settings.
		$settings = get_option( 'gravityformsaddon_gravityformscleverreach_settings', array() );

		// Load legacy Add-On.
		if ( ! rgar( $settings, 'api_key' ) || ( rgget( 'subview' ) === 'gravityformscleverreach' && rgget( 'page' ) === 'gf_settings' ) ) {
			require_once( 'class-gf-cleverreach.php' );
		} else {
			require_once( 'includes/class-gf-cleverreach-legacy.php' );
		}

		GFAddOn::register( 'GFCleverReach' );

	}

}

/**
 * Returns an instance of the GFCleverReach class
 *
 * @see    GFCleverReach::get_instance()
 *
 * @return object GFCleverReach
 */
function gf_cleverreach() {
	return GFCleverReach::get_instance();
}
