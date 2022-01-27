<?php
/*
Plugin Name: MU CAS Auth
Plugin URI: https://www.marshall.edu
Description: This plugin allows CAS authentication to WordPress administration
Version: 0.1
Author: Dustin Scarberry
Author URI: https://www.codeclouds.net
License: GPL2
*/

/*  2018 Dustin Scarberry

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

namespace Marshall\MUCasAuth;

use Marshall\MUCasAuth\Controller\LoginController;
use Marshall\MUCasAuth\Controller\AdminController;

require('config.php');

// override session name to prevent CAS plugin conflicts
session_name('mu-cas-auth-session');

class Bootstrap
{
	function __construct()
	{
		// register autoloader
 		spl_autoload_register([$this, 'autoloader']);

		// load external files
		$this->load_dependencies();

		// activation hook
		register_activation_hook(__FILE__, [$this, 'activate']);

		// logout redirect action
		add_action('wp_logout', [$this, 'logoutRedirect']);
	}

	// autoloader
	function autoloader($className)
	{
		if (strpos($className, 'Marshall\MUCasAuth') !== false) {
      $className = str_replace('\\', '/', $className);
      $className = str_replace('Marshall/MUCasAuth/', '', $className);
      include_once('src/' . $className . '.php');
    }
	}

	// activate plugin
	function activate($network)
	{
		// multisite call
		if (function_exists('is_multisite') && is_multisite() && $network) {
			global $wpdb;
      $old_blog =  $wpdb->blogid;

     	// get all blogs in multisite
     	$blogids =  $wpdb->get_col('SELECT blog_id FROM ' .  $wpdb->blogs);

     	foreach ($blogids as $blog_id) {
      	switch_to_blog($blog_id);
 				//$this->maintenance();
     	}

      switch_to_blog($old_blog);
   	}

		// regular call
		//$this->maintenance();
	}

	// load plugin dependencies
	private function load_dependencies()
	{
		if (is_admin())
			new AdminController();

		new LoginController();
	}

	// maintenance function for patch changes in the future
	private function maintenance()
	{
		// check for existing site options from simple ldap plugin
		$legacySettings = get_option('sll_settings');

		if (
			isset($legacySettings)
			&& isset($legacySettings['groups'])
			&& is_array($legacySettings['groups'])
		) {
			// get this sites cas auth settings
			$localSettings = get_option('mucasauth-settings', []);

			// sync settings from legacy ldap
			$localSettings['allowedADGroups'] = $legacySettings['groups'];
			update_option('mucasauth-settings', $localSettings, true);
		}
	}

	// logout redirect
	function logoutRedirect()
	{
		wp_redirect('https://www.marshall.edu');
		exit;
	}
}

new Bootstrap();
