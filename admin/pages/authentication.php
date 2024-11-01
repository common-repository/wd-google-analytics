<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 2/7/18
 * Time: 5:18 PM
 */

if(GAWD_helper::get_management_accounts() == false) { ?>
    <div class="no_account_content clearfix <?php echo $hide_refresh_accounts; ?>">
        <p style="color: red;">
            You have Google account, but it does not have configured Analytics account. Click create button to create account and refresh afterwards.
        </p>
        <a id="gawd_refresh_management_accounts" class="gawd_account_button gwd_refresh_page gawd_hidden" href="">Refresh</a>
        <div class="gawd_account_button gwd_another_account" onclick="gawd_show_authenticate_form()">
            Authenticate with another account
        </div>
        <a class="gawd_auth_button gwd_create_account"
           href="https://analytics.google.com/analytics/web/provision/?authuser=0#provision/SignUp/"
           target="_blank" onclick="gawd_reload_account()">CREATE</a>
    </div>
<?php } ?>
<div class="gawd_auth_wrap gawd_auth_authenticate <?php echo $hide_auth; ?>">
    <p class="auth_description">
        Click <b>Authenticate</b> button and login to your Google account. A window asking for relevant permissions will
        appear. Click <b>Allow</b> and copy the authentication code from the text input.
    </p>
    <div id="gawd_auth_url" onclick="gawd_auth_popup(800,400)" style="cursor: pointer;">
        <div class="gawd_auth_button">AUTHENTICATE</div>
        <div class="clear"></div>
    </div>
    <div id="gawd_auth_code">
        <form id="gawd_auth_code_paste" action="" method="post" onSubmit="return false;">
            <p style="margin:0;color: #444;">Paste the authentication code from the popup to this input.</p>
            <input id="gawd_token" type="text">
          <?php wp_nonce_field("gawd_save_form", "gawd_save_form_fild"); ?>
        </form>
        <div id="gawd_auth_code_submit">SUBMIT</div>
    </div>
  <?php if($gawd_credentials['default'] === false) { ?>
      <a class="gawd_reset_credentials" id="gawd_reset_credentials">Stop using own project</a>
  <?php } ?>
</div>
<div id="opacity_div"
     style="display: none; background-color: rgba(0, 0, 0, 0.2); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 99998;"></div>
<div id="loading_div"
     style="display:none; text-align: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 99999;">
    <img src="<?php echo GAWD_URL ?>/assets/ajax_loader.gif" style="margin-top: 200px; width:50px;">
</div>

