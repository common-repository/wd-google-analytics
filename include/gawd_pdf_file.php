<?php

include_once GAWD_DIR . '/include/gawd_file.php';

class gawd_pdf_file extends GAWD_file {

    private $img_url = '';
    public $file_type = 'pdf';
    private $file_dir_option = 'gawd_export_pdf_url';

    public function __construct() {
        
    }

    public function export_file() {
        $fullPath = get_option($this->file_dir_option);
       if ($fd = fopen($fullPath, "r")) {

            $fsize = filesize($fullPath);
            $path_parts = pathinfo($fullPath);
            $ext = strtolower($path_parts["extension"]);
            header("Content-type: application/pdf;charset=utf-8");
            header("Content-Disposition: attachment; filename=\"" . $path_parts["basename"] . "\""); // use 'attachment' to force a file download

            header("Content-length: $fsize");
            header("Cache-control: private"); //use this to open files directly
            while (!feof($fd)) {
                $buffer = fread($fd, 2048);
                echo $buffer;
            }
        }
        fclose($fd);
        exit;
    }
    public function export_reccuing(){
      return;//todo removed lib

     $datay1 = array();
     $datay2 = array();
     $labels = array(); 

      foreach($this->data as $val){
          if(isset($val[$this->metric])){
            $datay1[] = $val[$this->metric];
            $labels[] = $val[trim(preg_replace('/([A-Z])/', ' $1', $this->dimension))];
          }
          if($this->metric_compare != '' && $this->metric_compare != 0){
            if(isset($val[$this->metric])){
              $datay2[] = $val[$this->metric_compare];
            }
          }
      }
      if(count($datay1) <2 ){
        return false;
      }
      if(count($datay2) <2 ){
        $datay2 = array();
      }
      // Setup the graph
      $graph = new Graph(800,400);
      $graph->SetScale("textlin");

      $theme_class=new UniversalTheme;

      $graph->SetTheme($theme_class);
      $graph->img->SetAntiAliasing(false);
      $graph->SetBox(false);
      $graph->img->SetAntiAliasing();

      $graph->yaxis->HideZeroLabel();
      $graph->yaxis->HideLine(false);
      $graph->yaxis->HideTicks(false,false);

      $graph->xgrid->Show();
      $graph->xgrid->SetLineStyle("solid");
      $graph->xaxis->SetTickLabels($labels);
      $graph->xgrid->SetColor('#E3E3E3');

      // Create the first line
      $p1 = new LinePlot($datay1);
      $graph->Add($p1);
      $p1->SetColor("#6495ED");
      $p1->SetLegend($this->metric);    
      if($datay2 != array()){
        $p2 = new LinePlot($datay2);
        $graph->Add($p2);
        $p2->SetColor("#B22222");
        $p2->SetLegend($this->metric_compare);
      }
      $graph->legend->SetFrameWeight(1);
      // Output line
      $uplode_dir = wp_upload_dir();
      $image_dir = $uplode_dir['path'] . '/export_chart.png';
      imagejpeg($graph->Stroke(),$image_dir,100);
      return true;
    }
    public function create_file($flag = true) {
return;//todo
        $this->create_image();

        $this->set_file_dir();

        $content = $this->get_file_content($flag);

//        include_once GAWD_DIR . '/inc/html2pdf/html2pdf.class.php';
        try {
            $html2pdf = new HTML2PDF('L', 'A4', 'en', true, 'UTF-8', 0);
            $html2pdf->pdf->SetDisplayMode('fullpage');
            $html2pdf->setDefaultFont("freesans");
            //$html2pdf->setImageQuality(1);
            $html2pdf->pdf->addFont('freesans', 'B', GAWD_DIR . '/inc/html2pdf/_tcpdf_5.9.206/fonts/freesans.php');

            $html2pdf->writeHTML($content);
            $html2pdf->Output($this->file_dir, 'F');
        } catch (Exception $e) {
            echo $e;
            exit;
        }
        update_option($this->file_dir_option, $this->file_dir);
    }

    private function get_file_content($flag) {
      if($flag == false){
        $flag = true;
      }
        $dimension = isset($_REQUEST["dimension"]) ? sanitize_text_field($_REQUEST["dimension"]) : 'Date';
        if($flag === true){
          $_REQUEST['gawd_dimension'] =  isset($_REQUEST['gawd_dimension']) ? preg_replace('!\s+!',' ',trim(ucfirst(preg_replace('/([A-Z])/', ' $1', sanitize_text_field($_REQUEST['gawd_dimension']))))) : $dimension;
          $count = count($this->column_names) - 1;
          if($dimension == 'pagePath'){
            $count = 9.5;
          }
          if ($count == 0) {
              $no_width = "100";
              $row_width = '0';
          } else {
              $no_width = "5";
              $row_width = (95 / $count);
          }
          $html = '';
          if($this->img_url == '' && $this->dimension_recc != 'daysToTransaction' && $_REQUEST['gawd_dimension'] != 'Days To Transaction' ){
            $a = $this->export_reccuing();
            if($a){
              $uplode_dir = wp_upload_dir();
              $image_dir = $uplode_dir['path'] . '/export_chart.png';
              $this->img_url = $image_dir;
            }
            else{
              $this->img_url = '';
            }

          } 
          $html .= '<h2 style="text-align:center; margin-bottom:5px; color:#949494">' . $this->site_title . ' </h2>';
          $html .= '<h4 style="text-align:center; margin-bottom:5px; color:#7DB5D8">' . $this->file_name . '</h4>';
          if ($this->img_url != '') {
              $html .= '<div style="width:100%;height:auto;">';
              $html .= '<img width="800" style="margin-left:200px;" src="' . $this->img_url . '"/>';
              $html .= '</div>';
          }
          $margin = 'margin-left:47px';
          $html .= '<table border=1 style="width:100%;border: 1px solid #DCDCDC;border-spacing: 0;border-collapse: collapse;">'
                  . '<tr>';
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
            elseif($_REQUEST['gawd_dimension'] == "Days To Transaction"){
              foreach ($this->data as $key => $row) {
                  $daysToTransaction[$key]  = $row['Days To Transaction'];
              }
              array_multisort($daysToTransaction, SORT_ASC, $this->data);
              foreach($this->data as $j=>$val){
                  $val["No"] = ($j+1);
                  $this->data[$j] = $val;
              }
            }
          foreach ($this->column_names as $column) {
            $column = trim($column);
              $width = ($column == 'No') ? 'width:' . $no_width . '%;' : 'width:' . $row_width . '%;';
              $html .= '<th style="' . $width . ';background-color: #7DB5D8;color: #fff;">' . $column . '</th>';
          }
          $html .= '</tr>';
          $row_count = 0;
          foreach ($this->data as $dat => $value) {
              if ((strtolower($this->column_names[1]) == 'page path' || strtolower($this->column_names[1]) == 'landing page path') && $row_count >= 150) {
                  break;
              }
              $html .= '<tr>';     
              
              foreach ($this->column_names as $key => $column) {
                $column = trim(preg_replace('!\s+!',' ',trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $column)))));

                  $text = ucfirst($value[$column]);
                  $width = $column == 'No' ? 'width:' . $no_width . '%;' : 'width:' . $row_width . '%;';
                  if($column == "Page Path" || $column == "Landing Page" || strlen($text)>20){
                    if (strpos($text, ' ') === false) {
                        $text = '<span>' . $text;
                        $text = wordwrap($text, '10', '</span><span>', true);
                        $text .= '</span>';
                    }
                  }
                  elseif($column == 'Bounce Rate' || $column == 'Exit Rate' || $column == 'Ecommerce Conversion Rate'|| $column == 'Percent New Sessions' || $column == 'Transactions Per Session'){
                    $text = number_format(floatval($text),2, '.', ',') . '%';
                  }
                  elseif($column == 'Page Value' || $column == 'Item Revenue' || $column == 'Revenue' || $column == 'Transaction Revenue'){
                    $percentage_of_total = $this->data_sum[$column] != '0' ? floatval($text)/$this->data_sum[$column]*100 : 0;
                    $percentage_of_total = number_format(floatval($percentage_of_total),2, '.', ',');
                    $text = '$' . number_format(floatval($text),2, '.', ',') . ' <span class="row_percent">(' . $percentage_of_total .'%)</span>';
                  }
                  elseif($column == 'Pageviews Per Session'){
                    $text = number_format(floatval($text),2, '.', ',');
                  }
                  elseif($column == 'Avg Time On Page' || $column == 'Avg Page Load Time' || $column == 'Avg Session Duration' || $column =='Duration' || $column == 'Avg Page Load Time' || $column == 'Avg Redirection Time' || $column == 'Avg Server Response Time' || $column == 'Avg Page Download Time' || $column == 'Duration'){
                    if(strpos(':',$text) == false){
                      $text = $this->sec_to_normal(floatval($text));

                    }
                  }   
                  elseif($column == 'No' || $column == $_REQUEST['gawd_dimension'] || $column == $dimension){
                    $text = $text;
                  }                  
                  else{
                      $percentage_of_total = isset($this->data_sum[$column]) ? $this->data_sum[$column] != '0' ? floatval($text)/$this->data_sum[$column]*100 : 0 : '';
                      if($percentage_of_total != ''){
                        $percentage_of_total = number_format(floatval($percentage_of_total),2, '.', ',');
                        if(strlen($text) > 3){
                          $text = number_format(floatval($text),2, '.', ',');
                        }
                        $text = $text .' <span class="row_percent">(' . $percentage_of_total .'%)</span>';
                      }
                  }
                  $html .= '<td style="' . $width . ';border: 1px solid #DCDCDC;padding-top:5px;color:#828282; ">' . $text . '</td>';

              }
              $html .= '</tr>';
              $row_count++;
          }
          $html .= '<tr>';
            foreach ($this->column_names as $key => $column) {
              $column = preg_replace('!\s+!',' ',trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $column))));
              $width = $column == 'No' ? 'width:' . $no_width . '%;' : 'width:' . $row_width . '%;';
              if($column == 'No' || $column == $_REQUEST['gawd_dimension'] || $column == 'Week' || $column == 'Month' || $column == 'Hour' || $column == $dimension){
                $html .= '<td style="' . $width . ';border: 1px solid #DCDCDC;padding-top:5px;color:#828282; "></td>';
              }
              else{
                $val = isset($this->data_sum[$column]) ? $this->data_sum[$column] : '';
                if($column == 'Bounce Rate' || $column == 'Exit Rate' || $column == 'Ecommerce Conversion Rate'|| $column == 'Percent New Sessions' || $column == 'Transactions Per Session'){
                  $val = 'Avg: '.number_format(floatval($val),2, '.', ',') . '%';
                }
                else if($column == 'Page Value' || $column == 'Revenue' || $column == 'Item Revenue' || $column == 'Transaction Revenue'){
                  $val = 'Total: $'.number_format(floatval($val),2, '.', ',');
                }
                else if($column == 'Pageviews Per Session'){
                  $val = 'Total: '. number_format(floatval($val),2, '.', ',');
                }
                else if($column == 'Avg Time On Page' || $column == 'Avg Page Load Time' || $column == 'Avg Session Duration' || $column == 'Avg Session Duration' || $column == 'Avg Page Load Time' || $column == 'Avg Redirection Time' || $column == 'Avg Server Response Time' || $column == 'Avg Page Download Time' || $column == 'Duration'){
                  $val = 'Avg: '.$this->sec_to_normal(floatval($val));
                }
                else{
                  $val = $val != '' ? 'Total: '. number_format(floatval($val),2, '.', ',') : '';
                }
                $html .= '<td style="' . $width . ';border: 1px solid #DCDCDC;padding-top:5px;color:#828282; ">' . $val . '</td>';
              }
            }
          $html .= '</tr>';
          $html .= '</table>';
          //die($html);
        }
        elseif($flag == 'pages'){
          $html = $this->compared_pages_html();
        }
        
        elseif($flag == 'compare'){
          $html = $this->compared_data_html();
        }
        return $html;
    }
    public function compared_data_html(){
      $this->first_data_sum = json_decode(stripslashes($this->first_data_sum));
      $this->second_data_sum = json_decode(stripslashes($this->second_data_sum));
      $dimension = isset($_REQUEST["dimension"]) ? sanitize_text_field($_REQUEST["dimension"]) : 'Date';
          if($this->metric_compare == '0' || $this->metric_compare == ''){
              $this->column_names[] = $this->metric." compare";
          }
          $count = count($this->column_names) - 1;
          if($dimension == 'pagePath'){
            $count = 9.5;
          }
          if ($count == 0) {
              $no_width = "100";
              $row_width = '0';
          } else {
              $no_width = "5";
              $row_width = (95 / $count);
          }
          $html = '';

          $html .= '<h2 style="text-align:center; margin-bottom:5px; color:#949494">' . $this->site_title . ' </h2>';
          $html .= '<h4 style="text-align:center; margin-bottom:5px; color:#7DB5D8">' . $this->file_name . '</h4>';
          if ($this->img_url) {
              $html .= '<div style="width:100%;height:auto;">';
              $html .= '<img width="800" style="margin-left:200px;" src="' . $this->img_url . '"/>';
              $html .= '</div>';
          }
          $margin = 'margin-left:47px';
          $html .= '<table border=1 style="width:100%;border: 1px solid #DCDCDC;border-spacing: 0;border-collapse: collapse;">'
                  . '<tr>';

          foreach ($this->column_names as $column) {
              $width = ($column == 'No') ? 'width:' . $no_width . '%;' : 'width:' . $row_width . '%;';
              $html .= '<th style="' . $width . ';background-color: #7DB5D8;color: #fff;">' . $column . '</th>';
          }
          $html .= '</tr>';
          $row_count = 0;
          foreach ($this->_data_compare as $dat => $value) {
              if ($this->column_names[1] == 'page Path' && $row_count >= 150) {
                  break;
              }
              $html .= '<tr>';            
              foreach ($this->column_names as $key => $column) {
                $column = trim($column);
                  $text = ucfirst($value[$column]);
                  $width = $column == 'No' ? 'width:' . $no_width . '%;' : 'width:' . $row_width . '%;';
                  if($column == "Page Path" || $column == "Landing Page" || $column == 'Hour' || $column == 'Week' || $column == 'Month' || strlen($text)>20){
                    if (strpos($text, ' ') === false) {
                        $text = '<span>' . $text;
                        $text = wordwrap($text, '10', '</span><span>', true);
                        $text .= '</span>';
                    }
                  }
                  elseif($column == 'Bounce Rate' || $column == 'Bounce Rate compare' || $column == 'Exit Rate' || $column == 'Ecommerce Conversion Rate'|| $column == 'Percent New Sessions' || $column == 'Percent New Sessions compare' || $column == 'Transactions Per Session'){
                    $text = number_format(floatval($text),2, '.', ',') . '%';
                  }
                  elseif($column == 'Page Value' || $column == 'Revenue' || $column == 'Transaction Revenue'){
                    if(strpos($column, 'compare') !== false){
                      $percentage_of_total = $this->second_data_sum->$column != '0' ? floatval($text)/$this->second_data_sum->$column*100 : 0;
                    }
                    else{
                      $percentage_of_total = $this->first_data_sum->$column != '0' ? floatval($text)/$this->first_data_sum->$column*100 : 0;
                    }
                    $percentage_of_total = number_format(floatval($percentage_of_total),2, '.', ',');
                    $text = '$' . number_format(floatval($text),2, '.', ',') . ' <span class="row_percent">(' . $percentage_of_total .'%)</span>';
                  }
                  elseif($column == 'Pageviews Per Session' || $column == 'Pageviews Per Session compare'){
                    $text = number_format(floatval($text),2, '.', ',');
                  }
                  elseif($column == 'Avg Time On Page' || $column == 'Avg Page Load Time' || $column == 'Avg Session Duration' || $column == 'Avg Session Duration compare'){
                    $text = $this->sec_to_normal(floatval($text));
                  }    
                  elseif($column == 'No' || $column == $_REQUEST['gawd_dimension'] || $column == $dimension){
                    $text = $text;
                  }                  
                  else{
                    if(strpos($column, 'compare') !== false){
                      $column = str_replace(' compare','',$column);
                      $percentage_of_total = $this->second_data_sum->$column != '0' ? floatval($text)/$this->second_data_sum->$column*100 : 0;
                    }
                    else{
                      $percentage_of_total = $this->first_data_sum->$column != '0' ? floatval($text)/$this->first_data_sum->$column*100 : 0;
                    }
                    $percentage_of_total = number_format(floatval($percentage_of_total),2, '.', ',');
                    $text = number_format(floatval($text),2, '.', ',') . ' <span class="row_percent">(' . $percentage_of_total .'%)</span>';
                    $text = $text;
                  }
                  $html .= '<td style="' . $width . ';border: 1px solid #DCDCDC;padding-top:5px;color:#828282; ">' . $text . '</td>';

              }
              $html .= '</tr>';
              $row_count++;
          }
           $html .= '<tr>';
            foreach ($this->column_names as $key => $column) {
              $column = trim($column);
              $width = $column == 'No' ? 'width:' . $no_width . '%;' : 'width:' . $row_width . '%;';
              if($column == 'No' || $column == $dimension || $column == 'Hour' || $column == 'Week' || $column == 'Month'){
                $html .= '<td style="' . $width . ';border: 1px solid #DCDCDC;padding-top:5px;color:#828282; "></td>';
              }
              else{
                  $val = '';
                  $val_second = '';
                if(trim($column) != trim($this->metric.' compare')){
                  $column = trim($column);
                  $val = $this->first_data_sum->$column;
                  $val_second = $this->second_data_sum->$column;
                }
                if($column == 'Bounce Rate' || $column == 'Bounce Rate compare'){
                  $val = 'Avg: '.number_format(floatval($val),2, '.', ',') . '%';
                  $val_second = 'Avg: '.number_format(floatval($val_second),2, '.', ',') . '%';
                }
                else if($column == 'Pageviews Per Session' || $column == 'Pageviews Per Session compare'){
                  $val = 'Total: '. number_format(floatval($val),2, '.', ',');
                  $val_second = 'Total: '. number_format(floatval($val_second),2, '.', ',');
                }
                else if($column == 'Avg Session Duration' || $column == 'Avg Session Duration compare'){
                  $val = 'Avg: '.$this->sec_to_normal(floatval($val));
                  $val_second = 'Avg: '.$this->sec_to_normal(floatval($val_second));
                }
                elseif($column == 'Percent New Sessions' || $column == 'Percent New Sessions compare'){
                  $val = 'Avg: '. number_format(floatval($val),2, '.', ',').'%';
                  $val_second = 'Avg: '. number_format(floatval($val_second),2, '.', ',').'%';
                }
                else{
                  $val_second = $val_second != '' ? 'Total: '. number_format(floatval($val_second),2, '.', ',') : '';
                  $val = $val != '' ? 'Total: '. number_format(floatval($val),2, '.', ',') : '';

                }
                if($val != '' && $val_second != ''){
                  $html .= '<td style="' . $width . ';border: 1px solid #DCDCDC;padding-top:5px;color:#828282; ">' . $val . '</td>';
                  $html .= '<td style="' . $width . ';border: 1px solid #DCDCDC;padding-top:5px;color:#828282; ">' . $val_second . '</td>';
                }
              }
            }
          $html .= '</tr>';
          $html .= '</table>';
          //die($html);

          return $html;
    }
    public function compared_pages_html(){
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
     /*
        for($i=0 ;$i<$max_length; $i++){
          $all_pages[$max_length_data[$i]->$metrics[1]] = array();
          array_push($all_pages[$max_length_data[$i]->$metrics[1]], 'no', 'no');
        }
      
      
        for($i=0 ;$i<$max_length; $i++){
          if(isset($this->first_data[$i])){
            if(!isset($all_pages[$max_length_data[$i]->$metrics[1]])){
              $all_pages[$max_length_data[$i]->$metrics[1]] = array();
              array_push($all_pages[$max_length_data[$i]->$metrics[1]], 'no', 'no');
            }
            $all_pages[$max_length_data[$i]->$metrics[1]][0] = $this->first_data[$i];
          }
        }
      
        for($j=0 ;$j<$max_length; $j++){
          if(isset($this->second_data[$j])){
           
             if(!isset($all_pages[$max_length_data[$j]->$metrics[1]])){
              $all_pages[$max_length_data[$j]->$metrics[1]] = array();
              array_push($all_pages[$max_length_data[$j]->$metrics[1]], 'no', 'no');
             }
             $all_pages[$max_length_data[$j]->$metrics[1]][1] = $this->second_data[$j];
          }
        }*/
        
      $table = '<h2 style="text-align:center; margin-bottom:5px; color:#949494">' . $this->site_title . ' </h2>';
      $table .= '<h4 style="text-align:center; margin-bottom:5px; color:#7DB5D8">' . $this->file_name . '</h4>';
      $table .= '<table border="1" style="' . $margin . 'width:100%;border-spacing: 0;border-collapse: collapse;">';
        //$table .= '<thead>';
        $table .= '<tr>';
        for($j=0 ;$j<count($metrics); $j++){
              $width = ($metrics[$j] == 'No') ? 'width:' . $no_width . '%;' : 'width:' . $row_width . '%;';
          $table .= '<th style="' . $width . ';background-color: #7DB5D8;color: #fff;">' . $metrics[$j] . '</th>';
        }
        $table .= '</tr>';
        $table .= '<tr>';
        for($j=0 ;$j<count($metrics); $j++){
            $width = $metrics[$j] == 'No' ? 'width:' . $no_width . '%;' : 'width:' . $row_width . '%;';
          if($metrics[$j] == 'No' || $metrics[$j] == 'Page Path' || $metrics[$j] == 'Landing Page'){

            $table .= '<td style="' . $width . '"></td>';
          }
          else{
            $this->first_data_sum->$metrics[$j] = floatval($this->first_data_sum->$metrics[$j]);
            $this->second_data_sum->$metrics[$j] = floatval($this->second_data_sum->$metrics[$j]);
            $arrow = '<img src=""/>';
            $percent = number_format((($this->first_data_sum->$metrics[$j] - $this->second_data_sum->$metrics[$j])/((($this->second_data_sum->$metrics[$j])!=0) ? $this->second_data_sum->$metrics[$j] : 1)) * 100, 2, '.', ',');
            if(!($percent)){
              $percent = 0;
            }
            else if(strpos($percent, '-') === 0){
              $percent = substr($percent,1);
              $arrow = '<img src="' . GAWD_URL . '/assets/arrow-down.png"/>';
            }
            else{
              $arrow = '<img src="' . GAWD_URL . '/assets/arrow-up.png"/>';
            }
            $table .= '<td style="' . $width . ' font-weight:bold;padding-top:5px;color:#828282;"><div>' . sanitize_text_field($percent) . '%' . $arrow . '</div>';
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
            $table .= '<div>' . sanitize_text_field($this->first_data_sum_value) . ' vs ' . sanitize_text_field($this->second_data_sum_value) . '</div>';
            $table .= '</td>';
          }
        }
        $table .= '</tr>';
        //$table .= '</thead>';
        //$table .= '<tbody>';

        $length = count($all_pages);
        $keys = array_keys($all_pages);
      for($i=0 ;$i<$length; $i++){
        if($i >= 50){
          break;
        }
        $width = 'width:' . $row_width . '%;';
        $table .= '<tr>';
        $table .= '<td style="width:5%">' . intval($i+1) .'</td>';
        $keys[$i] = '<span>' . $keys[$i];
                      $keys[$i] = wordwrap($keys[$i], '10', '</span><span>', true);
                      $keys[$i] .= '</span>'; 
        $table .= '<td style="' . $width . '">' . $keys[$i] .'</td>';
        $table .= '</tr>';
        $table .= '<tr>';
        for($j = 0; $j < count($metrics); $j++){
          if($metrics[$j] == 'No'){
            $table .= '<td ></td>';
          }
          else if($metrics[$j] == 'Page Path' || $metrics[$j] == 'Landing Page'){
            $table .= '<td style="' . $width . '">' . date('M d, Y', strtotime($this->start_date)) . ' - ' . date('M d, Y', strtotime($this->end_date)) .'</td>';
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
                $line_value = '$<span class="row_percent">' . number_format(floatval($line_value),2, '.', ',') . ' (' . $percentage_of_total .'%)</span>';
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
                $line_value = number_format(floatval($line_value),2, '.', ',') . ' <span class="row_percent">(' . $percentage_of_total .'%)</span>';
              }
              if (strpos($line_value, ' ') === false) {
                  $line_value = '<span>' . $line_value;
                  $line_value = wordwrap($line_value, '10', '</span><span>', true);
                  $line_value .= '</span>';
              }
              $table .= '<td style="' . $width . 'text-align:center">' . $line_value . '</td>';
            }
            else{
              $table .= '<td style="text-align:center">0</td>';
            }
          }
        }
        $table .= '</tr>';
        $table .= '<tr>';
        for($j = 0; $j < count($metrics); $j++){
          if($metrics[$j] == 'No'){
            $table .= '<td style="width:5%"></td>';
          }      
          else if($metrics[$j] == 'Page Path' || $metrics[$j] == 'Landing Page'){
            $width = ($metrics[$j] == 'No') ? 'width:' . $no_width . '%;' : 'width:' . $row_width . '%;';
            $table .= '<td style="' . $width . '">' . date('M d, Y', strtotime($this->second_start_date)) . ' - ' . date('M d, Y', strtotime($this->second_end_date)) .'</td>';
          }
          else{
            $keys[$i] = sanitize_text_field($keys[$i]);
            if($all_pages[$keys[$i]][1] != 'no'){
              $line_value = $all_pages[$keys[$i]][1]->$metrics[$j];
              if($metrics[$j] == 'Bounce Rate' || $metrics[$j] == 'Exit Rate' || $metrics[$j] == 'New Sessions' || $metrics[$j] == 'Ecommerce Conversion Rate'){
                $line_value = number_format(floatval($line_value),2, '.', ',') . '%';
              }
              else if($metrics[$j] == 'Page Value' || $metrics[$j] == 'Transaction Revenue' || $metrics[$j] == 'Revenue' || $metrics[$j] == 'Item Revenue'){
                $percentage_of_total =  $this->second_data_sum->$metrics[$j] != '0' ? floatval($line_value)/$this->second_data_sum->$metrics[$j]*100 : 0;
                $percentage_of_total = number_format(floatval($percentage_of_total),2, '.', ',');
                $line_value = '$<span class="row_percent">' . number_format(floatval($line_value),2, '.', ',') . ' (' . $percentage_of_total .'%)</span>';
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
                $line_value = number_format(floatval($line_value),2, '.', ',') . '<span class="row_percent">(' . $percentage_of_total .'%)</span>';
              }
              $table .= '<td style="text-align:center">' . $line_value . '</td>';
            }
            else{
              $table .= '<td style="text-align:center">0</td>';
            }
          }
        }
        $table .= '</tr>';
        $table .= '<tr>';
        for($j = 0; $j < count($metrics); $j++){
          if($metrics[$j] == 'No'){
            $table .= '<td style="width:5%"></td>';
          }
          else if($metrics[$j] == 'Page Path' || $metrics[$j] == 'Landing Page'){
            $table .= '<td style="font-weight:bold;">% Change</td>';
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

              //$_single_data = ($_single_data[0] . ((!isset($_single_data[1])) ? 0 :  $_single_data[1]) . ((!isset($_single_data[2])) ? 0 :  $_single_data[2]));
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

            $single_percent = ($single_data != 0) ? number_format((($_single_data - $single_data)/$single_data) * 100,2, '.', ',') :'<img src="' . GAWD_URL . '/assets/infinity.png" />';

            //$single_percent = is_nan($single_percent) ? 0 : is_infinite($single_percent) ? $single_percent : '&infin;';
            $table .= '<td style="font-weight:bold;text-align:center">' . $single_percent . '%</td>';
          }
        }
        $table .= '</tr>';
      }
      //$table .= '</tbody>';
      $table .= '</table>';

      //echo $table;die;
      return $table;
    }
    public function sec_to_normal($data){
      $hours = strlen(floor($data / 3600)) < 2 ?  '0' . floor($data / 3600) : floor($data / 3600);
      $mins = strlen(floor($data / 60 % 60)) < 2 ? '0' . floor($data / 60 % 60) : floor($data / 60 % 60);
      $secs = strlen(ceil($data % 60)) < 2 ? '0' . ceil($data % 60) : ceil($data % 60);
      return $data = $hours . ':' . $mins . ':' . $secs;
    }
    public function create_image() {
        if ($this->img == '') {
            return;
        }
        $this->img = explode('image/png;base64,', $this->img);
        $uplode_dir = wp_upload_dir();
        $image_dir = $uplode_dir['path'] . '/export_chart.png';
        file_put_contents($image_dir, base64_decode($this->img[1]));
        chmod($image_dir, 0777);
        $this->img_url = $image_dir;
    }

}
