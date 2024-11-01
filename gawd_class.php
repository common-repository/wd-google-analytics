<?php

class GAWD {

  /**
   * @var GAWD The reference to Singleton instance of this class
   */
  private static $instance;
  private $gawd_pages = array();
  private $view_permission = false;
  private $settings_permission = false;
  public $redirect_uri = "urn:ietf:wg:oauth:2.0:oob";

  /**
   * Protected constructor to prevent creating a new instance of the
   * Singleton via the `new` operator from outside of this class.
   */
  protected function __construct(){
    if(get_site_transient('gawd_uninstall') === '1'){
      return;
    }

    if(!extension_loaded('openssl')) {
      add_action('admin_notices', array($this, 'nossl_message'), 1);
      return;
    }

    if(!is_dir(GAWD_UPLOAD_DIR)) {
      mkdir(GAWD_UPLOAD_DIR, 0777);
    }

    if(get_option('gawd_version') !== GAWD_VERSION){
      self::activate();
    }

    add_action('init', array($this, 'register_hooks'), 1);

    add_action('wp_head', array($this, 'gawd_tracking_code'), 99);
    $gawd_settings = GAWD_helper::get_settings();
    $gawd_user_data = GAWD_helper::get_user_data();
    $gawd_user_status = GAWD_helper::get_user_status();

    if(
      isset($gawd_user_data['refresh_token']) &&
      ($gawd_user_data['refresh_token'] != '') &&
      (isset($gawd_settings['gawd_tracking_enable']) &&
        $gawd_settings['gawd_tracking_enable'] == 'on')
    ) {
      require_once(GAWD_DIR . '/widgets.php');
    }

    $gawd_post_page_roles = isset($gawd_settings['gawd_post_page_roles']) ? $gawd_settings['gawd_post_page_roles'] : array();

    if(
      GAWD_helper::gawd_is_ready() &&
      (isset($gawd_settings['gawd_tracking_enable']) && $gawd_settings['gawd_tracking_enable'] == 'on') &&
      (isset($gawd_settings['post_page_chart']) && $gawd_settings['post_page_chart'] != '') &&
      GAWD_helper::check_permission($gawd_post_page_roles) &&
      (isset($gawd_user_data['refresh_token']) && ($gawd_user_data['refresh_token'] != ''))
      && !empty($gawd_user_data['property_id'])) {

      add_filter('manage_posts_columns', array($this, 'gawd_add_columns'));
      // Populate custom column in Posts List
      add_action('manage_posts_custom_column', array($this, 'gawd_add_icons'), 10, 2);
      // Add custom column in Pages List
      add_filter('manage_pages_columns', array($this, 'gawd_add_columns'));
      // Populate custom column in Pages List
      add_action('manage_pages_custom_column', array($this, 'gawd_add_icons'), 10, 2);
    }

    $gawd_frontend_roles = isset($gawd_settings['gawd_frontend_roles']) ? $gawd_settings['gawd_frontend_roles'] : array();

    if(
      GAWD_helper::gawd_is_ready() &&
      (isset($gawd_settings['gawd_tracking_enable']) && $gawd_settings['gawd_tracking_enable'] == 'on') &&
      GAWD_helper::check_permission($gawd_frontend_roles) &&
      (isset($gawd_user_data['refresh_token']) && ($gawd_user_data['refresh_token'] != '')) &&
      GAWD_helper::get_user_status() &&
      !empty($gawd_user_data['property_id'])
    ) {
      add_action('wp_enqueue_scripts', array($this, 'gawd_front_scripts'));
      add_action('admin_bar_menu', array($this, 'report_adminbar'), 999);
    }

  }

  public function admin_notices(){
    $notices = GAWD_helper::get_notices();

    foreach($notices as $notice) {
      $this->gawd_admin_notice($notice['msg'], $notice['status'], $notice['class']);
    }


    $user_data = GAWD_helper::get_user_data();
    $web_property_id = (isset($user_data['property_id'])) ? $user_data['property_id'] : "";
    $screen = get_current_screen();
    $accounts = GAWD_helper::get_management_accounts();
    $gawd_props = GAWD_helper::get_current_site_properties();

    if(count($gawd_props) === 0 && empty($web_property_id) && strpos($screen->base, 'gawd') !== false && !empty($accounts)) {
      $msg = "10Web Analytics: You haven't created a web-property with current site URL, or it has been deleted. Please <a href='" . admin_url() . "admin.php?page=gawd_settings#gawd_tracking_tab'>create </a> one.";
      $this->gawd_admin_notice($msg, "error", 'gawd_tracking_notice_link');
    }
  }

  function get_current_user_role(){
    global $wp_roles;
    if(is_user_logged_in()) {
      $current_user = wp_get_current_user();
      $roles = $current_user->roles;
      $role = array_shift($roles);

      return $role;
    } else {
      return "";
    }
  }

  function report_adminbar($wp_admin_bar){
    /* @formatter:off */
    $gawd_settings = get_option('gawd_settings');
    $gawd_frontend_roles = isset($gawd_settings['gawd_frontend_roles']) ? $gawd_settings['gawd_frontend_roles'] : array();
    $roles = $this->get_current_user_role();
    $id = intval(get_the_ID());
    if(((in_array($roles, $gawd_frontend_roles) || current_user_can('manage_options')) && !is_admin()) && $gawd_settings['post_page_chart'] != '' && $id > 0) {

      $uri_parts = get_post($id);
      $uri = '/' . $uri_parts->post_name;

      $filter = rawurlencode(rawurldecode($uri));

      $args = array(
        'id' => 'gawd',
        'title' => '<span data-url="' . $filter . '" class="ab-icon"></span><span class="">' . __("Analytics by 10Web", 'gawd') . '</span>',
        //'href' => '#1',
      );
      /* @formatter:on */
      $wp_admin_bar->add_node($args);
    }
  }

  function register_hooks(){

    if(is_admin() && get_option('gawd_upgrade_plugin') === '1'){
        add_action('admin_footer', array($this,'upgrade_plugin'));
    }

    $this->gawd_last_viewed_profile();

    $gawd_settings = GAWD_helper::get_settings();

    $gawd_permissions = isset($gawd_settings['gawd_permissions']) ? $gawd_settings['gawd_permissions'] : array();
    $gawd_overview_permissions = isset($gawd_settings['gawd_backend_roles']) ? $gawd_settings['gawd_backend_roles'] : array();
    $this->view_permission = GAWD_helper::check_permission($gawd_overview_permissions);
    $this->settings_permission = GAWD_helper::check_permission($gawd_permissions);




    if($this->view_permission || $this->settings_permission) {
      add_action('admin_menu', array($this, 'gawd_add_menu'), 9);
      add_action('admin_menu', array($this, 'remove_first_menu'), 10);
    }

    add_action('admin_enqueue_scripts', array($this, 'gawd_enqueue_scripts'));


    //todo    add_action('wp_ajax_create_pdf_file', array($this, 'create_pdf_file'));
    add_action('wp_ajax_gawd_create_csv_file', array($this, 'create_csv_file'));
    add_action('wp_ajax_gawd_send_email', array($this, 'send_email'));

    add_action('wp_ajax_show_data', array($this, 'show_data'));
    add_action('wp_ajax_remove_zoom_message', array($this, 'remove_zoom_message'));
    add_action('wp_ajax_show_data_compact', array($this, 'show_data_compact'));
    if(GAWD_helper::gawd_is_ready()){
        add_action('wp_dashboard_setup', array($this, 'google_analytics_wd_dashboard_widget'));
    }
    add_action('admin_menu', array($this, 'overview_date_meta'));
    //todo      add_action('admin_init', array($this, 'gawd_export'));
    //		add_action( 'gawd_pushover_daily', array( $this, 'gawd_pushover_daily' ) );
    //		add_action( 'gawd_pushover_gawd_weekly', array( $this, 'gawd_pushover_weekly' ) );
    //		add_action( 'gawd_pushover_gawd_monthly', array( $this, 'gawd_pushover_monthly' ) );
//    add_action('gawd_alert_daily', array($this, 'gawd_alert_daily'));
//    add_action('gawd_alert_gawd_monthly', array($this, 'gawd_alert_monthly'));
//    add_action('gawd_alert_gawd_weekly', array($this, 'gawd_alert_weekly'));
    add_action('admin_notices', array($this, 'admin_notices'), 9999);

    if(!wp_next_scheduled("gawd_email_scheduled")) {
      wp_schedule_single_event( time(), 'gawd_email_scheduled' );
    }
    add_action('gawd_email_scheduled', array($this, 'gawd_email_scheduled'));
  }

  public function gawd_last_viewed_profile(){
    $gawd_profile_data = array();
    if(isset($_POST['gawd_id'])) {
      $profiles = GAWD_helper::get_profiles();
      if(is_array($profiles)) {
        foreach($profiles as $profile) {
          if(!empty($profile)) {
            foreach($profile as $item) {

              if($item["id"] === $_POST['gawd_id']) {
                $gawd_profile_data['profile_id'] = isset($_POST['gawd_id']) ? sanitize_text_field($_POST['gawd_id']) : '';
                $gawd_profile_data['web_property_name'] = isset($_POST['web_property_name']) ? sanitize_text_field($_POST['web_property_name']) : '';
                $gawd_profile_data['web_property_id'] = isset($item["webPropertyId"]) ? $item["webPropertyId"] : '';
                $gawd_profile_data['account_id'] = isset($item["accountId"]) ? $item["accountId"] : '';
                update_option('gawd_last_viewed_profile', $gawd_profile_data);
                break;
              }

            }
          }
        }
      }
    }
  }

  public function gawd_add_icons($column, $id){
    if($column != 'gawd_stats') {
      return;
    }
    $uri_parts = get_post($id);
    $uri = '/' . $uri_parts->post_name;

    $filter = rawurlencode(rawurldecode($uri));
    $permalink = get_permalink($id, false);
    echo '<a id="gawd-' . $id . '" class="gawd_page_post_stats" title="' . get_the_title($id) . '" href="#' . $filter . '" data-permalink="' . $permalink . '"><img  src="' . GAWD_URL . '/assets/back_logo.png"</a>';
  }

  public function gawd_add_columns($columns){
    return array_merge($columns, array('gawd_stats' => __('Analytics by 10Web', 'gawd')));
  }

  public static function gawd_roles($access_level, $tracking = false){
    if(is_user_logged_in() && isset($access_level)) {
      $current_user = wp_get_current_user();
      $roles = (array)$current_user->roles;
      if((current_user_can('manage_options')) && !$tracking) {
        return true;
      }
      if(count(array_intersect($roles, $access_level)) > 0) {
        return true;
      } else {
        return false;
      }
    }

    return false;
  }

  public function gawd_tracking_code(){
    $gawd_user_data = GAWD_helper::get_user_data();
    if(empty($gawd_user_data['property_id'])) {
      return;
    }

    require_once(GAWD_DIR . '/admin/tracking.php');
  }

  public function create_pdf_file($ajax = true, $data = null, $dimension = null, $start_date = null, $end_date = null, $metric_compare_recc = null, $metric_recc = null){
    $first_data = isset($_REQUEST["first_data"]) ? sanitize_text_field($_REQUEST["first_data"]) : '';
    $_data_compare = isset($_REQUEST["_data_compare"]) ? sanitize_text_field($_REQUEST["_data_compare"]) : '';
    if($ajax == true) {
      $export_type = isset($_REQUEST["export_type"]) ? sanitize_text_field($_REQUEST["export_type"]) : '';
      if($export_type != 'pdf') {
        return;
      }

      $report_type = isset($_REQUEST["report_type"]) ? sanitize_text_field($_REQUEST["report_type"]) : '';


      if($report_type !== 'alert') {
        return;
      }

    }

    include_once GAWD_DIR . '/include/gawd_pdf_file.php';
    $file = new GAWD_PDF_FILE();

    /*
                require_once(GAWD_DIR . '/admin/gawd_google_class.php');
                $this->gawd_google_client = GAWD_google_client::get_instance();
        */
    $file->get_request_data($this, $ajax, $data, $dimension, $start_date, $end_date, $metric_compare_recc, $metric_recc);

    $file->sort_data();
    if($first_data != '') {
      $file->create_file('pages');
    } elseif(($_data_compare) != '') {
      $file->create_file('compare');
    } else {
      $file->create_file(true);
    }
    if($ajax == true) {
      die();
    } else {
      return $file->file_dir;
    }
  }

  public function create_csv_file(){
    $response = array();
    if(!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'gawd_admin_page_nonce')) {
      $response['error']['code'] = 'wrong_nonce';
      $response['error']['msg'] = 'wrong_nonce';
    }
    else {
      $csv_response = $this->generate_csv_file();
      $response['error'] = (!empty($csv_response['error'])) ? $csv_response['error'] : '';
      $response['success'] = (!empty($csv_response['success'])) ? $csv_response['success'] : '';
      $response['data'] = (!empty($csv_response['data'])) ? $csv_response['data'] : '';
    }
    die(json_encode($response));
  }


  public function save_alert($alert_data){

    include_once 'library/gawd-alert-class.php';
    $alert = new GAWD_alert($alert_data);
    $alert->add_email_data_to_POST();

    $this->send_email(false);
  }


  public function send_email($die = true){
    if($die !== false) {
      $die = true;
    }

    $response = array(
      'success' => false,
      'error' => array('code' => '', 'msg' => ''),
      'data' => array('msg' => 'Something went wrong')
    );

    if(!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'gawd_admin_page_nonce')) {
      $response['error']['code'] = 'wrong_nonce';
      $response['error']['msg'] = 'wrong_nonce';

      if($die == true) {
        die(json_encode($response));
      } else {
        return $response;
      }
    }
    else {
      include_once 'library/gawd-email-class.php';
      $email = new GAWD_email();
      if($email->parse_ajax_data() === false) {
        $response['error'] = $email->get_error();
        if($die == true) {
          die(json_encode($response));
        } else {
          return $response;
        }
      }

      $file_response = $this->generate_csv_file();
      if($file_response['success'] === false) {

        $response = array(
          'success'=> $file_response['success'],
          'error' => $file_response['error'],
          'data' => $file_response['data']
        );

        if($die == true) {
          die(json_encode($response));
        } else {
          return $response;
        }
      }


      if($email->get_period() !== 'once') {
        $email->save_email_info($file_response['ajax_args'], $file_response['csv_generator']);

        $response['success'] = true;
        $response['data']['msg'] = 'Email successfully Scheduled </br> Go to <a href="admin.php?page=gawd_settings#gawd_emails_tab">Settings page</a> to delete scheduled e-mails.';
        if($die == true) {
          die(json_encode($response));
        } else {
          return $response;
        }
      }

      $email->attach_file($file_response['csv_generator']);
      if($email->send_mail() === true){
        $response['success'] = true;
        $response['data']['msg'] = 'Email successfully sent.';
        if($die == true) {
          die(json_encode($response));
        } else {
          return $response;
        }
      }else{
        $response['error']['code'] = 'fail_to_sent_email';
        $response['error']['code'] = 'Fail to sent email.';

        if($die == true) {
          die(json_encode($response));
        } else {
          return $response;
        }
      }
    }
  }

  private function generate_csv_file($data = array(), $view_id = null, $ajax_response = array()){
    $response = array(
      'success' => false,
      'error' => array('code' => 'something_went_wrong', 'msg' => 'something went wrong.'),
      'data' => ''
    );

    if(!empty($data)) {
      $gawd_request_last_args = $data['last_args'];
      $compare_by = $data['compare_by'];
      $gawd_compare_request_last_args = $data['compare_last_args'];
      $menu_name = $data['menu_name'];
      $info = $data['info'];
    } else {
      $gawd_request_last_args = GAWD_helper::validate_string('gawd_request_last_args', array());
      $compare_by = sanitize_text_field($_POST['compare_by']);
      $gawd_compare_request_last_args = (!empty($_POST['gawd_compare_request_last_args'])) ? sanitize_text_field($_POST['gawd_compare_request_last_args']) : null;
      $info = GAWD_helper::validate_string("info", array());
      $menu_name = sanitize_text_field($_POST['menu_name']);
    }//else set defaults

    if(is_array($ajax_response) && !empty($ajax_response)) {
      $data = $ajax_response;
    } else {
      $data = GAWD_helper::ajax_request($gawd_request_last_args, $view_id);
      if($data === false) {
        return $response;
      }
    }

    if($gawd_compare_request_last_args !== null) {

      $compare_data = GAWD_helper::ajax_request($gawd_compare_request_last_args, $view_id);

      if($compare_data === false) {
        return $response;
      }

    } else {
      $compare_data = null;
    }

    include_once 'library/file-generators/gawd-csv-generator-class.php';
    $csv = new GAWD_csv_file_generator();
    $csv->parse_data($data, $gawd_request_last_args, $compare_data, $gawd_compare_request_last_args, $compare_by);
    if(!empty($menu_name)) {
      $csv->set_menu_name(sanitize_text_field($menu_name));
    }

    $site_title = "";
    if($view_id !== null) {
      $site_title = GAWD_helper::get_account_name_by_profile_id($view_id);
    } else {
      $last_viewed_profile = GAWD_helper::get_last_viewed_profile();
      if(isset($last_viewed_profile['web_property_name'])) {
        $site_title = $last_viewed_profile['web_property_name'];
      }
    }

    if(!empty($site_title)) {
      $csv->set_site_title($site_title);
    }

    $csv->generate();

    $response['success'] = true;
    $response['error'] = array();
    $response['data'] = array('download_url' => $csv->get_file_url());
    $response['csv_generator'] = $csv;
    $response['ajax_args'] = array(
      'last_args' => $gawd_request_last_args,
      'compare_last_args' => $gawd_compare_request_last_args,
      'compare_by' => $compare_by,
      'menu_name' => $menu_name,
      'info' => $info
    );

    return $response;
  }

  public function gawd_email_scheduled(){

    $gawd_emails_info = get_option('gawd_emails_info');
    if(empty($gawd_emails_info)) {
      return;
    }

    include_once 'library/gawd-email-class.php';

    $now = time();
    $new_dates = array();

    foreach($gawd_emails_info as $i => $email_info) {

      if($now < $email_info['next_date']) {
        continue;
      }

      $new_dates[] = $i;

      $email = new GAWD_email();
      $email->set_email_info($email_info['email_info']);

      if($email->check_condition($email_info) === false) {
        continue;
      }

      $email->add_content();

      $file_response = $this->generate_csv_file($email_info['ajax_args'], $email_info['email_info']['view_id'], $email->get_ajax_response());

      if($file_response['success'] === false) {
        continue;
      }

      $email->attach_file($file_response['csv_generator']);
      $email->send_mail();
    }

    if(!empty($new_dates)) {

      foreach($new_dates as $i => $index) {
        $info = GAWD_email::calc_next_date($gawd_emails_info[$index]);
        $info = GAWD_email::calc_date_range($info);
        $gawd_emails_info[$index] = $info;
      }

      update_option('gawd_emails_info', $gawd_emails_info);

      $this->gawd_email_scheduled();
      return;
    } else {
      GAWD_email::set_new_scheduled();
    }

  }

  public static function get_domain($domain){
    $root = explode('/', $domain);
    $ret_domain = str_ireplace('www', '', isset($root[2]) ? $root[2] : $domain);

    return $ret_domain;
  }

  public static function error_message($type, $message){
    echo '<div style="width:99%"><div class="' . $type . '"><p><strong>' . $message . '</strong></p></div></div>';
  }

  public function gawd_export(){
    if(!isset($_REQUEST['action']) || (isset($_REQUEST['action']) && $_REQUEST['action'] !== 'gawd_export')) {
      return;
    }

    $export_type = isset($_REQUEST["export_type"]) ? sanitize_text_field($_REQUEST["export_type"]) : '';
    if($export_type != 'pdf' && $export_type != 'csv') {
      return;
    }

    $report_type = isset($_REQUEST["report_type"]) ? sanitize_text_field($_REQUEST["report_type"]) : '';

    require_once(GAWD_DIR . '/admin/gawd_google_class.php');
    $this->gawd_google_client = GAWD_google_client::get_instance();

    if($export_type == 'pdf') {
      include_once GAWD_DIR . '/include/gawd_pdf_file.php';
      $file = new GAWD_PDF_FILE();
    } else {
      include_once GAWD_DIR . '/include/gawd_csv_file.php';
      $file = new GAWD_CSV_FILE();
    }

    if($report_type == 'alert') {
      if($export_type == 'pdf') {
        $file->export_file();
      } else {
        $file->export_file();
      }
    } else {
      $metric = isset($_REQUEST["gawd_metric"]) ? sanitize_text_field($_REQUEST["gawd_metric"]) : '';
      $_data_compare = isset($_REQUEST["_data_compare"]) ? sanitize_text_field($_REQUEST["_data_compare"]) : '';
      $first_data = isset($_REQUEST["first_data"]) ? sanitize_text_field($_REQUEST["first_data"]) : '';
      $view_id = isset($_REQUEST["view_id"]) ? sanitize_text_field($_REQUEST["view_id"]) : '';
      $metric_compare = isset($_REQUEST["gawd_metric_compare"]) ? sanitize_text_field($_REQUEST["gawd_metric_compare"]) : '';
      $dimension = isset($_REQUEST["gawd_dimension"]) ? sanitize_text_field($_REQUEST["gawd_dimension"]) : '';
      $tab_name = isset($_REQUEST["tab_name"]) ? sanitize_text_field($_REQUEST["tab_name"]) : '';
      $img = isset($_REQUEST["img"]) ? sanitize_text_field($_REQUEST["img"]) : '';
      $gawd_email_subject = isset($_REQUEST["gawd_email_subject"]) ? sanitize_text_field($_REQUEST["gawd_email_subject"]) : '';
      $gawd_email_body = isset($_REQUEST["gawd_email_body"]) && $_REQUEST["gawd_email_body"] != '' ? sanitize_text_field($_REQUEST["gawd_email_body"]) : ' ';
      $email_from = isset($_REQUEST["gawd_email_from"]) ? sanitize_email($_REQUEST["gawd_email_from"]) : '';
      $email_to = isset($_REQUEST["gawd_email_to"]) ? sanitize_email($_REQUEST["gawd_email_to"]) : '';
      $email_period = isset($_REQUEST["gawd_email_period"]) ? sanitize_text_field($_REQUEST["gawd_email_period"]) : '';
      $week_day = isset($_REQUEST["gawd_email_week_day"]) ? sanitize_text_field($_REQUEST["gawd_email_week_day"]) : '';
      $month_day = isset($_REQUEST["gawd_email_month_day"]) ? sanitize_text_field($_REQUEST["gawd_email_month_day"]) : '';
      $email_time = isset($_REQUEST["email_time"]) ? sanitize_text_field($_REQUEST["email_time"]) : '';
      $emails = array();
      $invalid_email = false;
      $email_to = explode(',', $email_to);
      foreach($email_to as $email) {
        if(is_email($email) == false) {
          $emails = $email;
        }
      }
      if(count($emails) > 0) {
        $invalid_email = true;
      }
      if(($invalid_email != true) && is_email($email_from) && $gawd_email_subject != '') {
        if($email_period == "once") {
          $file->get_request_data($this);
          $file->sort_data();
          if($export_type == 'csv') {
            if($first_data != '') {
              $file->create_file(false);
            } else {
              $file->create_file();
            }
          } else {
            if($first_data != '') {
              $file->create_file('pages');
            } elseif(($_data_compare) != '') {
              $file->create_file('compare');
            } else {
              $file->create_file(false);
            }
          }
          $attachment = $file->file_dir;

          if($report_type == 'email') {
            $headers = 'From: <' . $email_from . '>';
            wp_mail($email_to, $gawd_email_subject, $gawd_email_body, $headers, $attachment);
          }
          echo json_encode(array('status' => 'success', 'msg' => 'Email successfuly sent'));
        } else {
          if($email_period == 'gawd_weekly') {
            $period_day = $week_day;
            $timestamp = strtotime('this ' . $period_day . ' ' . $email_time);
          } elseif($email_period == 'gawd_monthly') {
            $period_day = $month_day;
            $timestamp = strtotime(date('Y-m-' . $period_day . ' ' . $email_time));
          } else {
            $period_day = '';
            $timestamp = strtotime(date('Y-m-d ' . $email_time));
          }
          $saved_email = get_option('gawd_email');
          if($saved_email) {
            $gawd_email_options = array(
              'name' => $gawd_email_subject,
              'period' => $email_period,
              'metric' => $metric,
              'metric_compare' => $metric_compare,
              'dimension' => $dimension,
              'creation_date' => date('Y-m-d') . ' ' . $email_time,
              'emails' => $email_to,
              'email_from' => $email_from,
              'email_subject' => $gawd_email_subject,
              'email_body' => $gawd_email_body,
              'period_day' => $period_day,
              'period_time' => $email_time,
              'img' => $img,
              'tab_name' => $tab_name,
              'view_id' => $view_id,
              'export_type' => $export_type
            );
            $saved_email[] = $gawd_email_options;
            update_option('gawd_email', $saved_email);
          } else {
            $gawd_email_options = array(
              0 => array(
                'name' => $gawd_email_subject,
                'period' => $email_period,
                'metric' => $metric,
                'metric_compare' => $metric_compare,
                'dimension' => $dimension,
                'creation_date' => date('Y-m-d') . ' ' . $email_time,
                'emails' => $email_to,
                'email_from' => $email_from,
                'email_subject' => $gawd_email_subject,
                'email_body' => $gawd_email_body,
                'period_day' => $period_day,
                'period_time' => $email_time,
                'img' => $img,
                'tab_name' => $tab_name,
                'view_id' => $view_id,
                'export_type' => $export_type
              )
            );
            update_option('gawd_email', $gawd_email_options);
          }
          $saved_email = get_option('gawd_email');
          if($saved_email) {
            foreach($saved_email as $email) {
              if(!wp_next_scheduled('gawd_email_' . $email['period'])) {
                wp_schedule_event($timestamp, $email['period'], 'gawd_email_' . $email['period']);
              }
            }
          }
          $success_message = 'Email successfuly Scheduled </br> Go to <a href="' . admin_url() . 'admin.php?page=gawd_settings#gawd_emails_tab">Settings page</a> to delete scheduled e-mails.';
          echo json_encode(array('status' => 'success', 'msg' => $success_message));
        }

        die;
      } else {
        if($invalid_email == true) {
          echo json_encode('Invalid email');
          die;
        } else if($gawd_email_subject == '') {
          echo json_encode("Can't send email with empty subject");
          die;
        }
      }
    }
  }

  public function overview_date_meta($screen = null, $context = 'advanced'){
    //righ side wide meta..
    $orintation = wp_is_mobile() ? 'side' : 'normal';
    add_meta_box('gawd-real-time', __('Real Time', 'gawd'), array(
      $this,
      'gawd_real_time'
    ), 'gawd_analytics', 'side', 'high');
    add_meta_box('gawd-date-meta', __('Audience', 'gawd'), array(
      $this,
      'gawd_date_box'
    ), 'gawd_analytics', $orintation, null);
    add_meta_box('gawd-country-box', __('Location', 'gawd'), array(
      $this,
      'gawd_country_box'
    ), 'gawd_analytics', $orintation, null);
    //left side thin meta.
    add_meta_box('gawd-visitors-meta', __('Visitors', 'gawd'), array(
      $this,
      'gawd_visitors'
    ), 'gawd_analytics', 'side', null);
    add_meta_box('gawd-browser-meta', __('Browsers', 'gawd'), array(
      $this,
      'gawd_browser'
    ), 'gawd_analytics', 'side', null);
  }

  public function gawd_date_box(){
    require_once('admin/pages/date.php');
  }

  public function gawd_country_box(){
    require_once('admin/pages/location.php');
  }

  public function gawd_real_time(){
    require_once('admin/pages/real_time.php');
  }

  public function gawd_visitors(){
    require_once('admin/pages/visitors.php');
  }

  public function gawd_browser(){
    require_once('admin/pages/browser.php');
  }


  /**
   * Enqueues the required styles and scripts, localizes some js variables.
   */
  public function gawd_front_scripts(){
    wp_enqueue_style('admin_css', GAWD_URL . '/inc/css/gawd_admin.css', false, GAWD_VERSION);
    /*wp_enqueue_script( 'date-js', GAWD_URL . '/inc/js/date.js', array( 'jquery' ), GAWD_VERSION );*/
    wp_enqueue_script('gawd_front_js', GAWD_URL . '/inc/js/gawd_front.js', array('jquery'), GAWD_VERSION);

    wp_enqueue_script('gawd_charts', GAWD_URL . '/inc/js/gawd_charts.js', array('gawd_plotly_basic'), GAWD_VERSION);
    wp_enqueue_script('gawd_plotly_basic', GAWD_URL . '/inc/js/plotly-basic.min.js', array('jquery'), GAWD_VERSION);

    wp_localize_script('gawd_front_js', 'gawd_front', array(
      'ajaxurl' => admin_url('admin-ajax.php'),
      'ajaxnonce' => wp_create_nonce('gawd_admin_page_nonce'),
      'gawd_plugin_url' => GAWD_URL,
      'date_30' => date('Y-m-d', strtotime('-31 day')) . '/-/' . date('Y-m-d', strtotime('-1 day')),
      'date_7' => date('Y-m-d', strtotime('-8 day')) . '/-/' . date('Y-m-d', strtotime('-1 day')),
      'date_last_week' => date('Y-m-d', strtotime('last week -1day')) . '/-/' . date('Y-m-d', strtotime('last week +5day')),
      'date_last_month' => date('Y-m-01', strtotime('last month')) . '/-/' . date('Y-m-t', strtotime('last month')),
      'date_this_month' => date('Y-m-01') . '/-/' . date('Y-m-d'),
      'date_today' => date('Y-m-d') . '/-/' . date('Y-m-d'),
      'date_yesterday' => date('Y-m-d', strtotime('-1 day')) . '/-/' . date('Y-m-d', strtotime('-1 day')),
      'wp_admin_url' => admin_url(),
      'gawd_custom_ajax_nonce' => wp_create_nonce('gawd_custom_ajax'),
      'gawd_custom_ajax_nonce_data' => array(
        'action' => 'gawd_ajax_front',
        'nonce' => wp_create_nonce('gawd_ajax_front'),
      ),
      'exportUrl' => add_query_arg(array('action' => 'gawd_export'), admin_url('admin-ajax.php'))
    ));
  }

  public function gawd_enqueue_scripts(){
    $options = get_option('gawd_settings');
    $default_date = (isset($options['default_date']) && $options['default_date'] != '') ? $options['default_date'] : 'last_30days';
    $default_date_format = (isset($options['default_date_format']) && $options['default_date_format'] != '') ? $options['default_date_format'] : 'ymd_with_week';
    $enable_hover_tooltip = (isset($options['enable_hover_tooltip']) && $options['enable_hover_tooltip'] != '') ? $options['enable_hover_tooltip'] : '';
    $screen = get_current_screen();

    $gawd_user_status = get_option('gawd_user_status');

    $is_gawd_page = in_array($screen->id, $this->gawd_pages);
    $is_dashboard = (strpos($screen->id, 'dashboard') !== false);
    $is_post_page = ($screen->base === "edit" && !empty($screen->post_type));
    $include_scripts_in_wp_admin_pages = (($is_post_page || strpos($screen->base, 'dashboard') !== false || strpos($screen->base, 'edit') !== false) && $gawd_user_status == '1');

    if(strpos($screen->base, 'gawd') !== false || $include_scripts_in_wp_admin_pages) {


      wp_enqueue_script('jquery');
      wp_enqueue_script('jquery-ui-widget');

      wp_enqueue_script('common');
      wp_enqueue_script('wp-lists');
      wp_enqueue_script('postbox');
      wp_enqueue_script('jquery-ui-tooltip');
      wp_enqueue_script('gawd_paging', GAWD_URL . '/inc/js/paging.js', array('jquery-ui-widget'), GAWD_VERSION);
      wp_enqueue_script('jquery.cookie', GAWD_URL . '/inc/js/jquery.cookie.js', false, GAWD_VERSION);
      wp_enqueue_style('timepicker_css', GAWD_URL . '/inc/css/jquery.timepicker.css', false, GAWD_VERSION);
      wp_enqueue_style('admin_css', GAWD_URL . '/inc/css/gawd_admin.css', false, GAWD_VERSION);
      wp_enqueue_style('gawd_dataTables_responsive_css', GAWD_URL . '/inc/css/responsive.dataTables.min.css', false, GAWD_VERSION);
      wp_enqueue_style('gawd_dataTables_ui_css', GAWD_URL . '/inc/css/dataTables.jqueryui.min.css', false, GAWD_VERSION);
      wp_enqueue_style('gawd_dataTables_base_ui_css', GAWD_URL . '/inc/css/dataTables-jquery-ui-base.css', false, GAWD_VERSION);
      wp_enqueue_style('font-awesome', GAWD_URL . '/inc/css/font_awesome.css', false, GAWD_VERSION);
      //wp_enqueue_style('jquery-ui.css', GAWD_URL . '/inc/css/jquery-ui.css', false, GAWD_VERSION);
      if(strpos($screen->post_type, 'page') === false && strpos($screen->post_type, 'post') === false && strpos($screen->base, 'edit') === false) {
        wp_enqueue_style('gawd_bootstrap', GAWD_URL . '/inc/css/bootstrap.css', false, GAWD_VERSION);
        wp_enqueue_style('gawd_bootstrap-chosen', GAWD_URL . '/inc/css/bootstrap-chosen.css', false, GAWD_VERSION);
        wp_enqueue_style('gawd_bootstrap-select', GAWD_URL . '/inc/css/bootstrap-select.css', false, GAWD_VERSION);
      }
      wp_enqueue_style('gawd_datepicker', GAWD_URL . '/inc/css/daterangepicker.css', false, GAWD_VERSION);
      wp_enqueue_script('gawd_moment', GAWD_URL . '/inc/js/moment.min.js', false, GAWD_VERSION);
      wp_enqueue_script('gawd_daterangepicker', GAWD_URL . '/inc/js/daterangepicker.js', false, GAWD_VERSION);
      /*Map*/
      wp_enqueue_script('gawd_map_chart', GAWD_URL . '/inc/js/gawd_map_chart.js', false, GAWD_VERSION);
      /*End Map*/
      wp_enqueue_script('rgbcolor.js', GAWD_URL . '/inc/js/rgbcolor.js', array('jquery'), GAWD_VERSION);
      wp_enqueue_script('StackBlur.js', GAWD_URL . '/inc/js/StackBlur.js', array('jquery'), GAWD_VERSION);
      wp_enqueue_script('canvg.js', GAWD_URL . '/inc/js/canvg.js', array('jquery'), GAWD_VERSION);
      wp_enqueue_script('gawd_tables', GAWD_URL . '/inc/js/loader.js', array('jquery'), GAWD_VERSION);
      wp_enqueue_script('date-js', GAWD_URL . '/inc/js/date.js', array('jquery'), GAWD_VERSION);
      wp_enqueue_script('timepicker_js', GAWD_URL . '/inc/js/jquery.timepicker.min.js', array('jquery'), GAWD_VERSION);
      wp_enqueue_script('admin_js', GAWD_URL . '/inc/js/gawd_admin.js', array('jquery','gawd_charts'), GAWD_VERSION);
      wp_enqueue_script('gawd_datatables_js', GAWD_URL . '/inc/js/jquery.dataTables.min.js', array('jquery','gawd_charts'), GAWD_VERSION);
      wp_enqueue_script('chosen.jquery.js', GAWD_URL . '/inc/js/chosen.jquery.js', array('jquery'), GAWD_VERSION);
      wp_enqueue_script('gawd_datatables_responsive_js', GAWD_URL . '/inc/js/dataTables.responsive.min.js', array('jquery','gawd_charts'), GAWD_VERSION);
      wp_enqueue_script('gawd_datatables_ui_js', GAWD_URL . '/inc/js/dataTables.jqueryui.min.js', array('jquery','gawd_charts'), GAWD_VERSION);

      if(strpos($screen->post_type, 'page') === false && strpos($screen->post_type, 'post') === false && strpos($screen->base, 'edit') === false) {
        wp_enqueue_script('bootstrap_js', GAWD_URL . '/inc/js/bootstrap.min.js', array('jquery'), '4.3.1');
        wp_enqueue_script('bootstrap-select', GAWD_URL . '/inc/js/bootstrap-select.js', array('jquery'), GAWD_VERSION);
      }
      wp_enqueue_script('highlight_js', GAWD_URL . '/inc/js/js_highlight.js', array('jquery'), GAWD_VERSION);
      wp_enqueue_script('settings_js', GAWD_URL . '/inc/js/gawd_settings.js', array('jquery'), GAWD_VERSION);
      wp_enqueue_script('overview', GAWD_URL . '/inc/js/gawd_overview.js', array('jquery'), GAWD_VERSION);
      wp_localize_script('overview', 'gawd_overview', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'ajaxnonce' => wp_create_nonce('gawd_admin_page_nonce'),
        'gawd_plugin_url' => GAWD_URL,
        'default_date' => $default_date,
        'enableHoverTooltip' => $enable_hover_tooltip,
        'wp_admin_url' => admin_url()
      ));

      if($is_gawd_page){
        $gawd_custom_ajax_nonce_data = array(
          'action' => 'gawd_analytics_pages',
          'nonce' => wp_create_nonce('gawd_analytics_pages'),
        );
      }else if($is_post_page){
        $gawd_custom_ajax_nonce_data = array(
          'action' => 'gawd_post_pages',
          'nonce' => wp_create_nonce('gawd_post_pages'),
        );
      }else if($is_dashboard){
        $gawd_custom_ajax_nonce_data = array(
          'action' => 'gawd_dashboard',
          'nonce' => wp_create_nonce('gawd_dashboard'),
        );
      }else{
        $gawd_custom_ajax_nonce_data = array(
          'action' => '',
          'nonce' => ''
        );
      }


      wp_localize_script('admin_js', 'gawd_admin', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'ajaxnonce' => wp_create_nonce('gawd_admin_page_nonce'),
        'gawd_plugin_url' => GAWD_URL,
        'wp_admin_url' => admin_url(),
        'enableHoverTooltip' => $enable_hover_tooltip,
        'default_date' => $default_date,
        'default_date_format' => $default_date_format,
        'date_30' => date('Y-m-d', strtotime('-31 day')) . '/-/' . date('Y-m-d', strtotime('-1 day')),
        'date_7' => date('Y-m-d', strtotime('-8 day')) . '/-/' . date('Y-m-d', strtotime('-1 day')),
        'date_last_week' => date('Y-m-d', strtotime('last week -1day')) . '/-/' . date('Y-m-d', strtotime('last week +5day')),
        'date_last_month' => date('Y-m-01', strtotime('last month')) . '/-/' . date('Y-m-t', strtotime('last month')),
        'date_this_month' => date('Y-m-01') . '/-/' . date('Y-m-d'),
        'date_today' => date('Y-m-d') . '/-/' . date('Y-m-d'),
        'date_yesterday' => date('Y-m-d', strtotime('-1 day')) . '/-/' . date('Y-m-d', strtotime('-1 day')),
        'exportUrl' => add_query_arg(array('action' => 'gawd_export'), admin_url('admin-ajax.php')),
        'gawd_custom_ajax_nonce' => wp_create_nonce('gawd_custom_ajax'),
        'gawd_custom_ajax_nonce_data' => $gawd_custom_ajax_nonce_data,
        'gawd_has_property' => (GAWD_helper::gawd_has_property() ? '1' : '0'),
      ));
    }
    if(strpos($screen->base, 'gawd_uninstall') !== false) {
      wp_enqueue_style('gawd_deactivate-css', GAWD_URL . '/wd/assets/css/deactivate_popup.css', array(), GAWD_VERSION);
      wp_enqueue_script('gawd-deactivate-popup', GAWD_URL . '/wd/assets/js/deactivate_popup.js', array(), GAWD_VERSION, true);
      $admin_data = wp_get_current_user();

      wp_localize_script('gawd-deactivate-popup', 'gawdWDDeactivateVars', array(
        "prefix" => "gawd",
        "deactivate_class" => 'gawd_deactivate_link',
        "email" => $admin_data->data->user_email,
        "plugin_wd_url" => "https://10web.io/plugins/wordpress-google-analytics/?utm_source=10web_analytics&utm_medium=free_plugin",
      ));
    }
    wp_enqueue_script('gawd_common_js', GAWD_URL . '/inc/js/gawd_common.js', array('jquery'), GAWD_VERSION);

    wp_enqueue_script('gawd_charts', GAWD_URL . '/inc/js/gawd_charts.js', array('gawd_plotly_basic'), GAWD_VERSION);
    wp_enqueue_script('gawd_plotly_basic', GAWD_URL . '/inc/js/plotly-basic.min.js', array('jquery'), GAWD_VERSION);
  }

  /**
   * Adds the menu page with its submenus.
   */
  public function gawd_add_menu(){
    $permission = 'read';
    $parent_slug = "gawd_analytics";

    /*Free*/
    if(get_option("gawd_subscribe_done") !== '1') {
      return;
    }

    if(GAWD_helper::gawd_is_ready()) {

      $this->gawd_pages[] = add_menu_page(
        __('Analytics', 'gawd'), //$page_title
        __('Analytics', 'gawd'), //$menu_title
        $permission, //$capability
        $parent_slug, //$menu_slug
        array($this, ($this->view_permission) ? 'gawd_display_overview_page' : 'gawd_display_settings_page'), //$function = '',
        GAWD_URL . '/assets/main_icon.png', "25");

      if($this->view_permission === true) {
        $this->gawd_pages[] = add_submenu_page(
          $parent_slug, //$parent_slug
          __('Analytics Dashboard', 'gawd'), //$page_title
          __('Analytics Dashboard', 'gawd'), //$menu_title
          $permission, //$capability
          'gawd_analytics', //$menu_slug
          array($this, 'gawd_display_overview_page') //$function = '',
        );

        $this->gawd_pages[] = add_submenu_page(
          $parent_slug, //$parent_slug
          __('Reports', 'gawd'), //$page_title
          __('Reports', 'gawd'), //$menu_title
          $permission, //$capability
          'gawd_reports', //$menu_slug
          array($this, 'gawd_display_reports_page') //$function = '',
        );
      }

      if($this->settings_permission === true) {
        $this->gawd_pages[] = add_submenu_page(
          $parent_slug, //$parent_slug
          __('Settings', 'gawd'), //$page_title
          __('Settings', 'gawd'), //$menu_title
          $permission, //$capability
          ($this->view_permission) ? 'gawd_settings' : 'gawd_analytics', //$menu_slug
          array($this, 'gawd_display_settings_page')   //$function = '',
        );

        $this->gawd_pages[] = add_submenu_page(
          $parent_slug, //$parent_slug
          __('Goal Management', 'gawd'), //$page_title
          __('Goal Management', 'gawd'), //$menu_title
          $permission, //$capability
          'gawd_goals', //$menu_slug
          array($this, 'gawd_display_goals_page') //$function = '',
        );

        $this->gawd_pages[] = add_submenu_page(
          $parent_slug, //$parent_slug
          __('Custom Reports', 'gawd'), //$page_title
          __('Custom Reports', 'gawd'), //$menu_title
          $permission, //$capability
          'gawd_custom_reports', //$menu_slug
          array($this, 'gawd_display_custom_reports_page') //$function = '',
        );

        //      $this->gawd_pages[] = add_submenu_page(
        //        $parent_slug, //$parent_slug
        //        __('Get Paid', 'gawd'), //$page_title
        //        __('Get Paid', 'gawd'), //$menu_title
        //        $permission, //$capability
        //        'gawd_licensing', //$menu_slug
        //        array($this, 'gawd_display_licensing_page') //$function = '',
        //      );

        $this->gawd_pages[] = add_submenu_page(
          $parent_slug, //$parent_slug
          __('Uninstall', 'gawd'), //$page_title
          __('Uninstall', 'gawd'), //$menu_title
          $permission, //$capability
          'gawd_uninstall', //$menu_slug
          array($this, 'gawd_display_uninstall_page') //$function = '',
        );
      }
    } else {

      if($this->settings_permission) {
        $this->gawd_pages[] = add_menu_page(
          __('Analytics', 'gawd'), //$page_title
          __('Analytics', 'gawd'), //$menu_title
          $permission, //$capability
          $parent_slug, //$menu_slug
          array($this, 'gawd_display_settings_page_for_auth'), //$function = '',
          GAWD_URL . '/assets/main_icon.png', "25");

        $this->gawd_pages[] = add_submenu_page(
          $parent_slug, //$parent_slug
          __('Settings', 'gawd'), //$page_title
          __('Settings', 'gawd'), //$menu_title
          $permission, //$capability
          'gawd_settings', //$menu_slug
          array($this, 'gawd_display_settings_page_for_auth')   //$function = '',
        );
      }
    }

    $this->gawd_pages[] = add_submenu_page(
      null, //$parent_slug
      __('Logs', 'gawd'), //$page_title
      __('Logs', 'gawd'), //$menu_title
      $permission, //$capability
      'gawd_logs', //$menu_slug
      array("GAWD_logs", "print_logs") //$function = '',
    );
  }

  public function remove_first_menu(){
    if(!GAWD_helper::gawd_is_ready()) {
      remove_submenu_page('gawd_analytics', 'gawd_analytics');
    }
  }

  public function gawd_display_licensing_page(){
    wp_enqueue_style('gawd_licensing', GAWD_URL . '/inc/css/gawd_licensing.css', false, GAWD_VERSION);
    require_once(GAWD_DIR . '/admin/pages/licensing.php');
  }

  public function gawd_display_uninstall_page(){

    require_once('admin/pages/uninstall.php');
    if(
      (isset($_POST['unistall_gawd']) && $_POST['unistall_gawd'] === 'yes') &&
      check_admin_referer('gawd_save_form', 'gawd_save_form_field') !== false
    ) {

      $deactivate_url = wp_nonce_url('plugins.php?action=deactivate&plugin=' . GWD_NAME . '/google-analytics-wd.php', 'deactivate-plugin_' . GWD_NAME . '/google-analytics-wd.php');
      $deactivate_url = str_replace('&amp;', '&', $deactivate_url);

      $gawd_uninstall = new GAWDUninstall();
      $gawd_uninstall->delete_options();

      echo '<script>window.location.href="' . $deactivate_url . '";</script>';
      die;

    }

    if(get_option('gawd_credentials')) {

      $gawd_uninstall = new GAWDUninstall();
      $gawd_uninstall->uninstall();

    }
  }

  public function gawd_display_goals_page(){
    $this->premium_bar('gawd_goals');

    $gawd_user_data = GAWD_helper::get_user_data();
    $property_id = $gawd_user_data['property_id'];
    $profiles = GAWD_helper::get_property_profiles($property_id);
    $property = GAWD_helper::get_property($property_id);
    $goals = get_option('gawd_goals_data');
    if(!is_array($goals)) {
      $goals = array();
    }

    $next_goals_id = GAWD_helper::get_next_goal_id($goals);
    $refresh_user_info_transient = get_site_transient('gawd_refresh_user_info');
    $display_goals_page = (!empty($gawd_user_data['property_id']));

    require_once('admin/pages/goals.php');
  }

  public function gawd_display_custom_reports_page(){
    $this->premium_bar('gawd_custom_reports');
    require_once('admin/pages/custom_reports.php');
  }

  public function gawd_display_overview_page(){
    $this->premium_bar('gawd_analytics');
    $profiles = GAWD_helper::get_profiles();
    $gawd_user_data = GAWD_helper::get_user_data();
    require_once('admin/pages/overview.php');
  }

  public function gawd_display_reports_page(){
    $this->premium_bar('gawd_reports');
    $profiles = GAWD_helper::get_profiles();
    $gawd_user_data = GAWD_helper::get_user_data();
    require_once('admin/pages/dashboard.php');
  }

  /**
   * Prepares the settings to be displayed then displays the settings page.
   */
  public static function gawd_settings_defaults(){
    $settings = get_option('gawd_settings');
    $settings['gawd_tracking_enable'] = 'on';
    $settings['gawd_custom_dimension_Logged_in'] = 'on';
    $settings['gawd_custom_dimension_Post_type'] = 'on';
    $settings['gawd_custom_dimension_Author'] = 'on';
    $settings['gawd_custom_dimension_Category'] = 'on';
    $settings['gawd_custom_dimension_Published_Month'] = 'on';
    $settings['gawd_custom_dimension_Published_Year'] = 'on';
    $settings['gawd_custom_dimension_Tags'] = 'on';
    $settings['enable_hover_tooltip'] = 'on';
    $settings['gawd_show_in_dashboard'] = 'on';
    $settings['post_page_chart'] = 'on';
    $settings['gawd_anonymize'] = 'on';
    update_option('gawd_settings', $settings);
  }

  public function gawd_admin_notice($message, $type, $class = ""){
    $class = 'notice notice-' . $type . ' ' . $class;
    echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
  }

  public function gawd_display_settings_page(){

    $this->premium_bar('gawd_settings');
    $gawd_user_data = GAWD_helper::get_user_data();

    if(isset($_POST['gawd_settings_logout']) && $_POST['gawd_settings_logout'] == 1) {
      check_admin_referer('gawd_save_form', 'gawd_save_form_fild');
      GAWD_helper::delete_user_data();
      $redirect_url = admin_url() . 'admin.php?page=gawd_settings';
      echo '<script>window.location.href="' . $redirect_url . '";</script>';
      die;
    }

    $selected_account_data = $gawd_user_data;
    if(!empty($_POST['web_property_name']) && !empty($_POST['gawd_profile_id'])) {
      $selected_account_data['profile_id'] = sanitize_text_field($_POST['gawd_profile_id']);
    }

    if(isset($_POST['web_property_name']) && $_POST['web_property_name'] != '') {

//todo filters
//      $gawd_user_data['gawd_id'] = isset($_POST['gawd_id']) ? $_POST['gawd_id'] : '';
//
//      foreach($gawd_user_data['gawd_profiles'] as $web_property_name => $web_property) {
//        foreach($web_property as $profile) {
//          if($profile['id'] == $gawd_user_data['gawd_id']) {
//            $gawd_user_data['web_property_name'] = $web_property_name;
//            $gawd_user_data['webPropertyId'] = $profile['webPropertyId'];
//            $gawd_user_data['accountId'] = $profile['accountId'];
//          }
//        }
//      }
//      $gawd_user_data['web_property_name'] = isset($_POST['web_property_name']) ? $_POST['web_property_name'] : '';
//      update_option('gawd_user_data', $gawd_user_data);
//      $redirect_url = admin_url() . 'admin.php?page=gawd_settings';
      //echo '<script>window.location.href="'.$redirect_url.'";</script>';
    }


    if(empty($gawd_user_data['property_id']) && count(GAWD_helper::get_current_site_properties()) > 1) {
      $this->gawd_admin_notice(
        "You have two or more web-properties configured with current site url. Please go with <a class='gawd_tracking_notice_link' href='" . admin_url('admin.php?page=gawd_settings#gawd_tracking_tab') . "'>this</a> link to select the proper one.",
        'error');
    }

    if(isset($_POST['gawd_email_remove'])){
      if(isset($_POST['gawd_save_form_fild']) && wp_verify_nonce($_POST['gawd_save_form_fild'], 'gawd_save_form')){
        include_once 'library/gawd-email-class.php';
        GAWD_email::delete_email(sanitize_text_field($_POST['gawd_email_remove']));
        }
    }

    require_once('admin/pages/settings.php');
  }

  public function gawd_display_settings_page_for_auth(){
    $gawd_user_data = GAWD_helper::get_user_data();
    $gawd_credentials = GAWD_helper::get_project_credentials();

    if(GAWD_helper::get_user_status() === "1") {
      $hide_auth = "gawd_hidden";
      $hide_refresh_accounts = "";
    } else {
      $hide_auth = "";
      $hide_refresh_accounts = "gawd_hidden";
    }

    require_once('admin/pages/authentication.php');
  }

  public function gawd_pushover_api($user_key, $metric, $condition, $value){
    $url = 'https://api.pushover.net/1/messages.json';
    $args = array(
      'timeout'     => 45,
      'redirection' => 5,
      'blocking'    => true,
      'headers' => array(),
      'body'    => array(
        "token" => "aJBDhTfhR87EaTzs7wpx1MMKwboBjB",
        "user" => $user_key,
        "message" => 'The ' . $metric . ' less ' . $value
      ),
      'cookies' => array()
    );
    wp_remote_post( $url, $args );
  }

  public function gawd_pushover_daily(){
    return;//todo
    $pushovers = get_option('gawd_pushovers');
    $data = '';
    $condition = '';

    foreach($pushovers as $pushover) {
      if(isset($pushover['period']) && $pushover['period'] == 'daily') {
        //pls send email if ....
        $date = date('Y-m-d', strtotime('yesterday'));
        //todo get $data from wp options
        //        $data = $gawd_client->get_data_alert('ga:' . $pushover['metric'], 'date', $date, $date, $pushover['pushover_view']);
        $pushover_condition = $pushover['condition'] == 'greater' ? '>' : '<';
        if(!eval($data . $pushover_condition . $pushover['value'] . ';')) {
          $cond = ' ' . $pushover['condition'] . ' than';
          $this->gawd_pushover_api($pushover['user_key'], $pushover['metric'], $pushover['condition'], $pushover['value']);
        }
      }
    }
  }

  public function gawd_pushover_weekly(){
    return;//todo
    $pushovers = get_option('gawd_pushovers');
    $data = '';
    $condition = '';
    foreach($pushovers as $pushover) {
      if(isset($pushover['period']) && $pushover['period'] == 'gawd_weekly') {
        //pls send email if ....
        $start_date = date('Y-m-d', strtotime('last week -1 day'));
        $end_date = date('l') != 'Sunday' ? date('Y-m-d', strtotime('last sunday -1 day')) : date('Y-m-d', strtotime('-1 day'));
        //todo get $data from wp options
        //        $data = $gawd_client->get_data_alert('ga:' . $pushover['metric'], 'date', $start_date, $end_date, $pushover['pushover_view']);
        $pushover_condition = $pushover['condition'] == 'greater' ? '>' : '<';
        if(!eval($data . $pushover_condition . $pushover['value'] . ';')) {
          $cond = ' ' . $pushover['condition'] . ' than';
          $this->gawd_pushover_api($pushover['user_key'], $pushover['metric'], $pushover['condition'], $pushover['value']);
        }
      }
    }
  }

  public function gawd_pushover_monthly(){
    return;//todo
    $pushovers = get_option('gawd_pushovers');
    $data = '';
    $condition = '';
    foreach($pushovers as $pushover) {
      if(isset($pushover['period']) && $pushover['period'] == 'gawd_monthly') {
        //pls send email if ....
        $end_date = date('Y-m-t', strtotime('last month'));
        $start_date = date('Y-m-01', strtotime('last month'));
        //todo get $data from wp options
        //        $data = $gawd_client->get_data_alert('ga:' . $pushover['metric'], 'date', $start_date, $end_date, $pushover['pushover_view']);
        $pushover_condition = $pushover['condition'] == 'greater' ? '>' : '<';
        if(!eval($data . $pushover_condition . $pushover['value'] . ';')) {
          $cond = ' ' . $pushover['condition'] . ' than';
          $this->gawd_pushover_api($pushover['user_key'], $pushover['metric'], $pushover['condition'], $pushover['value']);
        }
      }
    }
  }

  public function gawd_alert_daily(){
    return;//todo
    $alerts = get_option('gawd_alerts');
    $data = '';
    $condition = '';
    $email_from = get_option('admin_email');
    foreach($alerts as $alert) {
      if(isset($alert['period']) && $alert['period'] == 'daily') {
        //pls send email if ....
        $date = date('Y-m-d', strtotime('yesterday'));
        //todo get $data from wp options
        //        $data = $gawd_client->get_data_alert('ga:' . $alert['metric'], 'date', $date, $date, $alert['alert_view']);
        $alert_condition = $alert['condition'] == 'greater' ? '>' : '<';
        $color_condition = $alert['condition'] == 'greater' ? 'rgb(157, 207, 172)' : 'rgb(251, 133, 131)';
        if(!eval($data . $alert_condition . $alert['value'] . ';')) {
          $cond = ' ' . $alert['condition'] . ' than';
          $headers = array();
          $headers[] = 'From: <' . $email_from . '>';
          $headers[] = 'Content-Type: text/html';
          $content = '<div style="font-family: sans-serif;width:100%;height:50px;background-color:#FB8583;font-size:20px;color:#fff;margin-bottom:20px;text-align:center;line-height:50px">10Web Analytics Alert!</div><p style="color:#808080;text-align: center;font-size: 26px;font-family: sans-serif;">' . preg_replace('!\s+!', ' ', trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $alert['metric'])))) . ' in <a style="text-decoration:none;color:rgba(124,181,216,1);font-family: sans-serif;" href="' . $alert["alert_view_name"] . '" target="_blank">' . $alert["alert_view_name"] . '</a> are <span style="color:' . $color_condition . '">' . $cond . '</span></p><p style="color:rgba(124,181,216,1);font-size: 26px;font-family: sans-serif; text-align: center;">' . $alert['value'] . '</p>';
          wp_mail($alert['emails'], 'Analytics Alert', $content, $headers);
        }
      }
    }
  }

  public function gawd_alert_weekly(){
    return;//todo
    $alerts = get_option('gawd_alerts');
    $data = '';
    $condition = '';
    $email_from = get_option('admin_email');
    foreach($alerts as $alert) {
      if(isset($alert['period']) && $alert['period'] == 'gawd_weekly') {
        //pls send email if ....
        $start_date = date('Y-m-d', strtotime('last week -1 day'));
        $end_date = date('l') != 'Sunday' ? date('Y-m-d', strtotime('last sunday -1 day')) : date('Y-m-d', strtotime('-1 day'));
        //todo get $data
        //$data = $gawd_client->get_data_alert('ga:' . $alert['metric'], 'date', $start_date, $end_date, $alert['alert_view']);
        $alert_condition = $alert['condition'] == 'greater' ? '>' : '<';
        if(!eval($data . $alert_condition . $alert['value'] . ';')) {
          $cond = ' ' . $alert['condition'] . ' than';
          $headers = array();
          $headers[] = 'From: <' . $email_from . '>';
          $headers[] = 'Content-Type: text/html';
          $content = '<div style="font-family: sans-serif;width:100%;height:50px;background-color:#FB8583;font-size:20px;color:#fff;margin-bottom:20px;text-align:center;line-height:50px">10Web Analytics Alert!</div><p style="color:#808080;text-align: center;font-size: 26px;font-family: sans-serif;">' . preg_replace('!\s+!', ' ', trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $alert['metric'])))) . ' in <a style="text-decoration:none;color:rgba(124,181,216,1);font-family: sans-serif;" href="' . $alert["alert_view_name"] . '" target="_blank">' . $alert["alert_view_name"] . '</a> are <span style="color:' . $color_condition . '">' . $cond . '</span></p><p style="color:rgba(124,181,216,1);font-size: 26px;font-family: sans-serif; text-align: center;">' . $alert['value'] . '</p>';
          wp_mail($alert['emails'], 'Analytics Alert', $content, $headers);
        }
      }
    }
  }

  public function gawd_alert_monthly(){
    return;
    $alerts = get_option('gawd_alerts');
    $data = '';
    $email_from = get_option('admin_email');
    foreach($alerts as $alert) {
      if(isset($alert['period']) && $alert['period'] == 'gawd_monthly') {
        //pls send email if ....
        $end_date = date('Y-m-t', strtotime('last month'));
        $start_date = date('Y-m-01', strtotime('last month'));
        //todo get $data
        //$data = $gawd_client->get_data_alert('ga:' . $alert['metric'], 'date', $start_date, $end_date, $alert['alert_view']);
        $alert_condition = $alert['condition'] == 'greater' ? '>' : '<';
        if(!eval($data . $alert_condition . $alert['value'] . ';')) {
          $cond = ' ' . $alert['condition'] . ' than';
          $headers = array();
          $headers[] = 'From: <' . $email_from . '>';
          $headers[] = 'Content-Type: text/html';
          $content = '<div style="font-family: sans-serif;width:100%;height:50px;background-color:#FB8583;font-size:20px;color:#fff;margin-bottom:20px;text-align:center;line-height:50px">10Web Analytics Alert!</div><p style="color:#808080;text-align: center;font-size: 26px;font-family: sans-serif;">' . preg_replace('!\s+!', ' ', trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $alert['metric'])))) . ' in <a style="text-decoration:none;color:rgba(124,181,216,1);font-family: sans-serif;" href="' . $alert["alert_view_name"] . '" target="_blank">' . $alert["alert_view_name"] . '</a> are <span style="color:' . $color_condition . '">' . $cond . '</span></p><p style="color:rgba(124,181,216,1);font-size: 26px;font-family: sans-serif; text-align: center;">' . $alert['value'] . '</p>';
          wp_mail($alert['emails'], 'Analytics Alert', $content, $headers);
        }
      }
    }
  }

  public function wd_dashboard_widget(){
    $profiles = get_option('gawd_user_profiles');
    $gawd_user_data = GAWD_helper::get_user_data();
    $gawd_last_viewed_profile = GAWD_helper::get_last_viewed_profile();
    require_once('admin/pages/dashboard_widget.php');
  }

  public function google_analytics_wd_dashboard_widget(){
    $gawd_settings = GAWD_helper::get_settings();
    $gawd_backend_roles = isset($gawd_settings['gawd_backend_roles']) ? $gawd_settings['gawd_backend_roles'] : array();

    if(
      isset($gawd_settings['gawd_show_in_dashboard']) &&
      $gawd_settings['gawd_show_in_dashboard'] == 'on' &&
      GAWD_helper::check_permission($gawd_backend_roles)
    ) {

      wp_add_dashboard_widget('wd_dashboard_widget', '10Web Analytics', array(
        $this,
        'wd_dashboard_widget'
      ));
    }
  }

  public static function add_dashboard_menu(){
    $get_custom_reports = get_option('gawd_custom_reports');
    if(!$get_custom_reports) {
      $custom_report = array();
    } else {
      foreach($get_custom_reports as $name => $report) {
        $custom_report['custom_report_' . $name] = __($name, "gawd");
      }
    }
    $tabs = array(
      "general" => array(
        "title" => __("Audience", "gawd"),
        "childs" => array(),
        "desc" => "Report of your website audience. Provides details about new and returning users of your website, sessions, bounces, pageviews and session durations."
      ),
      "realtime" => array(
        "title" => __("Real Time", "gawd"),
        "childs" => array(),
        "desc" => "Real Time statistics show the number of active users currently visiting your website pages."
      ),
      /**  FREE **/
      "Pro" => array(
        "title" => __("Available in premium", "gawd"),
        "childs" => array(),
        "desc" => ""
      ),
      /** END FREE **/
      "demographics" => array(
        "title" => __("Demographics", "gawd"),
        "childs" => array(
          "userGender" => __("User Gender", "gawd"),
          "userAge" => __("User Age", "gawd")
        ),
        "desc" => "Demographics display tracking statistics of your website users based on their age and gender. "

      ),
      "interests" => array(
        "title" => __("Interests", "gawd"),
        "childs" => array(
          "inMarket" => __("In-Market Segment", "gawd"),
          "affinityCategory" => __("Affinity Category", "gawd"),
          "otherCategory" => __("Other Category", "gawd")
        ),
        "desc" => "Provides tracking information about site users depending on Affinity Categories (e.g. Music Lovers or Mobile Enthusiasts), In-Market Segments (based on online product purchase interests) and Other Categories (most specific identification, for example, tennis lovers among Sports Fans)."
      ),
      "geo" => array(
        "title" => __("GEO", "gawd"),
        "childs" => array(
          "location" => __("Location", "gawd"),
          "language" => __("Language", "gawd")
        ),
        "desc" => "Geo-identifier report is built from interactions of location (countries, cities) and language of your website users."
      ),
      "behavior" => array(
        "title" => __("Behavior", "gawd"),
        "childs" => array(
          "behaviour" => __("New vs Returning", "gawd"),
          "engagement" => __("Engagement", "gawd")
        ),
        "desc" => "Compares number of New visitors and Returning users of your website in percents. You can check the duration of sessions with Engagement report."
      ),
      "technology" => array(
        "title" => __("Technology", "gawd"),
        "childs" => array(
          "os" => __("OS", "gawd"),
          "browser" => __("Browser", "gawd")
        ),
        "desc" => "Identifies tracking of the site based on operating systems and browsers visitors use."
      ),
      "mobile" => array(
        "title" => __("Mobile", "gawd"),
        "childs" => array(
          "device_overview" => __("Overview", "gawd"),
          "devices" => __("Devices", "gawd")
        ),
        "desc" => "Shows statistics of mobile and desktop devices visitors have used while interacting with your website."
      ),
      "custom" => array(
        "title" => __("Custom Dimensions", "gawd"),
        "childs" => array(),
        "desc" => "Set up Custom Dimensions based on Users, Post type, Author, Category, Publication date and Tags in Custom Dimensions page, and view their report in this tab."
      ),
      "trafficSource" => array(
        "title" => __("Traffic Source", "gawd"),
        "childs" => array(),
        "desc" => "Displays overall graph of traffic sources directing to your website."
      ),
      "adWords" => array(
        "title" => __("AdWords", "gawd"),
        "childs" => array(),
        "desc" => "If your website is registered on Google AdWords, you can link its Google Analytics to AdWords, and gather relevant tracking information with this report."
      ),
      /*             "pagePath" => array(
                            "title" => __("Pages", "gawd"),
                            "childs" => array(),
                            "desc" => "Pages report table will provide you information about Bounces, Entrances, Pageviews, Unique Pageviews, time spent on pages, Exits and Average page loading time."
                        ), */
      "siteContent" => array(
        "title" => __("Site Content", "gawd"),
        "childs" => array(
          "pagePath" => __("All Pages", "gawd"),
          "landingPagePath" => __("Landing Pages", "gawd"),
        ),
        "desc" => "Pages report table will provide you information about Bounces, Entrances, Pageviews, Unique Pageviews, time spent on pages, Exits and Average page loading time."
      ),
      "siteSpeed" => array(
        "title" => __("Site Speed", "gawd"),
        "childs" => array(),
        "desc" => "Shows the average load time of your website users experienced during specified date range."
      ),
      "events" => array(
        "title" => __("Events", "gawd"),
        "childs" => array(
          "eventsLabel" => __("Events by Label", "gawd"),
          "eventsAction" => __("Events by Action", "gawd"),
          "eventsCategory" => __("Events by Category", "gawd")
        ),
        "desc" => "Displays the report based on Events you set up on Google Analytics of your website. Graphs are built based on Event Labels, Categories and Actions."
      ),
      "goals" => array(
        "title" => __("Goals", "gawd"),
        "childs" => array(),
        "desc" => "Set Goals from Goal Management and review their Google Analytics reports under this tab."
      ),
      "ecommerce" => array(
        "title" => __("Ecommerce", "gawd"),
        "childs" => array(
          "daysToTransaction" => __("TIme to Purchase", "gawd"),
          "transactionId" => __("Transaction ID", "gawd"),
          "sales_performance" => __("Sales Performance", "gawd"),
          "productSku" => __("Product Sku", "gawd"),
          "productCategory" => __("Product Category ", "gawd"),
          "productName" => __("Product Name", "gawd"),
        ),
        "desc" => "Check sales statistics of your website identified by revenues, transactions, products and performance."
      ),
      "adsense" => array(
        "title" => __("AdSense", "gawd"),
        "childs" => array(),
        "desc" => "Link your Google Analytics and AdSense accounts from Google Analytics Admin setting and keep track of AdSense tracking under this report."
      ),
      "customReport" => array(
        "title" => __("Custom Report", "gawd"),
        "childs" => $custom_report,
        "desc" => "Add Custom Reports from any metric and dimension in Custom Reports page, and view relevant Google Analytics tracking information in this tab."
      ),
    );
    update_option('gawd_menu_items', $tabs);
  }

  public function remove_zoom_message(){
    check_ajax_referer('gawd_admin_page_nonce', 'security');
    $got_it = isset($_REQUEST["got_it"]) ? sanitize_text_field($_REQUEST["got_it"]) : '';
    if($got_it != '') {
      add_option('gawd_zoom_message', $got_it);
    }
  }

  public function nossl_message(){
    $this->gawd_admin_notice(
      __('ERROR: 10Web Analytics cannot make insecure requests to Google API. Please enable PHP OpenSSL extension', 'gawd'),
      'error'
    );
  }

  protected function premium_bar($page){
    $text = 'See advanced and custom reports, ecommerce reports and customize tracking settings.';

    switch($page) {
      case  'gawd_analytics':
        $manual_link = 'https://help.10web.io/hc/en-us/articles/360017502592-Introducing-WordPress-Google-Analytics';
        break;
      case  'gawd_reports':
        $manual_link = 'https://help.10web.io/hc/en-us/articles/360017506312-Google-Analytics-Reports';
        break;
      case  'gawd_settings':
        $manual_link = 'https://help.10web.io/hc/en-us/articles/360018132671-Google-Analytics-Settings';
        break;
      case  'gawd_goals':
        $manual_link = 'https://help.10web.io/hc/en-us/articles/360018133231-Configuring-Google-Analytics-Goals';
        break;
      case 'gawd_custom_reports':
        $manual_link = 'https://help.10web.io/hc/en-us/articles/360018133271-Custom-Reports';
        break;
    }
    ?>
      <div class="gawd-topbar-container">
          <div class="gawd-topbar gawd-topbar-content">
              <div class="gawd-topbar-content-container">
                  <div class="gawd-topbar-content-title">
                      Google Analytics Premium
                  </div>
                  <div class="gawd-topbar-content-body"><?php echo $text; ?></div>
              </div>
              <div class="gawd-topbar-content-button-container">
                  <a href="https://10web.io/plugins/wordpress-google-analytics/?utm_source=10web_analytics&utm_medium=free_plugin" target="_blank"
                     class="gawd-topbar-upgrade-button">Upgrade</a>
              </div>
          </div>
          <div class="gawd-topbar gawd-topbar-links">
              <div class="gawd-topbar-links-container">
                  <a href="<?php echo $manual_link; ?>"
                     target="_blank">
                      <div class="gawd-topbar-links-item">User Manual</div>
                  </a>
                  <span class="gawd-topbar-separator"></span>
                  <a href="https://wordpress.org/support/plugin/wd-google-analytics" target="_blank">
                      <div class="gawd-topbar-links-item">Support Forum</div>
                  </a>
              </div>
          </div>
      </div>
    <?php
  }


  /************STATIC METHODS************/

  public static function deactivate(){
    delete_site_transient('gawd_uninstall');
  }

  /**
   * Activation function needed for the activation hook.
   */
  public static function global_activate($networkwide){

    if(function_exists('is_multisite') && is_multisite()) {
      // Check if it is a network activation - if so, run the activation function for each blog id.
      if($networkwide) {
        global $wpdb;
        // Get all blog ids.
        $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        foreach($blogids as $blog_id) {
          switch_to_blog($blog_id);
          self::activate();
          restore_current_blog();
        }
        return;
      }
    }
    self::activate();
  }

  public static function activate(){

    delete_site_transient('gawd_uninstall');
	  $gawd_credentials = get_option('gawd_credentials');
    if(empty($gawd_credentials)) {
      update_option('gawd_credentials', GAWD_helper::get_project_default_credentials());
    }


    $gawd_settings = get_option('gawd_settings');
    if($gawd_settings === false) {
      self::gawd_settings_defaults();
    }

    //logout issue
    if(GAWD_VERSION == '1.1.2' || GAWD_VERSION == '5.1.2') {

      if(get_option('gawd_user_data') == false) {
        delete_option('gawd_user_status');
        delete_option('gawd_account_status');
      }

    }

    self::add_dashboard_menu();

    $old_version = get_option('gawd_version');
    $settings = get_option('gawd_settings');
    if($old_version === false){
      $old_version = 'x.1.6';
    }

    $old_version_for_compare = substr($old_version, 2);
    $new_version_for_compare = substr(GAWD_VERSION, 2);

    if($old_version === false || version_compare($old_version_for_compare, '1.9','<')) {
      if(!empty($settings['gawd_permissions'])) {
        $new_permissions = array();
        $permissions = $settings['gawd_permissions'];
        $roles = new WP_Roles();
        foreach($roles->roles as $key => $role) {
          foreach($permissions as $permission) {
            if(isset($role['capabilities'][$permission]) && $role['capabilities'][$permission]) {
              $new_permissions[] = $key;
              break;
            }
          }
        }
        $new_permissions = array_unique($new_permissions);
        $settings['gawd_permissions'] = $new_permissions;
      }

      if(get_option('gawd_user_data') !== false){
        update_option('gawd_upgrade_plugin', '1');
      }
    }

    update_option('gawd_settings', $settings);
    update_option('gawd_version', GAWD_VERSION);
  }

  public function upgrade_plugin(){
    ?>

      <script>
          gawd_upgrade_plugin(
              "<?php echo admin_url('admin-ajax.php'); ?>",
              "<?php echo wp_create_nonce('gawd_custom_ajax'); ?>",
          );
      </script>

  <?php }

  /**
   * Returns the Singleton instance of this class.
   *
   * @return GAWD The Singleton instance.
   */
  public static function get_instance(){
    if(null === self::$instance) {
      self::$instance = new self();
    }

    return self::$instance;
  }

}
