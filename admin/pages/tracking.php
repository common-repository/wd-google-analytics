<?php

if (GAWD_helper::gawd_has_property() === false) {
  require_once 'add_web_property.php';
  return;
}
$gawd_user_data = GAWD_helper::get_user_data();

$tracking_dimensions = array();
if (isset($gawd_user_data['property_id'])) {
  $tracking_dimensions = GAWD_helper::get_custom_dimensions();
}

$supported_dimensions = GAWD_helper::get_supported_dimensions();
$ua_code = isset($gawd_user_data['property_id']) ? $gawd_user_data['property_id'] : '';

//$gawd_permissions = isset($gawd_settings['gawd_permissions']) ? $gawd_settings['gawd_permissions'] : array();
//$gawd_excluded_users = isset($gawd_settings['gawd_excluded_users']) ? $gawd_settings['gawd_excluded_users'] : array();
//$gawd_excluded_roles = isset($gawd_settings['gawd_excluded_roles']) ? $gawd_settings['gawd_excluded_roles'] : array();
//$gawd_backend_roles = isset($gawd_settings['gawd_backend_roles']) ? $gawd_settings['gawd_backend_roles'] : array();
//$gawd_frontend_roles = isset($gawd_settings['gawd_frontend_roles']) ? $gawd_settings['gawd_frontend_roles'] : array();
$gawd_anonymize = isset($gawd_settings['gawd_anonymize']) ? $gawd_settings['gawd_anonymize'] : '';
$gawd_tracking_enable = isset($gawd_settings['gawd_tracking_enable']) ? $gawd_settings['gawd_tracking_enable'] : 'on';
$gawd_outbound = isset($gawd_settings['gawd_outbound']) ? $gawd_settings['gawd_outbound'] : '';

$gawd_enhanced = isset($gawd_settings['gawd_enhanced']) ? $gawd_settings['gawd_enhanced'] : '';

$enable_custom_code = isset($gawd_settings['enable_custom_code']) ? $gawd_settings['enable_custom_code'] : '';
$gawd_custom_code = isset($gawd_settings['gawd_custom_code']) ? $gawd_settings['gawd_custom_code'] : '';

//$gawd_file_formats = isset($gawd_settings['gawd_file_formats']) ? $gawd_settings['gawd_file_formats'] : '';
$gawd_tracking_enable = isset($_GET['enableTracking']) ? 'on' : $gawd_tracking_enable;
$domain = GAWD::get_domain(esc_html(get_option('siteurl')));
?>


<div class="gawd_tracking">
  <div class="gawd_settings_wrapper">
    <div class="settings_row">
      <div class="onoffswitch">
        <input type="checkbox" name="gawd_tracking_enable" class="onoffswitch-checkbox"
               id="gawd_tracking_enable" <?php echo $gawd_tracking_enable != '' ? 'checked' : ''; ?>>
        <label class="onoffswitch-label" for="gawd_tracking_enable">
          <span class="onoffswitch-inner"></span>
          <span class="onoffswitch-switch"></span>
        </label>
      </div>
      <div class="gawd_info"
           title="Enable this option to add Google Analytics tracking code into <head> tag of your website HTML."></div>
      <div class="onoffswitch_text">
        Enable Tracking
      </div>
      <div class="clear"></div>
    </div>
    <div class="settings_row independent_setting">
      <div
        class="onoffswitch <?php echo(($gawd_tracking_enable == '') ? 'onoffswitch_disabled' : ''); ?> independent_switch">
        <input type="checkbox" name="gawd_anonymize" class="onoffswitch-checkbox independent_input"
               id="gawd_anonymize" <?php echo $gawd_anonymize != '' ? 'checked' : ''; ?> <?php echo(($gawd_tracking_enable == '') ? 'disabled' : ''); ?>>
        <label class="onoffswitch-label" for="gawd_anonymize">
          <span class="onoffswitch-inner"></span>
          <span class="onoffswitch-switch"></span>
        </label>
      </div>
      <div class="gawd_info"
           title="Turn this option on, in case you’d like to hide the last block of users’ IP addresses."></div>
      <div class="onoffswitch_text">
        Anonymize IP address
          <span style="color:#dd0000;"> If disabled, you must inform website visitors according to GDPR.<span>
      </div>
      <div class="clear"></div>
    </div>
    <div class="settings_row independent_setting">
      <div
        class="onoffswitch <?php echo(($gawd_tracking_enable == '') ? 'onoffswitch_disabled' : ''); ?> independent_switch">
        <input type="checkbox" name="gawd_enhanced" class="onoffswitch-checkbox independent_input"
               id="gawd_enhanced" <?php echo $gawd_enhanced != '' ? 'checked' : ''; ?> <?php echo(($gawd_tracking_enable == '') ? 'disabled' : ''); ?>>
        <label class="onoffswitch-label" for="gawd_enhanced">
          <span class="onoffswitch-inner"></span>
          <span class="onoffswitch-switch"></span>
        </label>
      </div>
      <div class="gawd_info"
           title="Enable this option to track multiple links with the same destination. Get information for buttons, menus, as well as elements with multiple destinations, e.g. search boxes."></div>
      <div class="onoffswitch_text">
        Enhanced Link Attribution
      </div>
      <div class="clear"></div>
    </div>
    <div class="settings_row independent_setting">
      <div
        class="onoffswitch <?php echo(($gawd_tracking_enable == '') ? 'onoffswitch_disabled' : ''); ?> independent_switch">
        <input type="checkbox" name="gawd_outbound" class="onoffswitch-checkbox independent_input"
               id="gawd_outbound" <?php echo $gawd_outbound != '' ? 'checked' : ''; ?> <?php echo(($gawd_tracking_enable == '') ? 'disabled' : ''); ?>>
        <label class="onoffswitch-label" for="gawd_outbound">
          <span class="onoffswitch-inner"></span>
          <span class="onoffswitch-switch"></span>
        </label>
      </div>
      <div class="gawd_info"
           title="Turn outbound clicks tracking on to track the links users click to leave your website."></div>
      <div class="onoffswitch_text">
        Outbound clicks tracking
      </div>
      <div class="clear"></div>
    </div>
    <div class="settings_row independent_setting">
      <div
        class="onoffswitch <?php echo(($gawd_tracking_enable == '') ? 'onoffswitch_disabled' : ''); ?> independent_switch">
        <input type="checkbox" name="gawd_file_formats"
               value="zip|mp3*|mpe*g|pdf|docx*|pptx*|xlsx*|rar*"
               class="onoffswitch-checkbox independent_input"
               id="gawd_file_formats" <?php echo $gawd_file_formats != '' ? 'checked' : ''; ?> <?php echo(($gawd_tracking_enable == '') ? 'disabled' : ''); ?>>
        <label class="onoffswitch-label" for="gawd_file_formats">
          <span class="onoffswitch-inner"></span>
          <span class="onoffswitch-switch"></span>
        </label>
      </div>
      <div class="gawd_info" title="Enable to track file downloads and mailing links."></div>
      <div class="onoffswitch_text track_label">
        Mailto, Download tracking (e.g. .doc, .pdf, .jpg)
      </div>
      <div class="clear"></div>
    </div>
    <div class="settings_row">
      <div class="onoffswitch">
        <input type="checkbox" name="enable_custom_code" class="onoffswitch-checkbox"
               id="enable_custom_code" <?php echo $enable_custom_code != '' ? 'checked' : ''; ?>>
        <label class="onoffswitch-label" for="enable_custom_code">
          <span class="onoffswitch-inner"></span>
          <span class="onoffswitch-switch"></span>
        </label>
      </div>
      <div class="gawd_info" title="Enable adding custom code to tracking code."></div>
      <div class="onoffswitch_text">
        Enable custom code <span
          style="color:#dd0000;"> Make sure the code is provided by a trustworthy source.<span>
      </div>
      <div class="clear"></div>
    </div>
    <?php
    $custom_code_show = $enable_custom_code == '' ? 'style="display:none"' : '';
    ?>
    <div id="gawd_custom_code" class="gawd_goal_row" <?php echo $custom_code_show; ?>>
      <span class="gawd_goal_label">Custom Code</span>
            <span class="gawd_goal_input">
            <div class="time_input">
              <textarea class="gawd_custom_code" name="gawd_custom_code"><?php echo $gawd_custom_code; ?></textarea>
            </div>
          </span>
      <div class="gawd_info" title="Input the custom script to add to Google Analytics tracking code."></div>
      <div class="clear"></div>
    </div>
      <div style="margin-top: 15px;">
          <img class="gawd_pro_img" data-gawd-screenshot="custom_dimensions" src="<?php echo GAWD_URL . '/assets/free-pages/custom_dimensions.png'; ?>"/>
      </div>
      <div style="margin-top: 15px;">
          <img class="gawd_pro_img" data-gawd-screenshot="exclude_tracking" src="<?php echo GAWD_URL . '/assets/free-pages/exclude_tracking.png'; ?>"/>
      </div>
  </div>
  <input id="gawd_custom_dimension_id" name="gawd_custom_dimension_id" type="hidden"
         value="<?php echo count($tracking_dimensions); ?>"/>
  <div class="gawd_tracking_display">
    <p>TRACKING CODE ADDED TO SITE:</p>
    <div id="gawd_tracking_enable_code"
         <?php if ('on' != $gawd_tracking_enable): ?>style="display: none;"<?php endif; ?>>
      <code class="html">&#60;script&#62;</code>
      <code class="javascript">
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
        <br/><br/>
        ga('create', '<?php echo $ua_code ?>', 'auto');
      </code>
      <code id="enable_custom_code_code" class="javascript" <?php if ('on' != $enable_custom_code) {
        ; ?> style="display: none;"<?php }; ?>>
        </br>
        <?php echo "/*CUSTOM CODE START*/ </br>" . $gawd_custom_code . "</br>/*CUSTOM CODE END*/ </br>"; ?>
      </code>
      <code id="gawd_anonymize_code" class="javascript"
            <?php if ('on' != $gawd_anonymize): ?>style="display: none;"<?php endif; ?>>
        ga('set', 'anonymizeIp', true);
      </code>
      <code id="gawd_enhanced_code" class="javascript"
            <?php if ('on' != $gawd_enhanced): ?>style="display: none;"<?php endif; ?>>
        ga('require', 'linkid', 'linkid.js');
      </code>
      <code id="gawd_outbound_code"
            class="javascript" <?php echo $gawd_outbound != '' && isset($domain) && $domain != '' ? '' : 'style="display: none;"'; ?>>
        var links_out = document.querySelectorAll('a[href^="http"]');
        links_out.forEach(function (link, key, listObj){
        if (!link.href.match(/.*\.(zip|mp3*|mpe*g|pdf|docx*|pptx*|xlsx*|rar*)(\?.*)?$/) {
        if (link.href.indexOf('mysite.example') == -1) {
        link.addEventListener('click', function (e){
        ga('send', 'event', 'outbound', 'click', e.target.href, {'nonInteraction': 1});
        });
        });
        }
        }});
      </code>
      <code id="gawd_file_formats_code"
            class="javascript" <?php echo isset($gawd_file_formats) && $gawd_file_formats != '' ? '' : 'style="display: none"'; ?>>
        var links_download = document.querySelectorAll( 'a' );
        links_download.forEach(function(link, key, listObj){
        if (link.href.match(/.*\.(zip|mp3*|mpe*g|pdf|docx*|pptx*|xlsx*|rar*)(\?.*)?$/)) {
        link.addEventListener('click', function (e)
        {
        ga('send', 'event', 'download', 'click', e.target.href,{'nonInteraction': 1});
        });
        }
        });
        var links_mailto = document.querySelectorAll('a[href^="mailto"]');
        links_mailto.forEach(function(link, key, listObj) {
        link.addEventListener('click', function (e)
        {
        ga('send', 'event', 'email', 'send', e.target.href, {'nonInteraction': 1}";
        });
        });
        });
      </code>
      <code class="javascript">
        </br>
        ga('send', 'pageview');
      </code>
      <code class="html">&#60;/script&#62;</code>
    </div>
  </div>
  <div class="clear"></div>
</div>

<input type='hidden' name="gawd_settings_tab" id="gawd_settings_tab"/>
<input type='hidden' name="add_dimension_value" id="add_dimension_value"/>
<input type="hidden" id="gawd_excluded_roles_list" name="gawd_excluded_roles_list"
       value="<?php echo implode(',', $gawd_excluded_roles); ?>"/>
<input type="hidden" id="gawd_excluded_users_list" name="gawd_excluded_users_list" value="<?php echo implode(',', $gawd_excluded_users); ?>" />

<div class="gawd_exclude_users_popup_overlay"></div>
<div class="gawd_exclude_users_popup">
  <div class="close_btn_cont">
    <div class="gawd_exclude_users_popup_btn">X</div>
  </div>
  <?php
  $all_users = get_users();

  if ($all_users) {
    ?>
    <table border="1" class="gawd_table">
      <tr>
        <th>Name</th>
        <th>Role</th>
        <th>Action</th>
      </tr>
      <?php
      $inp_id = 0;
      foreach ($all_users as $user) {
        $inp_id++;
        ?>
        <tr>
          <td>
            <label for="gawd_excluded_users<?php echo $inp_id; ?>">
              <?php echo $user->user_nicename; ?>
            </label>
          </td>
          <td>
            <?php
            if(isset($user->roles[0])){
              echo $user->roles[0];
            }else{
              echo "No role for this site";
            }
            ?>
          </td>
          <td>
            <input id="gawd_excluded_users<?php echo $inp_id; ?>" type="checkbox"
                   name="gawd_excluded_users[]" <?php echo in_array($user->user_nicename, $gawd_excluded_users) ? 'checked' : ''; ?>
                   value="<?php echo $user->user_nicename; ?>"/>
          </td>


        </tr>
        <?php
      }
      ?>
    </table>
    <div class="add_btn_cont">
      <input type="button" class="button_gawd" id="gawd_add_users" value="Add"/>
    </div>
    <?php
  }
  ?>
</div>
<div class="gawd_exclude_roles_popup_overlay"></div>
<div class="gawd_exclude_roles_popup">
  <div class="close_btn_cont">
    <div class="gawd_exclude_roles_popup_btn">X</div>
  </div>
  <?php
  $roles = new WP_Roles();
  if ($roles) {
    ?>
    <table border="1" class="gawd_table">
      <tr>
        <th>Name</th>
        <th>Action</th>
      </tr>
      <?php
      $inp_id = 0;
      foreach ($roles->role_names as $key => $name) {
        $inp_id++;
        ?>
        <tr>
          <td>
            <label for="user_type_inp<?php echo $inp_id; ?>">
              <?php echo $name; ?>
            </label>
          </td>

          <td>
            <input id="user_type_inp<?php echo $inp_id; ?>" type="checkbox"
                   name="gawd_excluded_roles[]" <?php echo in_array($key, $gawd_excluded_roles) ? 'checked' : ''; ?>
                   value="<?php echo $key; ?>"/>
          </td>
        </tr>
        <?php
      }
      ?>
    </table>
    <div class="add_btn_cont">
      <input type="button" class="button_gawd" id="gawd_add_roles" value="Add"/>
    </div>
    <?php
  }
  ?>
</div>

