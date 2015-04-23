<?php
/*
Plugin Name: WooCommerce Pure EFT Gateway
Plugin URI: http://webunraveling.com
Description: Extends WooCommerce. Provides an Electronic Funds Transfer (EFT) gateway.
Version: 1.0
Author: Jason Raveling
Author URI: http://webunraveling.com/
License: GPL2

/*  Copyright 2015 Jason Raveling  (email : jason@webunraveling.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action('plugins_loaded', 'init_pure_eft_gateway', 0);

function init_pure_eft_gateway() {

	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

    /**
    * Localisation 
    * Not provided... if someone wants to, feel free :-)
    *
    * load_plugin_textdomain('lang-pure-eft', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
    */
    
    /**
    * Gateway class
    */
	
    // All the work happens here
    include('classes/eft-payments-api.php');
		
    /**
    * Add the Gateway to WooCommerce
    **/
    function add_pure_eft_gateway($methods) {
        $methods[] = 'WC_Gateway_Pure_EFT';
        return $methods;
    }
    
    add_filter('woocommerce_payment_gateways', 'add_pure_eft_gateway' );
}
