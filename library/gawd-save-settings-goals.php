<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 1/24/18
 * Time: 1:41 PM
 */

function gawd_save_settings($post, &$custom_ajax){
  $gawd_alert_remove = isset($post['gawd_alert_remove']) ? intval($post['gawd_alert_remove']) : false;
  $gawd_menu_remove = isset($post['gawd_menu_remove']) ? intval($post['gawd_menu_remove']) : false;
//  $gawd_email_remove = isset($post['gawd_email_remove']) ? intval($post['gawd_email_remove']) : false;
  $gawd_filter_remove = isset($post['gawd_filter_remove']) ? intval($post['gawd_filter_remove']) : false;

  if($gawd_menu_remove) {
    $all_menues = get_option('gawd_menu_for_user');
    if($all_menues) {
      unset($all_menues[$gawd_menu_remove]);
      update_option('gawd_menu_for_user', $all_menues);
    }
  }

//  if($gawd_email_remove) {
//    $all_emails = get_option('gawd_email');
//    if($all_emails) {
//      foreach($all_emails as $email) {
//        wp_unschedule_event(wp_next_scheduled('gawd_email_' . $email['period']), 'gawd_email_' . $email['period']);
//      }
//      unset($all_emails[$gawd_email_remove - 1]);
//      update_option('gawd_email', $all_emails);
//    }
//  }

  if($gawd_filter_remove) {
    $custom_ajax->delete_management_filter($gawd_filter_remove);
  }


  $gawd_pushover_remove = isset($post['gawd_pushover_remove']) ? $post['gawd_pushover_remove'] : false;
  if($gawd_pushover_remove) {
    $all_pushovers = get_option('gawd_pushovers');
    if($all_pushovers) {
      foreach($all_pushovers as $pushover) {
        wp_unschedule_event(wp_next_scheduled('gawd_pushover_' . $pushover['period']), 'gawd_pushover_' . $pushover['period']);
      }
      unset($all_pushovers[$gawd_pushover_remove - 1]);
      update_option('gawd_pushovers', $all_pushovers);
    }
  }


  if(!isset($post['settings_submit'])) {
    return;
  }

  $gawd_alert_name = isset($post['gawd_alert_name']) ? sanitize_text_field($post['gawd_alert_name']) : '';
  $gawd_alert_period = isset($post['gawd_alert_name']) ? sanitize_text_field($post['gawd_alert_period']) : '';
  $gawd_alert_metric = isset($post['gawd_alert_metric']) ? sanitize_text_field($post['gawd_alert_metric']) : '';
  $gawd_alert_condition = isset($post['gawd_alert_condition']) ? sanitize_text_field($post['gawd_alert_condition']) : '';
  $gawd_alert_value = isset($post['gawd_alert_value']) ? sanitize_text_field($post['gawd_alert_value']) : '';
  $gawd_alert_emails = isset($post['gawd_alert_emails']) ? sanitize_email($post['gawd_alert_emails']) : '';
  $gawd_alert_view = isset($post['gawd_alert_view']) ? sanitize_text_field($post['gawd_alert_view']) : '';
  $alert_view_name = isset($post['alert_view_name']) ? sanitize_text_field($post['alert_view_name']) : '';

  if($gawd_alert_name != '' && $gawd_alert_period != '' && $gawd_alert_metric != '' && $gawd_alert_condition != '' && $gawd_alert_value != '' && $gawd_alert_emails != '') {


    $gawd_alert_options = array(
      'name' => $gawd_alert_name,
      'period' => $gawd_alert_period,
      'metric' => $gawd_alert_metric,
      'condition' => $gawd_alert_condition,
      'value' => $gawd_alert_value,
      'emails' => $gawd_alert_emails,
      'alert_view' => $gawd_alert_view,
      'alert_view_name' => $alert_view_name
    );

    $gawd = GAWD::get_instance();
    $gawd->save_alert($gawd_alert_options);
  }

  $gawd_pushover_name = isset($post['gawd_pushover_name']) ? sanitize_text_field($post['gawd_pushover_name']) : '';
  $gawd_pushover_period = isset($post['gawd_pushover_period']) ? sanitize_text_field($post['gawd_pushover_period']) : '';
  $gawd_pushover_metric = isset($post['gawd_pushover_metric']) ? sanitize_text_field($post['gawd_pushover_metric']) : '';
  $gawd_pushover_condition = isset($post['gawd_pushover_condition']) ? sanitize_text_field($post['gawd_pushover_condition']) : '';
  $gawd_pushover_value = isset($post['gawd_pushover_value']) ? intval($post['gawd_pushover_value']) : '';
  $gawd_pushover_user_keys = isset($post['gawd_pushover_user_keys']) ? sanitize_text_field($post['gawd_pushover_user_keys']) : '';
  $gawd_pushover_view = isset($post['gawd_pushover_view']) ? sanitize_text_field($post['gawd_pushover_view']) : '';
  $pushover_view_name = isset($post['pushover_view_name']) ? sanitize_text_field($post['pushover_view_name']) : '';

  if($gawd_pushover_name != '' && $gawd_pushover_period != '' && $gawd_pushover_metric != '' && $gawd_pushover_condition != '' && $gawd_pushover_value !== '' && $gawd_pushover_user_keys != '') {

    $saved_pushovers = get_option('gawd_pushovers');
    if(empty($saved_pushovers)) {
      $saved_pushovers = array();
    }

    $gawd_pushover_options = array(
      'name' => $gawd_pushover_name,
      'period' => $gawd_pushover_period,
      'metric' => $gawd_pushover_metric,
      'condition' => $gawd_pushover_condition,
      'value' => $gawd_pushover_value,
      'creation_date' => date('Y-m-d'),
      'user_key' => $gawd_pushover_user_keys,
      'pushover_view' => $gawd_pushover_view,
      'pushover_view_name' => $pushover_view_name
    );

    $saved_pushovers[] = $gawd_pushover_options;
    update_option('gawd_pushovers', $saved_pushovers);

    if($saved_pushovers) {//todo check all $this
      foreach($saved_pushovers as $pushover) {
        $this->gawd_pushover_api($pushover['user_key'], $pushover['metric'], $pushover['condition'], $pushover['value']);
        if(!wp_next_scheduled('gawd_pushover_' . $pushover['period'])) {
          wp_schedule_event(time(), $pushover['period'], 'gawd_pushover_' . $pushover['period']);
        }
      }
    }

  }

  $gawd_show_in_dashboard = isset($post['gawd_show_in_dashboard']) ? sanitize_text_field($post['gawd_show_in_dashboard']) : '';
  $site_speed_rate = isset($post['site_speed_rate']) ? intval(sanitize_text_field($post['site_speed_rate'])) : 1;
  $adsense_acc_linking = isset($post['adsense_acc_linking']) ? sanitize_text_field($post['adsense_acc_linking']) : '';
  $post_page_chart = isset($post['post_page_chart']) ? sanitize_text_field($post['post_page_chart']) : '';
  $enable_cross_domain = isset($post['enable_cross_domain']) ? sanitize_text_field($post['enable_cross_domain']) : '';
  $cross_domains = isset($post['cross_domains']) ? sanitize_text_field($post['cross_domains']) : '';
  $default_date = isset($post['default_date']) ? sanitize_text_field($post['default_date']) : 'last_7_days';
  $default_date_format = isset($post['default_date_format']) ? sanitize_text_field($post['default_date_format']) : 'ymd_with_week';
  $enable_hover_tooltip = isset($post['enable_hover_tooltip']) ? sanitize_text_field($post['enable_hover_tooltip']) : '';
  $exclude_events = isset($post['exclude_events']) ? sanitize_text_field($post['exclude_events']) : '';
  $gawd_own_project = isset($post['gawd_own_project']) ? sanitize_text_field($post['gawd_own_project']) : '';

  $gawd_permissions = isset($post['gawd_permissions']) ? explode(',', sanitize_text_field($post['gawd_permissions'])) : array('manage_options');
  $gawd_backend_roles = isset($post['gawd_backend_roles']) ? explode(',', sanitize_text_field($post['gawd_backend_roles'])) : array('administrator');
  $gawd_frontend_roles = isset($post['gawd_frontend_roles']) ? explode(',', sanitize_text_field($post['gawd_frontend_roles'])) : array('administrator');
  $gawd_post_page_roles = isset($post['gawd_post_page_roles']) ? explode(',', sanitize_text_field($post['gawd_post_page_roles'])) : array('administrator');

  $gawd_settings_exist = get_option('gawd_settings');
  $gawd_settings_exist['gawd_show_in_dashboard'] = $gawd_show_in_dashboard;
  $gawd_settings_exist['site_speed_rate'] = $site_speed_rate;
  $gawd_settings_exist['adsense_acc_linking'] = $adsense_acc_linking;
  $gawd_settings_exist['post_page_chart'] = $post_page_chart;
  $gawd_settings_exist['enable_cross_domain'] = $enable_cross_domain;
  $gawd_settings_exist['cross_domains'] = $cross_domains;
  $gawd_settings_exist['gawd_backend_roles'] = $gawd_backend_roles;
  $gawd_settings_exist['gawd_frontend_roles'] = $gawd_frontend_roles;
  $gawd_settings_exist['gawd_post_page_roles'] = $gawd_post_page_roles;
  $gawd_settings_exist['default_date'] = $default_date;
  $gawd_settings_exist['default_date_format'] = $default_date_format;
  $gawd_settings_exist['enable_hover_tooltip'] = $enable_hover_tooltip;
  $gawd_settings_exist['exclude_events'] = $exclude_events;
  $gawd_settings_exist['gawd_permissions'] = $gawd_permissions;
  update_option('gawd_settings', $gawd_settings_exist);

  $gawd_filter_name = isset($post['gawd_filter_name']) ? sanitize_text_field($post['gawd_filter_name']) : '';
  $gawd_filter_type = isset($post['gawd_filter_type']) ? sanitize_text_field($post['gawd_filter_type']) : '';
  $gawd_filter_value = isset($post['gawd_filter_value']) ? $gawd_filter_type == 'GEO_IP_ADDRESS' ? ($post['gawd_filter_value']) : sanitize_text_field($post['gawd_filter_value']) : '';
  if($gawd_filter_name != '' && $gawd_filter_type != '' && $gawd_filter_value != '') {
    $custom_ajax->add_management_filter($gawd_filter_name, $gawd_filter_type, $gawd_filter_value);//todo
  }


  /****** Update Credentials ******/
  $gawd_own_client_id = (isset($post['gawd_own_client_id'])) ? sanitize_text_field($post['gawd_own_client_id']) : "";
  $gawd_own_client_secret = (isset($post['gawd_own_client_secret'])) ? sanitize_text_field($post['gawd_own_client_secret']) : "";

  if(
    empty($gawd_own_client_id) && !empty($gawd_own_client_secret) ||
    empty($gawd_own_client_secret) && !empty($gawd_own_client_id)
  ) {
    return;
  }

  $current_credentials = GAWD_helper::get_project_credentials();
  $default_credentials = GAWD_helper::get_project_default_credentials();

  $credentials = array(
    'project_id' => $gawd_own_client_id,
    'project_secret' => $gawd_own_client_secret
  );

  $is_default = false;
  if(
    ($credentials['project_id'] === $default_credentials['project_id'] && $credentials['project_secret'] === $default_credentials['project_secret']) ||
    ($credentials['project_id'] === "" && $credentials['project_secret'] === "")
  ) {
    $is_default = true;
  }

  if($current_credentials['default'] === true && $is_default === true){
    return;
  }

  if(
    $credentials['project_id'] !== $current_credentials['project_id'] ||
    $credentials['project_secret'] !== $current_credentials['project_secret']
  ) {
    GAWD_helper::delete_user_data();
    update_option('gawd_credentials', $credentials);
  }

}

function gawd_save_tracking($post, &$custom_ajax){
  $add_dimension_value = isset($post['add_dimension_value']) ? $post['add_dimension_value'] : '';

  if($add_dimension_value == 'add_dimension_Logged_in') {
    $id = isset($post['gawd_custom_dimension_id']) ? ($post['gawd_custom_dimension_id'] + 1) : 1;
    $error = $custom_ajax->add_custom_dimension('Logged in', $id);
    $settings = get_option('gawd_settings');
    $optname = 'gawd_custom_dimension_Logged_in';
    $settings[$optname] = isset($post['gawd_tracking_enable']) ? $post['gawd_tracking_enable'] : '';
    if($error != 'error') {
      update_option('gawd_settings', $settings);
    }
  }

  if($add_dimension_value == 'add_dimension_Post_type') {
    $id = isset($post['gawd_custom_dimension_id']) ? ($post['gawd_custom_dimension_id'] + 1) : 1;
    $error = $custom_ajax->add_custom_dimension('Post type', $id);
    $settings = get_option('gawd_settings');
    $optname = 'gawd_custom_dimension_Post_type';
    $settings[$optname] = isset($post['gawd_tracking_enable']) ? $post['gawd_tracking_enable'] : '';
    if($error != 'error') {
      update_option('gawd_settings', $settings);
    }
  }

  if($add_dimension_value == 'add_dimension_Author') {
    $id = isset($post['gawd_custom_dimension_id']) ? ($post['gawd_custom_dimension_id'] + 1) : 1;
    $error = $custom_ajax->add_custom_dimension('Author', $id);
    $settings = get_option('gawd_settings');
    $optname = 'gawd_custom_dimension_Author';
    $settings[$optname] = isset($post['gawd_tracking_enable']) ? $post['gawd_tracking_enable'] : '';
    if($error != 'error') {
      update_option('gawd_settings', $settings);
    }
  }

  if($add_dimension_value == 'add_dimension_Category') {
    $id = isset($post['gawd_custom_dimension_id']) ? ($post['gawd_custom_dimension_id'] + 1) : 1;
    $error = $custom_ajax->add_custom_dimension('Category', $id);
    $settings = get_option('gawd_settings');
    $optname = 'gawd_custom_dimension_Category';
    $settings[$optname] = isset($post['gawd_tracking_enable']) ? $post['gawd_tracking_enable'] : '';
    if($error != 'error') {
      update_option('gawd_settings', $settings);
    }
  }

  if($add_dimension_value == 'add_dimension_Published_Month') {
    $id = isset($post['gawd_custom_dimension_id']) ? ($post['gawd_custom_dimension_id'] + 1) : 1;
    $error = $custom_ajax->add_custom_dimension('Published Month', $id);
    $settings = get_option('gawd_settings');
    $optname = 'gawd_custom_dimension_Published_Month';
    $settings[$optname] = isset($post['gawd_tracking_enable']) ? $post['gawd_tracking_enable'] : '';
    if($error != 'error') {
      update_option('gawd_settings', $settings);
    }
  }

  if($add_dimension_value == 'add_dimension_Published_Year') {
    $id = isset($post['gawd_custom_dimension_id']) ? ($post['gawd_custom_dimension_id'] + 1) : 1;
    $error = $custom_ajax->add_custom_dimension('Published Year', $id);
    $settings = get_option('gawd_settings');
    $optname = 'gawd_custom_dimension_Published_Year';
    $settings[$optname] = isset($post['gawd_tracking_enable']) ? $post['gawd_tracking_enable'] : '';
    if($error != 'error') {
      update_option('gawd_settings', $settings);
    }
  }

  if($add_dimension_value == 'add_dimension_Tags') {
    $id = isset($post['gawd_custom_dimension_id']) ? ($post['gawd_custom_dimension_id'] + 1) : 1;
    $error = $custom_ajax->add_custom_dimension('Tags', $id);
    $settings = get_option('gawd_settings');
    $optname = 'gawd_custom_dimension_Tags';
    $settings[$optname] = isset($post['gawd_tracking_enable']) ? $post['gawd_tracking_enable'] : '';
    if($error != 'error') {
      update_option('gawd_settings', $settings);
    }
  }

  if(!isset($post['settings_submit'])) {
    return;
  }

  $gawd_settings = get_option('gawd_settings');

  $gawd_file_formats = isset($post['gawd_file_formats']) ? sanitize_text_field($post['gawd_file_formats']) : '';
  $gawd_anonymize = isset($post['gawd_anonymize']) ? sanitize_text_field($post['gawd_anonymize']) : '';
  $gawd_tracking_enable = isset($post['gawd_tracking_enable']) ? sanitize_text_field($post['gawd_tracking_enable']) : '';
  $gawd_outbound = isset($post['gawd_outbound']) ? sanitize_text_field($post['gawd_outbound']) : '';
  $gawd_enhanced = isset($post['gawd_enhanced']) ? sanitize_text_field($post['gawd_enhanced']) : '';
  $enable_custom_code = isset($post['enable_custom_code']) ? sanitize_text_field($post['enable_custom_code']) : '';
  $custom_code = isset($post['gawd_custom_code']) ? stripslashes($post['gawd_custom_code']) : '';

  if($add_dimension_value == '') {
    $gawd_cd_Logged_in = isset($post['gawd_custom_dimension_Logged_in']) ? sanitize_text_field($post['gawd_custom_dimension_Logged_in']) : '';
    $gawd_cd_Post_type = isset($post['gawd_custom_dimension_Post_type']) ? sanitize_text_field($post['gawd_custom_dimension_Post_type']) : '';
    $gawd_cd_Author = isset($post['gawd_custom_dimension_Author']) ? sanitize_text_field($post['gawd_custom_dimension_Author']) : '';
    $gawd_cd_Category = isset($post['gawd_custom_dimension_Category']) ? sanitize_text_field($post['gawd_custom_dimension_Category']) : '';
    $gawd_cd_Published_Month = isset($post['gawd_custom_dimension_Published_Month']) ? sanitize_text_field($post['gawd_custom_dimension_Published_Month']) : '';
    $gawd_cd_Published_Year = isset($post['gawd_custom_dimension_Published_Year']) ? sanitize_text_field($post['gawd_custom_dimension_Published_Year']) : '';
    $gawd_cd_Tags = isset($post['gawd_custom_dimension_Tags']) ? sanitize_text_field($post['gawd_custom_dimension_Tags']) : '';
    $gawd_settings['gawd_custom_dimension_Logged_in'] = $gawd_cd_Logged_in;
    $gawd_settings['gawd_custom_dimension_Post_type'] = $gawd_cd_Post_type;
    $gawd_settings['gawd_custom_dimension_Author'] = $gawd_cd_Author;
    $gawd_settings['gawd_custom_dimension_Category'] = $gawd_cd_Category;
    $gawd_settings['gawd_custom_dimension_Published_Month'] = $gawd_cd_Published_Month;
    $gawd_settings['gawd_custom_dimension_Published_Year'] = $gawd_cd_Published_Year;
    $gawd_settings['gawd_custom_dimension_Tags'] = $gawd_cd_Tags;
  }

  $gawd_excluded_roles = array();
  if(!empty($post['gawd_excluded_roles_list'])) {
    $gawd_excluded_roles = explode(',', $post['gawd_excluded_roles_list']);
  }

  $gawd_excluded_users = array();
  if(!empty($post['gawd_excluded_users_list'])) {
    $gawd_excluded_users = explode(',', $post['gawd_excluded_users_list']);
  }

  $gawd_settings['gawd_file_formats'] = $gawd_file_formats;
  $gawd_settings['gawd_anonymize'] = $gawd_anonymize;
  $gawd_settings['gawd_file_formats'] = $gawd_file_formats;
  $gawd_settings['gawd_tracking_enable'] = $gawd_tracking_enable;
  $gawd_settings['gawd_outbound'] = $gawd_outbound;
  $gawd_settings['gawd_enhanced'] = $gawd_enhanced;
  $gawd_settings['enable_custom_code'] = $enable_custom_code;

  $gawd_settings['gawd_custom_code'] = $custom_code;
  $gawd_settings['gawd_excluded_roles'] = $gawd_excluded_roles;
  $gawd_settings['gawd_excluded_users'] = $gawd_excluded_users;
  update_option('gawd_settings', $gawd_settings);
}

function gawd_save_goals($post, &$custom_ajax){
  $gawd_goal_profile = isset($post['gawd_goal_profile']) ? sanitize_text_field($post['gawd_goal_profile']) : '';
  $gawd_goal_name = isset($post['gawd_goal_name']) ? sanitize_text_field($post['gawd_goal_name']) : '';
  $gawd_goal_type = isset($post['gawd_goal_type']) ? sanitize_text_field($post['gawd_goal_type']) : '';
  $gawd_visit_hour = isset($post['gawd_visit_hour']) ? sanitize_text_field($post['gawd_visit_hour']) : '';
  $gawd_visit_minute = isset($post['gawd_visit_minute']) ? sanitize_text_field($post['gawd_visit_minute']) : '';
  $gawd_visit_second = isset($post['gawd_visit_second']) ? sanitize_text_field($post['gawd_visit_second']) : '';
  $gawd_goal_duration_comparison = isset($post['gawd_goal_duration_comparison']) ? sanitize_text_field($post['gawd_goal_duration_comparison']) : '';
  $gawd_goal_page_comparison = isset($post['gawd_goal_page_comparison']) ? sanitize_text_field($post['gawd_goal_page_comparison']) : '';
  $gawd_page_sessions = isset($post['gawd_page_sessions']) ? sanitize_text_field($post['gawd_page_sessions']) : '';
  $goal_id = isset($post['gawd_next_goal_id']) ? sanitize_text_field($post['gawd_next_goal_id']) : 1;
  $gawd_goal_page_destination_match = isset($post['gawd_goal_page_destination_match']) ? sanitize_text_field($post['gawd_goal_page_destination_match']) : '';
  $gawd_page_url = isset($post['gawd_page_url']) ? sanitize_text_field($post['gawd_page_url']) : '';
  $url_case_sensitve = isset($post['url_case_sensitve']) ? sanitize_text_field($post['url_case_sensitve']) : '';

  if($gawd_goal_type == 'VISIT_TIME_ON_SITE') {
    if($gawd_visit_hour != '' || $gawd_visit_minute != '' || $gawd_visit_second != '') {
      $value = 0;
      if($gawd_visit_hour != '') {
        $value += $gawd_visit_hour * 60 * 60;
      }
      if($gawd_visit_minute != '') {
        $value += $gawd_visit_minute * 60;
      }
      $value += $gawd_visit_second;
    }

    return $custom_ajax->add_management_goal($gawd_goal_profile, $goal_id, $gawd_goal_type, $gawd_goal_name, $gawd_goal_duration_comparison, $value);
  } elseif($gawd_goal_type == 'VISIT_NUM_PAGES') {
    if($gawd_page_sessions != '') {
      return $custom_ajax->add_management_goal($gawd_goal_profile, $goal_id, $gawd_goal_type, $gawd_goal_name, $gawd_goal_page_comparison, $gawd_page_sessions);
    }
  } elseif($gawd_goal_type == 'URL_DESTINATION') {
    if($gawd_page_url != '') {
      return $custom_ajax->add_management_goal($gawd_goal_profile, $goal_id, $gawd_goal_type, $gawd_goal_name, $gawd_goal_page_destination_match, $gawd_page_url, $url_case_sensitve);
    }
  } elseif($gawd_goal_type == 'EVENT') {
    if($gawd_page_url != '') {
      return $custom_ajax->add_management_goal($gawd_goal_profile, $goal_id, $gawd_goal_type, $gawd_goal_name, $gawd_goal_page_comparison, $gawd_page_url, $url_case_sensitve);
    }
  }

  return false;
}