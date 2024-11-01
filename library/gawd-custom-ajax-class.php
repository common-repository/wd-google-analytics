<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 12/20/17
 * Time: 11:09 AM
 */

class GAWD_custom_ajax_class {

  private $action = "";
  private $request_data = array();
  private $nonce_data = array();
  private $user_data = array();
  private $expiration_time = array();
  private $gawd_google = null;
  private $view_id = null;
  //private $gawd_last_viewed_profile = null;


  public function __construct(){
    $get_last_viewed_profile = GAWD_helper::get_last_viewed_profile();
    //$this->gawd_last_viewed_profile = $get_last_viewed_profile["profile_id"];

//    ini_set('display_errors', 1);
//    ini_set('display_startup_errors', 1);
//    error_reporting(E_ALL);

    $this->set_expiration_time();
    $this->user_data = get_option('gawd_user_data');

    $this->verify_nonce();
    $this->parse_data();
    $this->include_libs();
    $this->set_gawd_google();
    $this->do_action();
  }


  private function do_action(){

    if(get_option('gawd_upgrade_plugin') === '1') {
      $this->upgrade_plugin();
    }

    if($this->check_permission() === false) {
      $this->send_response(false, 'no_permission');
    }

    $reset = (isset($_REQUEST['gawd_refresh_report_cache']));

    switch($this->action) {
      case 'get_auth_url':
        $this->get_auth_url();
        break;
      case 'authenticate':
        $this->authenticate();
        break;
      case 'get_real_time_data':
        $this->get_real_time_data();
        break;
      case 'refresh_management_accounts':
        $this->refresh_management_accounts();
        break;
      case 'add_property':
        $this->add_property();
        break;
      case 'choose_property':
        $this->choose_property();
        break;
      case 'refresh_user_info':
        $this->refresh_user_info();
        break;
      case 'gawd_show_data':
        $this->gawd_show_data($reset);
        break;
      case 'gawd_show_post_page_data':
        $this->gawd_show_page_post_data();
        break;
      case 'gawd_show_data_compact':
        $this->gawd_show_data_compact();
        break;
      case 'save_settings':
        $this->save_settings();
        break;
      case 'save_goals':
        $this->save_goals();
        break;
      case 'get_custom_dimensions':
        $this->get_custom_dimensions();
        break;
      case 'gawd_get_management_goals':
        $this->gawd_get_management_goals();
        break;
      case 'delete_credentials':
        $this->delete_credentials();
        break;
      case 'get_widget_data':
        $this->get_widget_data();
        break;
      default:
        $this->send_response(false, 'wrong_action');
    }

  }

  private function get_auth_url(){
    $this->send_response(true, '', '', array(
        'url' => GAWD_google::get_authentication_url()
      )
    );
  }

  private function authenticate(){
    GAWD_helper::delete_user_data();

    $response_data = array();

    if(empty($this->request_data['token'])) {
      $this->send_response(false, 'wrong_token', 'Token is empty.', $response_data);
    }

    $token = esc_html($this->request_data['token']);
    $tokens = GAWD_google::authenticate($token);

    if(empty($tokens)) {
      $this->send_response(false, 'wrong_token', 'Access and Refresh tokens are invalid.', $response_data);
    }

    $this->set_gawd_google($tokens);
    $user_new_data = array(
      'access_token' => $tokens['access_token'],
      'refresh_token' => $tokens['refresh_token'],
      'profile_id' => '',
      'account_id' => '',
      'property_id' => '',
      'web_property_name' => ''
    );

    $management_accounts = $this->get_management_accounts(true);
    if(empty($management_accounts)) {
      $response_data['redirect'] = 'reload';
    }

    $this->get_properties(true);
    $profiles = $this->get_profiles(true);
    if($profiles === null) {
      update_option("gawd_user_data", $user_new_data);
      update_option("gawd_user_status", "1");
      $this->send_response(false, 'there_are_no_profiles', 'Profiles are empty.', $response_data);
    }

    $properties = GAWD_helper::get_current_site_properties(true);

    if(count($properties) === 1) {
      $user_new_data['profile_id'] = $properties[0]['defaultProfileId'];
      $user_new_data['property_id'] = $properties[0]['id'];
      $user_new_data['account_id'] = $properties[0]['accountId'];
      $user_new_data['web_property_name'] = $properties[0]['name'];
    }


    $response_data['redirect'] = (empty($user_new_data['property_id'])) ? 'tracking_tab' : 'dashboard';

    update_option("gawd_user_data", $user_new_data);
    update_option("gawd_user_status", "1");

    $this->send_response(true, '', '', $response_data);
  }

  private function refresh_management_accounts(){
    $accounts = $this->get_management_accounts(true);

    if(empty($accounts)) {
      $this->send_response(false, 'no_account', 'There are no accounts.');
    }
    $properties = $this->get_properties(true);
    if(empty($properties)) {
      $this->send_response(false, 'no_properties', 'There are no properties.');
    }
    $profiles = $this->get_profiles(true);
    if(empty($profiles)) {
      $this->send_response(false, 'no_profile', 'There are no profiles.');
    }

    $this->send_response(true);
  }

  private function upgrade_plugin(){
    $new_user_data = array(
      'access_token' => $this->user_data['access_token'],
      'refresh_token' => $this->user_data['refresh_token'],
      'property_id' => !empty($this->user_data['default_webPropertyId']) ? $this->user_data['default_webPropertyId'] : "",
      'account_id' => !empty($this->user_data['default_accountId']) ? $this->user_data['default_accountId'] : ""
    );
    $this->user_data = $new_user_data;
    $this->request_data['remove_reports_data'] = '1';
    update_option('gawd_upgrade_plugin', '0');
    $this->refresh_user_info();
  }

  private function refresh_user_info(){

    $property_id = $this->user_data['property_id'];

    $this->user_data['profile_id'] = "";
    $this->user_data['property_id'] = "";
    $this->user_data['account_id'] = "";
    $this->user_data['web_property_name'] = "";

    set_site_transient('gawd_refresh_user_info', '1', $this->expiration_time['refresh_user_info']);
    $add_notice = (isset($this->request_data['add_notice']) && $this->request_data['add_notice'] == '1');
    $remove_reports_data = (isset($this->request_data['remove_reports_data']) && $this->request_data['remove_reports_data'] == '1');

    if($remove_reports_data === true) {
      GAWD_helper::delete_reports_data();
    }
    $management_accounts = $this->get_management_accounts(true);
    $properties = $this->get_properties(true);
    $profiles = $this->get_profiles(true);
    if(
      empty($management_accounts) ||
      empty($properties) ||
      empty($profiles)
    ) {
      update_option('gawd_user_data', $this->user_data);
      if($add_notice) {
        GAWD_helper::add_notice(__('No GA account, property or profile.', 'gawd'), 'error');
      }
      $this->send_response(false, 'empty_account_data');
    }


    $all_properties = GAWD_helper::get_properties(true);


    $last_viewed_profile = GAWD_helper::get_last_viewed_profile();
    $last_viewed_profile_exists = false;
    if(!empty($all_properties)) {
      foreach($all_properties as $prop) {
        if($prop['id'] === $last_viewed_profile['web_property_id']) {
          $last_viewed_profile_exists = true;
          break;
        }
      }

      if($last_viewed_profile_exists === false) {
        delete_option('gawd_last_viewed_profile');
      }

    }

    $properties = GAWD_helper::get_current_site_properties(true);

    if(empty($properties)) {
      update_option('gawd_user_data', $this->user_data);
      $this->send_response(false, 'no_properties', 'There are no properties.');
    }

    $property = null;


    if(count($properties) === 1) {
      $property = $properties[0];
    } else {
      foreach($properties as $prop) {
        if($prop['id'] === $property_id) {
          $property = $prop;
        }
      }
    }

    if(!empty($property)) {

      $this->user_data['profile_id'] = $property['defaultProfileId'];
      $this->user_data['property_id'] = $property['id'];
      $this->user_data['account_id'] = $property['accountId'];
      $this->user_data['web_property_name'] = $property['name'];

      update_option('gawd_user_data', $this->user_data);

      $this->get_custom_dimensions_from_ga(null, null, true);


      $this->get_goals_list_from_ga($property['id'], $property['accountId']);

      if($add_notice) {
        GAWD_helper::add_notice(__('User data refreshed successfully.', 'gawd'), 'success');
      }
      $this->send_response(true);
    }

    update_option('gawd_user_data', $this->user_data);
    $this->send_response(false);
  }

  /**
   * @param $reset_cache boolean true for getting profiles from api
   * @return null|array profiles array or null
   * */
  private function get_management_accounts($reset_cache = false){
    $accounts = get_option('gawd_management_accounts');

    if(is_array($accounts) && !empty($accounts) && $reset_cache === false) {
      return $accounts;
    }

    $accounts = $this->gawd_google->get_management_accounts();
    if($accounts === null) {
      update_option('gawd_management_accounts', array());
      return null;
    }

    $accounts_light = array();
    foreach($accounts as $account) {
      $edit_flag = false;
      $permissions = $account['permissions']['effective'];
      foreach($permissions as $permission) {
        if($permission == 'EDIT') {
          $edit_flag = true;
        }
      }
      $accounts_light[] = array(
        'name' => $account['name'],
        'id' => $account['id'],
        'edit_permissions' => $edit_flag
      );
    }

    update_option('gawd_management_accounts', $accounts_light);
    return $accounts_light;
  }

  /**
   * @param $reset_cache boolean true for getting profiles from api
   * @return null|array profiles array or null
   * */
  private function get_properties($reset_cache = false){
    $properties = get_option('gawd_properties');

    if(is_array($properties) && $reset_cache === false) {
      return $properties;
    }

    $properties = $this->gawd_google->get_properties();

    if($properties == null) {
      update_option('gawd_properties', array());
    } else {
      update_option('gawd_properties', $properties);
    }
    return $properties;
  }

  /**
   * @param $reset_cache boolean true for getting profiles from api
   * @return null|array profiles array or null
   * */
  private function get_profiles($reset_cache = false){
    $profiles = get_option('gawd_user_profiles');

    if(is_array($profiles) && $reset_cache === false) {
      return $profiles;
    }

    $profiles = $this->gawd_google->get_profiles();

    if($profiles !== null) {
      set_transient(
        'gawd_user_profiles', '1',
        $this->expiration_time['profiles']
      );
      update_option('gawd_user_profiles', $profiles);
    }

    return $profiles;
  }

  private function save_settings(){
    include "gawd-save-settings-goals.php";
    $post = $this->request_data['form'];
    gawd_save_settings($post, $this);
    gawd_save_tracking($post, $this);
    GAWD_helper::add_notice(__('Your changes have been saved successfully.', 'gawd'), 'success');
    $this->send_response(true);
  }

  private function save_goals(){
    include "gawd-save-settings-goals.php";
    $post = $this->request_data['form'];
    if(gawd_save_goals($post, $this)) {
      //      $this->gawd_get_management_goals_from_ga(true, true);

      GAWD_helper::add_notice(__('Your changes have been saved successfully.', 'gawd'), 'success');
      $this->send_response(true);
    } else {
      GAWD_helper::add_notice(__('Something went wrong. Please try again', 'gawd'), 'error');
      $this->send_response(false);
    }

  }

  public function delete_management_filter($remove_filter){
    $this->gawd_google->delete_management_filter($remove_filter);
  }

  public function add_management_filter($gawd_filter_name, $gawd_filter_type, $gawd_filter_value){
    $this->gawd_google->add_management_filter($gawd_filter_name, $gawd_filter_type, $gawd_filter_value);
  }

  public function add_custom_dimension($name, $id){
    $add_custom_dimension = $this->gawd_google->add_custom_dimension($name, $id);
    if($add_custom_dimension) {
      $dim = $this->get_custom_dimensions_from_ga(null, null, true);
    }
    return $add_custom_dimension;
  }

  private function get_custom_dimensions(){
    $dimensions = $this->get_custom_dimensions_from_ga();
    if($dimensions === null) {
      $this->send_response(false);
    } else {
      $this->send_response(true, '', '', $dimensions);
    }
  }

  public function get_custom_dimensions_from_ga($web_property_id = null, $account_id = null, $reset_cache = false){
    if(isset($this->request_data["is_last_viewed_profile"]) && $this->request_data["is_last_viewed_profile"] == "1") {
      $is_last_viewed_profile = GAWD_helper::get_last_viewed_profile();
      $web_property_id = $is_last_viewed_profile["web_property_id"];
      $account_id = $is_last_viewed_profile["account_id"];
    } else if($web_property_id == null) {
      $web_property_id = $this->user_data['property_id'];
      $account_id = $this->user_data['account_id'];
    }

    $transient = get_transient('gawd-custom-dimensions-' . $web_property_id);
    $custom_dimensions = get_option('gawd-custom-dimensions-' . $web_property_id);

    if(is_array($custom_dimensions) && $transient === '1' && $reset_cache === false) {
      return $custom_dimensions;
    }

    $dimensions = $this->gawd_google->get_custom_dimensions($web_property_id, $account_id);

    if($dimensions === null) {
      set_transient(
        'gawd-custom-dimensions-' . $web_property_id,
        '1',
        $this->expiration_time['custom_dimensions']
      );
      return null;
    }

    set_transient('gawd-custom-dimensions-' . $web_property_id, '1', $this->expiration_time['custom_dimensions']);
    update_option('gawd-custom-dimensions-' . $web_property_id, $dimensions);

    if($web_property_id === $this->user_data['property_id']) {

      $supported_dimensions = GAWD_helper::get_supported_dimensions();
      $gawd_dimensions = array();

      foreach($dimensions as $dimension) {
        foreach($supported_dimensions as $supported_dimension) {
          if(trim(strtolower($dimension['name'])) == strtolower($supported_dimension)) {
            $dimension['name'] = $supported_dimension;
            $dimension['id'] = substr($dimension['id'], -1);
            $gawd_dimensions[] = $dimension;
          }
        }
      }

      update_option('gawd_custom_dimensions', $gawd_dimensions);
    }

    return $dimensions;
  }

  private function choose_property(){
    $selected_property = (!empty($this->request_data['gawd_property'])) ? $this->request_data['gawd_property'] : "";

    if(empty($selected_property)) {
      $this->send_response(false, 'wrong_data', 'Wrong data.');
    }

    $properties = GAWD_helper::get_current_site_properties(true);

    foreach($properties as $property) {
      if($property['id'] == $selected_property) {

        $this->user_data['profile_id'] = $property['defaultProfileId'];
        $this->user_data['property_id'] = $property['id'];
        $this->user_data['account_id'] = $property['accountId'];
        $this->user_data['web_property_name'] = $property['name'];

        update_option('gawd_user_data', $this->user_data);

        $this->gawd_google->refresh_user_data($this->user_data);
        $this->get_custom_dimensions_from_ga($property['id'], $property['accountId'], true);
        $this->get_goals_list_from_ga($property['id'], $property['accountId']);

        $this->send_response(true);
      }
    }
    $this->send_response(false);
  }

  private function add_property(){

    $gawd_account_select = isset($this->request_data['gawd_account_select']) ? $this->request_data['gawd_account_select'] : '';
    $gawd_property_name = isset($this->request_data['gawd_property_name']) ? $this->request_data['gawd_property_name'] : '';

    if(empty($gawd_account_select) || empty($gawd_property_name)) {
      $this->send_response(false, 'wrong_data', 'Wrong data.');
    }

    $added_profile = $this->gawd_google->add_property($gawd_account_select, $gawd_property_name);
    if($added_profile === null) {
      $this->send_response(false);
    }

    $this->user_data['profile_id'] = $added_profile[0]['id'];
    $this->user_data['property_id'] = $added_profile[0]['webPropertyId'];
    $this->user_data['account_id'] = $added_profile[0]['accountId'];
    $this->user_data['web_property_name'] = $added_profile[0]['name'];
    update_option("gawd_user_data", $this->user_data);

    $this->refresh_user_info();
    $this->send_response(true);
  }

  public function get_real_time_data($reset = false){

    $option_name = 'gawd-chart-data-real_time_' . $this->gawd_google->get_profile_id(false);

    $option = get_option($option_name);
    $transient = get_transient($option_name);

    if($option !== false && $transient !== false && $reset === false) {
      $this->send_response(true, '', '', array('real_time_data' => $option));
    }

    $value = $this->gawd_google->get_real_time_data();
    update_option($option_name, $value);
    set_transient($option_name, '1', $this->expiration_time['real_time']);
    if($value !== null) {
      $this->send_response(true, '', '', array('real_time_data' => $value));
    } else {
      $this->send_response(false, 'real_time_error', '');
    }
  }

  public function gawd_show_data($reset = false, $params = ""){
    $return = true;
    if($params == '') {
      $params = $this->request_data;
      $return = false;
    }
    $start_date = isset($params["start_date"]) && $params["start_date"] != '' ? $params["start_date"] : date('Y-m-d', strtotime('-7 days'));
    $end_date = isset($params["end_date"]) && $params["end_date"] != '' ? $params["end_date"] : date('Y-m-d');
    $metric = isset($params["metric"]) ? $params["metric"] : 'ga:sessions';
    $metric = is_array($metric) ? count($metric) > 1 ? implode(",", $metric) : $metric[0] : $metric;
    $dimension = isset($params["dimension"]) ? $params["dimension"] : 'date';

    $country_filter = isset($params["country_filter"]) ? $params["country_filter"] : '';
    $geo_type = isset($params["geo_type"]) ? $params["geo_type"] : '';
    $filter_type = isset($params["filter_type"]) && $params["filter_type"] != '' ? $params["filter_type"] : '';
    $custom = isset($params["custom"]) && $params["custom"] != '' ? $params["custom"] : '';
    $same_dimension = $dimension;
    $dimension = $filter_type != '' && $dimension == 'date' ? $filter_type : $dimension;

    if($dimension == 'week' || $dimension == 'month') {
      $same_dimension = $dimension;
    }
    $timezone = isset($params["timezone"]) && $params["timezone"] != '' ? $params["timezone"] : 0;
    $return_data = null;
    if($dimension == 'pagePath' || $dimension == 'PagePath' || $dimension == 'landingPagePath' || $dimension == 'LandingPagePath') {
      $transient_key = $this->get_transient_key("chart-data", array($dimension, $start_date, $end_date, $timezone));

      $grid_data_transient = get_transient($transient_key);
      if($grid_data_transient && $reset === false) {
        $grid_data = $grid_data_transient;
      } else {
        $grid_data = $this->gawd_google->get_page_data($dimension, $start_date, $end_date, $timezone);
        if(empty($grid_data)) {
          $grid_data = array();
        }
        set_transient($transient_key, $grid_data, $this->expiration_time['chart_data']);
      }
      $return_data = $grid_data;

    } elseif($dimension == 'goals') {
      $transient_key = $this->get_transient_key("chart-data", array('date', $start_date, $end_date, $timezone, $same_dimension));

      $goal_data_transient = get_transient($transient_key);
      if($goal_data_transient  && $reset === false) {
        $goal_data = $goal_data_transient;
      } else {
        $goal_data = $this->gawd_google->get_goal_data('date', $start_date, $end_date, $timezone, $same_dimension);
        if(empty($goal_data)) {
          $goal_data = array();
        }
        set_transient($transient_key, $goal_data, $this->expiration_time['chart_data']);
      }
      $return_data = $goal_data;
    } elseif($custom == '' && (($dimension == 'region' || $dimension == 'city') || ($dimension == 'Region' || $dimension == 'City'))) {
      $transient_key = $this->get_transient_key("chart-data", array($metric, $dimension, $start_date, $end_date, $country_filter, $geo_type, $timezone));

      $chart_data_transient = get_transient($transient_key);
      if($chart_data_transient && $reset === false) {
        $chart_data = $chart_data_transient;
      } else {
        $chart_data = $this->gawd_google->get_country_data($metric, $dimension, $start_date, $end_date, $country_filter, $geo_type, $timezone);
        if(empty($chart_data)) {
          $chart_data = array();
        }
        set_transient($transient_key, $chart_data, $this->expiration_time['chart_data']);
      }
      $return_data = $chart_data;
    } else {
      if($custom != '') {
        $transient_key = $this->get_transient_key("chart-data", array($metric, $dimension, $start_date, $end_date, $filter_type, $timezone, $same_dimension));

        $chart_data_transient = get_transient($transient_key);
        if($chart_data_transient && $reset === false) {
          $chart_data = $chart_data_transient;
        } else {
          $chart_data = $this->gawd_google->get_data($metric, $dimension, $start_date, $end_date, $filter_type, $timezone, $same_dimension);
          if(empty($chart_data)) {
            $chart_data = array();
          }
          set_transient($transient_key, $chart_data, $this->expiration_time['chart_data']);
        }
        $return_data = $chart_data;
      } else {
        if($dimension == 'siteSpeed') {
          $transient_key = $this->get_transient_key("chart-data", array($metric, $dimension, $start_date, $end_date, $filter_type, $timezone, $same_dimension));

          $siteSpeed_transient = get_transient($transient_key);
          if($siteSpeed_transient && $reset === false) {
            $chart_data = $siteSpeed_transient;
          } else {
            $chart_data = $this->gawd_google->get_data($metric, $dimension, $start_date, $end_date, $filter_type, $timezone, $same_dimension);
            if(empty($chart_data)) {
              $chart_data = array();
            }
            set_transient($transient_key, $chart_data, $this->expiration_time['chart_data']);
          }
          $return_data = $chart_data;
        } else {
          $transient_key = $this->get_transient_key("chart-data", array($metric, $dimension, $start_date, $end_date, $filter_type, $timezone, $same_dimension));

          $chart_data_transient = get_transient($transient_key);
          if($chart_data_transient && $reset === false) {
            $chart_data = $chart_data_transient;
          } else {
            $chart_data = $this->gawd_google->get_data($metric, $dimension, $start_date, $end_date, $filter_type, $timezone, $same_dimension);
            if(empty($chart_data) || isset($chart_data['error_message'])) {
              $chart_data = array();
            }
            set_transient($transient_key, $chart_data, $this->expiration_time['chart_data']);
          }
          $return_data = $chart_data;
        }
      }
    }

    if($return_data != null) {
      if($return) {
        return $return_data;
      }
      $error_code = "";
      $error_msg = "";
      $request_success = true;
      if(isset($return_data["error_message"])) {
        $request_success = false;
        $error_msg = $return_data["error_message"];
      }
      $this->send_response($request_success, 'show_data_error', $error_msg, array('gawd_reports_data' => json_encode($return_data)));
    }
    $this->send_response(false);
  }

  public function gawd_show_page_post_data(){
    $gawd_client = $this->gawd_google;
    $profileId = $gawd_client->get_profile_id();
    $start_date = isset($this->request_data["start_date"]) && $this->request_data["start_date"] != '' ? $this->request_data["start_date"] : date('Y-m-d', strtotime('-30 days'));
    $end_date = isset($this->request_data["end_date"]) && $this->request_data["end_date"] != '' ? $this->request_data["end_date"] : date('Y-m-d');
    $metric = isset($this->request_data["metric"]) ? $this->request_data["metric"] : 'ga:sessions';
    $metric = is_array($metric) ? count($metric) > 1 ? implode(",", $metric) : $metric[0] : $metric;
    $dimension = isset($this->request_data["dimension"]) ? $this->request_data["dimension"] : 'date';
    $timezone = isset($this->request_data["timezone"]) ? $this->request_data["timezone"] : 0;
    if(isset($this->request_data["filter"])) {
      $filter = str_replace(get_home_url(), "", $this->request_data["filter"]);

    } else {
      $filter = '/';
    }
    $chart = isset($this->request_data["chart"]) ? $this->request_data["chart"] : '';

    $transient_key = $this->get_transient_key("chart-data-page-post", array(false, $metric, $dimension, $start_date, $end_date, $filter, $timezone, $chart));

    $chart_data_transient = get_transient($transient_key);
    $error_message = "";
    $request_success = true;


    if($chart_data_transient) {
      $chart_data = $chart_data_transient;
    } else {
      $chart_data = $gawd_client->get_post_page_data(false, $metric, $dimension, $start_date, $end_date, $filter, $timezone, $chart);
      if(empty($chart_data)) {
        $chart_data = array();
      }
      if(isset($chart_data["error_message"]) && !empty($chart_data["error_message"])) {
        $error_message = $chart_data["error_message"];
        $request_success = false;
        $chart_data = array();
      }
      set_transient($transient_key, $chart_data, $this->expiration_time['chart_data']);
    }
    if($request_success === true) {
      $this->send_response(true, '', '', array('gawd_page_post_data' => json_encode($chart_data)));
    } else {
      $this->send_response(false, 'gawd_page_post_error', $error_message, array('gawd_page_post_data' => json_encode(array())));
    }

  }

  public function get_widget_data(){
    $instance = $this->request_data;
    $gawd_client = $this->gawd_google;
    $gawd_widget_default_date = $instance["gawd_widget_default_date"];
    $gawd_widget_report_data = $instance["gawd_widget_report_data"];

    switch($gawd_widget_default_date) {
      case 'all_days':
        $start_date = date('Y-m-d', "2000-02-09");
        $end_date = date('Y-m-d', strtotime('-1 day'));
        break;
      case 'last_30days':
        $start_date = date('Y-m-d', strtotime('-31 day'));
        $end_date = date('Y-m-d', strtotime('-1 day'));
        break;
      case 'last_7days':
        $start_date = date('Y-m-d', strtotime('-8 day'));
        $end_date = date('Y-m-d', strtotime('-1 day'));
        break;
      case 'last_week':
        $start_date = date('Y-m-d', strtotime('last week -1day'));
        $end_date = date('Y-m-d', strtotime('last week +5day'));
        break;
      case 'this_month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-d', strtotime('-1 day'));
        break;
      case 'last_month':
        $start_date = date('Y-m-01', strtotime('last month'));
        $end_date = date('Y-m-t', strtotime('last month'));
        break;
      case 'today':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        break;
      case 'yesterday':
        $start_date = date('Y-m-d', strtotime('-1 day'));
        $end_date = date('Y-m-d', strtotime('-1 day'));
        break;
      default:
        $start_date = date('Y-m-d', strtotime('-8 day'));
        $end_date = date('Y-m-d', strtotime('-1 day'));
        break;
    }

    $metric = 'ga:sessions';
    $dimension = 'date';
    $timezone = 0;
    $filter = str_replace(get_home_url(), "", $instance["gawd_location_href"]);
    if($gawd_widget_report_data == "site_data") {
      $filter = "/";
    }

    $transient_key = $this->get_transient_key("chart-data-page-post", array(false, $metric, $dimension, $start_date, $end_date, $filter, $timezone));

    $chart_data_transient = get_transient($transient_key);
    $error_message = "";
    $request_success = true;


    if($chart_data_transient) {
      $chart_data = $chart_data_transient;
    } else {
      $chart_data = $gawd_client->get_post_page_data(false, $metric, $dimension, $start_date, $end_date, $filter, $timezone);
      if(empty($chart_data)) {
        $chart_data = array();
      }
      if(isset($chart_data["error_message"]) && !empty($chart_data["error_message"])) {
        $error_message = $chart_data["error_message"];
        $request_success = false;
        $chart_data = array();
      }
      set_transient($transient_key, $chart_data, $this->expiration_time['chart_data']);
    }
    $this->send_response($request_success, '', $error_message, array('gawd_page_post_data' => json_encode($chart_data)));
    die();
  }


  public function add_management_goal($gawd_goal_profile, $goal_id, $gawd_goal_type, $gawd_goal_name, $gawd_goal_duration_comparison, $value){
    $result = $this->gawd_google->add_management_goal($gawd_goal_profile, $goal_id, $gawd_goal_type, $gawd_goal_name, $gawd_goal_duration_comparison, $value);

    $user_data = GAWD_helper::get_user_data();
    $data = $this->get_goals_list_from_ga($user_data['property_id'], $user_data['account_id']);

    if($result === false && isset($data[$gawd_goal_profile])) {
      $profile_goals = array($gawd_goal_profile => $data[$gawd_goal_profile]);
      $next_goal_id = GAWD_helper::get_next_goal_id($profile_goals);
      if(isset($next_goal_id[$gawd_goal_profile])) {
        $goal_id = $next_goal_id[$gawd_goal_profile];
        $result = $this->gawd_google->add_management_goal($gawd_goal_profile, $goal_id, $gawd_goal_type, $gawd_goal_name, $gawd_goal_duration_comparison, $value);
        if($result === true) {
          $data = $this->get_goals_list_from_ga($user_data['property_id'], $user_data['account_id']);
        }
      }
    }

    return $result;
  }

  public function gawd_show_data_compact(){
    $gawd_client = $this->gawd_google;
    $start_date = isset($this->request_data["start_date"]) && $this->request_data["start_date"] != '' ? $this->request_data["start_date"] : date('Y-m-d', strtotime('-30 days'));
    $end_date = isset($this->request_data["end_date"]) && $this->request_data["end_date"] != '' ? $this->request_data["end_date"] : date('Y-m-d');
    $metric = isset($this->request_data["metric"]) ? $this->request_data["metric"] : 'sessions';
    $metric = is_array($metric) ? count($metric) > 1 ? implode(",", $metric) : $metric[0] : 'ga:' . $metric;
    $dimension = isset($this->request_data["dimension"]) ? $this->request_data["dimension"] : 'date';
    $timezone = isset($this->request_data["timezone"]) ? $this->request_data["timezone"] : 0;

    $transient_key = $this->get_transient_key('chart-data-compact', array($metric, $dimension, $start_date, $end_date, $timezone));
    $gawd_chart_data_compact = get_transient($transient_key);
    $request_success = true;
    $error_message = "";
    if(false && $gawd_chart_data_compact) {
      $chart_data = $gawd_chart_data_compact;
    } else {
      $chart_data = $gawd_client->get_data_compact($metric, $dimension, $start_date, $end_date, $timezone);
      if(empty($chart_data)) {
        $chart_data = array();
      }
      if(isset($chart_data["error_message"]) && !empty($chart_data["error_message"])) {
        $error_message = $chart_data["error_message"];
        $request_success = false;
        $chart_data = array();
      }
      set_transient($gawd_chart_data_compact, $chart_data, $this->expiration_time['chart_data']);
    }
    if($request_success === true) {
      $this->send_response($request_success, '', '', array('gawd_show_data_compact' => json_encode($chart_data)));
    } else {
      $this->send_response(false, 'show_data_compact_error', $error_message);
    }
  }

  public function gawd_get_management_goals($reset_data = false){
    $goals = $this->gawd_get_management_goals_from_ga($reset_data);

    $request_success = true;
    if(!is_array($goals)) {
      $goals = array();
      $request_success = false;
    }

    $this->send_response($request_success, '', '', array('gawd_goals_data' => json_encode($goals)));
  }

  public function gawd_get_management_goals_from_ga($reset_data = false, $profile = null){
    if($profile === null) {
      $profileId = $this->gawd_google->get_profile_id();
    } else {
      $profileId = $profile['id'];
    }

    $gawd_goals = get_transient('gawd-goals-' . $profileId);
    $gawd_goals_data_option = get_option('gawd-goals-' . $profileId);

    if($gawd_goals_data_option !== false && $gawd_goals === "1" && $reset_data === false) {
      return $gawd_goals_data_option;
    }

    $new_management_goals = $this->gawd_google->get_management_goals($profile);

    if($new_management_goals !== null) {
      update_option('gawd-goals-' . $profileId, $new_management_goals);

      $return_data = $new_management_goals;
    } else {
      if(is_array($gawd_goals_data_option)) {
        $return_data = $gawd_goals_data_option;
      } else {
        $return_data = array();
      }
    }

    set_transient(
      'gawd-goals-' . $profileId,
      "1",
      $this->expiration_time['goal_data']
    );
    return $return_data;
  }


  //for default property
  public function get_goals_list_from_ga($property_id, $account_id){
    $goals = $this->gawd_google->get_goals_list($property_id, $account_id);

    if($goals === null) {
      return false;
    }

    if(empty($goals)) {
      update_option('gawd_goals_data', array());
      return $goals;
    }

    $goals_light = array();

    foreach($goals as $goal) {
      $caseSensitive = '';

      if($goal->getType() == 'URL_DESTINATION') {
        $type = 'Destination';
        $details = $goal->getUrlDestinationDetails();

        if($details->getMatchType() == 'EXACT') {
          $match_type = 'Equals';
        } elseif($details->getMatchType() == 'HEAD') {
          $match_type = 'Begin with';
        } else {
          $match_type = 'Regular expresion';
        }

        $value = $details->getUrl();
        $caseSensitive = $details->getCaseSensitive();

      } elseif($goal->getType() == 'VISIT_TIME_ON_SITE') {
        $type = 'Duration';
        $details = $goal->getVisitTimeOnSiteDetails();

        $match_type = $details->getComparisonType();
        $value = $details->getComparisonValue();

        $hours = strlen(floor($value / 3600)) < 2 ? '0' . floor($value / 3600) : floor($value / 3600);
        $mins = strlen(floor($value / 60 % 60)) < 2 ? '0' . floor($value / 60 % 60) : floor($value / 60 % 60);
        $secs = strlen(floor($value % 60)) < 2 ? '0' . floor($value % 60) : floor($value % 60);
        $value = $hours . ':' . $mins . ':' . $secs;

      } else if($goal->getType() === "VISIT_NUM_PAGES") {
        $type = 'Pages/Screens per session';
        $details = $goal->getVisitNumPagesDetails();

        $match_type = $details->getComparisonType();
        $value = $details->getComparisonValue();
      } else {
        continue;
      }

      $profile_id = $goal->getProfileId();
      if(!isset($goals_light[$profile_id])) {
        $goals_light[$profile_id] = array();
      }

      $goals_light[$profile_id][] = array(
        'name' => $goal->getName(),
        'id' => $goal->getId(),
        'type' => $type,
        'match_type' => $match_type,
        'profileID' => $profile_id,
        'caseSensitive' => $caseSensitive,
        'value' => $value
      );

    }


    update_option('gawd_goals_data', $goals_light);
    return $goals_light;
  }

  private function delete_credentials(){
    GAWD_helper::delete_user_data();
    update_option('gawd_credentials', GAWD_helper::get_project_default_credentials());
    $this->send_response(true);
  }


  private function parse_data(){
    $this->action = (!empty($_REQUEST['gawd_action'])) ? sanitize_text_field($_REQUEST['gawd_action']) : "";
    $this->request_data = (!empty($_REQUEST['gawd_data'])) ? GAWD_helper::validate_string('gawd_data', array()) : array();
    $this->nonce_data = (!empty($_REQUEST['gawd_nonce_data'])) ? GAWD_helper::validate_string('gawd_nonce_data', array()) : array();
    if(isset($_REQUEST['gawd_view_id'])){
      $this->view_id = GAWD_helper::validate_string($_REQUEST['gawd_view_id']);
    }
  }

  private function check_permission(){
    $gawd_settings = GAWD_helper::get_settings();

    $actions_for_view = array(
      'gawd_show_data',
      'gawd_show_post_page_data',
      'gawd_show_data_compact',
      'get_real_time_data'
    );

    if(isset($this->nonce_data['action'])) {

      if($this->nonce_data['action'] === 'gawd_ajax_front') {
        $gawd_frontend_roles = isset($gawd_settings['gawd_frontend_roles']) ? $gawd_settings['gawd_frontend_roles'] : array();

        if(
          wp_verify_nonce($this->nonce_data['nonce'], 'gawd_ajax_front') !== false &&
          GAWD_helper::check_permission($gawd_frontend_roles) &&
          $this->action === "gawd_show_post_page_data"
        ) {
          return true;
        } else {
          return false;
        }

      } else if($this->nonce_data['action'] === 'gawd_post_pages') {
        $gawd_post_page_roles = isset($gawd_settings['gawd_post_page_roles']) ? $gawd_settings['gawd_post_page_roles'] : array();
        if(
          wp_verify_nonce($this->nonce_data['nonce'], 'gawd_post_pages') !== false &&
          GAWD_helper::check_permission($gawd_post_page_roles) &&
          $this->action === "gawd_show_post_page_data"
        ) {
          return true;
        } else {
          return false;
        }

      } else if($this->nonce_data['action'] === 'gawd_dashboard') {
        $gawd_backend_roles = isset($gawd_settings['gawd_backend_roles']) ? $gawd_settings['gawd_backend_roles'] : array();
        if(
          wp_verify_nonce($this->nonce_data['nonce'], 'gawd_dashboard') !== false &&
          GAWD_helper::check_permission($gawd_backend_roles) &&
          $this->action === "gawd_show_data" ||
          $this->action === "get_real_time_data"
        ) {
          return true;
        } else {
          return false;
        }
      } else if($this->nonce_data['action'] === 'gawd_analytics_pages') {

        $gawd_permissions = isset($gawd_settings['gawd_backend_roles']) ? $gawd_settings['gawd_backend_roles'] : array();
        if(
        (
          wp_verify_nonce($this->nonce_data['nonce'], 'gawd_analytics_pages') !== false &&
          GAWD_helper::check_permission($gawd_permissions)
        ) ||
        (
          isset($_REQUEST['gawd_cron_token']) &&
          GAWD_helper::check_cron_token(sanitize_text_field($_REQUEST['gawd_cron_token']))
        )
        ) {
          return true;
        } else {
          return false;
        }
      } else if($this->nonce_data['action'] === 'gawd_widget_view') {
        if(
          wp_verify_nonce($this->nonce_data['nonce'], 'gawd_widget_view') !== false &&
          $this->action === "get_widget_data"
        ) {
          return true;
        } else {
          return false;
        }
      }

    }
    return false;
  }

  private function verify_nonce(){

    if(empty($_REQUEST['gawd_nonce'])) {
      $this->send_response(false, 'wrong_nonce');
    }

    include_once ABSPATH . 'wp-includes/pluggable.php';
    if(wp_verify_nonce($_REQUEST['gawd_nonce'], 'gawd_custom_ajax') === false) {
      $this->send_response(false, 'wrong_nonce');
    }

  }

  private function include_libs(){
    if(!class_exists("Google_Client")) {
      require_once(GAWD_DIR . '/google/vendor/autoload.php');
    }

    require_once('gawd-google-class.php');
    require_once(GAWD_DIR . '/gawd_class.php');
  }

  /**
   * Print response and die()
   * @param $success boolean
   * @param $error_code string
   * @param $error_msg string
   * @param $data array
   */
  public function send_response($success = true, $error_code = "", $error_msg = "", $data = array()){

    if(!is_array($data)) {
      $data = array($data);
    }

    $response = array(
      'success' => $success,
      'error' => array(
        'code' => $error_code,
        'msg' => $error_msg
      ),
      'data' => $data
    );

    $response = json_encode($response);
    die($response);
  }

  private function get_transient_key($prefix, $args){
    return "gawd-" . $prefix . "-" . $this->gawd_google->get_profile_id(false) . "-" . md5(json_encode($args));
  }

  private function set_expiration_time(){
    $this->expiration_time = array(
      'profiles' => 60,
      'real_time' => 5,
      'custom_dimensions' => 12 * 60 * 60,
      'chart_data' => 24 * 60 * 60,
      'goal_data' => 24 * 60 * 60,
      'refresh_user_info' => 2 * 60 * 60
    );
  }

  private function set_gawd_google($tokens = null){
    if($tokens !== null) {
      $this->gawd_google = new GAWD_google($tokens['access_token'], $tokens['refresh_token'], $this);
    } else if(!empty($this->user_data['access_token'])) {
      $this->gawd_google = new GAWD_google($this->user_data['access_token'], $this->user_data['refresh_token'], $this);
    }
    if($this->view_id !== null) {
      $this->gawd_google->set_profile_info($this->view_id);
    }
  }

}