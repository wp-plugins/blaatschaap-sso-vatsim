<?php
/*
Plugin Name: BlaatSchaap SSO: VATSIM
Plugin URI: http://code.blaatschaap.be
Description: BlaatSchaap SSO support for VATSIM, a modified OAuth protocol.
Version: 0.4.0
Author: André van Schoubroeck
Author URI: http://andre.blaatschaap.be
License: BSD
*/

//------------------------------------------------------------------------------
// Required files
//------------------------------------------------------------------------------

require_once("blaat.php");  // Moved to Separate Plugin
require_once("bsauth.php"); // Moved to Separate Plugin

require_once("bs_vatsimsso_config.php");
require_once("vatsimsso/OAuth.php");
require_once("vatsimsso/SSO.class.php");
require_once("classes/AuthService.class.php");
require_once("classes/VatsimSSO.class.php");
//------------------------------------------------------------------------------
// Starting PHP Session and Output Buffering
//------------------------------------------------------------------------------
session_start();
ob_start();
//------------------------------------------------------------------------------
load_plugin_textdomain('blaat_auth', false, basename( dirname( __FILE__ ) ) . '/languages' );
//------------------------------------------------------------------------------
function bsvatsimsso_init(){
  $vatsimsso = new VatsimSSO();
  global $BSAUTH_SERVICES;
  if (!isset($BSAUTH_SERVICES)) $BSAUTH_SERVICES = array();
  $BSAUTH_SERVICES["blaat_vatsimsso"]=$vatsimsso;
}
//------------------------------------------------------------------------------

wp_register_style("bsauth_btn" , plugin_dir_url(__FILE__) . "css/bs-auth-btn.css");
wp_enqueue_style( "bsauth_btn");

wp_register_style("blaat_auth" , plugin_dir_url(__FILE__) . "blaat_auth.css");
wp_enqueue_style( "blaat_auth");

add_action("admin_menu",  bs_vatsimsso_menu);
register_activation_hook(__FILE__, 'VatsimSSO::install');
add_filter( 'the_content', 'bsauth_display' );
bsvatsimsso_init();



?>
