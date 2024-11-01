<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 1/31/18
 * Time: 1:38 PM
 */

$gawd_user_data = GAWD_helper::get_user_data();
$properties = GAWD_helper::get_current_site_properties();
$accounts = GAWD_helper::get_management_accounts();

if(empty($properties)) { ?>
    <p class='gawd_notice'>
        Create <b>web property</b> on your Google Analytics account to enable tracking of this website. After
        creating a <b>web property</b> Google Analytics tracking code automatically will beadded to your website.
    </p>
    <div class='gawd_settings_wrapper'>
        <div class='gawd_goal_row'>
            <span class='gawd_goal_label'>Account</span>
            <span class='gawd_goal_input'>
                        <select class='gawd_account_select'
                                style='padding: 2px;width: 96%;line-height: 30px;height: 30px !important;'>";
                          <?php foreach($accounts as $account) { ?>
                              <option value="<?php echo $account['id']; ?>"><?php echo $account['name']; ?></option>
                          <?php } ?>
                        </select>
                    </span>
            <div class='gawd_info'
                 title='Choose the Google Analytics account to connect this property to.'></div>
            <div class='clear'></div>
        </div>
        <div class='gawd_goal_row'>
            <span class='gawd_goal_label'>Name</span>
            <span class='gawd_goal_input'>
                                <input id='gawd_property_name' name='gawd_property_name' type='text'>
                            </span>
            <div class='gawd_info' title='Provide a name for the property.'></div>
            <div class='clear'></div>
        </div>
    </div>
    <div class='clear'></div>
    <div class='gawd_add_prop gawd_submit'>
        <input type='button' id='gawd_add_property' class='button_gawd' value='Add'/>
    </div>
  <?php
}

if(count($properties) > 1) { ?>
    <p class='gawd_notice'>
        You have multiple web-properties set with current site url. Please select the one that you want to use for
        tracking from the list below.
    </p>
    <div class='gawd_settings_wrapper'>
        <div class='gawd_goal_row'>
            <span class='gawd_goal_label'>Web-property</span>
            <span class='gawd_goal_input'>
    <select class='gawd_property_select'
            style='padding: 2px;width: 96%;line-height: 30px;height: 30px !important;'>
        <option value="0">Select a web-property (required)</option>
      <?php foreach($properties as $select_property) { ?>
          <option value='<?php echo $select_property['id']; ?>'>
        <?php echo $select_property['name'] . " (" . $select_property['id'] . ")"; ?>
        </option>
      <?php } ?>
    </select>
        </span>
            <div class='gawd_info' title=''></div>
            <div class='clear'></div>
        </div>
    </div>
    <div class='clear'></div>
    <div class='gawd_submit'>
        <input type='button' id='gawd_choose_property' class='button_gawd gawd_disabled_button' value='Add'/>
    </div>
  <?php
}


