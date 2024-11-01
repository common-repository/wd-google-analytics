<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 6/17/18
 * Time: 3:05 PM
 */

class  GAWD_alert {

  private $alert_data;
  private $date_range = 'Today';

  public function __construct($alert_data){
    $this->alert_data = $alert_data;
  }

  public function add_email_data_to_POST(){

    $_POST['security'] = wp_create_nonce('gawd_admin_page_nonce');
    $_POST['menu_name'] = '';
    $_POST['compare_by'] = 'metric';
    $_POST['action'] = 'gawd_send_email';


    if($this->alert_data['period'] == 'daily') {
      $this->date_range = 'Today';
    } else if($this->alert_data['period'] == 'gawd_weekly') {
      $this->date_range = 'Last 7 Days';
    } else if($this->alert_data['period'] == 'gawd_monthly') {
      $this->date_range = 'This Month';
    }

    $request_last_args = $this->get_request_last_args();
    $info = array('date_ranges' => array($this->date_range, 'Previous period'));
    $email_info = $this->get_email_info();

    $_POST['gawd_request_last_args'] = $request_last_args;
    $_POST['info'] = $info;
    $_POST['email_info'] = $email_info;
  }

  private function get_request_last_args(){

    $gawd_request_last_args = array(
      'gawd_ajax' => '1',
      'gawd_nonce' => '',
      'gawd_nonce_data' => array(
        'action' => 'gawd_analytics_pages',
        'nonce' => wp_create_nonce('gawd_analytics_pages'),
      ),
      'gawd_action' => 'gawd_show_data',
      'gawd_data' => array(
        'start_date' => null,
        'end_date' => null,
        'metric' => array('ga:' . $this->alert_data['metric']),
        'dimension' => 'date',
        'security' => '',
        'filter_type' => '',
        'custom' => '',
        'timezone' => intval(get_option('gmt_offset'))
      ));

    return $gawd_request_last_args;
  }

  private function get_email_info(){
    $email_info = array(
      'period' => 'daily',
      'email_from' => get_option('admin_email'),
      'email_to' => $this->alert_data['emails'],
      'subject' => 'Google Analytics alert',
      'content' => '',
      'week_day' => '',
      'month_day' => '1',
      'email_time' => '00:00',
      'view_id' => $this->alert_data['alert_view'],
      'other_info' => array(
        'condition_data' => $this->get_condition_data(),
        'alert_data' => $this->alert_data
      )
    );

    return $email_info;
  }

  private function get_condition_data(){
    $condition = array(
      'when' => $this->alert_data['metric'],
      'condition' => $this->alert_data['condition'],
      'value' => $this->alert_data['value']
    );

    return $condition;
  }
}