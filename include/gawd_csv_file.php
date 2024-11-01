<?php

include_once GAWD_DIR . '/include/gawd_file.php';

class GAWD_CSV_FILE extends GAWD_FILE {

    public $file_type = 'csv';
    private $file_dir_option = 'gawd_export_csv_url';
    public function __construct() {
        
    }

    public function export_file($flag=true) { 
        $fullPath = get_option($this->file_dir_option);  
          if ($fd = fopen($fullPath, "r")) {

            $fsize = filesize($fullPath);
            $path_parts = pathinfo($fullPath);
            $ext = strtolower($path_parts["extension"]); 
            $this->file_name = $path_parts["basename"];
            $this->send_headers();

            while (!feof($fd)) {
                $buffer = fread($fd, 2048);
                echo $buffer;
            }
        }
        fclose($fd);  
        
       /* //$this->set_file_dir();
        $this->send_headers();
        $this->output = fopen('php://output', 'w');
        $this->put_csv();
        @fclose($this->output);*/
        die;
    }

    public function create_file() {
        $this->set_file_dir();        
        $this->output = fopen($this->file_dir, "w");        
        $this->put_csv();
        chmod($this->file_dir, 0777);
        @fclose($this->output);   
        update_option($this->file_dir_option, $this->file_dir);        
        return $this->file_dir;
    }

    private function put_csv() {
        //
        if($this->first_data != ''){
          if($this->_data_compare != ""){
            $columns = $this->get_columns();
          }
          else{
            $columns = $this->get_columns_compared_pages();
          }
        }
        elseif($this->_data_compare != ""){
          $columns = $this->get_compared_data();      
        }
        else{
          $columns = $this->get_columns();
        }
        echo "\xEF\xBB\xBF";
        fputcsv($this->output, array($this->site_title), ',');
        $date_text = ucfirst($this->name);
        if($this->_data_compare != ''){
          $date_text .= '(' . date('M d, Y', strtotime($this->start_date)) . ' - ' . date('M d, Y', strtotime($this->end_date)) . ') -compare- (' . date('M d, Y', strtotime($this->second_start_date)) . ' - ' . date('M d, Y', strtotime($this->second_end_date)) . ')';
        }
        else{
          $date_text .= ' (' . $this->start_date . ' -  ' . $this->end_date . ')';
        }
        fputcsv($this->output, array($date_text), ',');
        foreach ($columns as $line) {
            fputcsv($this->output, $line, ',');
        }
    }
    private function get_compared_data(){
      $this->first_data_sum = json_decode(stripslashes($this->first_data_sum));
      $this->second_data_sum = json_decode(stripslashes($this->second_data_sum));
      $dimension = isset($_REQUEST["dimension"]) ? sanitize_text_field($_REQUEST["dimension"]) : 'Date';
      //$this->column_names[] = $this->metric . ' compare';
        $columns = array($this->column_names);
        foreach ($this->_data_compare as $dat => $value) {
          $temp = array();
          foreach ($this->column_names as $cols) {
             $cols = trim($cols);
              $val = $value[$cols];
              if($cols == 'Bounce Rate' || $cols == 'Bounce Rate compare' || $cols == 'Exit Rate compare' || $cols == 'Exit Rate' || $cols == 'Ecommerce Conversion Rate' || $cols == 'Percent New Sessions' || $cols == 'Percent New Sessions compare' || $cols == 'Transactions Per Session'){
                $val =  number_format(floatval($val),2, '.', ',') . '%';
              }
              else if($cols == 'Page Value' || $cols == 'Revenue' || $cols == 'Transaction Revenue'){
                $percentage_of_total = $this->data_sum[$cols] != '0' ? floatval($val)/$this->data_sum[$cols]*100 : 0;
                $percentage_of_total = number_format(floatval($percentage_of_total),2, '.', ',');
                $val = '$' . number_format(floatval($val),2, '.', ',') . ' (' . $percentage_of_total .'%)';
              }
              else if($cols == 'Pageviews Per Session'){
                $val = number_format(floatval($val),2, '.', ',');
              }
              else if($cols == 'Avg Time On Page' || $cols == 'Avg Page Load Time' || $cols == 'Avg Session Duration' || $cols == 'Avg Page Load Time' || $cols == 'Avg Redirection Time' || $cols == 'Avg Server Response Time' || $cols == 'Avg Page Download Time' || $cols == 'Duration'){
                if(strpos(':',$val) == false){
                  $val = $this->sec_to_normal(floatval($val));
                }
              }
              elseif($cols == 'No' || $cols == $_REQUEST['gawd_dimension'] || $cols == $dimension || $cols == 'Week' || $cols == 'Month' || $cols == 'Hour'){
                $val = $val;
              }                  
              else{
                if(strpos($cols, 'compare') !== false){
                  $compare_cols = str_replace(' compare','',$cols);
                  $percentage_of_total = $this->second_data_sum->$compare_cols != '0' ? floatval($val)/$this->second_data_sum->$compare_cols*100 : 0;
                }
                else{
                  $percentage_of_total = $this->first_data_sum->$cols != '0' ? floatval($val)/$this->first_data_sum->$cols*100 : 0;
                }
                $percentage_of_total = number_format(floatval($percentage_of_total),2, '.', ',');
                $val = number_format(floatval($val),2, '.', ',') . ' (' . $percentage_of_total .'%)';
              }
              $temp[] = $val;
          }
          $columns[] = $temp;
        }
        $row = array();
        foreach ($this->column_names as $cols) {
          $cols = trim($cols);
          if($cols == 'No' || $cols == $dimension || $cols == 'Hour' || $cols == 'Week' || $cols == 'Month'){
            $row[] = ' ';
          }
          else{
            $val = '';
            $val_second = '';
            if($cols != trim($this->metric.' compare')){
                $val = $this->first_data_sum->$cols;
                $val_second = $this->second_data_sum->$cols;
            }
            if($cols == 'Bounce Rate'){
              $val = 'Avg: '.number_format(floatval($val),2, '.', ',') . '%';
              $val_second = 'Avg: '.number_format(floatval($val_second),2, '.', ',') . '%';
            }
            else if($cols == 'Pageviews Per Session'){
              $val = 'Total: '. number_format(floatval($val),2, '.', ',');
              $val_second = 'Total: '. number_format(floatval($val_second),2, '.', ',');
            }
            else if($cols == 'Avg Session Duration'){
              $val = 'Avg: '.$this->sec_to_normal(floatval($val));
              $val_second = 'Avg: '.$this->sec_to_normal(floatval($val_second));
            }
            elseif($cols == 'Percent New Sessions'){
              $val = 'Avg: '. number_format(floatval($val),2, '.', ',').'%';
              $val_second = 'Avg: '. number_format(floatval($val_second),2, '.', ',').'%';
            }
            else{
              $val_second = $val_second != '' ? 'Total: '. number_format(floatval($val_second),2, '.', ',') : '';
              $val = $val != '' ? 'Total: '. number_format(floatval($val),2, '.', ',') : '';

            }
            if($val != '' && $val_second != ''){
              $row[] = $val;
              $row[] = $val_second;
            }
          }
        }
        $columns[] = $row;
        return $columns;
    }
    private function get_columns_compared_pages() {
      $this->first_data = json_decode(stripslashes($this->first_data));
      $this->second_data = json_decode(stripslashes($this->second_data));
      $this->first_data_sum = json_decode(stripslashes($this->first_data_sum));
      $this->second_data_sum = json_decode(stripslashes($this->second_data_sum));
      $dimension = isset($_REQUEST["dimension"]) ? sanitize_text_field($_REQUEST["dimension"]) : 'Date';

      if ($dimension == 'Page Path' || $dimension == 'pagePath') {
            $metrics = array(
                'No','Page Path','Pageviews','Unique Pageviews',
                'Avg Time On Page','Entrances','Bounce Rate',
                'Exit Rate','Page Value','Avg Page Load Time'
            );
        } elseif ($dimension == 'Landing Page Path' || $dimension == 'landingPagePath') {
            $metrics = array(
                'No','Landing Page','Sessions','Percent New Sessions','New Users','Bounce Rate','Pageviews Per Session','Avg Session Duration','Transactions','Transaction Revenue','Transactions Per Session'
            );
        }

      $count = count($metrics) - 1;
      $margin = '';
      if($dimension == 'pagePath'){
        $margin = 'margin-left:18px;';
        $count = 9.5;
      }
      if ($count == 0) {
          $no_width = "100";
          $row_width = '0';
      } else {
          $no_width = "5";
          $row_width = (95 / $count);
      }

      $all_pages = array();
      $max_length_data = count($this->first_data) >= count($this->second_data) ? $this->first_data : $this->second_data;
      $min_length_data = count($this->first_data) >= count($this->second_data) ? $this->second_data : $this->first_data;
      
      $max_length = count($max_length_data);
      $min_length = count($min_length_data);
      

      $min_paths = array(); 

      for($i=0 ;$i<$min_length; $i++){

        $min_paths[] = $min_length_data[$i]->$metrics[1];
      } 

      for($i=0 ;$i<$max_length; $i++){
        $all_pages[$max_length_data[$i]->$metrics[1]] = Array();
        $all_pages[$max_length_data[$i]->$metrics[1]][0] = $max_length_data[$i];
        if(!isset($min_length_data[$i]) || in_array($max_length_data[$i]->$metrics[1],$min_paths) === false){
          $all_pages[$max_length_data[$i]->$metrics[1]][1] = 'no';
        }
        else{
          $key = $i;
          for($j=0; $j<$min_length; $j++){
            if($min_length_data[$j]->$metrics[1] == $max_length_data[$i]->$metrics[1]){
              $key = $j;
              break;
            }
          }
          $all_pages[$max_length_data[$i]->$metrics[1]][1] = $min_length_data[$key];
        }
      }  
        $table = array();
        $row = array();
        for($j=0 ;$j<count($metrics); $j++){
          $row[] =  $metrics[$j];
        }
        $table[] = $row;

        $row = array();
        for($j=0 ;$j<count($metrics); $j++){
          if($metrics[$j] == 'No' || $metrics[$j] == 'Page Path' || $metrics[$j] == 'Landing Page'){
            $row[] = ' ';
          }
          else{
            $this->first_data_sum->$metrics[$j] = floatval($this->first_data_sum->$metrics[$j]);
            $this->second_data_sum->$metrics[$j] = floatval($this->second_data_sum->$metrics[$j]);
            $percent = number_format((($this->first_data_sum->$metrics[$j] - $this->second_data_sum->$metrics[$j])/((($this->second_data_sum->$metrics[$j])!=0) ? $this->second_data_sum->$metrics[$j] : 1)) * 100, 2, '.', ',');
            if(!($percent)){
              $percent = 0;
            }
            else if(strpos($percent, '-') === 0){
              $percent = substr($percent,1);
            }
            $this->second_data_sum_value = number_format($this->second_data_sum->$metrics[$j],2, '.', ',');
            $this->first_data_sum_value = number_format($this->first_data_sum->$metrics[$j],2, '.', ',');
            if($metrics[$j] == 'Avg Time On Page' || $metrics[$j] == 'Avg Page Load Time' || $metrics[$j] == 'Avg Session Duration'){
              $this->second_data_sum_value = $this->sec_to_normal($this->second_data_sum_value);
              $this->first_data_sum_value = $this->sec_to_normal($this->first_data_sum_value);
            }       
            if($metrics[$j] == 'Bounce Rate' || $metrics[$j] == 'Exit Rate' || $metrics[$j] == 'New Sessions' || $metrics[$j] == 'Ecommerce Conversion Rate'){
              $this->first_data_sum_value = number_format(floatval($this->first_data_sum_value),2, '.', ',') . '%';
              $this->second_data_sum_value = number_format(floatval($this->second_data_sum_value),2, '.', ',') . '%';
            }
            else if($metrics[$j] == 'Page Value' || $metrics[$j] == 'Revenue'){
              $this->first_data_sum_value = '$' . number_format(floatval($this->first_data_sum_value),2, '.', ',');
              $this->second_data_sum_value = '$' . number_format(floatval($this->second_data_sum_value),2, '.', ',');
            }
            $row[] = sanitize_text_field($percent) . '%  ' . sanitize_text_field($this->first_data_sum_value).' vs '.sanitize_text_field($this->second_data_sum_value);
          }
        }
        $table[] = $row;
        $length = count($all_pages);
        $keys = array_keys($all_pages);
      for($i=0 ;$i<$length; $i++){
        $row = array();
        $row[] = intval($i+1);
        $row[] = $keys[$i];
        $table[] = $row;
        $row = array();
        for($j = 0; $j < count($metrics); $j++){
          if($metrics[$j] == 'No'){
            $row[] = ' ';
          }
          else if($metrics[$j] == 'Page Path' || $metrics[$j] == 'Landing Page'){
            $row[] = date('M d, Y', strtotime($this->start_date)) . ' - ' . date('M d, Y', strtotime($this->end_date));
          }
          else{
            $keys[$i] = sanitize_text_field($keys[$i]);
            if($all_pages[$keys[$i]][0] != 'no'){
              $line_value = $all_pages[$keys[$i]][0]->$metrics[$j];
              if($metrics[$j] == 'Bounce Rate' || $metrics[$j] == 'Exit Rate' || $metrics[$j] == 'New Sessions' || $metrics[$j] == 'Ecommerce Conversion Rate'){
                $line_value = number_format(floatval($line_value),2, '.', ',') . '%';
              }
              else if($metrics[$j] == 'Page Value' || $metrics[$j] == 'Revenue' || $metrics[$j] == 'Transaction Revenue' || $metrics[$j] == 'Item Revenue'){
                $percentage_of_total =  $this->first_data_sum->$metrics[$j] != '0' ? floatval($line_value)/$this->first_data_sum->$metrics[$j]*100 : 0;
                $percentage_of_total = number_format(floatval($percentage_of_total),2, '.', ',');
                $line_value = '$' . number_format(floatval($line_value),2, '.', ',') . ' (' . $percentage_of_total .'%)'; 
              }
              else if($metrics[$j] == 'Pageviews Per Session'){
                $line_value = number_format(floatval($line_value),2, '.', ',');
              }
              else if($metrics[$j] == 'Avg Time On Page' || $metrics[$j] == 'Avg Page Load Time' || $metrics[$j] == 'Avg Session Duration'){
                $line_value = $this->sec_to_normal(floatval($line_value));
              }
              else{
                $percentage_of_total =  $this->first_data_sum->$metrics[$j] != '0' ? floatval($line_value)/$this->first_data_sum->$metrics[$j]*100 : 0;
                $percentage_of_total = number_format(floatval($percentage_of_total),2, '.', ',');
                $line_value = number_format(floatval($line_value),2, '.', ',') . ' (' . $percentage_of_total .'%)';
              }
              $row[] = $line_value;
            }
            else{
              $row[] = '0';
            }
          }
        }
        $table[] = $row;
        $row = array();
        for($j = 0; $j < count($metrics); $j++){
          if($metrics[$j] == 'No'){
            $row[] = ' ';
          }      
          else if($metrics[$j] == 'Page Path' || $metrics[$j] == 'Landing Page'){
            $row[] = date('M d, Y', strtotime($this->second_start_date)) . ' - ' . date('M d, Y', strtotime($this->second_end_date));
          }
          else{
            $keys[$i] = sanitize_text_field($keys[$i]);
            if($all_pages[$keys[$i]][1] != 'no'){
              $line_value = $all_pages[$keys[$i]][1]->$metrics[$j];
              if($metrics[$j] == 'Bounce Rate' || $metrics[$j] == 'Exit Rate' || $metrics[$j] == 'New Sessions' || $metrics[$j] == 'Ecommerce Conversion Rate'){
                $line_value = number_format(floatval($line_value),2, '.', ',') . '%';
              }
              else if($metrics[$j] == 'Page Value' || $metrics[$j] == 'Revenue' || $metrics[$j] == 'Transaction Revenue' || $metrics[$j] == 'Item Revenue'){
                $percentage_of_total =  $this->second_data_sum->$metrics[$j] != '0' ? floatval($line_value)/$this->second_data_sum->$metrics[$j]*100 : 0;
                $percentage_of_total = number_format(floatval($percentage_of_total),2, '.', ',');
                $line_value = '$' . number_format(floatval($line_value),2, '.', ',') . ' (' . $percentage_of_total .'%)';
              }
              else if($metrics[$j] == 'Avg Time On Page' || $metrics[$j] == 'Avg Page Load Time' || $metrics[$j] == 'Avg Session Duration'){
                $line_value = $this->sec_to_normal(floatval($line_value));
              }
              else if($metrics[$j] == 'Pageviews Per Session'){
                $line_value = number_format(floatval($line_value),2, '.', ',');
              }
              else{
                $percentage_of_total =  $this->second_data_sum->$metrics[$j] != '0' ? floatval($line_value)/$this->second_data_sum->$metrics[$j]*100 : 0;
                $percentage_of_total = number_format(floatval($percentage_of_total),2, '.', ',');
                $line_value = number_format(floatval($line_value),2, '.', ',') . ' (' . $percentage_of_total .'%)';
              }
              $row[] = $line_value;
            }
            else{
              $row[] = '0';
            }
          }
        }
        $table[] = $row;
        $row = array();
        for($j = 0; $j < count($metrics); $j++){
          if($metrics[$j] == 'No'){
            $row[] = ' ';
          }
          else if($metrics[$j] == 'Page Path' || $metrics[$j] == 'Landing Page'){
            $row[] = '% Change';
          }
          else{
            if($all_pages[$keys[$i]][0] != 'no'){
              $_single_data = $all_pages[$keys[$i]][0]->$metrics[$j];
            }
            else{
              $_single_data = 0;
            }
            if($all_pages[$keys[$i]][1] != 'no'){
              $single_data = $all_pages[$keys[$i]][1]->$metrics[$j];        
            }
            else{
              $single_data = 0;
            }
            if($metrics[$j] == 'Avg Time On Page' || $metrics[$j] == 'Avg Page Load Time' || $metrics[$j] == 'Avg Session Duration'){
              $_single_data = explode(":",$_single_data);
              if(strpos($_single_data[0], '0') === 0){
                $_single_data[0] = substr($_single_data[0],0,1);
                $_single_data[0] = $_single_data[0];
              }
              if(count($_single_data) > 1){
                if(strpos($_single_data[1],'0') === 0){
                $_single_data[1] = substr($_single_data[1],0,1);
                $_single_data[1] = $_single_data[1]*60;
                }
                if(strpos($_single_data[2],'0') === 0){
                  $_single_data[2] = substr($_single_data[2],0,1);
                  $_single_data[2] = $_single_data[2];
                }
              }
              $a  = !isset($_single_data[0]) ? 0 : floatval($_single_data[0]); 
              $b =  !isset($_single_data[1]) ? 0 : floatval($_single_data[1]);
              $c =  !isset($_single_data[2]) ? 0 : floatval($_single_data[2]);
              $_single_data = $a + $b + $c;
              $single_data = explode(":",$single_data);
              if(strpos($single_data[0], '0') === 0){
                $single_data[0] = substr($single_data[0],0,1);
                $single_data[0] = $single_data[0];
              }
              if(count($single_data) > 1){
                if(strpos($single_data[1],'0') === 0){
                  $single_data[1] = substr($single_data[1],0,1);
                  $single_data[1] = $single_data[1]*60;
                }
                if(strpos($single_data[2],'0') === 0){
                  $single_data[2] = substr($single_data[2],0,1);
                  $single_data[2] = $single_data[2];
                }
              }
              $a  = !isset($single_data[0]) ? 0 : floatval($single_data[0]); 
              $b =  !isset($single_data[1]) ? 0 : floatval($single_data[1]);
              $c =  !isset($single_data[2]) ? 0 : floatval($single_data[2]);
              $single_data = $a + $b + $c;
            }

            $single_percent = ($single_data != 0) ? number_format((($_single_data - $single_data)/$single_data) * 100,2, '.', ',').'%' :'infinity';
            $row[] = $single_percent;
          }
        }
        $table[] = $row;
      }
      return $table;
    }
    public function sec_to_normal($data){
      $hours = strlen(floor($data / 3600)) < 2 ?  '0' . floor($data / 3600) : floor($data / 3600);
      $mins = strlen(floor($data / 60 % 60)) < 2 ? '0' . floor($data / 60 % 60) : floor($data / 60 % 60);
      $secs = strlen(ceil($data % 60)) < 2 ? '0' . ceil($data % 60) : ceil($data % 60);
      return $data = $hours . ':' . $mins . ':' . $secs;
    }
    private function get_columns() {
      $dimension = isset($_REQUEST["dimension"]) ? sanitize_text_field($_REQUEST["dimension"]) : 'Date';
      $columns = array($this->column_names);
      $_REQUEST['gawd_dimension'] = isset($_REQUEST['gawd_dimension']) ? sanitize_text_field($_REQUEST['gawd_dimension']) : $dimension;
        if($_REQUEST['gawd_dimension'] == "Session Duration Bucket"){
          $_data = array();
          //$j = 1;
          foreach($this->data as $val){
            if(isset($_data[$val["Session Duration Bucket"]])){
              $_data[$val["Session Duration Bucket"]]["Users"] += floatval($val["Users"]);
              $_data[$val["Session Duration Bucket"]]["Sessions"] += floatval($val["Sessions"]);
              $_data[$val["Session Duration Bucket"]]["Percent New Sessions"] += floatval($val["Percent New Sessions"]);
              $_data[$val["Session Duration Bucket"]]["Bounce Rate"] += floatval($val["Bounce Rate"]);
              $_data[$val["Session Duration Bucket"]]["Pageviews"] += floatval($val["Pageviews"]);
              $_data[$val["Session Duration Bucket"]]["Avg Session Duration"] += $val["Avg Session Duration"];
            }
            else{
              // $val["No"] = $j;
              // $j++;
              $_data[$val["Session Duration Bucket"]] = $val; 
              $_data[$val["Session Duration Bucket"]]["order"] = intval($val["Session Duration Bucket"]);
            }
          }
          $this->data = array_values($_data); 
          foreach ($this->data as $key => $row) {
            $yyy[$key]  = $row['order'];
          }
          array_multisort($yyy, SORT_ASC, $this->data);              
          foreach($this->data as $j=>$val){
            $val["No"] = ($j+1);
            $this->data[$j] = $val;
          }
        }
        elseif($_REQUEST['gawd_dimension'] == "daysToTransaction"){
          foreach ($this->data as $key => $row) {
              $daysToTransaction[$key]  = $row['Days To Transaction'];
          }
          array_multisort($daysToTransaction, SORT_ASC, $this->data);
          foreach($this->data as $j=>$val){
              $val["No"] = ($j+1);
              $this->data[$j] = $val;
          }
        }

        foreach ($this->data as $dat => $value) {
          $temp = array();
          foreach ($this->column_names as $cols) {
            $cols = preg_replace('!\s+!',' ',trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $cols))));
              $val = $value[$cols];
              if($cols == 'Bounce Rate' || $cols == 'Exit Rate' || $cols == 'Ecommerce Conversion Rate'|| $cols == 'Percent New Sessions' || $cols == 'Transactions Per Session'){
                $val =  number_format(floatval($val),2, '.', ',') . '%';
              }
              else if($cols == 'Page Value' || $cols == 'Revenue' || $cols == 'Transaction Revenue'){
                $percentage_of_total = $this->data_sum[$cols] != '0' ? floatval($val)/$this->data_sum[$cols]*100 : 0;
                $percentage_of_total = number_format(floatval($percentage_of_total),2, '.', ',');
                $val = '$' . number_format(floatval($val),2, '.', ',') . ' (' . $percentage_of_total .'%)';
              }
              else if($cols == 'Pageviews Per Session'){
                $val = number_format(floatval($val),2, '.', ',');
              }
              else if($cols == 'Avg Time On Page' || $cols == 'Avg Page Load Time' || $cols == 'Avg Session Duration' || $cols == 'Avg Page Load Time' || $cols == 'Avg Redirection Time' || $cols == 'Avg Server Response Time' || $cols == 'Avg Page Download Time' || $cols == 'Duration'){
                if(strpos(':',$val) == false){
                  $val = $this->sec_to_normal(floatval($val));
                }
              }
              elseif($cols == 'No' || $cols == $_REQUEST['gawd_dimension'] || $cols == $dimension){
                $val = $val;
              }                  
              else{
                if($cols != preg_replace('!\s+!',' ',trim(ucfirst(preg_replace('/([A-Z])/', ' $1', sanitize_text_field($_REQUEST['gawd_dimension'])))))){
                  $percentage_of_total = isset($this->data_sum[$cols]) ? $this->data_sum[$cols] != '0' ? floatval($val)/$this->data_sum[$cols]*100 : 0 : '';
                  if($percentage_of_total != ''){
                    $percentage_of_total = number_format(floatval($percentage_of_total),2, '.', ',');
                    if(strlen($val) > 3){
                      $val = number_format(floatval($val),2, '.', ',');
                    }
                    $val = $val .' (' . $percentage_of_total .'%)';
                  }
                }
              }
              $temp[] = $val;
          }
          $columns[] = $temp;
        }
        $row = array();
        foreach ($this->column_names as $cols) {
          $cols = preg_replace('!\s+!',' ',trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $cols))));
          if($cols == 'No' || $cols == $dimension || $cols == preg_replace('!\s+!',' ',trim(ucfirst(preg_replace('/([A-Z])/', ' $1', sanitize_text_field($_REQUEST['gawd_dimension'])))))){
            $row[] = ' ';
          }
          else{
            $val = isset($this->data_sum[$cols]) ? $this->data_sum[$cols] : '';
            if($cols == 'Bounce Rate' || $cols == 'Exit Rate' || $cols == 'Ecommerce Conversion Rate'|| $cols == 'Percent New Sessions' || $cols == 'Transactions Per Session'){
              $val = 'Avg: '.number_format(floatval($val),2, '.', ',') . '%';
            }
            else if($cols == 'Page Value' || $cols == 'Revenue' || $cols == 'Transaction Revenue'){
              $val = 'Total: $'.number_format(floatval($val),2, '.', ',');
            }
            else if($cols == 'Pageviews Per Session'){
              $val = 'Total: '. number_format(floatval($val),2, '.', ',');
            }
            else if($cols == 'Avg Time On Page' || $cols == 'Avg Page Load Time' || $cols == 'Avg Session Duration' || $cols == 'Avg Page Load Time' || $cols == 'Avg Redirection Time' || $cols == 'Avg Server Response Time' || $cols == 'Avg Page Download Time' || $cols == 'Duration'){
              $val = 'Avg: '.$this->sec_to_normal(floatval($val));
            }
            else{
              $val = $val != '' ? 'Total: '. number_format(floatval($val),2, '.', ',') : '';
            }
             $row[] = $val;
          }
        }
        $columns[] = $row;
        return $columns;
    }

    private function send_headers() {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $this->file_name . '.csv');
    }

}
