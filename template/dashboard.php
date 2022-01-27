<div class="wrap mucasauth-wrapper">
  <h2>CAS Authentication Settings</h2>
    <form method="post">
      <div class="form-field-row">
        <label>Allowed AD Groups</label>
        <input type="text" name="mucas_allowed_ad_groups" value="<?php echo implode(',', $this->settings['allowedADGroups']); ?>">
        <span class="field-hint">Whitelisted user groups comma seperated</span>
      </div>
      <div class="form-field-row">
        <input class="button-primary" type="submit" name="mucas_save_settings" value="Save Settings">
      </div>
      <?php wp_nonce_field('mucas_save_admin_settings'); ?>
    </form>
</div>
