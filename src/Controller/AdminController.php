<?php

namespace Marshall\MUCasAuth\Controller;

class AdminController
{
  private $settings;

  function __construct()
  {
    $this->settings = get_option('mucasauth-settings',[]);

    // register hooks
    add_action('admin_init', [$this, 'saveAdminSettings']);
		add_action('admin_menu', [$this, 'adminMenu']);
    add_action('admin_enqueue_scripts', [$this, 'addCSS']);
  }

  // add admin menu
  function adminMenu()
  {
    add_options_page(
      'CAS Authentication',
      'CAS Authentication',
      'manage_options',
      "mu-cas-authentication",
      [$this, 'adminPage']
    );
  }

  // process admin page saving of settings
  function saveAdminSettings()
  {
    if (!empty($_POST) && isset($_POST['mucas_save_settings'])) {
      $allowedADGroups = sanitize_text_field($_POST['mucas_allowed_ad_groups']);

      // split value on commas into array
      if (strpos($allowedADGroups, ',') !== false)
        $this->settings['allowedADGroups'] = explode(',', $allowedADGroups);
      else
        $this->settings['allowedADGroups'] = [$allowedADGroups];

      update_option('mucasauth-settings', $this->settings, true);

      add_action('admin_notices', function() {
        echo '<div class="updated">
           <p>CAS settings saved.</p>
        </div>';
      });
    }
  }

  // admin page
  function adminPage()
  {
    require(plugin_dir_path(__FILE__) . '../../template/dashboard.php');
  }

  // load css
  function addCSS()
  {
    wp_enqueue_style(
      'mucasauth_dashboard_css',
      plugins_url('../../public/css/dashboard.min.css', __FILE__),
      false,
      '1.0'
    );
  }
}
