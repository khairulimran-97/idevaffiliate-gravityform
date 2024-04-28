<?php
/*
Plugin Name: Gravity Forms AffiliateNiaga
Plugin URI: https://portal.affiliateniaga.com
Description: Addon to track lead from form to Portal AffiliateNiaga Account
Version: 1.0
Author: Web Impian Sdn Bhd
Author URI: http://webimpian.com

------------------------------------------------------------------------
Copyright 2012-2016 Web Impian Sdn Bhd.

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

// Check if the is_plugin_active function exists
if (!function_exists('is_plugin_active')) {
	include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

// Check if Gravity Forms is active
if (!is_plugin_active('gravityforms/gravityforms.php')) {
	add_action('admin_notices', 'gf_affiliateniaga_dependency_notice');
	return;
}

define( 'GF_AFFILIATENIAGA_VERSION', '1.0' );

add_action( 'gform_loaded', array( 'GF_Affiliate_Niaga', 'load' ), 5 );

class GF_Affiliate_Niaga {

	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gf-affiliateniaga.php' );

		GFAddOn::register( 'GFAffiliateNiaga' );
	}

}

function gf_affiliateniaga() {
	return GFAffiliateNiaga::get_instance();
}

function gf_affiliateniaga_dependency_notice() {
	$message = __('Gravity Forms AffiliateNiaga Addon requires Gravity Forms to be installed and activated. Please activate Gravity Forms to enable this addon.', 'gravityforms-affiliateniaga');
	echo '<div class="error"><p>' . $message . '</p></div>';
}
