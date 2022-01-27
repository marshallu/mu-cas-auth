<?php

namespace Marshall\MUCasAuth\Controller;

use Marshall\MUCasAuth\Service\Security\UserManagement;
use Marshall\MUCasAuth\Service\SSO\CasRunner;

class LoginController
{
  private $settings;

  function __construct()
  {
    // load settings
    $this->settings = get_option('mucasauth-settings');

    add_action('init', [$this, 'checkLogin']);
  }

  // check if on login page and if so login user via cas
  function checkLogin()
  {
    // if no further auth needed
    if (is_admin() && is_user_logged_in() && (is_user_member_of_blog(get_current_user_id(), get_current_blog_id()) || is_super_admin()))
      return;

    // check for wordpress login page or authenticated user without a blog role
    if (UserManagement::isWordpressAdminLogin()
      || is_admin() && is_user_logged_in() && !is_user_member_of_blog(get_current_user_id(), get_current_blog_id())
    ) {
      // remove existing authentication hook
  		remove_filter('authenticate', 'wp_authenticate_username_password', 20, 3);

      // log user in
      $this->loginUser();
    }
  }

  // login user
  private function loginUser()
  {
    // authenticate user
    $authData = CasRunner::authenticate(MUCASAUTH_CAS_HOST, MUCASAUTH_CAS_PATH);

    // check for existing user account
		$user = UserManagement::getUserByUsername($authData['user']);

    // check for group membership unless empty (ie no whitelist)
    $siteAccessAllowed = false;
    if (
      UserManagement::isUserWhitelistedForSite($this->settings['allowedADGroups'] ?? [], $authData['attributes'][MUCASAUTH_CAS_ATTRIBUTE_GROUPS] ?? [])
      || $user && is_super_admin($user->ID)
    )
      $siteAccessAllowed = true;

    // redirect invalid user to homepage
    if (!$siteAccessAllowed) {
  		wp_redirect(MUCASAUTH_LOGOUT_REDIRECT_URL);
      exit;
    }

		// if no existing user account
		if (!$user) {
			$user = UserManagement::createUser(
				$authData['user'],
				$authData['attributes'][MUCASAUTH_CAS_ATTRIBUTE_FIRSTNAME],
				$authData['attributes'][MUCASAUTH_CAS_ATTRIBUTE_LASTNAME],
				$authData['attributes'][MUCASAUTH_CAS_ATTRIBUTE_EMAIL],
				'administrator'
			);

			// check for valid created user
			if (!$user) {
        wp_redirect(MUCASAUTH_LOGOUT_REDIRECT_URL);
        exit;
      }
		}

    // add user to blog if needed
    if ($user && !is_user_member_of_blog($user->ID, get_current_blog_id()) && !is_super_admin($user->ID))
      add_user_to_blog(get_current_blog_id(), $user->ID, 'administrator');

    // login user if needed
    if (!is_user_logged_in()) {
      // log user in
  		UserManagement::loginUser($user);

  		// redirect to dashboard for site
  		$redirectTo = get_dashboard_url();
  		wp_safe_redirect($redirectTo);
  		exit;
    }
	}
}
