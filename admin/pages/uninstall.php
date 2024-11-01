<?php

class GAWDUninstall{

	public function uninstall(){
    ?>
		<form method="post" action="" id="adminForm">
			<div class="gawd">
				<h2>
        <img src="<?php echo GAWD_URL . '/assets/uninstall-icon.png';?>" width="30" style="vertical-align:middle;">
                    <span><?php _e("Uninstall 10Web Analytics","gawd"); ?></span>
                </h2>
        <div class="goodbye-text">
          <?php
          $support_team = '<a href="https://help.10web.io/hc/en-us/requests/new" target="_blank">' . __('support team', 'gawd') . '</a>';
          $contact_us = '<a href="https://help.10web.io/hc/en-us/requests/new" target="_blank">' . __('Contact us', 'gawd') . '</a>';
          ?>
          Before uninstalling the plugin, please contact <?php echo $support_team; ?>. We'll do our best to assist you with your request.<br/>
          However, if you have made a decision to uninstall Google Analytics plugin, we would be happy to hear your feedback and comments on further improvement of the product.
        </div>
				<p style="color: red;">
				  <strong><?php _e("WARNING:","gawd"); ?></strong>
                    Uninstalling Google Analytics will completely remove all its data. This includes deleting tracking code and resetting all options to their defaults.<br/>
                    Please make sure you don’t have any important information before you proceed.
				</p>
				<p style="color: red">
					<strong><?php _e("The following database options will be deleted:","gawd"); ?></strong>
				</p>
				<table class="widefat">
					<thead>
						<tr>
							<th><?php _e("Database options","gawd"); ?></th>
						</tr>
					</thead>
					<tr>
						<td valign="top">
							<ol>
							  <li>gawd_menu_for_user</li>
							  <li>gawd_all_metrics</li>
							  <li>gawd_all_dimensions</li>
							  <li>gawd_custom_dimensions</li>
							  <li>gawd_settings</li>
							  <li>gawd_user_data</li>
							  <li>gawd_credentials</li>
							  <li>gawd_menu_items</li>
							  <li>gawd_export_chart_data</li>
							  <li>gawd_email</li>
							  <li>gawd_alerts</li>
							  <li>gawd_pushovers</li>
							  <li>gawd_menu_for_users</li>
								<li>gawd_own_project</li>
								<li>gawd_zoom_message</li>
							</ol>
						</td>
					</tr>

				</table>
				<p style="text-align: center;">	<?php _e("Are you sure you want to uninstall Google Analytics plugin?","gawd"); ?></p>
				<p style="text-align: center;">
					<input type="checkbox" name="unistall_gawd" id="check_yes" value="yes" />&nbsp;
					<label for="check_yes"><?php _e("Yes","gawd"); ?></label>
				</p>
				<p style="text-align: center;">
				<input type="button" id="gawd_uninstall" value="<?php _e("UNINSTALL","gawd"); ?>" onclick="if (check_yes.checked) {
																					if (confirm('You are About to Uninstall 10Web Analytics from WordPress.\nThis Action Is Not Reversible.')) {
																						jQuery('#adminForm').submit();;
																					} else {
																						return false;
																					}
																				  }
																				  else {
																					return false;
																				  }" class="wd-btn wd-btn-primary"  />
				</p>
			</div>
      <?php wp_nonce_field('gawd_save_form', 'gawd_save_form_field'); ?>
		</form>
<?php
  }

  public function delete_options(){
    global $wpdb;
    check_admin_referer('gawd_save_form', 'gawd_save_form_field');
    $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%gawd%'");

    $this->delete_upload_dir();

    set_site_transient('gawd_uninstall', '1', 5 * 60);
  }

  public function delete_upload_dir(){
    $upload_dir = wp_get_upload_dir();
    if(empty($upload_dir['basedir'])){
        return;
    }

    $upload_dir = $upload_dir['basedir'] . '/wd-google-analytics/';
    $this->delete_dir($upload_dir);
  }

  private function delete_dir($dir){

    if(empty($dir)) {
      return;
    }

    if(substr($dir, -1) !== '/') {
      $dir .= '/';
    }

    $dir_info = scandir($dir);

    foreach($dir_info as $item) {

      if($item == '.' || $item == '..') {
        continue;
      }

      $new_dir = $dir . $item;

      if(is_dir($new_dir)) {
        $this->delete_dir($new_dir);
      } else {
        unlink($new_dir);
      }

    }

    rmdir($dir);
  }

}