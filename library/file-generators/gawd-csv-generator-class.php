<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 6/10/18
 * Time: 2:07 PM
 */

include_once 'gawd-file-generator-class.php';

class  GAWD_csv_file_generator extends GAWD_file_generator {

  private $metrics = array();
  private $dimention = "";
  private $data;

  private $compare_metrics = array();
  private $compare_dimention = "";
  private $compare_data;

  private $site_title = "";
  private $date_text = "";
  private $menu_name = "";
  private $columns = array();
  private $sec_filter_metrics = array(
    "Avg Session Duration",
    "Avg Page Load Time",
    "Avg Redirection Time",
    "Avg Server Response Time",
    "Avg Page Download Time",
    'Avg Time On Page',
  );

  private $calc_percent_metrics = array(
    "Sessions",
    "Users",
    "Pageviews",
    "Unique Pageviews",
    "Entrances",
    "New Users",
    "Transactions"
  );

  private $add_percent_metrics = array(
    "Percent New Sessions",
    "Bounce Rate",
    "Exit Rate",
    "Transactions Per Session"
  );

  public function __construct(){
    $file_name = GAWD_helper::get_unique_string() . '.csv';
    parent::__construct($file_name);
  }

  protected function file_put_content(){

    echo "\xEF\xBB\xBF";

    if(!empty($this->site_title)) {
      fputcsv($this->output, array($this->site_title), ',');
    }

    $line2 = (!empty($this->menu_name)) ? $this->menu_name : "";

    if(!empty($this->date_text)) {
      $line2 .= " (" . $this->date_text . ")";
    }

    if(!empty($line2)) {
      fputcsv($this->output, array($line2), ',');
      fputcsv($this->output, array(""), ',');
    }


    foreach($this->columns as $column) {
      fputcsv($this->output, $column, ',');
    }

  }


  public function parse_data($data, $args, $compare_data = null, $compare_args = null, $compare_by){

    $this->data = $data;
    $this->metrics = $this->get_metric($args);

    if(strpos($args['gawd_data']['dimension'], 'dimension') === false) {
      $this->dimention = $this->get_dimention($args);
    } else {
      $this->is_custom_dimention = true;
      $this->dimention = "custom";

      $custom_dim_metrics = array('No');
      if(!empty($this->data['chart_data'])) {


        $custom_dim_metrics = array_merge($custom_dim_metrics, $this->metrics);

        foreach($this->data['chart_data'][0] as $metric => $val) {
          if(!in_array($metric, $custom_dim_metrics)) {
            $custom_dim_metrics[] = $metric;
          }
        }

      }

      $this->metrics = $custom_dim_metrics;
    }

    $this->date_text = $args['gawd_data']['start_date'] . ' - ' . $args['gawd_data']['end_date'];

    if($compare_data !== null) {
      if($compare_by === "metric") {
        $this->compare_metrics = $this->get_metric($compare_args);
      } else {
        $this->compare_metrics = $this->metrics;
      }

      $this->compare_dimention = $this->get_dimention($compare_args);
      $this->compare_data = $compare_data;

      if(isset($compare_args['gawd_data']['start_date']) && isset($compare_args['gawd_data']['end_date'])) {
        $this->date_text .= ' -compare- ' . $compare_args['gawd_data']['start_date'] . ' - ' . $compare_args['gawd_data']['end_date'];
      }
    }

    if($this->dimention === 'Page Path' || $this->dimention === "Landing Page Path") {
      $this->set_site_content_columns();
    } elseif($this->dimention === "custom") {
      $this->set_custom_dimention_columns();
    } elseif($compare_data === null) {
      $this->set_columns();
    } else {
      $this->set_columns_with_compare_data();
    }

  }

  private function set_custom_dimention_columns(){
    $titles = $this->metrics;

    $columns = array($titles);

    foreach($this->data['chart_data'] as $data) {
      $columns[] = $this->get_row_values($data, array(), $this->metrics, "", false);
    }

    $this->columns = $columns;
  }

  private function set_columns(){
    $titles = array('No', $this->dimention);
    $titles = array_merge($titles, $this->metrics);

    $columns = array($titles);

    foreach($this->data['chart_data'] as $data) {
      $columns[] = $this->get_row_values($data, $this->data['data_sum'], $this->metrics, $this->dimention, true);
    }

    $this->columns = $columns;
  }

  private function set_columns_with_compare_data(){

    $titles = array(
      'No',
      $this->dimention,
      ($this->dimention === $this->compare_dimention) ? $this->dimention . ' compare' : $this->compare_dimention
    );

    $titles = array_merge($titles, $this->metrics);
    foreach($this->compare_metrics as $compare_metric) {
      $titles[] = (in_array($compare_metric, $this->metrics)) ? $compare_metric . ' compare' : $compare_metric;
    }

    $columns = array($titles);

    $data = $this->data['chart_data'];
    $compare_data = $this->compare_data['chart_data'];

    $length = (count($data) > count($compare_data)) ? count($data) : count($compare_data);

    for($i = 0; $i < $length; $i++) {

      $rows = $compare_rows = array("", "");

      if(isset($data[$i])) {
        $rows = $this->get_row_values($data[$i], $this->data['data_sum'], $this->metrics, $this->dimention);
      }

      if(isset($compare_data[$i])) {
        $compare_rows = $this->get_row_values($compare_data[$i], $this->compare_data['data_sum'], $this->compare_metrics, $this->compare_dimention);
      }

      $columns[] = array(
        $i + 1,
        $rows[0],
        $compare_rows[0],
        $rows[1],
        $compare_rows[1]
      );

    }

    $this->columns = $columns;
  }

  private function get_row_values($data, $data_sum, $metrics, $dimention, $num = false){

    $col = array();

    if($num === true) {
      $col[] = $data['No'];
    }
    if(!empty($dimention)) {
      $col[] = $data[$dimention];
    }

    foreach($metrics as $metric) {

      if(!isset($data[$metric])) {
        $col[] = "";
        continue;
      }

      if(in_array($metric, $this->sec_filter_metrics)) {

        $col[] = $this->sec_to_normal($data[$metric]);

      } else if(in_array($metric, $this->calc_percent_metrics) && isset($data_sum[$metric])) {

        $sum_val = floatval($data_sum[$metric]);
        if($sum_val != 0) {
          $percent = ($data[$metric] / $sum_val) * 100;
          $percent = round($percent, 2);
        } else {
          $percent = 0;
        }
        $col[] = $data[$metric] . '(' . $percent . '%)';

      } else if(in_array($metric, $this->add_percent_metrics)) {

        $col[] = round($data[$metric], 2) . '%';

      } else {

        if($metric === "Pageviews Per Session") {

          $col[] = round($data[$metric], 3);

        } else {

          $col[] = $data[$metric];

        }

      }

    }

    return $col;
  }

  private function set_site_content_columns(){
    if($this->dimention === 'Page Path') {
      $titles = array(
        'No', 'Page Path', 'Pageviews', 'Unique Pageviews',
        'Avg Time On Page', 'Entrances', 'Bounce Rate',
        'Exit Rate', 'Page Value', 'Avg Page Load Time'
      );
    } else {
      //$this->dimention === "Landing Page Path"
      $titles = array(
        'No', 'Landing Page', 'Sessions', 'Percent New Sessions',
        'New Users', 'Bounce Rate', 'Pageviews Per Session',
        'Avg Session Duration', 'Transactions', 'Transaction Revenue', 'Transactions Per Session'
      );
    }


    $columns = array($titles);
    foreach($this->data['chart_data'] as $data) {

      $col = array();
      foreach($titles as $t) {

        if(in_array($t, $this->sec_filter_metrics)) {
          $col[] = $this->sec_to_normal($data[$t]);
          continue;
        }

        if(in_array($t, $this->calc_percent_metrics)) {

          $sum_val = floatval($this->data['data_sum'][$t]);
          if($sum_val != 0) {
            $percent = ($data[$t] / $this->data['data_sum'][$t]) * 100;
            $percent = round($percent, 2);
          } else {
            $percent = 0;
          }
          $col[] = $data[$t] . '(' . $percent . '%)';
          continue;
        }

        if(in_array($t, $this->add_percent_metrics)) {
          $col[] = round($data[$t], 2) . '%';
          continue;
        }

        if($t === 'Page Value' || $t === 'Transaction Revenue') {

          $sum_val = floatval($this->data['data_sum'][$t]);
          if($sum_val != 0) {
            $percent = ($data[$t] / $sum_val) * 100;
          } else {
            $percent = 0;
          }

          $col[] = "$" . round(floatval($data[$t]), 2) . "(" . $percent . "%)";
          continue;
        }

        if($t === "Pageviews Per Session") {
          $col[] = round($data[$t], 3);
          continue;
        }

        $col[] = $data[$t];
      }
      $columns[] = $col;

    }

    $this->columns = $columns;
  }

  private function get_metric($args){
    if(!(isset($args['gawd_data']['metric'][0]))) {
      return 'Sessions';
    }

    $metrics = array();
    foreach($args['gawd_data']['metric'] as $metric_name) {

      $metric = str_replace('ga:', '', $metric_name);
      $metric = preg_replace_callback('/([A-Z])/', array($this, 'regex_callback'), $metric);
      $metric = ucfirst($metric);

      $metrics[] = $metric;
    }

    return $metrics;
  }

  private function get_dimention($args){

    if(!(isset($args['gawd_data']['dimension']))) {
      return 'Date';
    }

    $dim = preg_replace_callback('/([A-Z])/', array($this, 'regex_callback'), $args['gawd_data']['dimension']);
    $dim = ucfirst($dim);

    if($dim === 'Site Speed' || $dim === "Goals") {
      $dim = 'Date';
    }

    if(!empty($args['gawd_data']['filter_type']) && $dim === 'Date') {
      $dim = ucfirst($args['gawd_data']['filter_type']);
    }

    return $dim;
  }

  public function regex_callback($matches){
    return " " . $matches[0];
  }

  public function set_site_title($site_title){
    $this->site_title = $site_title;
  }

  public function set_menu_name($menu_name){
    $this->menu_name = $menu_name;
  }

  protected function sec_to_normal($data){
    if(strpos($data, ':') !== false) {
      return $data;
    }
    $data = ceil(floatval($data));
    $hours = strlen(floor($data / 3600)) < 2 ? '0' . floor($data / 3600) : floor($data / 3600);
    $mins = strlen(floor($data / 60 % 60)) < 2 ? '0' . floor($data / 60 % 60) : floor($data / 60 % 60);
    $secs = strlen(ceil($data % 60)) < 2 ? '0' . ceil($data % 60) : ceil($data % 60);
    return $data = $hours . ':' . $mins . ':' . $secs;
  }

}