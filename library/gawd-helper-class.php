<?php

class GAWD_helper {

  private static $profiles = null;
  private static $management_accounts = null;
  private static $properties = null;
  private static $current_site_properties = null;
  private static $user_data = null;
  private static $custom_dimensions = null;
  private static $project_credentials = null;
  private static $settings = null;

  public static function get_profiles($reset = false){
    if(self::$profiles === null || $reset === true) {
      $option = get_option('gawd_user_profiles');
      self::$profiles = (is_array($option)) ? $option : array();
    }
    return self::$profiles;
  }

  public static function get_properties($reset = false){
    if(self::$properties === null || $reset === true) {
      $option = get_option('gawd_properties');

      $properties = array();
      if(is_array($option)) {
        foreach($option as $item) {
          $properties[] = (array)$item;
        }
      }

      self::$properties = $properties;
    }
    return self::$properties;
  }

  public static function get_current_site_properties($reset = false){
    if(self::$current_site_properties === null || $reset === true) {
      $web_properties = self::get_properties($reset);

      $exact_properties = array();
      $site_url = get_site_url() . '/';

      foreach($web_properties as $web_property) {
        $current_url = $web_property['websiteUrl'];
        if(($current_url == $site_url) || (($current_url . '/') == $site_url)) {
          $exact_properties[] = $web_property;
        }
      }

      self::$current_site_properties = $exact_properties;
    }
    return self::$current_site_properties;
  }

  public static function get_custom_dimensions($reset = false){
    if(self::$custom_dimensions === null || $reset === true) {
      $option = get_option('gawd_custom_dimensions');
      self::$custom_dimensions = (is_array($option)) ? $option : array();
    }
    return self::$custom_dimensions;
  }

  public static function get_management_accounts($reset = false){
    if(self::$management_accounts === null || $reset === true) {
      $option = get_option('gawd_management_accounts');
      self::$management_accounts = (is_array($option)) ? $option : array();
    }
    return self::$management_accounts;
  }

  public static function get_property_profiles($property_id, $reset = false){

    $all_profiles = self::get_profiles($reset);
    $property_profiles = array();

    foreach($all_profiles as $key => $profiles) {
      foreach($profiles as $profile) {
        if($profile['webPropertyId'] === $property_id) {
          $property_profiles[] = $profile;
        }
      }
    }
    return $property_profiles;
  }

  public static function get_account_name_by_profile_id($profile_id, $reset = false){
    $profiles = self::get_profiles($reset);

    foreach($profiles as $name => $profile) {
      foreach($profile as $pr) {
        if($pr['id'] === $profile_id){
          return $name;
        }
      }
    }

    return "";
  }

  public static function get_property($property_id){
    $properties = self::get_properties();
    foreach($properties as $property) {
      if($property['id'] === $property_id) {
        return $property;
      }
    }
    return null;
  }

  public static function get_profile_goals($profile_id){
    $option = get_option('gawd-goals-' . $profile_id);
    if(is_array($option)) {
      return $option;
    } else {
      return array();
    }
  }

  public static function get_user_data(){
    if(self::$user_data === null) {
      $option = get_option('gawd_user_data');
      self::$user_data = (is_array($option)) ? $option : array();
    }
    return self::$user_data;
  }

  public static function get_user_status(){
    $user_data = self::get_user_data();
    return (empty($user_data['refresh_token'])) ? '0' : '1';
  }

  public static function get_project_default_credentials(){
    $credentials = array(
      'project_id' => '115052745574-5vbr7tci4hjkr9clkflmnpto5jisgstg.apps.googleusercontent.com',
      'project_secret' => 'wtNiu3c_bA_g7res6chV0Trt'
    );
    return $credentials;
  }

  public static function get_project_credentials($reset = false){
    if(self::$project_credentials === null || $reset === true) {
      $project_credentials = get_option('gawd_credentials');
      if(!is_array($project_credentials)) {
        $project_credentials = array();
      }
      $default_credentials = self::get_project_default_credentials();

      if(
        empty($project_credentials) ||
        empty($project_credentials['project_id']) ||
        empty($project_credentials['project_secret'])
      ) {
        $project_credentials['project_id'] = "115052745574-5vbr7tci4hjkr9clkflmnpto5jisgstg.apps.googleusercontent.com";
        $project_credentials['project_secret'] = "wtNiu3c_bA_g7res6chV0Trt";
        $project_credentials['default'] = true;
      } else if(
        $project_credentials['project_id'] === $default_credentials['project_id'] &&
        $project_credentials['project_secret'] === $default_credentials['project_secret']
      ) {
        $project_credentials['default'] = true;
      } else {
        $project_credentials['default'] = false;
      }
      self::$project_credentials = $project_credentials;
    }

    return self::$project_credentials;
  }

  public static function write_log_into_file($log){
    $log .= "--->" . date('Y-m-d H:i:s');
    $file_path = GAWD_UPLOAD_DIR . "/logfile.txt";;
    $fh = fopen($file_path, 'a');
    fwrite($fh, $log . PHP_EOL);
    fclose($fh);
  }

  public static function get_settings(){
    if(self::$settings == null) {
      $settings = get_option('gawd_settings');
      if(!is_array($settings)) {
        $settings = array();
      }
      self::$settings = $settings;
    }
    return self::$settings;
  }

  public static function check_permission($roles){
    $user = wp_get_current_user();

    if(in_array('administrator', $user->roles)) {
      return true;
    }

    foreach($user->roles as $role) {
      if(in_array($role, $roles)) {
        return true;
      }
    }
    return false;
  }

  public static function gawd_user_data_updated($value, $old_value){

    if(empty($value['profile_id'])) {
      delete_site_transient('gawd_user_data');
    } else {
      set_site_transient('gawd_user_data', 24 * 60 * 60);
    }

    return $value;
  }

  public static function gawd_has_property(){
    $user_data = self::get_user_data();
    return (!empty($user_data['property_id']));
  }

  public static function get_next_goal_id($profiles_goals){
    $data = array();
    foreach($profiles_goals as $profile_id => $goals) {

      for($i = 1; $i <= 20; $i++) {

        $exists = false;
        foreach($goals as $goal) {
          if($i == $goal['id']) {
            $exists = true;
            break;
          }
        }

        if($exists === false) {
          $data[$profile_id] = $i;
          break;
        }

      }

    }

    return $data;
  }

  public static function delete_user_data(){
    delete_option("gawd_user_data");
    delete_option("gawd_user_status");
    delete_option("gawd_management_accounts");
    delete_option("gawd_properties");
    delete_option("gawd_user_profiles");
    delete_option("widget_gawd_widget");
    delete_option("gawd_last_viewed_profile");
    delete_option("gawd_custom_dimensions");
    delete_option("gawd_goals_data");
  }

  public static function delete_reports_data(){
    global $wpdb;
    $wpdb->get_results("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%gawd-chart-data%'", OBJECT);
    $wpdb->get_results("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%gawd-goals-%'", OBJECT);
    $wpdb->get_results("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%gawd-custom-dimensions-%'", OBJECT);
  }

  /**
   * @return boolean true if authenticated and has accounts, or false
   * */
  public static function gawd_is_ready(){
    $user_data = self::get_user_data();
    $accounts = self::get_management_accounts();
    if(!empty($user_data['refresh_token']) && !empty($accounts)) {
      return true;
    }
    return false;
  }

  /**
   * Checks if the protocol is secure.
   *
   * @return boolean
   */
  public static function is_ssl(){
    if(isset($_SERVER['HTTPS'])) {
      if('on' == strtolower($_SERVER['HTTPS'])) {
        return true;
      }
      if('1' == $_SERVER['HTTPS']) {
        return true;
      }
    } elseif(isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
      return true;
    }

    return false;
  }

  /**
   * @param $msg string
   * @param $status string success|error
   * */
  public static function add_notice($msg, $status, $class = ""){
    $notices = get_option('gawd_notices');
    if(!is_array($notices)) {
      $notices = array();
    }
    $notices[] = array(
      'msg' => $msg,
      'status' => $status,
      'class' => $class
    );
    update_option('gawd_notices', $notices);
  }

  public static function get_notices($remove = true){
    $notices = get_option('gawd_notices');
    if(!is_array($notices)) {
      $notices = array();
    }

    if($remove === true) {
      delete_option('gawd_notices');
    }
    return $notices;
  }

  /**
   * @return string post type or empty string
   * */
  public static function get_post_type(){
    if(is_singular()) {
      $post_type = get_post_type(get_the_ID());
      if($post_type !== false) {
        return $post_type;
      }
    }

    return "";
  }

  /**
   * @return string post type or empty string
   * */
  public static function get_author_nickname(){

    if(is_singular()) {
      if(have_posts()) {
        while(have_posts()) {
          the_post();
        }
      }
      $name = get_the_author_meta('user_nicename');
      return trim($name);
    }

    return "";
  }

  /**
   * @return string post type or empty string
   * */
  public static function get_categories(){

    if(is_single()) {
      $categories = get_the_category(get_the_ID());

      if($categories) {
        $category_names = array();
        foreach($categories as $category) {
          $category_names[] = $category->slug;
        }

        return implode(',', $category_names);
      }
    }

    return "";
  }

  /**
   * @return string post type or empty string
   * */
  public static function get_published_month(){
    if(is_singular()) {
      return get_the_date('M-Y');
    }
    return "";
  }

  /**
   * @return string post type or empty string
   * */
  public static function get_tags(){
    if(is_single()) {
      $tag_names = 'untagged';

      $tags = get_the_tags(get_the_ID());

      if($tags) {
        $tag_names = implode(',', wp_list_pluck($tags, 'name'));
      }

      return $tag_names;
    }

    return "";
  }

  /**
   * @return string post type or empty string
   * */
  public static function get_published_year(){
    if(is_singular()) {
      return get_the_date('Y');
    }

    return "";
  }

  public static function get_supported_dimensions(){
    $supported_dimensions = array(
      "Logged in",
      "Post type",
      "Author",
      "Category",
      "Tags",
      "Published Month",
      "Published Year"
    );
    return $supported_dimensions;
  }

  public static function get_last_viewed_profile(){
    $gawd_last_viewed_profile = get_option("gawd_last_viewed_profile");
    $user_data = self::get_user_data();
    if(isset($gawd_last_viewed_profile) && $gawd_last_viewed_profile) {
      return $gawd_last_viewed_profile;
    } else if(!empty($user_data['account_id'])) {
      $gawd_last_viewed_profile = array(
        'profile_id' => $user_data['profile_id'],
        'web_property_name' => $user_data['web_property_name'],
        'web_property_id' => $user_data['property_id'],
        'account_id' => $user_data['account_id']
      );
      update_option('gawd_last_viewed_profile', $gawd_last_viewed_profile);
      return $gawd_last_viewed_profile;
    } else {
      $profiles_list = self::get_profiles();
      foreach($profiles_list as $name => $profiles) {
        if(isset($profiles) && isset($profiles[0]) && isset($profiles[0]["id"])) {
          $gawd_last_viewed_profile = array(
            'profile_id' => $profiles[0]["id"],
            'web_property_name' => $name,
            'web_property_id' => $profiles[0]["webPropertyId"],
            'account_id' => $profiles[0]["accountId"]
          );
          update_option('gawd_last_viewed_profile', $gawd_last_viewed_profile);
          return $gawd_last_viewed_profile;
        }
      }
    }
  }

  public static function ajax_request($args, $view_id = null){

    $custom_ajax_nonce = wp_create_nonce('gawd_custom_ajax');
    $gawd_ajax_nonce_data = array(
      'action' => $args['gawd_nonce_data']['action'],
      'nonce' => wp_create_nonce($args['gawd_nonce_data']['action']),
    );

    $args['gawd_nonce'] = $custom_ajax_nonce;
    $args['gawd_nonce_data'] = $gawd_ajax_nonce_data;
    if($view_id !== null) {
      $args['gawd_view_id'] = $view_id;
    }


    $cron_token = self::add_cron_token();
    $args['gawd_cron_token'] = $cron_token;
    $args['gawd_refresh_report_cache'] = '1';

    $args = array(
      'body' => $args,
      'cookies' => $_COOKIE
    );

    $data = wp_remote_get(admin_url('admin-ajax.php'), $args);

    self::remove_cron_token($cron_token);

    if(is_wp_error($data)) {
      GAWD_logs::add('ajax_request_' . uniqid(), json_encode($data->get_error_messages()));
      return false;
    }

    $data = json_decode($data['body'], true);
    if(!is_array($data)) {
      GAWD_logs::add('ajax_request_' . uniqid(), 'json decode error.');
      return false;
    }

    if(!isset($data['success']) || $data['success'] === false) {
      GAWD_logs::add('ajax_request_' . uniqid(), 'wrong ajax request.'.json_encode($data));
      return false;
    }

    if(!isset($data['data']['gawd_reports_data'])) {
      GAWD_logs::add('ajax_request_' . uniqid(), 'something went wrong.');
      return false;
    }

    return json_decode($data['data']['gawd_reports_data'], true);
  }

  public static function get_unique_string(){
    return md5(uniqid('gawd') . time());
  }

  private static function add_cron_token(){
    $gawd_cron_tokens = get_option('gawd_cron_tokens');
    if(!is_array($gawd_cron_tokens)) {
      $gawd_cron_tokens = array();
    }

    $token = self::get_unique_string();
    $gawd_cron_tokens[] = $token;
    update_option('gawd_cron_tokens', $gawd_cron_tokens);
    return $token;
  }

  public static function check_cron_token($token){
    $gawd_cron_tokens = get_option('gawd_cron_tokens');
    if(is_array($gawd_cron_tokens) && in_array($token, $gawd_cron_tokens)) {
      return true;
    }
    return false;
  }

  public static function remove_cron_token($token){
    $gawd_cron_tokens = get_option('gawd_cron_tokens');

    if(is_array($gawd_cron_tokens)) {

      $index = null;
      foreach($gawd_cron_tokens as $k => $gawd_cron_token) {

        if($token === $gawd_cron_token) {
          $index = $k;
          break;
        }

      }

      unset($gawd_cron_tokens[$index]);
      update_option('gawd_cron_tokens', $gawd_cron_tokens);

    }

  }

  public static function print_pro_popup(){ ?>
      <div class="gawd_pro_popup_overlay" style="display: none;"></div>
      <div class="gawd_pro_popup" style="display: none;">
          <div class="gawd_pro_popup_close_btn"></div>
          <div class="gawd_pro_popup_container">
              <div class="gawd_pro_popup_title gawd_pro_popup_section">
                  <div class="gawd_pro_popup_title1"></div>
                  <div class="gawd_pro_popup_title2"></div>
              </div>
              <div class="gawd_pro_popup_content gawd_pro_popup_section">
                  <div>Premium Plan includes:</div>
                  <ul></ul>
              </div>
              <div class="gawd_pro_popup_section gawd_pro_popup_button">
                  <a target="_blank"
                     href="https://10web.io/plugins/wordpress-google-analytics/?utm_source=10web_analytics&utm_medium=free_plugin">
                      UPGRADE
                  </a>
              </div>
          </div>
      </div>
  <?php }

  public static function validate_string($key , $default=""){
    if (isset($_GET[$key])) {
      $data = $_GET[$key];
    }
    elseif (isset($_POST[$key])) {
      $data = $_POST[$key];
    }
    elseif (isset($_REQUEST[$key])) {
      $data = $_REQUEST[$key];
    }
    else {
      $data = $default;
    }
    if(is_array($data)){
      $arr_data = array_map(array('GAWD_helper','tw_sanitize_text'), $data );
      return $arr_data;
    }
    $return_data = sanitize_text_field($data);
    return $return_data;
  }

  public static function tw_sanitize_text($val){
    if(is_array($val)){
      return self::validate_string_array($val);
    }
    return sanitize_text_field($val);
  }


  public static function validate_string_array($data){
    if(is_array($data)){
      $arr_data = array_map(array("GAWD_helper", "tw_sanitize_text"), $data );
      return $arr_data;
    }
    $return_data = sanitize_text_field($data);
    return $return_data;
  }

}

