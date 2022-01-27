<?php

namespace Marshall\MUCasAuth\Service\Security;

class UserManagement
{
  // create new wordpress user account
  static function createUser(
    string $username,
    string $firstName,
    string $lastName,
    string $email,
    string $role = 'administrator'
  )
  {
    $userData = [
      'user_pass' => md5(microtime()),
      'user_login' => $username,
      'user_nicename' => $username,
      'user_email' => $email,
      'display_name' => $firstName . ' ' . $lastName,
      'first_name' => $firstName,
      'last_name' => $lastName,
      'role' => $role
    ];

    $user = wp_insert_user($userData);

    if (is_wp_error($user))
      return false;

    return new \WP_User($user);
  }

  // get local user by username
  static function getUserByUsername(string $username)
  {
    return get_user_by('login', $username);
  }

  // admin log user in
  static function loginUser($user)
  {
    wp_clear_auth_cookie();
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID);
  }

  // is user groups included in whitelisted groups
  static function isUserWhitelistedForSite(array $whitelistedGroups = [], array $userGroups)
  {
    // convert groups to test against to lowercase and trim
    $userGroups = array_map('strtolower', $userGroups);
    $userGroups = array_map('trim', $userGroups);

    foreach ($whitelistedGroups as $whitelistedGroup) {
      if (in_array(strtolower(trim($whitelistedGroup)), $userGroups))
        return true;
    }

    return false;
  }

  // is on login page
  static function isWordpressAdminLogin()
	{
		$currentUrl = trim($_SERVER['REQUEST_URI'], '/');

		return (strpos($currentUrl, 'wp-login.php') !== false
			&& strpos($currentUrl, 'action=logout') === false
      && strpos($currentUrl, 'action=postpass') === false);
	}
}
