<?php if ($display_goals_page === false) { ?>
  <div>
    <h4 style="margin-top: 30px;">
      You cannot configure goals for this site, because there is no property chosen for it.
      <a href="<?php echo admin_url('admin.php?page=gawd_settings#gawd_tracking_tab'); ?>"
         style="cursor: pointer">
        Click here
      </a>
      to set up a property.
    </h4>
  </div>
  <?php
  return;
} ?>
<div class="goal_wrap">
  <div>
    <h3 class="gawd_page_titles">Goal Management</h3>
    <div class="gawd_refresh_button_wrapper">
      <div class="gawd_info gawd_info_goal"
           title="<?php esc_attr_e('Use this button to synchronize user data on this site with Google Analytics service. For example, after adding GA Account or changing web-properties.', 'gawd'); ?>">
      </div>
      <div class="gawd_account_button refresh_user_info refresh_user_info_goal" onclick="gawd_refresh_user_info(false,true)">
        Refresh user info
      </div>
    </div>
  </div>
  <p style="width: 80%;">
    You can set and manage goals for your website tracking. Select the View that you’re going to track and configure
    these options based on the type of goal you would like to set.
  </p>
  <form method="post" id="gawd_goal_form">
    <div class="gawd_goal_row">
      <span class="gawd_goal_label">Website</span>
            <span class="gawd_goal_input">
          <select name="gawd_goal_profile" class="gawd_goal_profile">
            <?php foreach ($profiles as $profile) {
              $next_goal_id = (isset($next_goals_id[$profile['id']])) ? $next_goals_id[$profile['id']] : "1";
              echo '<option data-next_id="' . $next_goal_id . '" value="' . $profile['id'] . '">' . $property['name'] . ' - ' . $profile['name'] . '</option>';
            } ?>
          </select>
        </span>
      <div class="gawd_info"
           title="Choose the website, to which you would like to set Google Analytics Goals. "></div>
      <div class='clear'></div>
    </div>
    <div class="gawd_goal_row">
      <span class="gawd_goal_label">Name</span>
            <span class="gawd_goal_input">
          <input id="gawd_goal_name" name="gawd_goal_name" class="" type="text" value="">
        </span>
      <div class="gawd_info" title="Provide a name for this goal"></div>
      <div class='clear'></div>
    </div>
    <div class="gawd_goal_row">
      <span class="gawd_goal_label">Type</span>
            <span class="gawd_goal_input">
          <select name="gawd_goal_type" class="gawd_goal_type">
            <option value="URL_DESTINATION">Destination</option>
            <option value="VISIT_TIME_ON_SITE">Duration</option>
            <option value="VISIT_NUM_PAGES">Pages/Screens per session</option>
            <!-- <option value="EVENT">Event</option> -->
          </select>
        </span>
      <div class="gawd_info"
           title="Select its type (Destination, Duration, Pages/Screens per session or Event)."></div>
      <div class='clear'></div>
    </div>
    <div class="gawd_goal_duration_wrap" id="gawd_goal_duration_wrap">
      <div class="gawd_duration_label">Duration</div>
      <div class="gawd_comparison_input">
        <select name="gawd_goal_duration_comparison" class="gawd_goal_duration_comparison">
          <option value="GREATER_THAN">Greater than</option>
        </select>
      </div>
      <div class="gawd_duration">
        <div class="time_wrap">
          <!--<div class="time_label">Hour</div> -->
          <div class="time_input"><input placeholder="hour" type="number" min='0' name="gawd_visit_hour"/>
          </div>
        </div>
        <div class="time_wrap">
          <!--<div class="time_label">Minute</div> -->
          <div class="time_input"><input placeholder="min." type="number" min='0' name="gawd_visit_minute"/>
          </div>
        </div>
        <div class="time_wrap" id="time_wrap">
          <!--<div class="time_label">Second</div> -->
          <div class="time_input"><input placeholder="sec." type="number" min='0' name="gawd_visit_second"/>
          </div>
        </div>
        <div class='clear'></div>
      </div>
      <div class="gawd_info" style="margin-left: 15px"
           title="Set a duration for this goal. For example, if you select 20 minutes, each time users spend 20 minutes or more on your site, it will be counted as goal completion."></div>
      <div class='clear'></div>
    </div>
    <div class="gawd_page_sessions" id="gawd_page_sessions">
      <div class="gawd_duration_label">Pages per session</div>
      <div class="gawd_comparison_input">
        <select name="gawd_goal_page_comparison" class="gawd_goal_duration_comparison">
          <option value="GREATER_THAN">Greater than</option>
        </select>
      </div>
      <div class="gawd_duration">
        <div class="time_wrap">
          <!--<div class="time_label">Hour</div> -->
          <input type="number" min='0' name="gawd_page_sessions"/>
        </div>
        <div class='clear'></div>
      </div>
      <div class="gawd_info" style="margin-left: 15px"
           title="Choose the number of pages/screens users should view to complete this goal."></div>
      <div class='clear'></div>
    </div>
    <div class="gawd_page_destination" id="gawd_page_destination">
      <div class="gawd_duration_label">Destination type</div>
      <div class="gawd_url_comparison_input">
        <select name="gawd_goal_page_destination_match" class="gawd_goal_duration_comparison">
          <option value="EXACT">Equals to</option>
          <option value="HEAD">Begins with</option>
          <option value="REGEX">Regular expression</option>
        </select>
      </div>
      <div class="gawd_info" style="margin-left: 8px;"
           title="Set the destination of your goal. It can be equal to the specified value, as well as begin with it. You can also add a Regular Expression as destination value."></div>
      <div class="gawd_destination_url">
        <label for="gawd_case_sensitive" class="case_sensitive gawd_duration_label">URL</label>
        <div class="time_wrap">
          <div class="time_input"><input type="text" name="gawd_page_url"/></div>
        </div>
        <div class="gawd_info" title="Set the URL or Regular Expression of the destination."></div>
        <div class='clear'></div>
      </div>
      <div class="gawd_destination_url">
        <label for="gawd_case_sensitive" class="case_sensitive gawd_duration_label">Case-sensitive URL</label>
        <div class="time_wrap">
          <div class="onoffswitch" style="margin: 3px 0 0 6px;">
            <input type="checkbox" name="url_case_sensitve" class="onoffswitch-checkbox"
                   id="gawd_case_sensitive">
            <label class="onoffswitch-label" for="gawd_case_sensitive">
              <span class="onoffswitch-inner"></span>
              <span class="onoffswitch-switch"></span>
            </label>
          </div>
        </div>
        <div class="gawd_info" title="Enable this option to set destination URL to be case-sensitive."></div>
      </div>

      <div class='clear'></div>
    </div>
    <div class="gawd_buttons" id="goal_submit">
      <input class="button_gawd" type="button" name="add_goal" value="Save"/>
    </div>
    <input type="hidden" name="gawd_next_goal_id" id="gawd_next_goal_id" value=""/>
  </form>
  <input type="hidden" id="gawd_refresh_user_info_transient"
         value="<?php echo $refresh_user_info_transient; ?>"/>
  <?php if (!empty($goals)) {
    $counter = 0;
    foreach ($goals as $profile_id => $profile_goals) { ?>
      <table border="1" class="gawd_table" id="<?php echo $profile_id; ?>"
             style="<?php echo(($counter != 0) ? 'display:none;' : ''); ?>">
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Type</th>
          <th>Match Type</th>
          <th>Value</th>
        </tr>
        <?php
        foreach ($profile_goals as $goal) {
          $case_sensitive = $goal['caseSensitive'] ? ' - case sensitive' : '';
          ?>
          <tr class="gawd_rows">
            <td><?php echo $goal['id']; ?></td>
            <td><?php echo $goal['name']; ?></td>
            <td><?php echo $goal['type']; ?></td>
            <td><?php echo ($goal['match_type'] !== 'GREATER_THAN') ? $goal['match_type'] : "Greater than"; ?></td>
            <td><?php echo $goal['value'] . $case_sensitive; ?></td>
          </tr>
          <?php
        }
        ?>
      </table>
      <?php $counter++;
    }
  } ?>

</div>
<script>

  jQuery('.gawd_goal_type').on('change', function ()
  {
    if (jQuery('.gawd_goal_type :selected').val() == 'VISIT_TIME_ON_SITE') {
      jQuery('.gawd_goal_duration_wrap').show();
      jQuery('.gawd_page_sessions').hide();
      jQuery('.gawd_page_destination').hide();
      jQuery('.gawd_page_event').hide();
    } else
      if (jQuery('.gawd_goal_type :selected').val() == 'VISIT_NUM_PAGES') {
        jQuery('.gawd_goal_duration_wrap').hide();
        jQuery('.gawd_page_destination').hide();
        jQuery('.gawd_page_event').hide();
        jQuery('.gawd_page_sessions').show();
      } else
        if (jQuery('.gawd_goal_type :selected').val() == 'EVENT') {
          jQuery('.gawd_goal_duration_wrap').hide();
          jQuery('.gawd_page_sessions').hide();
          jQuery('.gawd_page_destination').hide();
          jQuery('.gawd_page_event').show();
        } else {
          jQuery('.gawd_goal_duration_wrap').hide();
          jQuery('.gawd_page_sessions').hide();
          jQuery('.gawd_page_event').hide();
          jQuery('.gawd_page_destination').show();
        }
  });

  jQuery('.button_gawd').on('click', function ()
  {
    var submit_form = true;
    var gawd_goal_name = jQuery("#gawd_goal_name");
    var gawd_goal_name = jQuery("#gawd_goal_name");
    if (gawd_goal_name.val() === "") {
      gawd_goal_name.addClass('gawd_invalid');
      submit_form = false;
    }
    else
      if (
        (jQuery('input[name="gawd_page_sessions"]').val() === '' && jQuery('.gawd_goal_type :selected').val() == 'VISIT_NUM_PAGES') ||
        (jQuery('input[name="gawd_page_url"]').val() === '' && jQuery('.gawd_goal_type :selected').val() == 'URL_DESTINATION') ||
        ((jQuery('input[name="gawd_visit_hour"]').val() === '' || jQuery('input[name="gawd_visit_minute"]').val() === '' || jQuery('input[name="gawd_visit_second"]').val() === '') && jQuery('.gawd_goal_type :selected').val() == 'VISIT_TIME_ON_SITE')) {
        jQuery('input[name="gawd_page_url"]').addClass('gawd_invalid');
        jQuery('input[name="gawd_page_sessions"]').addClass('gawd_invalid');
        jQuery('input[name="gawd_visit_hour"]').addClass('gawd_invalid');
        jQuery('input[name="gawd_visit_minute"]').addClass('gawd_invalid');
        jQuery('input[name="gawd_visit_second"]').addClass('gawd_invalid');
        submit_form = false;
      }
      else {
        gawd_goal_name.removeClass('gawd_invalid');
        jQuery('input[name="gawd_page_url"]').removeClass('gawd_invalid');
        jQuery('input[name="gawd_page_sessions"]').removeClass('gawd_invalid');
        jQuery('input[name="gawd_visit_hour"]').removeClass('gawd_invalid');
        jQuery('input[name="gawd_visit_minute"]').removeClass('gawd_invalid');
        jQuery('input[name="gawd_visit_second"]').removeClass('gawd_invalid');
      }

    var table_id = jQuery('.gawd_goal_profile').val();
    var next_id = jQuery('.gawd_goal_profile').find('option:selected').data('next_id');

    if (jQuery('#' + table_id + ' .gawd_table tr').length - 1 >= 20 || next_id === "") {
      alert('You have reached the maximum number of goals.')
      return;
    }

    jQuery('#gawd_next_goal_id').val(next_id);
    if (submit_form) {
      gawd_save_goals();
      return false;
    }
  });

  jQuery('.gawd_goal_profile').on('change', function ()
  {
    jQuery('.gawd_table').each(function ()
    {
      jQuery(this).hide();
    });
    var id = jQuery(this).val();
    jQuery('#' + id).show();
  });

  function gawd_save_goals()
  {

    var serialized_form = jQuery("#gawd_goal_form").serializeArray();
    var form_data = {};
    for (var i = 0; i < serialized_form.length; i++) {
      form_data[serialized_form[i].name] = serialized_form[i].value;
    }

    var args = gawd_custom_ajax_args();
    args.type = 'POST';
    args.async = true;
    args.data.gawd_action = "save_goals";
    args.data.gawd_data = {
      'form': form_data
    };

    var $loader_container = jQuery("#gawd_goal_form");
    args.beforeSend = function ()
    {
      gawd_add_loader($loader_container);
    };

    args.success = function (response)
    {
      window.location.reload();
    };

    args.error = function ()
    {
      window.location.reload();
    };

    jQuery.ajax(args).done(function ()
    {
      gawd_remove_loader($loader_container);
    });
  }
</script>
