<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 6/17/18
 * Time: 3:05 PM
 */

class  GAWD_email {

  private $period = "once";
  private $email_from = "";
  private $email_to = "";
  private $subject = "";
  private $content = "";
  private $week_day = "";
  private $month_day = "1";
  private $time = "00:00";
  private $view_id = "";
  private $error = array();
  private $attachments = array();
  private $other_info = array();
  private $ajax_response = array();

  public function __construct(){

  }

  public function parse_ajax_data(){

    $response = array(
      'success' => false,
      'error' => array('code' => 'something_went_wrong', 'msg' => 'something went wrong.'),
      'data' => ''
    );

    if(empty($_POST['email_info']['email_to'])) {
      $this->set_error('no_email_to', 'No email');
      return false;
    }

    $this->email_to = sanitize_text_field($_POST['email_info']['email_to']);
    $this->email_from = (isset($_POST['email_info']['email_from'])) ? sanitize_text_field($_POST['email_info']['email_from']) : get_option('admin_email');

    if(empty($this->email_from)) {
      $this->set_error('no_email_from', 'No email');
      return false;
    }


    $this->period = (isset($_POST['email_info']['period'])) ? sanitize_text_field($_POST['email_info']['period']) : "once";
    $this->subject = (isset($_POST['email_info']['subject'])) ? sanitize_text_field($_POST['email_info']['subject']) : "";
    $this->content = (isset($_POST['email_info']['content'])) ? sanitize_text_field($_POST['email_info']['content']) : "";
    $this->week_day = (isset($_POST['email_info']['week_day'])) ? sanitize_text_field($_POST['email_info']['week_day']) : "";
    $this->month_day = (isset($_POST['email_info']['month_day'])) ? sanitize_text_field($_POST['email_info']['month_day']) : "1";
    $this->time = (isset($_POST['email_info']['email_time'])) ? sanitize_text_field($_POST['email_info']['email_time']) : "00:00";
    $this->view_id = (isset($_POST['email_info']['view_id'])) ? sanitize_text_field($_POST['email_info']['view_id']) : "";

    if(isset($_POST['email_info']) && isset($_POST['email_info']['other_info'])){
      $this->other_info = GAWD_helper::validate_string_array($_POST['email_info']['other_info']);
    }else{
      $this->other_info = array();
    }

    return true;
  }

  public function set_email_info($info){

    $this->email_to = $info['email_to'];
    $this->email_from = $info['email_from'];
    $this->period = $info['period'];
    $this->subject = $info['subject'];
    $this->content = $info['content'];
    $this->week_day = $info['week_day'];
    $this->month_day = $info['month_day'];
    $this->time = $info['time'];
    $this->view_id = $info['view_id'];
    $this->other_info = (isset($info['other_info'])) ? $info['other_info'] : array();

  }

  public function check_condition($email_info){

    if(!isset($this->other_info['condition_data'])) {
      return true;
    }

    $response = GAWD_helper::ajax_request($email_info['ajax_args']['last_args'], $this->view_id);
    if($response === false) {
      return false;
    }

    switch($this->other_info['condition_data']['when']) {
      case 'sessions':
        $metric = 'Sessions';
        $value_type = 'int';
        break;
      case 'users':
        $metric = 'Users';
        $value_type = 'int';
        break;
      case 'bounceRate':
        $metric = 'Bounce Rate';
        $value_type = 'float';
        break;
      case 'avgSessionDuration':
        $metric = 'Avg Session Duration';
        $value_type = 'float';
        break;
      default:
        $metric = 'Sessions';
        $value_type = 'int';
        break;
    }

    if(!isset($response['data_sum'][$metric])) {
      return false;
    }

    $this->ajax_response = $response;

    $real_value = ($value_type == 'int') ? intval($response['data_sum'][$metric]) : floatval($response['data_sum'][$metric]);
    $condition_value = ($value_type == 'int') ? intval($this->other_info['condition_data']['value']) : floatval($this->other_info['condition_data']['value']);

    if($this->other_info['condition_data']['condition'] == 'less') {
      return ($real_value < $condition_value);
    } else {
      return ($real_value > $condition_value);
    }

  }

  public function add_content(){
    if(!empty($this->content)) {
      return;
    }

    if(isset($this->other_info['alert_data'])) {


      $alert = $this->other_info['alert_data'];
      $condition = $alert['condition'] == 'less' ? ' is less then ' : ' greater than ';
      $metric = $alert['metric'];

      if($metric == 'bounceRate') {
        $metric = 'Bounce Rate';
      } else if($metric == 'avgSessionDuration') {
        $metric = 'Avg Session Duration';
      } else {
        $metric = ucfirst($metric);
      }

      $period = (strpos($alert['period'], 'gawd') > -1) ? substr($alert['period'], 5) : $alert['period'];
      $period = ucfirst($period);

      $this->content = 'Name: ' . $alert['name'] . "<br/>";
      $this->content .= 'Period: ' . $period . "<br/>";
      $this->content .= 'Condition: ' . $metric . $condition . $alert['value'] . "<br/>";
      $this->content .= 'View: ' . $alert['alert_view_name'];

    }

  }

  /**
   * @param $csv GAWD_csv_file_generator
   * */
  public function attach_file($csv){
    $this->attachments[] = $csv->get_file_dir();
  }

  public function send_mail(){
    $headers = array(
      'From: <' . $this->email_from . '>',
      'content-type: text/html'
    );
    $content = (!empty($this->content)) ? $this->content : " ";
    return wp_mail($this->email_to, $this->subject, $content, $headers, $this->attachments);
  }

  public function save_email_info($ajax_args, $csv){

    $gawd_emails_info = get_option('gawd_emails_info');
    if(!is_array($gawd_emails_info)) {
      $gawd_emails_info = array();
    }

    $id = uniqid();
    $email_info = array(
      'email_info' => $this->get_email_info(),
      'ajax_args' => $ajax_args,
      'creation_date' => time(),
      'next_date' => null
    );
    $email_info = self::calc_next_date($email_info);

    if($email_info['ajax_args']['last_args']['gawd_data']['start_date'] === null) {
      $email_info = self::calc_date_range($email_info);
    }

    $gawd_emails_info[$id] = $email_info;

    update_option('gawd_emails_info', $gawd_emails_info);
    self::set_new_scheduled();
  }


  private function set_error($code, $msg){
    $this->error = array(
      'code' => $code,
      'msg' => $msg
    );
  }

  public function get_error(){
    return $this->error;
  }

  public function get_period(){
    return $this->period;
  }

  public function get_email_info(){
    $info = array(
      'period' => $this->period,
      'email_from' => $this->email_from,
      'email_to' => $this->email_to,
      'subject' => $this->subject,
      'content' => $this->content,
      'week_day' => $this->week_day,
      'month_day' => $this->month_day,
      'time' => $this->time,
      'view_id' => $this->view_id,
      'other_info' => $this->other_info,
    );

    return $info;
  }

  public function get_ajax_response(){
    return $this->ajax_response;
  }


  public static function calc_next_date($email_info){

    $last_date = ($email_info['next_date'] === null) ? $email_info['creation_date'] : $email_info['next_date'];
    $time = (!empty($email_info['email_info']['time'])) ? $email_info['email_info']['time'] : "00:00";
    $now = time();
    $next_date = null;

    switch($email_info['email_info']['period']) {
      case "daily":
        $next_date = (date('Y-m-d', $last_date) . ' ' . $time);

        if($now > strtotime($next_date)) {
          $next_date = strtotime($next_date . ' +1 day');
        } else {
          $next_date = strtotime($next_date);
        }

        break;
      case "gawd_weekly":

        $week_day = (!empty($email_info['email_info']['week_day'])) ? $email_info['email_info']['week_day'] : date('l');
        $next_date = (date('Y-m-d', strtotime("this " . $week_day, $last_date)) . ' ' . $time);

        if($now > strtotime($next_date)) {
          $next_date = strtotime((date('Y-m-d', strtotime("next " . $week_day)) . ' ' . $time));
        } else {
          $next_date = strtotime($next_date);
        }

        break;
      case "gawd_monthly":

        $week_day = $email_info['email_info']['month_day'];
        if($week_day !== 'last') {
          $next_date = (date('Y-m-' . $week_day, $last_date)) . ' ' . $time;

          if($now > strtotime($next_date)) {
            $next_date = strtotime($next_date . ' +1 month');
          } else {
            $next_date = strtotime($next_date);
          }

        } else {
          $next_date = (date('Y-m-t', $last_date)) . ' ' . $time;

          if($now > strtotime($next_date)) {
            $next_date = (date('Y-m-1', $last_date));
            $next_date = strtotime($next_date . ' +1 month');
            $next_date = (date('Y-m-t', $next_date)) . ' ' . $time;
          } else {
            $next_date = strtotime($next_date);
          }

        }


        break;
    }

    $email_info['next_date'] = $next_date;

    return $email_info;
  }

  public static function calc_date_range($email_info){

    $date_range = 'Last 30 Days';
    //    $compare_date_range = 'Previous period';
    if(isset($email_info['ajax_args']['info']['date_ranges'])) {
      $date_range = trim($email_info['ajax_args']['info']['date_ranges'][0]);
      //      $compare_date_range = trim($email_info['ajax_args']['info']['date_ranges'][1]);
    }

    $next_date = $email_info['next_date'];
    $date = date('Y-m-d', $next_date);

    switch($date_range) {
      case "Today";
        $start_date = date('Y-m-d', time());
        $end_date = date('Y-m-d', $next_date);
        break;
      case "Yesterday";
        $start_date = date('Y-m-d', strtotime($date . " -1 days"));
        $end_date = $start_date;
        break;
      case "Last 7 Days";
        $end_date = date('Y-m-d', strtotime($date . " -1 days"));
        $start_date = date('Y-m-d', strtotime($end_date . " -6 days"));
        break;
      case "Last Week";
        $end_date = date("Y-m-d", strtotime($date . " previous saturday"));
        $start_date = date('Y-m-d', strtotime($end_date . " -6 days"));
        break;
      case "This Month";
        $start_date = date('Y-m-01', $next_date);
        $end_date = date('Y-m-d', $next_date);
        break;
      case "Last Month";
        $start_date = date("Y-m-d", strtotime($date . " first day of previous month"));
        $end_date = date("Y-m-d", strtotime($date . " last day of previous month"));
        break;
      default:
        //Last 30 Days
        $end_date = date('Y-m-d', strtotime($date . " -1 days"));
        $start_date = date('Y-m-d', strtotime($end_date . " -29 days"));
        break;
    }

    $email_info['ajax_args']['last_args']['gawd_data']['start_date'] = $start_date;
    $email_info['ajax_args']['last_args']['gawd_data']['end_date'] = $end_date;

    return $email_info;
  }

  public static function calc_next_mail_date($emails_info){

    if(empty($emails_info)) {
      return time() + (24 * 60 * 60);
    }

    $min = null;
    foreach($emails_info as $info) {
      if($min === null) {
        $min = $info['next_date'];
        continue;
      }

      if($info['next_date'] < $min) {
        $min = $info['next_date'];
      }
    }

    return $min;
  }

  public static function delete_email($index){
    $gawd_emails_info = get_option('gawd_emails_info');
    unset($gawd_emails_info[$index]);
    update_option('gawd_emails_info', $gawd_emails_info);
    self::set_new_scheduled();
  }

  public static function set_new_scheduled(){
    $next_mail_date = GAWD_email::calc_next_mail_date(get_option('gawd_emails_info'));
    update_option('gawd_next_mail_date', $next_mail_date);

    wp_clear_scheduled_hook('gawd_email_scheduled');
    wp_schedule_single_event($next_mail_date, 'gawd_email_scheduled');
  }

}