<?php

abstract class GAWD_FILE {

    public $data;
    public $data_sum;
    public $_data_compare;
    public $file_type;
    public $report_type;
    public $dimension;
    public $dimension_recc;
    public $metric;
    public $metric_compare;
    public $img;
    public $name;
    public $start_date;
    public $end_date;
    public $tab_name;
    public $site_title;
    public $filter_type;
    public $column_names;
    public $sort;
    public $sort_by;
    public $file_name;
    public $file_dir;
    public $country_filter;
    public $geo_type;
    public $first_data;
    public $second_data;
    public $first_data_sum;
    public $second_data_sum;
    public $second_start_date;
    public $second_end_date;
    public $custom;

    protected $output;

    abstract protected function __construct();

    abstract protected function create_file();

    abstract protected function export_file();

    public function sort_data() {
        if ($this->sort_by == 'No') {
            $this->sort_by = $this->column_names[1];
        }

        if ($this->sort_by == $this->column_names[1] && $this->sort == 'asc') {
            return true;
        }

        //usort($this->data, array($this, "compare"));
    }

    public function compare($a, $b) {
        $data1 = $a[$this->sort_by];
        $data2 = $b[$this->sort_by];


        if ($this->sort_by == 'date') {
            $data1 = strtotime($data1);
            $data2 = strtotime($data2);
        }

        if ($data1 == $data2) {
            return 0;
        }
        if ($this->sort == 'asc') {
            return ($data1 < $data2) ? -1 : 1;
        } else {
            return ($data1 > $data2) ? -1 : 1;
        }
    }

    public function get_request_data($obj,$ajax = true, $email_data = null, $dimension_recc = null, $start_date = null, $end_date = null, $metric_compare_recc = null, $metric_recc = null) {
        $gawd_user_data = get_option('gawd_user_data');
        $this->site_title = $gawd_user_data['web_property_name'];
        $this->img = isset($_REQUEST["img"]) && $_REQUEST["img"] != '' ? sanitize_text_field($_REQUEST["img"]) : '';
        $this->start_date = isset($_REQUEST["gawd_start_date"]) && $_REQUEST["gawd_start_date"] != '' ? sanitize_text_field($_REQUEST["gawd_start_date"]) : date('Y-m-d', strtotime('-7 days'));
        $this->end_date = isset($_REQUEST["gawd_end_date"]) && $_REQUEST["gawd_end_date"] != '' ? sanitize_text_field($_REQUEST["gawd_end_date"]) : date('Y-m-d', strtotime('-1 days'));
        if(isset($_REQUEST["gawd_metric"])){
          $metric = sanitize_text_field($_REQUEST["gawd_metric"]);
        }
        else{
          if($metric_recc != null){
            $metric = $metric_recc;
          }
          else{
            $metric = 'ga:sessions';
          }
        }
        $this->set_metric($metric);
        $this->country_filter = isset($_REQUEST["country_filter"]) ? sanitize_text_field($_REQUEST["country_filter"]) : '';
        $this->custom         = isset( $_REQUEST["custom"] ) && $_REQUEST["custom"] != '' ? sanitize_text_field($_REQUEST["custom"]) : '';

        $this->geo_type = isset($_REQUEST["geo_type"]) ? sanitize_text_field($_REQUEST["geo_type"]) : '';
        if(isset($_REQUEST["gawd_metric_compare"])){
          $metric_compare = sanitize_text_field($_REQUEST["gawd_metric_compare"]);
        }
        else{
          if($metric_compare_recc != null){
            $metric_compare = $metric_compare_recc;
          }
          else{
            $metric_compare = '';
          }
        }
      
        $this->set_metric_compare($metric_compare);
        $this->tab_name = isset($_REQUEST["tab_name"]) ? sanitize_text_field($_REQUEST["tab_name"]) : '';
        /////COMPARED PAGES VARIABLES////
        $this->first_data = isset($_REQUEST["first_data"]) ? sanitize_text_field($_REQUEST["first_data"]) : '';
        $this->second_data = isset($_REQUEST["second_data"]) ? sanitize_text_field($_REQUEST["second_data"]) : '';
        $this->first_data_sum = isset($_REQUEST["first_data_sum"]) ? sanitize_text_field($_REQUEST["first_data_sum"]) : '';
        $this->second_data_sum = isset($_REQUEST["second_data_sum"]) ? sanitize_text_field($_REQUEST["second_data_sum"]) : '';
        $this->second_start_date = isset($_REQUEST["second_start_date"]) ? sanitize_text_field($_REQUEST["second_start_date"]) : '';
        $this->second_end_date = isset($_REQUEST["second_end_date"]) ? sanitize_text_field($_REQUEST["second_end_date"]) : '';
        /////END COMPARED PAGES VARIABLES////
        
        $this->_data_compare = isset($_REQUEST["_data_compare"]) ? sanitize_text_field($_REQUEST["_data_compare"]) : '';

        if(isset($_REQUEST["gawd_dimension"]) && $dimension_recc == null){
          $dimension = sanitize_text_field($_REQUEST["gawd_dimension"]);
        }
        else{
          if($dimension_recc != null){
            $dimension = $dimension_recc;
            $this->dimension_recc = $dimension_recc;
          }
          else{
            $dimension = 'Date';
          }
        }
        if($this->dimension_recc != null){
          $this->set_name($this->dimension_recc);

        }
        else{
          $this->set_name($this->tab_name);
        }
        $dimension = ucfirst(preg_replace('/([A-Z])/', '$1', $dimension));
        $dimension_arg = $dimension;
        if($this->tab_name == 'goals'){
          $dimension_arg = $this->tab_name;
        }
        $this->filter_type = isset($_REQUEST["filter_type"]) && $_REQUEST["filter_type"] != '' ? sanitize_text_field($_REQUEST["filter_type"]) : 'Date';
        $this->set_colum_names($dimension);
        $this->dimension = ($this->filter_type != 'Date') ? $this->filter_type : $dimension;
        if(strpos($this->dimension,'dimension') >-1){
          require_once(GAWD_DIR . '/admin/gawd_google_class.php');
          $gawd_client = GAWD_google_client::get_instance();
          $dimension_data = $gawd_client->get_custom_dimensions();
          foreach($dimension_data as $key => $value){
            if($dimension == substr($value['id'],3)){
              $dimension = $value['name'];
            }
          }
        }


        $this->export_type = isset($_REQUEST["export_type"]) ? sanitize_text_field($_REQUEST["export_type"]) : '';
        $this->report_type = isset($_REQUEST["report_type"]) ? sanitize_text_field($_REQUEST["report_type"]) : '';

        $this->sort = isset($_REQUEST["sort"]) ? sanitize_text_field($_REQUEST["sort"]) : 'asc';
        $this->sort_by = isset($_REQUEST["sort_by"]) ? sanitize_text_field($_REQUEST["sort_by"]) : '';

        $args = array(
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'metric' => preg_replace('/\s+/', '', $this->metric),
            'dimension' => preg_replace('/\s+/', '', $dimension_arg),
            'filter_type' => $this->filter_type,
            'geo_type' => $this->geo_type,
            'country_filter' => $this->country_filter,
            'custom' => $this->custom
        );

        if ($this->metric_compare != '') {
            $args['metric_compare'] = $this->metric_compare;
        }
        //if($ajax == true){
          $data = $obj->show_data($args);
        //}
        //else{
          if($this->first_data != '' || $this->_data_compare != ''){
            $this->set_data($data,false);
          }
          else{
            if($start_date != null){
              $this->start_date =  $start_date;
              $this->end_date = $end_date;
              $data = $email_data;
            }
              $this->set_data($data);
          }

        //}
       
    }

    public function set_data($data, $flag = true) {
      if($flag){
        $data = json_decode($data, true);

        $this->data = isset($data['chart_data']) ? $data['chart_data'] : $data;
        $this->data_sum = isset($data['data_sum']) ? $data['data_sum'] : '';
      }
    }

    private function set_metric($metric) {
        if (strpos($metric, 'ga:') !== false) {
            $metric = substr($metric, 3);
        }
        $this->metric = ucfirst(preg_replace('/([A-Z])/', ' $1', $metric));
    }

    private function set_metric_compare($metric_compare) {
        if (strpos($metric_compare, 'ga:') !== false) {
            $metric_compare = substr($metric_compare, 3);
        }
        $this->metric_compare = ucfirst(preg_replace('/([A-Z])/', ' $1', $metric_compare));
    }

    public function set_name($name = null) {
        if ($this->tab_name == 'pagePath') {
            $name = 'pages';
        }
        $name = ($name != null) ? $name : 'general';
        $this->name = ($name != 'general' && $name != 'general#') ? ucfirst(preg_replace('/([A-Z])/', ' $1', $name)) : "Audience";
    }

    public function set_colum_names($dimension) {
        if(function_exists('lcfirst') === false) {
            function lcfirst($str) {
                $str[0] = strtolower($str[0]);
                return $str;
            }
        }
        if(strpos($dimension,'Dimension') >-1){
          require_once(GAWD_DIR . '/admin/gawd_google_class.php');
          $gawd_client = GAWD_google_client::get_instance();
          $dimension_data = $gawd_client->get_custom_dimensions();
          foreach($dimension_data as $key => $value){
            if(lcfirst($dimension) == substr($value['id'],3)){
              $dimension = $value['name'];
            }
          }
        }
        if ($dimension == 'SiteSpeed' || $dimension == 'Adsense' || $dimension == 'Goals' || $dimension == 'Sales_performance' || $dimension == 'Date') {
            $dimension = ucfirst($this->filter_type);

        }

        if ($this->metric_compare != '' && $this->metric_compare != '0') {
            $colum_names = array(
                'No', trim($dimension), trim($this->metric), trim($this->metric_compare)
            );

        } else {
            $colum_names = array(
                'No', trim($dimension), trim($this->metric)
            );
            
        }
        
        if ($dimension == 'Page Path' || $dimension == 'PagePath') {
            $colum_names = array(
                'No','Page Path','Pageviews','Unique Pageviews',
                'Avg Time On Page','Entrances','Bounce Rate',
                'Exit Rate','Page Value','Avg Page Load Time'
            );
        } 
        elseif ($dimension == 'Landing Page Path' || $dimension == 'LandingPagePath') {
            $colum_names = array(
                'No','Landing Page','Sessions','Percent New Sessions','New Users','Bounce Rate','Pageviews Per Session','Avg Session Duration','Transactions','Transaction Revenue','Transactions Per Session'
            );
        }
        elseif ($dimension == 'Days To Transaction') {
          $colum_names = array('No', $dimension, 'transactions');
        } 
        elseif ($dimension == 'Date') {
            if ($this->metric_compare != '' && $this->metric_compare != '0') {
                $colum_names = array(
                    'No', $dimension, $this->metric, $this->metric_compare
                );

            } else {
                $colum_names = array('No', $dimension, $this->metric);

            }
        }

        $this->column_names = $colum_names;
                        

    }

    protected function set_file_name() {
        $file_name = ucfirst($this->name);
        if($this->second_data != '' || $this->_data_compare != ''){
          $file_name .= '(' . date('Y-m-d', strtotime($this->second_start_date)) . ' - ' . date('Y-m-d', strtotime($this->second_end_date)) . ') -compare- (' . date('Y-m-d', strtotime($this->start_date)) . ' - ' . date('Y-m-d', strtotime($this->end_date)) . ')';
        }
        else{
          $file_name .= '(' . date('Y-m-d', strtotime($this->start_date)) . ' - ' . date('Y-m-d', strtotime($this->end_date)). ')';
        }
        $this->file_name = (str_replace(array('"', "'", ' ', ','), '_', $file_name));
    }

    protected function set_file_dir() {
        if (!$this->file_name) {
            $this->set_file_name();
        }
        $uplode_dir = wp_upload_dir();

        $this->file_dir = $uplode_dir['path'] . '/' . $this->file_name . '-' . uniqid() . '.' . $this->file_type;
        //chmod($this->file_dir, 0777);
        $this->file_url = $uplode_dir['url'] . '/' . $this->file_name . '-' . uniqid() . '.' . $this->file_type;
    }

}
